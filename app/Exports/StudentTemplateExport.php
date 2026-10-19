<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['20260001', 'Andi Pratama', 'X IPA 1'],
            ['20260002', 'Budi Santoso', 'X IPA 2'],
            ['20260003', 'Citra Lestari', 'XI IPS 1'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['NIS', 'Nama', 'Kelas'];
    }
}
