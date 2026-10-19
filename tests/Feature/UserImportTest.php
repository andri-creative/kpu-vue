<?php

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\Profile;
use App\Models\User;
use App\Support\UploadStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $root = sys_get_temp_dir().'/kpu-imports-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($root);

    app()->instance('test.imports_root', $root);
    app()->instance(UploadStore::class, new UploadStore($root));
});

afterEach(function () {
    File::deleteDirectory(app('test.imports_root'));
});

/**
 * Build a real .xlsx upload from the given heading row and data rows.
 *
 * @param  array<int, string>  $headings
 * @param  array<int, array<int, string>>  $rows
 */
function studentExcelUpload(array $headings, array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;

    $spreadsheet->getActiveSheet()->fromArray(array_merge([$headings], $rows), null, 'A1');

    $path = tempnam(sys_get_temp_dir(), 'student-import').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return new UploadedFile($path, 'data-siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('guests are redirected from the student import page', function () {
    $this->get(route('users.import'))->assertRedirect(route('home'));
});

test('pemilih users cannot access the student import page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('users.import'))->assertRedirect(route('pemilih'));
});

test('kandidat users cannot access the student import page', function () {
    $user = User::factory()->kandidat()->create();

    $this->actingAs($user)->get(route('users.import'))->assertRedirect(route('kandidat'));
});

test('admin users can access the student import page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('users.import'))->assertOk();
});

test('admin users can download the excel template', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.import.template'))
        ->assertOk()
        ->assertDownload('template-data-siswa.xlsx');
});

test('preview marks every row of a valid file as valid', function () {
    $admin = User::factory()->admin()->create();
    $file = studentExcelUpload(['NIS', 'Nama', 'Kelas'], [
        ['20260001', 'Andi Pratama', 'X IPA 1'],
        ['20260002', 'Budi Santoso', 'X IPA 2'],
    ]);

    $response = $this->actingAs($admin)->post(route('users.import.preview'), ['file' => $file]);

    $response->assertRedirect(route('users.import'));

    $preview = session('student_import_preview');

    expect($preview['header_valid'])->toBeTrue()
        ->and($preview['total'])->toBe(2)
        ->and($preview['valid'])->toBe(2)
        ->and($preview['invalid'])->toBe(0)
        ->and($preview['ok'])->toBeTrue()
        ->and($preview['rows'][0]['status'])->toBe('valid');

    $this->get(route('users.import'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Users/Import')
            ->where('preview.header_valid', true)
            ->where('preview.total', 2)
            ->where('preview.valid', 2)
            ->where('preview.invalid', 0)
            ->where('preview.ok', true)
            ->where('preview.rows.0.status', 'valid'));
});

test('preview rejects files whose header does not match the template', function () {
    $admin = User::factory()->admin()->create();
    $file = studentExcelUpload(['Nama', 'NIS', 'Kelas'], [
        ['Andi Pratama', '20260001', 'X IPA 1'],
    ]);

    $response = $this->actingAs($admin)->post(route('users.import.preview'), ['file' => $file]);

    $response->assertRedirect(route('users.import'));

    $preview = session('student_import_preview');

    expect($preview['header_valid'])->toBeFalse()
        ->and($preview['ok'])->toBeFalse()
        ->and($preview['message'])->toBe('Format kolom Excel tidak sesuai dengan template. Gunakan template Excel yang disediakan.');
});

test('preview detects duplicate nis within the same file', function () {
    $admin = User::factory()->admin()->create();
    $file = studentExcelUpload(['NIS', 'Nama', 'Kelas'], [
        ['20260001', 'Andi Pratama', 'X IPA 1'],
        ['20260001', 'Budi Santoso', 'X IPA 2'],
    ]);

    $response = $this->actingAs($admin)->post(route('users.import.preview'), ['file' => $file]);

    $response->assertRedirect(route('users.import'));

    $preview = session('student_import_preview');

    expect($preview['rows'][0]['status'])->toBe('valid')
        ->and($preview['rows'][1]['status'])->toBe('error')
        ->and($preview['rows'][1]['errors'][0])->toContain('NIS 20260001 duplicate pada file Excel.')
        ->and($preview['invalid'])->toBe(1)
        ->and($preview['ok'])->toBeFalse();
});

test('preview detects nis that is already registered in the database', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['nis' => '20260001']);
    $file = studentExcelUpload(['NIS', 'Nama', 'Kelas'], [
        ['20260001', 'Andi Pratama', 'X IPA 1'],
    ]);

    $response = $this->actingAs($admin)->post(route('users.import.preview'), ['file' => $file]);

    $response->assertRedirect(route('users.import'));

    $preview = session('student_import_preview');

    expect($preview['rows'][0]['status'])->toBe('error')
        ->and($preview['rows'][0]['errors'][0])->toBe('NIS: NIS 20260001 sudah terdaftar.');
});

test('preview reports missing nama and kelas per row', function () {
    $admin = User::factory()->admin()->create();
    $file = studentExcelUpload(['NIS', 'Nama', 'Kelas'], [
        ['20260001', '', ''],
    ]);

    $response = $this->actingAs($admin)->post(route('users.import.preview'), ['file' => $file]);

    $response->assertRedirect(route('users.import'));

    $preview = session('student_import_preview');

    expect($preview['rows'][0]['row'])->toBe(2)
        ->and($preview['rows'][0]['status'])->toBe('error')
        ->and($preview['rows'][0]['errors'])->toContain('Nama: Nama wajib diisi.')
        ->and($preview['rows'][0]['errors'])->toContain('Kelas: Kelas wajib diisi.');
});

test('preview rejects uploads that are not excel files', function () {
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->create('data.csv', 100, 'text/csv');

    $this->actingAs($admin)
        ->from(route('users.import'))
        ->post(route('users.import.preview'), ['file' => $file])
        ->assertRedirect(route('users.import'))
        ->assertSessionHasErrors(['file']);
});

test('store imports every valid row with its profile', function () {
    $admin = User::factory()->admin()->create();
    $file = studentExcelUpload(['NIS', 'Nama', 'Kelas'], [
        ['20260001', 'Andi Pratama', 'X IPA 1'],
        ['20260002', 'Budi Santoso', 'X IPA 2'],
    ]);

    $response = $this->actingAs($admin)->post(route('users.import.store'), ['file' => $file]);

    $response->assertRedirect(route('users.index'));

    $imported = User::query()->whereIn('nis', ['20260001', '20260002'])->get();

    expect($imported)->toHaveCount(2)
        ->and($imported->first()->role)->toBe(RoleEnum::Pemilih)
        ->and($imported->first()->status)->toBe(StatusEnum::Aktif)
        ->and($imported->first()->profile->nama_lengkap)->toBe('Andi Pratama')
        ->and($imported->first()->profile->kelas)->toBe('X IPA 1');

    // Kode login dibuat otomatis saat import sehingga siswa bisa langsung login.
    $generated = session('generated_codes');
    $andi = $imported->firstWhere('nis', '20260001');

    expect($generated)->toHaveKeys(['20260001', '20260002'])
        ->and($generated['20260001'])->toMatch('/^\d{6}$/')
        ->and($generated['20260002'])->toMatch('/^\d{6}$/')
        ->and($andi->verifyKode($generated['20260001']))->toBeTrue()
        ->and($andi->kode)->toBe($generated['20260001']);

    $flash = session('inertia.flash_data');

    expect($flash['toast']['type'])->toBe('success')
        ->and($flash['toast']['message'])->toContain('Import berhasil. 2 data siswa berhasil ditambahkan.');
});

test('store keeps the uploaded excel file in public uploads', function () {
    $admin = User::factory()->admin()->create();
    $file = studentExcelUpload(['NIS', 'Nama', 'Kelas'], [
        ['20260001', 'Andi Pratama', 'X IPA 1'],
    ]);

    $this->actingAs($admin)->post(route('users.import.store'), ['file' => $file]);

    $stored = File::files(app('test.imports_root').'/imports');

    expect($stored)->toHaveCount(1)
        ->and($stored[0]->getExtension())->toBe('xlsx');
});

test('store keeps every row untouched when the file contains errors', function () {
    $admin = User::factory()->admin()->create();
    $file = studentExcelUpload(['NIS', 'Nama', 'Kelas'], [
        ['20260001', 'Andi Pratama', 'X IPA 1'],
        ['20260001', 'Budi Santoso', 'X IPA 2'],
    ]);

    $response = $this->actingAs($admin)
        ->from(route('users.import'))
        ->post(route('users.import.store'), ['file' => $file]);

    $response->assertRedirect(route('users.import'))
        ->assertSessionHasErrors('file');

    expect(User::query()->where('nis', '20260001')->exists())->toBeFalse()
        ->and(Profile::query()->count())->toBe(0);
});
