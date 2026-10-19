<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PemilihSeeder extends Seeder
{
    public function run(): void
    {
        // Pemilih development: kode 100001-100010, NIS 2000000000-2000000009.
        // firstOrNew + fill hanya menulis field kosong, jadi user yang sudah
        // punya kode tetap mempertahankan kode lamanya.
        for ($index = 0; $index < 10; $index++) {
            $nis = (string) (2000000000 + $index);
            $kode = str_pad((string) (100001 + $index), 6, '0', STR_PAD_LEFT);
            $nama = 'Pemilih '.($index + 1);
            $kelas = 'X-'.chr(65 + ($index % 5));

            $user = User::query()->firstOrNew(['nis' => $nis]);

            if (! $user->exists) {
                $user->fill([
                    'kode' => $kode,
                    'role' => RoleEnum::Pemilih,
                    'status' => StatusEnum::Aktif,
                    'password' => Str::password(32),
                ]);
            } elseif ($user->kode === null) {
                $user->kode = $kode;
            }

            $user->save();

            Profile::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $nama,
                    'kelas' => $kelas,
                ],
            );

            $this->command->info("Pemilih {$nama} (NIS {$nis}): kode {$user->kode}");
        }
    }
}
