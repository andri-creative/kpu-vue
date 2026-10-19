<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\Candidate;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KandidatSeeder extends Seeder
{
    public function run(): void
    {
        // Kandidat development: kode 111111/222222/333333, sesuai nomor urut.
        // firstOrNew + fill hanya menulis field kosong agar tidak menimpa
        // data kandidat yang sudah diubah lewat UI (nomor urut, visi, misi, foto).
        $kandidats = [
            ['nis' => '1000000001', 'nama' => 'Ahmad Fauzi', 'kelas' => 'XII-A', 'nomor_urut' => 1, 'kode' => '111111'],
            ['nis' => '1000000002', 'nama' => 'Budi Santoso', 'kelas' => 'XII-B', 'nomor_urut' => 2, 'kode' => '222222'],
            ['nis' => '1000000003', 'nama' => 'Citra Dewi', 'kelas' => 'XII-C', 'nomor_urut' => 3, 'kode' => '333333'],
        ];

        for ($urut = 1; $urut <= 3; $urut++) {
            $data = $kandidats[$urut - 1];

            $user = User::query()->firstOrNew(['nis' => $data['nis']]);

            if (! $user->exists) {
                $user->fill([
                    'kode' => $data['kode'],
                    'role' => RoleEnum::Kandidat,
                    'status' => StatusEnum::Aktif,
                    'password' => Str::password(32),
                ]);
            } elseif ($user->kode === null) {
                $user->kode = $data['kode'];
            }

            $user->save();

            Profile::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $data['nama'],
                    'kelas' => $data['kelas'],
                ],
            );

            $candidate = Candidate::query()->firstOrNew(['user_id' => $user->id]);

            if (! $candidate->exists) {
                $candidate->fill([
                    'foto' => null,
                    'nama_lengkap' => $data['nama'],
                    'nomor_urut' => $data['nomor_urut'],
                    'nis' => $data['nis'],
                    'kelas' => $data['kelas'],
                    'visi' => 'Mewujudkan sekolah yang lebih baik.',
                    'misi' => '1. Meningkatkan kualitas belajar',
                ]);
            }

            $candidate->save();

            $this->command->info("Kandidat {$data['nama']} (NIS {$data['nis']}): kode {$user->kode}");
        }
    }
}
