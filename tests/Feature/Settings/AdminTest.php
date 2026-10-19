<?php

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\User;

test('admin can view admin management page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('settings.admins.index'))
        ->assertOk();
});

test('non admin cannot access admin management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.admins.index'))
        ->assertRedirect(route('pemilih'));
});

test('admin can create another admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('settings.admins.store'), [
            'nis' => '9999999999',
            'nama_lengkap' => 'Admin Baru',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'nis' => '9999999999',
        'role' => RoleEnum::Admin->value,
        'status' => StatusEnum::Nonaktif->value,
    ]);

    $this->assertDatabaseHas('profiles', [
        'nama_lengkap' => 'Admin Baru',
    ]);

    $created = User::where('nis', '9999999999')->firstOrFail();

    expect($created->kode)->not->toBeNull();
});

test('new admin receives a 6 digit kode shown in the toast', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('settings.admins.store'), [
            'nis' => '8888888888',
            'nama_lengkap' => 'Admin Kode',
        ])
        ->assertSessionHasNoErrors();

    $created = User::where('nis', '8888888888')->firstOrFail();

    preg_match('/(\d{6})/', session('inertia.flash_data')['toast']['message'], $matches);

    expect($created->verifyKode($matches[1]))->toBeTrue();
});

test('admin can update another admin', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('settings.admins.update', $target), [
            'nis' => $target->nis,
            'nama_lengkap' => 'Admin Diubah',
        ])
        ->assertSessionHasNoErrors();

    expect($target->fresh()->nis)->toBe($target->nis);
    expect($target->fresh()->profile->nama_lengkap)->toBe('Admin Diubah');
});

test('admin can delete another admin', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('settings.admins.destroy', $target))
        ->assertSessionHasNoErrors();

    expect($target->fresh())->toBeNull();
});

test('admin cannot delete own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('settings.admins.destroy', $admin))
        ->assertSessionHasNoErrors();

    expect($admin->fresh())->not->toBeNull();
});
