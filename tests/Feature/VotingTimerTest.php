<?php

use App\Enums\StatusEnum;
use App\Models\Candidate;
use App\Models\Profile;
use App\Models\User;
use Database\Factories\UserFactory;

test('logging in records the voting start time for a pemilih', function () {
    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create();

    $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => UserFactory::TEST_KODE,
    ])->assertRedirect(route('pemilih', absolute: false));

    expect($user->fresh()->voting_started_at)->not->toBeNull();
});

test('logging in again does not reset an existing voting start time', function () {
    $startedAt = now()->subMinute()->startOfSecond();

    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create([
        'voting_started_at' => $startedAt,
    ]);

    $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => UserFactory::TEST_KODE,
    ]);

    expect($user->fresh()->voting_started_at->equalTo($startedAt))->toBeTrue();
});

test('voting helpers compute the deadline and remaining time', function () {
    config(['branding.voting_duration' => 10]);

    $user = User::factory()->create(['voting_started_at' => now()]);

    expect($user->votingRemainingSeconds())->toBeGreaterThan(0)
        ->and($user->votingRemainingSeconds())->toBeLessThanOrEqual(600)
        ->and($user->hasVotingExpired())->toBeFalse();
});

test('pemilih can access and vote while the timer is running', function () {
    config(['branding.voting_duration' => 5]);

    $user = User::factory()->create(['voting_started_at' => now()]);
    $candidate = candidateForTest();

    $this->actingAs($user)->get(route('pemilih'))->assertOk();

    $this->actingAs($user)->post(route('pemilih.vote'), [
        'candidate_id' => $candidate->id,
    ]);

    expect($user->fresh()->status)->toBe(StatusEnum::SudahVoting);
});

test('casting a vote records it and shows the thank-you page', function () {
    config(['branding.voting_duration' => 5]);

    $user = User::factory()->create(['voting_started_at' => now()]);
    $candidate = candidateForTest();

    $this->actingAs($user)
        ->post(route('pemilih.vote'), ['candidate_id' => $candidate->id])
        ->assertRedirect(route('pemilih'));

    $this->assertDatabaseHas('votes', [
        'user_id' => $user->id,
        'candidate_id' => $candidate->id,
    ]);
    $this->assertDatabaseHas('vote_logs', [
        'user_id' => $user->id,
        'action' => 'vote_cast',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('pemilih'))
        ->assertInertia(fn ($page) => $page->component('PemilihDone'));
});

test('pemilih is logged out and marked kedaluwarsa when the timer has expired', function () {
    config(['branding.voting_duration' => 1]);

    $user = User::factory()->create(['voting_started_at' => now()->subMinutes(5)]);

    $this->actingAs($user)->get(route('pemilih'))->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh()->status)->toBe(StatusEnum::Kedaluwarsa);
});

test('expired pemilih cannot cast a vote', function () {
    config(['branding.voting_duration' => 1]);

    $user = User::factory()->create(['voting_started_at' => now()->subMinutes(5)]);
    $candidate = candidateForTest();

    $this->actingAs($user)->post(route('pemilih.vote'), [
        'candidate_id' => $candidate->id,
    ])->assertRedirect(route('home'));

    $this->assertDatabaseMissing('votes', ['user_id' => $user->id]);
    expect($user->fresh()->status)->toBe(StatusEnum::Kedaluwarsa);
});

test('expired pemilih cannot log in again', function () {
    config(['branding.voting_duration' => 1]);

    $user = User::factory()->withKode(UserFactory::TEST_KODE)->create([
        'voting_started_at' => now()->subMinutes(5),
    ]);

    $this->post(route('login.store'), [
        'nis' => $user->nis,
        'kode' => UserFactory::TEST_KODE,
    ])->assertSessionHasErrors('nis');

    $this->assertGuest();
    expect($user->fresh()->status)->toBe(StatusEnum::Kedaluwarsa);
});

test('shared voting prop exposes the timer state to the client', function () {
    config(['branding.voting_duration' => 10]);

    $user = User::factory()->create(['voting_started_at' => now()]);

    $this->actingAs($user)
        ->get(route('pemilih'))
        ->assertInertia(fn ($page) => $page->where('voting.expired', false));
});

function candidateForTest(): Candidate
{
    $user = User::factory()->kandidat()->create();

    Profile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Kandidat Uji',
        'kelas' => 'XII-A',
    ]);

    return Candidate::create([
        'user_id' => $user->id,
        'foto' => null,
        'nama_lengkap' => 'Kandidat Uji',
        'nomor_urut' => 1,
        'nis' => $user->nis,
        'kelas' => 'XII-A',
        'visi' => '<p>Visi</p>',
        'misi' => '<p>Misi</p>',
    ]);
}
