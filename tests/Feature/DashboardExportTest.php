<?php

use App\Exports\DashboardExport;
use App\Models\Candidate;
use App\Models\Profile;
use App\Models\User;
use App\Models\Vote;
use App\Support\DashboardMetrics;
use PhpOffice\PhpSpreadsheet\Chart\Chart;

/**
 * Helper: siapkan 1 kandidat + 2 pemilih yang sudah voting.
 */
function seedDashboardData(): Candidate
{
    $kandidat = User::factory()->kandidat()->create();
    $candidate = Candidate::create([
        'user_id' => $kandidat->id,
        'foto' => 'kandidat.jpg',
        'nama_lengkap' => 'Kandidat Satu',
        'nomor_urut' => 1,
        'nis' => '1001',
        'kelas' => 'XII IPA 1',
        'visi' => 'Visi',
        'misi' => 'Misi',
    ]);

    foreach (['Pemilih Satu' => 'X-A', 'Pemilih Dua' => 'X-B'] as $nama => $kelas) {
        $pemilih = User::factory()->create();
        Profile::create(['user_id' => $pemilih->id, 'nama_lengkap' => $nama, 'kelas' => $kelas]);
        Vote::create(['user_id' => $pemilih->id, 'candidate_id' => $candidate->id, 'voted_at' => now()]);
    }

    return $candidate;
}

test('guests are redirected from the dashboard export routes', function () {
    $this->get(route('dashboard.export.excel'))->assertRedirect(route('home'));
    $this->get(route('dashboard.export.pdf'))->assertRedirect(route('home'));
});

test('pemilih users are redirected from the dashboard export routes', function () {
    $pemilih = User::factory()->create();

    $this->actingAs($pemilih)
        ->get(route('dashboard.export.excel'))
        ->assertRedirect(route('pemilih'));
});

test('admin can download the dashboard as an excel file', function () {
    $admin = User::factory()->admin()->create();
    seedDashboardData();

    $response = $this->actingAs($admin)->get(route('dashboard.export.excel'));

    $response->assertOk();

    expect($response->headers->get('content-disposition'))->toMatch('/\d{8}-\d{6}(-\d+)?\.xlsx/')
        ->and($response->headers->get('content-type'))->toContain('spreadsheetml')
        ->and($response->streamedContent())->toStartWith('PK'); // ZIP magic bytes .xlsx
});

test('admin can download the dashboard as a pdf file', function () {
    $admin = User::factory()->admin()->create();
    seedDashboardData();

    $response = $this->actingAs($admin)->get(route('dashboard.export.pdf'));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');
});

test('the excel export writes derived columns as formulas and builds a donut chart', function () {
    seedDashboardData();

    $export = new DashboardExport(app(DashboardMetrics::class)->all());
    $rows = $export->array();

    // Baris "Belum Voting" & "Partisipasi (%)" berupa rumus.
    $rowByLabel = fn (string $label): array => collect($rows)->first(fn (array $row): bool => ($row[0] ?? null) === $label) ?? [];
    expect($rowByLabel('Belum Voting')[1] ?? null)->toBe('=B5-B6')
        ->and($rowByLabel('Partisipasi (%)')[1] ?? null)->toBe('=IFERROR(B6/B5*100,0)');

    // Satu baris data perolehan memakai rumus persentase terhadap Total Suara.
    $perolehanRows = array_values(array_filter($rows, fn (array $row): bool => isset($row[3]) && str_starts_with((string) $row[3], '=IFERROR')));
    expect($perolehanRows)->toHaveCount(1)
        ->and($perolehanRows[0][3])->toContain('$C$');

    // Pie chart native Excel dibuat saat ada kandidat.
    $chart = $export->charts();
    expect($chart)->toBeInstanceOf(Chart::class);
});

test('the downloaded excel file embeds the donut chart', function () {
    $admin = User::factory()->admin()->create();
    seedDashboardData();

    $response = $this->actingAs($admin)->get(route('dashboard.export.excel'));
    $response->assertOk();

    $zipPath = tempnam(sys_get_temp_dir(), 'dash-').'.xlsx';
    file_put_contents($zipPath, $response->streamedContent());

    try {
        $zip = new ZipArchive;
        expect($zip->open($zipPath))->toBeTrue();

        $chartEntry = $zip->locateName('xl/charts/chart1.xml');
        expect($chartEntry !== false)->toBeTrue();

        // Donut chart asli Excel (menyerupai tampilan dashboard).
        $chartXml = $zip->getFromIndex($chartEntry);
        expect($chartXml)->toContain('doughnutChart')
            ->and($chartXml)->toContain('showPercent');

        // Cache kategori chart harus memuat nama kandidat asli — bukan data
        // bergeser. Ini menjaga bug "baris kosong hilang" agar tidak terulang.
        expect($chartXml)->toContain('Kandidat Satu');

        // Urutan elemen <c:ser> harus sesuai skema OOXML (CT_PieSer): tx & spPr
        // sebelum cat/val, tanpa dPt, dan dLbls di level plot (setelah </c:ser>).
        // Kalau PhpSpreadsheet kembali menulis dPt/dLbls sebelum c:tx, Excel akan
        // membuang chart saat file dibuka meski tabel datanya tetap tampil.
        expect($chartXml)->not->toContain('<c:dPt');
        expect(strpos($chartXml, '<c:tx'))->toBeLessThan(strpos($chartXml, '<c:cat'));
        expect(strpos($chartXml, '</c:ser>'))->toBeLessThan(strpos($chartXml, '<c:dLbls>'));

        $zip->close();
    } finally {
        @unlink($zipPath);
    }
});
