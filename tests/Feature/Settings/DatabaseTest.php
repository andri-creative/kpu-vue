<?php

use App\Models\Candidate;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteLog;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view database settings page with statistics', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('settings.database.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Database')
            ->has('summary', fn (Assert $summary) => $summary
                ->has('file_size_bytes')
                ->has('file_size_formatted')
                ->has('database_path')
                ->has('driver')
                ->has('total_rows')
                ->has('total_logs')
                ->has('total_votes')
                ->has('total_users')
                ->etc()
            )
            ->has('tables')
            ->has('recentLogs')
            ->has('lastUpdated')
        );
});

test('non admin cannot access database settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.database.index'))
        ->assertRedirect(route('pemilih'));
});

test('guest cannot access database settings', function () {
    $this->get(route('settings.database.index'))
        ->assertRedirect(route('home'));
});

test('admin can trigger vacuum on database', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('settings.database.index'))
        ->post(route('settings.database.vacuum'))
        ->assertRedirect(route('settings.database.index'))
        ->assertSessionHasNoErrors();
});

test('admin can reset a single table and archive its data', function () {
    $admin = User::factory()->admin()->create();
    seedResetScenario();

    $this->actingAs($admin)
        ->from(route('settings.database.index'))
        ->post(route('settings.database.reset'), [
            'table' => 'votes',
            'confirmation' => 'RESET',
        ])
        ->assertRedirect(route('settings.database.index'))
        ->assertSessionHasNoErrors();

    expect(DB::table('votes')->count())->toBe(0)
        ->and(DB::table('votes_archived')->count())->toBe(1);
});

test('resetting users keeps admin and archives non-admin accounts', function () {
    $admin = User::factory()->admin()->create();
    $pemilih = User::factory()->create();
    $kandidat = User::factory()->kandidat()->create();

    $this->actingAs($admin)
        ->from(route('settings.database.index'))
        ->post(route('settings.database.reset'), [
            'table' => 'users',
            'confirmation' => 'RESET',
        ])
        ->assertSessionHasNoErrors();

    expect(User::find($admin->id))->not->toBeNull()
        ->and(User::find($pemilih->id))->toBeNull()
        ->and(User::find($kandidat->id))->toBeNull()
        ->and(DB::table('users_archived')->count())->toBe(2);
});

test('resetting a protected table is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('settings.database.index'))
        ->post(route('settings.database.reset'), [
            'table' => 'migrations',
            'confirmation' => 'RESET',
        ])
        ->assertSessionHasErrors('table');
});

test('reset requires the RESET confirmation text', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('settings.database.index'))
        ->post(route('settings.database.reset'), [
            'table' => 'votes',
            'confirmation' => 'nope',
        ])
        ->assertSessionHasErrors('confirmation');
});

test('admin can reset all data while keeping admin and migrations', function () {
    $admin = User::factory()->admin()->create();
    [$pemilih] = seedResetScenario();

    DB::table('sessions')->insert([
        'id' => 'reset-session',
        'payload' => 'payload',
        'last_activity' => time(),
    ]);

    $migrations = DB::table('migrations')->count();

    $this->actingAs($admin)
        ->from(route('settings.database.index'))
        ->post(route('settings.database.reset-all'), [
            'confirmation' => 'RESET',
        ])
        ->assertRedirect(route('settings.database.index'))
        ->assertSessionHasNoErrors();

    expect(User::find($admin->id))->not->toBeNull()
        ->and(User::find($pemilih->id))->toBeNull()
        ->and(DB::table('votes')->count())->toBe(0)
        ->and(DB::table('vote_logs')->count())->toBe(0)
        ->and(DB::table('candidates')->count())->toBe(0)
        ->and(DB::table('sessions')->count())->toBe(0)
        ->and(DB::table('migrations')->count())->toBe($migrations);
});

test('non admin cannot reset data', function () {
    $user = User::factory()->create();
    $victim = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.database.index'))
        ->post(route('settings.database.reset'), [
            'table' => 'users',
            'confirmation' => 'RESET',
        ])
        ->assertRedirect(route('pemilih'));

    expect(User::find($victim->id))->not->toBeNull();
});

/**
 * Siapkan satu kandidat, satu suara, dan satu log suara untuk pengujian reset.
 *
 * @return array{0: User, 1: Candidate, 2: Vote}
 */
function seedResetScenario(): array
{
    $pemilih = User::factory()->create();
    $kandidat = User::factory()->kandidat()->create();

    $candidate = Candidate::create([
        'user_id' => $kandidat->id,
        'foto' => 'test.jpg',
        'nama_lengkap' => 'Kandidat Satu',
        'nomor_urut' => 1,
        'nis' => $kandidat->nis,
        'kelas' => 'XII-1',
        'visi' => 'Visi kandidat',
        'misi' => 'Misi kandidat',
    ]);

    $vote = Vote::create([
        'user_id' => $pemilih->id,
        'candidate_id' => $candidate->id,
        'voted_at' => now(),
    ]);

    VoteLog::create([
        'user_id' => $pemilih->id,
        'candidate_id' => $candidate->id,
        'action' => 'vote',
    ]);

    return [$pemilih, $candidate, $vote];
}
