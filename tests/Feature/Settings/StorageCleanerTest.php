<?php

use App\Models\User;
use App\Support\StorageCleaner;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

test('logs page exposes storage cleanup categories', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('settings.logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Logs')
            ->has('storage')
        );
});

test('admin can clean a single storage category', function () {
    $admin = User::factory()->admin()->create();
    $base = tempStorageBase();

    File::ensureDirectoryExists($base.'/logs');
    File::put($base.'/logs/laravel.log', 'log content');
    File::put($base.'/logs/keep.txt', 'keep me');

    app()->instance(StorageCleaner::class, new StorageCleaner($base));

    $this->actingAs($admin)
        ->from(route('settings.logs.index'))
        ->post(route('settings.storage.clean'), ['category' => 'logs'])
        ->assertRedirect(route('settings.logs.index'))
        ->assertSessionHasNoErrors();

    expect(File::exists($base.'/logs/laravel.log'))->toBeFalse()
        ->and(File::exists($base.'/logs/keep.txt'))->toBeTrue();

    File::deleteDirectory($base);
});

test('admin can clean all storage categories', function () {
    $admin = User::factory()->admin()->create();
    $base = tempStorageBase();

    $files = [
        'logs/laravel.log',
        'pail/cache.pail',
        'debugbar/snapshot.json',
        'inertia-devtools/record.json',
        'framework/views/compiled.php',
    ];

    foreach ($files as $relative) {
        File::ensureDirectoryExists(dirname($base.'/'.$relative));
        File::put($base.'/'.$relative, 'data');
    }

    app()->instance(StorageCleaner::class, new StorageCleaner($base));

    $this->actingAs($admin)
        ->from(route('settings.logs.index'))
        ->post(route('settings.storage.clean-all'))
        ->assertRedirect(route('settings.logs.index'))
        ->assertSessionHasNoErrors();

    foreach ($files as $relative) {
        expect(File::exists($base.'/'.$relative))->toBeFalse();
    }

    File::deleteDirectory($base);
});

test('unknown storage category is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('settings.logs.index'))
        ->post(route('settings.storage.clean'), ['category' => 'tidak-ada'])
        ->assertSessionHasErrors('category');
});

test('non admin cannot clean storage', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.logs.index'))
        ->post(route('settings.storage.clean'), ['category' => 'logs'])
        ->assertRedirect(route('pemilih'));
});

test('guest cannot clean storage', function () {
    $this->from(route('home'))
        ->post(route('settings.storage.clean'), ['category' => 'logs'])
        ->assertRedirect(route('home'));
});

function tempStorageBase(): string
{
    $path = sys_get_temp_dir().'/kpu-test-storage-'.bin2hex(random_bytes(6));

    File::ensureDirectoryExists($path);

    return $path;
}
