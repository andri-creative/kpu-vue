<?php

use App\Models\User;
use App\Support\LogTailer;
use Inertia\Testing\AssertableInertia as Assert;

function makeTempLog(string $contents): string
{
    $path = (string) tempnam(sys_get_temp_dir(), 'kpu-log-');
    file_put_contents($path, $contents);

    return $path;
}

test('admin can view logs terminal page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('settings.logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Logs')
            ->has('sources')
            ->has('activeSource')
            ->has('tailLines')
        );
});

test('non admin cannot access logs terminal page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.logs.index'))
        ->assertRedirect(route('pemilih'));
});

test('guest cannot access logs terminal page', function () {
    $this->get(route('settings.logs.index'))
        ->assertRedirect(route('home'));
});

test('log tailer returns the last lines of a file', function () {
    $path = makeTempLog("line 1\nline 2\nline 3\n");

    $tailer = app(LogTailer::class);

    expect($tailer->lastLines($path, 2))->toBe(['line 2', 'line 3'])
        ->and($tailer->fileSize($path))->toBeGreaterThan(0);
});

test('log tail returns backlog with a cursor on first request', function () {
    $admin = User::factory()->admin()->create();
    $path = makeTempLog("[2026-01-01 00:00:00] local.ERROR: streaming boom\n");

    config(['log_viewer.sources.laravel' => $path]);

    $response = $this->actingAs($admin)
        ->getJson(route('settings.logs.tail', ['source' => 'laravel', 'cursor' => -1]));

    $response->assertOk()
        ->assertJsonPath('exists', true)
        ->assertJsonPath('reset', true)
        ->assertJsonPath('lines.0', '[2026-01-01 00:00:00] local.ERROR: streaming boom');

    expect($response->json('cursor'))->toBeGreaterThan(0);
});

test('log tail returns only new lines after a cursor', function () {
    $admin = User::factory()->admin()->create();
    $path = makeTempLog("first line\n");

    config(['log_viewer.sources.laravel' => $path]);

    $cursor = $this->actingAs($admin)
        ->getJson(route('settings.logs.tail', ['source' => 'laravel', 'cursor' => -1]))
        ->json('cursor');

    file_put_contents($path, "first line\nsecond line\n");

    $this->actingAs($admin)
        ->getJson(route('settings.logs.tail', ['source' => 'laravel', 'cursor' => $cursor]))
        ->assertOk()
        ->assertJsonPath('reset', false)
        ->assertJsonPath('lines', ['second line']);
});

test('log tail rejects an unknown source', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson(route('settings.logs.tail', ['source' => 'unknown']))
        ->assertNotFound();
});

test('admin can reset a log file', function () {
    $admin = User::factory()->admin()->create();
    $path = makeTempLog("some log content\n");

    config(['log_viewer.sources.laravel' => $path]);

    $this->actingAs($admin)
        ->from(route('settings.logs.index'))
        ->post(route('settings.logs.reset'), ['source' => 'laravel'])
        ->assertRedirect(route('settings.logs.index'))
        ->assertSessionHasNoErrors();

    expect(file_get_contents($path))->toBe('');
});

test('reset rejects an unknown log source', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('settings.logs.index'))
        ->post(route('settings.logs.reset'), ['source' => 'unknown'])
        ->assertSessionHasErrors('source');
});
