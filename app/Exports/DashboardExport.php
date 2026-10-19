<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

/**
 * Export dashboard ke file Excel: berisi tabel ringkasan, perolehan suara,
 * partisipasi per kelas, riwayat aktivitas, serta donut chart native Excel
 * yang menyerupai tampilan dashboard. Kolom turunan (belum voting,
 * persentase) ditulis sebagai rumus Excel.
 */
class DashboardExport implements FromArray, WithCharts, WithColumnWidths, WithPreCalculateFormulas, WithTitle
{
    private const SHEET_TITLE = 'Dashboard';

    /**
     * Baris header tabel perolehan suara; diisi saat array() dibangun.
     */
    private int $perolehanHeaderRow = 0;

    /**
     * Baris pertama & terakhir data perolehan suara; dipakai sebagai sumber pie chart.
     */
    private int $perolehanFirstRow = 0;

    private int $perolehanLastRow = 0;

    /**
     * @param  array{
     *     stats: array{total_pemilih: int, sudah_voting: int, belum_voting: int, partisipasi: float},
     *     perolehan: array<int, array{nomor_urut: int, nama_lengkap: string, votes: int, percentage: float}>,
     *     kelas: array<int, array{kelas: string, sudah: int, belum: int, total: int, persentase: float}>,
     *     aktivitas: array<int, array{
     *         id: int,
     *         aksi: string,
     *         detail: string|null,
     *         ip_address: string|null,
     *         waktu: string,
     *         nama_pemilih: string,
     *         kelas: string|null,
     *         nomor_urut: int|null,
     *         nama_kandidat: string|null
     *     }>
     * }  $data
     */
    public function __construct(private array $data) {}

    public function title(): string
    {
        return self::SHEET_TITLE;
    }

    /**
     * @return array<string, float|int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 22,
            'C' => 12,
            'D' => 15,
            'E' => 16,
            'F' => 20,
        ];
    }

    public function array(): array
    {
        $stats = $this->data['stats'];
        $perolehan = $this->data['perolehan'];
        $kelas = $this->data['kelas'];
        $aktivitas = $this->data['aktivitas'];

        $rows = [];
        $rows[] = ['Dashboard KPU'];
        $rows[] = ['Partisipasi '.$stats['partisipasi'].'% dari '.$stats['total_pemilih'].' pemilih'];
        // Baris spasi memakai [''] bukan [] — Maatwebsite membuang baris kosong [],
        // sehingga nomor baris berikutnya bergeser dan rumus/referensi chart jadi salah.
        $rows[] = [''];

        $rows[] = ['Ringkasan'];
        $rows[] = ['Total Pemilih', $stats['total_pemilih']];
        $rows[] = ['Sudah Voting', $stats['sudah_voting']];
        $rows[] = ['Belum Voting', '=B5-B6'];
        $rows[] = ['Partisipasi (%)', '=IFERROR(B6/B5*100,0)'];
        $rows[] = [''];

        // Perolehan suara per kandidat + rumus persentase.
        $rows[] = ['Perolehan Suara per Kandidat'];
        $this->perolehanHeaderRow = count($rows) + 1;
        $rows[] = ['No', 'Nama Kandidat', 'Suara', 'Persentase (%)'];
        $this->perolehanFirstRow = count($rows) + 1;
        $totalRow = $this->perolehanFirstRow + count($perolehan);

        foreach ($perolehan as $item) {
            $row = count($rows) + 1;
            $rows[] = [
                $item['nomor_urut'],
                $item['nama_lengkap'],
                $item['votes'],
                "=IFERROR(C{$row}/\$C\${$totalRow}*100,0)",
            ];
        }

        $this->perolehanLastRow = count($rows);

        if ($perolehan !== []) {
            $rows[] = [
                '',
                'Total Suara',
                "=SUM(C{$this->perolehanFirstRow}:C{$this->perolehanLastRow})",
                "=SUM(D{$this->perolehanFirstRow}:D{$this->perolehanLastRow})",
            ];
        }

        $rows[] = [''];

        // Partisipasi per kelas + rumus belum voting & persentase.
        $rows[] = ['Partisipasi per Kelas'];
        $rows[] = ['Kelas', 'Total', 'Sudah', 'Belum', 'Partisipasi (%)'];

        foreach ($kelas as $item) {
            $row = count($rows) + 1;
            $rows[] = [
                $item['kelas'],
                $item['total'],
                $item['sudah'],
                "=B{$row}-C{$row}",
                "=IFERROR(C{$row}/B{$row}*100,0)",
            ];
        }

        $rows[] = [''];

        // Riwayat aktivitas voting terbaru.
        $rows[] = ['Riwayat Aktivitas Voting'];
        $rows[] = ['No', 'Pemilih', 'Kelas', 'Aksi', 'Kandidat', 'Waktu'];

        foreach ($aktivitas as $index => $item) {
            $rows[] = [
                $index + 1,
                $item['nama_pemilih'],
                $item['kelas'] ?? '-',
                $item['aksi'],
                $item['nama_kandidat'] ?? '-',
                $item['waktu'],
            ];
        }

        return $rows;
    }

    /**
     * Donut chart distribusi suara per kandidat, memakai range pada sheet ini.
     */
    public function charts(): Chart|array
    {
        $count = count($this->data['perolehan']);

        if ($count === 0) {
            return [];
        }

        $sheet = "'".self::SHEET_TITLE."'";
        $first = $this->perolehanFirstRow;
        $last = $this->perolehanLastRow;

        $label = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "{$sheet}!\$C\${$this->perolehanHeaderRow}",
            null,
            1,
            ['Suara']
        );

        $categories = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "{$sheet}!\$B\${$first}:\$B\${$last}",
            null,
            $count,
            array_column($this->data['perolehan'], 'nama_lengkap')
        );

        $values = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_NUMBER,
            "{$sheet}!\$C\${$first}:\$C\${$last}",
            null,
            $count,
            array_column($this->data['perolehan'], 'votes')
        );

        $series = new DataSeries(
            DataSeries::TYPE_DONUTCHART,
            null,
            [0],
            [$label],
            [$categories],
            [$values]
        );

        // Label ditempel di PlotArea, bukan pada DataSeriesValues. PhpSpreadsheet
        // menulis <c:dPt>/<c:dLbls> milik seri SEBELUM <c:tx>/<c:spPr>; urutan itu
        // melanggar skema OOXML (CT_PieSer) sehingga Excel membuang chart saat file
        // dibuka walau tabel datanya tetap tampil. Di level PlotArea, <c:dLbls>
        // ditulis tepat setelah </c:ser>, sesuai urutan skema.
        $layout = new Layout([
            'showPercent' => true,
            'showVal' => false,
            'showCatName' => false,
            'showSerName' => false,
            'showLegendKey' => false,
            'dLblPos' => 'bestFit',
        ]);

        $chart = new Chart(
            'perolehan',
            new Title('Distribusi Suara per Kandidat'),
            new Legend(Legend::POSITION_RIGHT, null, false),
            new PlotArea($layout, [$series])
        );
        $chart->setTopLeftPosition('F4');
        $chart->setBottomRightPosition('M22');

        return $chart;
    }
}
