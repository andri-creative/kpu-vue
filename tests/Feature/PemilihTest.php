<?php

use App\Models\User;

test('guests are redirected to the login page when accessing pemilih', function () {
    $response = $this->get(route('pemilih'));
    $response->assertRedirect(route('home'));
});

test('pemilih can access the pemilih page', function () {
    $user = User::factory()->create(); // default role is Pemilih

    $response = $this->actingAs($user)->get(route('pemilih'));

    $response->assertOk();
});

test('admin is redirected away from pemilih page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('pemilih'));

    $response->assertRedirect(route('candidates.index'));
});

test('kandidat is redirected away from pemilih page', function () {
    $kandidat = User::factory()->kandidat()->create();

    $response = $this->actingAs($kandidat)->get(route('pemilih'));

    $response->assertRedirect(route('kandidat'));
});
