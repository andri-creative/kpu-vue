<?php

use App\Models\Candidate;
use App\Models\Profile;
use App\Models\User;
use Database\Factories\UserFactory;

function buatKandidat(array $attributes = []): Candidate
{
    $user = User::factory()->kandidat()->create($attributes['user'] ?? []);

    Profile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Kandidat Uji',
        'kelas' => 'XII-A',
    ]);

    return Candidate::create([
        'user_id' => $user->id,
        'foto' => 'uploads/candidates/uji.jpg',
        'nama_lengkap' => 'Kandidat Uji',
        'nomor_urut' => $attributes['nomor_urut'] ?? Candidate::max('nomor_urut') + 1,
        'nis' => $user->nis,
        'kelas' => 'XII-A',
        'visi' => '<p>Visi lama</p>',
        'misi' => '<p>Misi lama</p>',
    ]);
}

test('tamu tidak bisa membuka halaman kandidat', function () {
    $this->get(route('kandidat'))->assertRedirect(route('home'));
});

test('pemilih tidak bisa membuka halaman kandidat', function () {
    $pemilih = User::factory()->create();

    $this->actingAs($pemilih)
        ->get(route('kandidat'))
        ->assertRedirect(route('pemilih'));
});

test('admin tidak bisa membuka halaman kandidat', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('kandidat'))
        ->assertRedirect(route('candidates.index'));
});

test('kandidat bisa membuka halaman profilnya sendiri', function () {
    $candidate = buatKandidat();

    $this->actingAs($candidate->user)
        ->get(route('kandidat'))
        ->assertOk()
        ->assertInertia(
            fn($page) => $page
                ->component('Kandidat')
                ->where('candidate.nis', $candidate->nis)
                ->where('candidate.visi', '<p>Visi lama</p>')
                ->where('candidate.misi', '<p>Misi lama</p>')
        );
});

test('kandidat hanya melihat profilnya sendiri bukan kandidat lain', function () {
    $milikSaya = buatKandidat(['user' => ['nis' => '1000000001']]);
    buatKandidat(['user' => ['nis' => '1000000002']]);

    $this->actingAs($milikSaya->user)
        ->get(route('kandidat'))
        ->assertOk()
        ->assertInertia(
            fn($page) => $page
                ->where('candidate.nis', '1000000001')
        );
});

test('kandidat bisa memperbarui visi dan misi sendiri', function () {
    $candidate = buatKandidat();

    $this->actingAs($candidate->user)
        ->patch(route('kandidat.update'), [
            'visi' => '<p>Visi baru</p>',
            'misi' => '<p>Misi baru</p>',
        ])
        ->assertRedirect(route('kandidat'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('candidates', [
        'id' => $candidate->id,
        'visi' => '<p>Visi baru</p>',
        'misi' => '<p>Misi baru</p>',
    ]);
});

test('kandidat tidak bisa mengubah data profil milik admin', function () {
    $candidate = buatKandidat();

    $this->actingAs($candidate->user)
        ->patch(route('kandidat.update'), [
            'visi' => '<p>Visi baru</p>',
            'misi' => '<p>Misi baru</p>',
            'nama_lengkap' => 'Nama Dirubah',
            'nomor_urut' => 99,
            'role' => 'admin',
        ])
        ->assertSessionHasNoErrors();

    expect($candidate->fresh())
        ->nama_lengkap->toBe('Kandidat Uji')
        ->nomor_urut->toBe(1);

    expect($candidate->user->fresh()->role->value)->toBe('kandidat');
});

test('kandidat harus mengisi visi dan misi saat memperbarui', function () {
    $candidate = buatKandidat();

    $this->actingAs($candidate->user)
        ->patch(route('kandidat.update'), [
            'visi' => '',
            'misi' => '',
        ])
        ->assertSessionHasErrors(['visi', 'misi']);

    expect($candidate->fresh()->visi)->toBe('<p>Visi lama</p>');
});

test('pemilih tidak bisa memperbarui visi misi', function () {
    $candidate = buatKandidat();
    $pemilih = User::factory()->create();

    $this->actingAs($pemilih)
        ->patch(route('kandidat.update'), [
            'visi' => '<p>Visi malam</p>',
            'misi' => '<p>Misi malam</p>',
        ])
        ->assertRedirect(route('pemilih'));

    expect($candidate->fresh()->visi)->toBe('<p>Visi lama</p>');
});

test('kandidat yang login langsung diarahkan ke halaman kandidat', function () {
    $candidate = buatKandidat(['user' => ['kode' => UserFactory::TEST_KODE]]);

    $response = $this->post(route('login.store'), [
        'nis' => $candidate->nis,
        'kode' => UserFactory::TEST_KODE,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('kandidat', absolute: false));
});

test('perubahan profil kandidat ikut memperbarui tabel candidates', function () {
    $candidate = buatKandidat();

    $this->actingAs($candidate->user)
        ->patch(route('profile.update'), [
            'nama_lengkap' => 'Nama Baru',
            'kelas' => 'XII-Z',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('candidates', [
        'id' => $candidate->id,
        'nama_lengkap' => 'Nama Baru',
        'kelas' => 'XII-Z',
    ]);

    $this->assertDatabaseHas('profiles', [
        'user_id' => $candidate->user_id,
        'nama_lengkap' => 'Nama Baru',
        'kelas' => 'XII-Z',
    ]);
});
