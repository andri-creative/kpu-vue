<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\User;
use App\Support\KodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Kode yang dipakai seluruh test login.
     */
    public const TEST_KODE = '123456';

    public function definition(): array
    {
        return [
            'nis' => fake()->unique()->numerify('##########'),
            // Default-nya kode acak supaya tetap unik saat factory dipanggil
            // berulang. Test yang butuh kode tertentu memakai withKode().
            'kode' => KodeGenerator::generate(),
            // Kolom password tidak lagi dipakai untuk login, hanya memenuhi NOT NULL.
            'password' => Str::password(32),
            'role' => RoleEnum::Pemilih->value,
            'status' => StatusEnum::Aktif->value,
        ];
    }

    /**
     * Set a known plain kode, dipakai test yang melakukan login.
     */
    public function withKode(string $kode): static
    {
        return $this->state(fn (array $attributes) => [
            'kode' => $kode,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::Admin->value,
            'status' => StatusEnum::Nonaktif->value,
        ]);
    }

    public function kandidat(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::Kandidat->value,
            'status' => StatusEnum::Nonaktif->value,
        ]);
    }
}
