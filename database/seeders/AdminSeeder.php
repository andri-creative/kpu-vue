<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrNew(['nis' => '0000000001']);

        if (! $admin->exists) {
            $admin->fill([
                'kode' => '000000',
                'role' => RoleEnum::Admin,
                'status' => StatusEnum::Aktif,
                'password' => Str::password(32),
            ]);
        } elseif ($admin->kode === null) {
            // Kode login admin: 000000.
            $admin->kode = '000000';
        }

        $admin->save();

        Profile::query()->firstOrCreate(
            ['user_id' => $admin->id],
            [
                'nama_lengkap' => 'Administrator',
                'kelas' => '-',
            ],
        );

        $this->command->info("Admin Administrator (NIS {$admin->nis}): kode {$admin->kode}");
    }
}
