<?php

use App\Models\User;
use Database\Factories\UserFactory;

test('login screen can be rendered', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('pemilih is redirected to pemilih page after login', function () {
    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create(); // default role Pemilih

    $response = $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => UserFactory::TEST_KODE,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('pemilih', absolute: false));
});

test('admin is redirected to dashboard after login', function () {
    $admin = User::factory()->admin()->withKode(UserFactory::TEST_KODE)->create();

    $response = $this->post(route('login.store'), [
        'nis' => $admin->nis,
        'kode' => UserFactory::TEST_KODE,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('kandidat is redirected to kandidat page after login', function () {
    $kandidat = User::factory()->kandidat()->withKode(UserFactory::TEST_KODE)->create();

    $response = $this->post(route('login.store'), [
        'nis' => $kandidat->nis,
        'kode' => UserFactory::TEST_KODE,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('kandidat', absolute: false));
});

test('users can not authenticate with wrong kode', function () {
    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create();

    $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => '999999',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with a kode that is not 6 digits', function () {
    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create();

    $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => '12345',
    ])->assertSessionHasErrors('kode');

    $this->assertGuest();
});

test('login requires kode', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => '',
    ])->assertSessionHasErrors('kode');

    $this->assertGuest();
});

test('users without kode can not authenticate', function () {
    $user = User::factory()->create(['kode' => null]);

    $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => UserFactory::TEST_KODE,
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.store'), [
            'nis' => $user->nis,
            'kode' => '999999',
        ]);
    }

    $response = $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => '999999',
    ]);

    $response->assertTooManyRequests();
});
