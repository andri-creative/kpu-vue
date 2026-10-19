<?php

use App\Models\Profile;
use App\Models\User;
use Database\Factories\UserFactory;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    Profile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Test User',
        'kelas' => 'XII-A',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'nama_lengkap' => 'Test User',
            'kelas' => 'XII-A',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->profile->nama_lengkap)->toBe('Test User');
    expect($user->profile->kelas)->toBe('XII-A');
});

test('user can delete their account', function () {
    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'kode' => UserFactory::TEST_KODE,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct kode must be provided to delete account', function () {
    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'kode' => '999999',
        ]);

    $response
        ->assertSessionHasErrors('kode')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('kode must be 6 digits to delete account', function () {
    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'kode' => '123',
        ]);

    $response->assertSessionHasErrors('kode');

    expect($user->fresh())->not->toBeNull();
});
