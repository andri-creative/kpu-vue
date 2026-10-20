<?php

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\Candidate;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $root = sys_get_temp_dir().'/kpu-candidates-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($root);

    app()->instance('test.candidates_root', $root);
    config(['filesystems.uploads_path' => $root]);
});

afterEach(function () {
    File::deleteDirectory(app('test.candidates_root'));
});

test('admin can view candidate management page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('candidates.index'))
        ->assertOk();
});

test('non admin cannot access candidate management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('candidates.index'))
        ->assertRedirect(route('pemilih'));
});

test('admin can create a candidate', function () {
    $admin = User::factory()->admin()->create();
    $foto = UploadedFile::fake()->image('foto.jpg');

    $this->actingAs($admin)
        ->post(route('candidates.store'), [
            'nomor_urut' => 1,
            'nama_lengkap' => 'Test Kandidat',
            'kelas' => 'XII-A',
            'nis' => '9999999999',
            'foto' => $foto,
            'visi' => '<p>Visi kandidat</p>',
            'misi' => '<p>Misi kandidat</p>',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'nis' => '9999999999',
        'role' => RoleEnum::Kandidat->value,
        'status' => StatusEnum::Nonaktif->value,
    ]);

    $this->assertDatabaseHas('candidates', [
        'nomor_urut' => 1,
        'nis' => '9999999999',
        'nama_lengkap' => 'Test Kandidat',
        'kelas' => 'XII-A',
        'visi' => '<p>Visi kandidat</p>',
        'misi' => '<p>Misi kandidat</p>',
    ]);

    $this->assertDatabaseHas('profiles', [
        'nama_lengkap' => 'Test Kandidat',
        'kelas' => 'XII-A',
    ]);

    $candidate = Candidate::where('nis', '9999999999')->first();
    expect($candidate)->not->toBeNull();
    expect($candidate->foto)->toEndWith('.webp');
    expect(file_exists(app('test.candidates_root').'/candidates/'.$candidate->foto))->toBeTrue();
});

test('candidate without a photo exposes a null foto_url', function () {
    $user = User::factory()->kandidat()->create(['nis' => '7777777777']);

    $candidate = Candidate::create([
        'user_id' => $user->id,
        'foto' => null,
        'nama_lengkap' => 'Tanpa Foto',
        'nomor_urut' => 9,
        'nis' => '7777777777',
        'kelas' => 'XII-A',
        'visi' => '<p>Visi</p>',
        'misi' => '<p>Misi</p>',
    ]);

    expect($candidate->foto_url)->toBeNull();
});

test('admin can update a candidate', function () {
    $admin = User::factory()->admin()->create();

    $user = User::factory()->kandidat()->create([
        'nis' => '1111111111',
    ]);
    Profile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Kandidat Lama',
        'kelas' => 'XII-B',
    ]);
    $candidate = Candidate::create([
        'user_id' => $user->id,
        'foto' => 'uploads/candidates/old.jpg',
        'nama_lengkap' => 'Kandidat Lama',
        'nomor_urut' => 1,
        'nis' => '1111111111',
        'kelas' => 'XII-B',
        'visi' => '<p>Visi lama</p>',
        'misi' => '<p>Misi lama</p>',
    ]);

    $this->actingAs($admin)
        ->put(route('candidates.update', $candidate), [
            'nomor_urut' => 1,
            'nama_lengkap' => 'Kandidat Baru',
            'kelas' => 'XII-C',
            'nis' => '1111111111',
            'visi' => '<p>Visi baru</p>',
            'misi' => '<p>Misi baru</p>',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('candidates', [
        'id' => $candidate->id,
        'nama_lengkap' => 'Kandidat Baru',
        'kelas' => 'XII-C',
        'visi' => '<p>Visi baru</p>',
        'misi' => '<p>Misi baru</p>',
    ]);

    expect($user->fresh()->nis)->toBe('1111111111');
    expect($user->fresh()->profile->nama_lengkap)->toBe('Kandidat Baru');
});

test('admin can delete a candidate', function () {
    $admin = User::factory()->admin()->create();

    $user = User::factory()->kandidat()->create([
        'nis' => '2222222222',
    ]);
    Profile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Kandidat Hapus',
        'kelas' => 'XII-D',
    ]);
    $candidate = Candidate::create([
        'user_id' => $user->id,
        'foto' => 'uploads/candidates/to-delete.jpg',
        'nama_lengkap' => 'Kandidat Hapus',
        'nomor_urut' => 2,
        'nis' => '2222222222',
        'kelas' => 'XII-D',
        'visi' => '<p>Visi</p>',
        'misi' => '<p>Misi</p>',
    ]);

    $this->actingAs($admin)
        ->delete(route('candidates.destroy', $candidate))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('candidates', [
        'id' => $candidate->id,
    ]);

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('candidate creation validates duplicate nomor_urut', function () {
    $admin = User::factory()->admin()->create();

    $existingUser = User::factory()->kandidat()->create([
        'nis' => '3333333333',
    ]);
    Profile::create([
        'user_id' => $existingUser->id,
        'nama_lengkap' => 'Kandidat Existing',
        'kelas' => 'XII-E',
    ]);
    Candidate::create([
        'user_id' => $existingUser->id,
        'foto' => 'uploads/candidates/existing.jpg',
        'nama_lengkap' => 'Kandidat Existing',
        'nomor_urut' => 1,
        'nis' => '3333333333',
        'kelas' => 'XII-E',
        'visi' => '<p>Visi</p>',
        'misi' => '<p>Misi</p>',
    ]);

    $foto = UploadedFile::fake()->image('foto.jpg');

    $this->actingAs($admin)
        ->post(route('candidates.store'), [
            'nomor_urut' => 1,
            'nama_lengkap' => 'Kandidat Duplikat',
            'kelas' => 'XII-F',
            'nis' => '4444444444',
            'foto' => $foto,
            'visi' => '<p>Visi</p>',
            'misi' => '<p>Misi</p>',
        ])
        ->assertSessionHasErrors(['nomor_urut']);
});

test('candidate creation validates duplicate nis', function () {
    $admin = User::factory()->admin()->create();

    $existingUser = User::factory()->kandidat()->create([
        'nis' => '5555555555',
    ]);
    Profile::create([
        'user_id' => $existingUser->id,
        'nama_lengkap' => 'Kandidat Existing',
        'kelas' => 'XII-G',
    ]);
    Candidate::create([
        'user_id' => $existingUser->id,
        'foto' => 'candidates/existing.jpg',
        'nama_lengkap' => 'Kandidat Existing',
        'nomor_urut' => 1,
        'nis' => '5555555555',
        'kelas' => 'XII-G',
        'visi' => '<p>Visi</p>',
        'misi' => '<p>Misi</p>',
    ]);

    $foto = UploadedFile::fake()->image('foto.jpg');

    $this->actingAs($admin)
        ->post(route('candidates.store'), [
            'nomor_urut' => 2,
            'nama_lengkap' => 'Kandidat Duplikat Nis',
            'kelas' => 'XII-H',
            'nis' => '5555555555',
            'foto' => $foto,
            'visi' => '<p>Visi</p>',
            'misi' => '<p>Misi</p>',
        ])
        ->assertSessionHasErrors(['nis']);
});
