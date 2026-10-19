<?php

namespace App\Support;

use App\Models\User;
use RuntimeException;

final class KodeGenerator
{
    public const LENGTH = 6;

    public const MAX_ATTEMPTS = 10;

    /**
     * Generate a single 6-digit kode that is not already taken.
     */
    public static function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $plain = (string) random_int(100000, 999999);

            if (! User::query()->where('kode', $plain)->exists()) {
                return $plain;
            }
        }

        throw new RuntimeException('Gagal membuat kode unik.');
    }

    /**
     * Generate the given number of distinct 6-digit kode in one batch.
     *
     * @return list<string>
     */
    public static function generateMany(int $jumlah): array
    {
        if ($jumlah <= 0) {
            return [];
        }

        $taken = [];

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS && count($taken) < $jumlah; $attempt++) {
            $candidates = [];

            for ($i = count($taken); $i < $jumlah; $i++) {
                $candidates[] = (string) random_int(100000, 999999);
            }

            $existing = User::query()
                ->whereIn('kode', $candidates)
                ->pluck('kode')
                ->all();

            foreach ($candidates as $plain) {
                if (! in_array($plain, $existing, true)) {
                    $taken[$plain] = $plain;
                }
            }
        }

        if (count($taken) < $jumlah) {
            throw new RuntimeException('Gagal membuat kode unik.');
        }

        return array_slice(array_values($taken), 0, $jumlah);
    }
}
