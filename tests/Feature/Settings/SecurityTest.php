<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('security page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/Security')->where('hasKode', true));
});

test('security page reports a user without kode', function () {
    $user = User::factory()->create(['kode' => null]);

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('hasKode', false));
});

test('user can change their own kode', function () {
    $user = User::factory()->withKode('123456')->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->patch(route('user-kode.update'), [
            'kode_lama' => '123456',
            'kode_baru' => '654321',
            'kode_baru_confirmation' => '654321',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    $user->refresh();

    expect($user->kode)->toBe('654321')
        ->and($user->verifyKode('654321'))->toBeTrue()
        ->and($user->verifyKode('123456'))->toBeFalse();
});

test('kode lama must match the stored kode', function () {
    $user = User::factory()->withKode('123456')->create();

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->patch(route('user-kode.update'), [
            'kode_lama' => '999999',
            'kode_baru' => '654321',
            'kode_baru_confirmation' => '654321',
        ])
        ->assertSessionHasErrors('kode_lama');

    expect($user->refresh()->kode)->toBe('123456');
});

test('new kode must differ from the current kode', function () {
    $user = User::factory()->withKode('123456')->create();

    $this->actingAs($user)
        ->patch(route('user-kode.update'), [
            'kode_lama' => '123456',
            'kode_baru' => '123456',
            'kode_baru_confirmation' => '123456',
        ])
        ->assertSessionHasErrors('kode_baru');
});

test('new kode must not already be used by another account', function () {
    User::factory()->withKode('654321')->create();

    $user = User::factory()->withKode('123456')->create();

    $this->actingAs($user)
        ->patch(route('user-kode.update'), [
            'kode_lama' => '123456',
            'kode_baru' => '654321',
            'kode_baru_confirmation' => '654321',
        ])
        ->assertSessionHasErrors('kode_baru');

    expect($user->refresh()->kode)->toBe('123456');
});

test('kode stays unique in database after changes', function () {
    User::factory()->count(5)->create();

    $user = User::factory()->withKode('123456')->create();

    $this->actingAs($user)->patch(route('user-kode.update'), [
        'kode_lama' => '123456',
        'kode_baru' => '999999',
        'kode_baru_confirmation' => '999999',
    ]);

    $codes = DB::table('users')->whereNotNull('kode')->pluck('kode');

    expect($codes->count())->toBe($codes->unique()->count());
});
