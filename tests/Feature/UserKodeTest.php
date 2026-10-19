<?php

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('admin can create user and receives a 6 digit kode', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'nis' => '20260010',
            'nama_lengkap' => 'Andi Pratama',
            'kelas' => 'X IPA 1',
        ])
        ->assertSessionHasNoErrors();

    $created = User::where('nis', '20260010')->firstOrFail();

    preg_match('/(\d{6})/', session('flash.success'), $matches);

    expect($matches[1])->toHaveLength(6)
        ->and($created->verifyKode($matches[1]))->toBeTrue()
        // Kode disimpan plaintext sehingga sama dengan yang ditampilkan.
        ->and($created->kode)->toBe($matches[1]);
});

test('admin can reset kode for a single user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $originalHash = $user->kode;

    $this->actingAs($admin)
        ->from(route('users.index'))
        ->patch(route('users.reset-kode', $user))
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->kode)->not->toBe($originalHash);

    $kode = session('generated_codes')[$user->nis];

    expect($kode)->toHaveLength(6)
        ->and($user->verifyKode($kode))->toBeTrue();
});

test('reset kode flash is consumed after being read once', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->patch(route('users.reset-kode', $user));

    $this->actingAs($admin)->get(route('users.index'))->assertOk();

    expect(session('generated_codes'))->toBeEmpty();
});

test('admin can generate kode for every user that has none', function () {
    $admin = User::factory()->admin()->create();
    $withoutKode = User::factory()->count(3)->create(['kode' => null]);
    $withKode = User::factory()->create();

    $this->actingAs($admin)
        ->from(route('users.index'))
        ->patch(route('users.reset-kode-kosong'))
        ->assertRedirect(route('users.index'));

    expect($withKode->refresh()->kode)->not->toBeNull();

    foreach ($withoutKode as $user) {
        $kode = $user->refresh()->kode;

        expect($kode)->not->toBeNull();
        expect(session('generated_codes')[$user->nis])->toHaveLength(6);
    }
});

test('bulk kode generation skips admins', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create(['kode' => null]);

    $this->actingAs($admin)->patch(route('users.reset-kode-kosong'));

    expect($otherAdmin->refresh()->kode)->toBeNull();
});

test('bulk kode generation is skipped when everyone already has a kode', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->from(route('users.index'))
        ->patch(route('users.reset-kode-kosong'))
        ->assertRedirect(route('users.index'));

    expect(session('flash.success'))
        ->toBe('Semua user sudah memiliki kode.');
});

test('users index exposes the plaintext kode but never the password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->where('users.data.0.kode', $user->kode)
        ->missing('users.data.0.password'));

    expect(json_encode($response->viewData('page')['props']['users']['data']))
        ->toContain($user->kode);
});

test('users index exposes the kelas from the profile relation', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    Profile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Andi Pratama',
        'kelas' => 'X IPA 1',
    ]);

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->where('users.data.0.kelas', 'X IPA 1'));
});

test('every generated kode is unique in the database', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(5)->create(['kode' => null]);

    $this->actingAs($admin)->patch(route('users.reset-kode-kosong'));

    $hashes = DB::table('users')->whereNotNull('kode')->pluck('kode');

    expect($hashes->count())->toBe(6)
        ->and($hashes->unique()->count())->toBe(6);
});

test('pemilih cannot reset kode', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('users.reset-kode', $other))
        ->assertRedirect(route('pemilih'));

    expect($other->refresh()->kode)->not->toBeNull();
});

test('new users created on the user page are pemilih with aktif status and a profile', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'nis' => '20260011',
            'nama_lengkap' => 'Budi Santoso',
            'kelas' => 'X IPA 2',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'nis' => '20260011',
        'role' => RoleEnum::Pemilih->value,
        'status' => StatusEnum::Aktif->value,
    ]);

    $this->assertDatabaseHas('profiles', [
        'user_id' => User::where('nis', '20260011')->firstOrFail()->id,
        'nama_lengkap' => 'Budi Santoso',
        'kelas' => 'X IPA 2',
    ]);
});

test('nonaktif users are listed last on the users index', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['status' => StatusEnum::Nonaktif, 'created_at' => now()->subMinutes(3)]);
    User::factory()->create(['status' => StatusEnum::SudahVoting, 'created_at' => now()->subMinutes(2)]);
    $aktif = User::factory()->create(['status' => StatusEnum::Aktif, 'created_at' => now()->subMinute()]);

    $response = $this->actingAs($admin)->get(route('users.index'));

    $statuses = collect($response->viewData('page')['props']['users']['data'])
        ->pluck('status');

    expect($statuses)->toContain(StatusEnum::Aktif->value)
        ->and($statuses->last())->toBe(StatusEnum::Nonaktif->value)
        ->and($statuses[0])->not->toBe(StatusEnum::Nonaktif->value);

    // User aktif yang paling baru tetap menduduki urutan pertama.
    expect($response->viewData('page')['props']['users']['data'][0]['id'])->toBe($aktif->id);
});
