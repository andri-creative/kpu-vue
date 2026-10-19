<?php

namespace App\Imports;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\User;
use App\Support\KodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use RuntimeException;

class StudentImport implements ToArray
{
    /**
     * @var array<int, string>
     */
    public const HEADINGS = ['NIS', 'Nama', 'Kelas'];

    /**
     * The 6-digit kode generated for each imported student, keyed by NIS.
     *
     * @var array<string, string>
     */
    public array $generatedCodes = [];

    /**
     * @var array<int, array<int, mixed>>
     */
    private array $rows = [];

    /**
     * @param  array<int, array<int, mixed>>  $array
     */
    public function array(array $array): void
    {
        $this->rows = array_values($array);
    }

    /**
     * Read, validate and describe the uploaded Excel contents.
     *
     * @return array{
     *     header_valid: bool,
     *     message: string|null,
     *     rows: list<array{row: int, nis: string, nama: string, kelas: string, status: string, errors: list<string>}>,
     *     total: int,
     *     valid: int,
     *     invalid: int,
     *     ok: bool,
     * }
     */
    public function preview(): array
    {
        if ($this->rows === []) {
            return $this->result(false, 'File Excel kosong atau tidak dapat dibaca.');
        }

        if (! $this->hasValidHeadings()) {
            return $this->result(false, 'Format kolom Excel tidak sesuai dengan template. Gunakan template Excel yang disediakan.');
        }

        $rows = $this->validatedRows();

        if ($rows === []) {
            return $this->result(true, 'File Excel tidak memiliki data siswa.');
        }

        return $this->result(true, null, $rows);
    }

    /**
     * Persist every validated row within a single transaction.
     *
     * @return int number of imported students
     */
    public function persist(): int
    {
        $preview = $this->preview();

        if (! $preview['ok']) {
            throw new RuntimeException('Data siswa tidak valid untuk diimport.');
        }

        $codes = KodeGenerator::generateMany(count($preview['rows']));

        return DB::transaction(function () use ($preview, $codes): int {
            foreach ($preview['rows'] as $index => $row) {
                User::create([
                    'nis' => $row['nis'],
                    'kode' => $codes[$index],
                    'password' => Str::password(32),
                    'role' => RoleEnum::Pemilih,
                    'status' => StatusEnum::Aktif,
                ])->profile()->create([
                    'nama_lengkap' => $row['nama'],
                    'kelas' => $row['kelas'],
                ]);

                $this->generatedCodes[$row['nis']] = $codes[$index];
            }

            return count($preview['rows']);
        });
    }

    private function hasValidHeadings(): bool
    {
        $header = array_values(array_filter(
            array_map(fn (mixed $value): string => $this->normalize($value), $this->rows[0]),
            fn (string $value): bool => $value !== '',
        ));

        if (count($header) !== count(self::HEADINGS)) {
            return false;
        }

        foreach (self::HEADINGS as $index => $expected) {
            if (Str::lower($header[$index]) !== Str::lower($expected)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array{row: int, nis: string, nama: string, kelas: string, status: string, errors: list<string>}>
     */
    private function validatedRows(): array
    {
        $data = array_slice($this->rows, 1);

        $identifiers = [];

        foreach ($data as $row) {
            $nis = $this->normalize($row[0] ?? null);

            if ($nis !== '') {
                $identifiers[] = $nis;
            }
        }

        $existing = $this->existingIdentifiers($identifiers);

        $seen = [];
        $rows = [];

        foreach ($data as $index => $row) {
            $nis = $this->normalize($row[0] ?? null);
            $nama = $this->normalize($row[1] ?? null);
            $kelas = $this->normalize($row[2] ?? null);

            if ($nis === '' && $nama === '' && $kelas === '') {
                continue;
            }

            $errors = [];

            if ($nis === '') {
                $errors[] = 'NIS: NIS wajib diisi.';
            }

            if ($nama === '') {
                $errors[] = 'Nama: Nama wajib diisi.';
            }

            if ($kelas === '') {
                $errors[] = 'Kelas: Kelas wajib diisi.';
            }

            if ($nis !== '') {
                if (isset($seen[$nis])) {
                    $errors[] = "NIS: NIS {$nis} duplicate pada file Excel.";
                }

                if (isset($existing[$nis])) {
                    $errors[] = "NIS: NIS {$nis} sudah terdaftar.";
                }

                $seen[$nis] = true;
            }

            $rows[] = [
                'row' => $index + 2,
                'nis' => $nis,
                'nama' => $nama,
                'kelas' => $kelas,
                'status' => $errors === [] ? 'valid' : 'error',
                'errors' => $errors,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $identifiers
     * @return array<string, true>
     */
    private function existingIdentifiers(array $identifiers): array
    {
        if ($identifiers === []) {
            return [];
        }

        $found = [];

        User::query()
            ->whereIn('nis', $identifiers)
            ->pluck('nis')
            ->each(function (string $nis) use (&$found): void {
                $found[$nis] = true;
            });

        return $found;
    }

    /**
     * @param  list<array{row: int, nis: string, nama: string, kelas: string, status: string, errors: list<string>}>  $rows
     * @return array{
     *     header_valid: bool,
     *     message: string|null,
     *     rows: list<array{row: int, nis: string, nama: string, kelas: string, status: string, errors: list<string>}>,
     *     total: int,
     *     valid: int,
     *     invalid: int,
     *     ok: bool,
     * }
     */
    private function result(bool $headerValid, ?string $message, array $rows = []): array
    {
        $invalid = count(array_filter($rows, fn (array $row): bool => $row['status'] === 'error'));

        return [
            'header_valid' => $headerValid,
            'message' => $message,
            'rows' => $rows,
            'total' => count($rows),
            'valid' => count($rows) - $invalid,
            'invalid' => $invalid,
            'ok' => $headerValid && $rows !== [] && $invalid === 0,
        ];
    }

    private function normalize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        return trim((string) $value);
    }
}
