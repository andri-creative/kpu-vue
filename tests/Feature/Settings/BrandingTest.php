<?php

use App\Models\Setting;
use App\Models\User;
use App\Providers\BrandingServiceProvider;
use App\Support\Branding;
use App\Support\UploadStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $root = sys_get_temp_dir().'/kpu-branding-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($root);

    app()->instance('test.branding_root', $root);
    app()->instance(UploadStore::class, new UploadStore($root));
});

afterEach(function () {
    File::deleteDirectory(app('test.branding_root'));
});

test('guest cannot view the branding settings page', function () {
    $this->get(route('settings.branding.edit'))->assertRedirect(route('home'));
});

test('non admin cannot view the branding settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.branding.edit'))
        ->assertRedirect(route('pemilih'));
});

test('admin can view the branding settings page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('settings.branding.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Branding')
            ->has('branding', fn (Assert $branding) => $branding
                ->has('name')
                ->has('tagline')
                ->has('logo_url')
                ->has('logo_source')
                ->has('secondary_logo_url')
                ->has('secondary_logo_source')
                ->has('favicon_url')
                ->has('favicon_source')
                ->has('brand_color')
                ->has('brand_color_alt')
                ->has('background_image_url')
                ->has('background_image_source')
                ->has('background_image_width')
                ->has('background_image_height')
                ->has('login_card_color')
                ->has('voting_duration')
            )
        );
});

test('admin can update name and tagline', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'tagline' => 'Satu suara, satu perubahan',
        ])
        ->assertRedirect(route('settings.branding.edit'))
        ->assertSessionHasNoErrors();

    expect(Setting::query()->where('key', 'app_name')->value('value'))->toBe('Pemilihan OSIS')
        ->and(Setting::query()->where('key', 'tagline')->value('value'))->toBe('Satu suara, satu perubahan');
});

test('updating one branding field does not wipe the others', function () {
    $admin = User::factory()->admin()->create();

    Setting::create(['key' => 'app_name', 'value' => 'Awal']);
    Setting::create(['key' => 'tagline', 'value' => 'Tagline awal']);
    Setting::create(['key' => 'brand_color', 'value' => '#111111']);
    Setting::create(['key' => 'brand_color_alt', 'value' => '#222222']);
    Setting::create(['key' => 'login_card_color', 'value' => '#fafafa']);
    Setting::create(['key' => 'background_image_width', 'value' => '800']);
    Setting::create(['key' => 'background_image_height', 'value' => '600']);
    Setting::create(['key' => 'voting_duration', 'value' => '7']);

    $this->actingAs($admin)
        ->post(route('settings.branding.update'), [
            'app_name' => 'Baru',
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::query()->where('key', 'app_name')->value('value'))->toBe('Baru')
        ->and(Setting::query()->where('key', 'tagline')->value('value'))->toBe('Tagline awal')
        ->and(Setting::query()->where('key', 'brand_color')->value('value'))->toBe('#111111')
        ->and(Setting::query()->where('key', 'brand_color_alt')->value('value'))->toBe('#222222')
        ->and(Setting::query()->where('key', 'login_card_color')->value('value'))->toBe('#fafafa')
        ->and(Setting::query()->where('key', 'background_image_width')->value('value'))->toBe('800')
        ->and(Setting::query()->where('key', 'background_image_height')->value('value'))->toBe('600')
        ->and(Setting::query()->where('key', 'voting_duration')->value('value'))->toBe('7');
});

test('admin can upload a logo', function () {
    $admin = User::factory()->admin()->create();
    $root = app('test.branding_root');

    $this->actingAs($admin)
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])
        ->assertSessionHasNoErrors();

    $stored = Setting::query()->where('key', 'logo_url')->value('value');

    expect($stored)->toStartWith('branding/')
        ->and(File::exists($root.'/'.$stored))->toBeTrue();
});

test('admin can update brand colors', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'brand_color' => '#2563eb',
            'brand_color_alt' => '#7c3aed',
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::query()->where('key', 'brand_color')->value('value'))->toBe('#2563eb')
        ->and(Setting::query()->where('key', 'brand_color_alt')->value('value'))->toBe('#7c3aed');
});

test('admin can upload a login background image with size', function () {
    $admin = User::factory()->admin()->create();
    $root = app('test.branding_root');

    $this->actingAs($admin)
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'background_image' => UploadedFile::fake()->image('sekolah.jpg', 1600, 900),
            'background_image_width' => 1600,
            'background_image_height' => 900,
        ])
        ->assertSessionHasNoErrors();

    $stored = Setting::query()->where('key', 'background_image_url')->value('value');

    expect($stored)->toStartWith('branding/')
        ->and(File::exists($root.'/'.$stored))->toBeTrue()
        ->and(Setting::query()->where('key', 'background_image_width')->value('value'))->toBe('1600')
        ->and(Setting::query()->where('key', 'background_image_height')->value('value'))->toBe('900');
});

test('admin can update the login card color', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'login_card_color' => '#f8fafc',
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::query()->where('key', 'login_card_color')->value('value'))->toBe('#f8fafc');
});

test('admin can update the voting timer duration', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'voting_duration' => 5,
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::query()->where('key', 'voting_duration')->value('value'))->toBe('5');
});

test('invalid voting timer duration is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('settings.branding.edit'))
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'voting_duration' => 0,
        ])
        ->assertSessionHasErrors('voting_duration');
});

test('invalid background size is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('settings.branding.edit'))
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'background_image_width' => 0,
        ])
        ->assertSessionHasErrors('background_image_width');
});

test('invalid brand color is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('settings.branding.edit'))
        ->post(route('settings.branding.update'), [
            'app_name' => 'Pemilihan OSIS',
            'brand_color' => 'not-a-color',
        ])
        ->assertSessionHasErrors('brand_color');
});

test('admin can reset branding to default', function () {
    $admin = User::factory()->admin()->create();

    Setting::create(['key' => 'app_name', 'value' => 'Custom']);
    Setting::create(['key' => 'tagline', 'value' => 'Halo']);
    Setting::create(['key' => 'brand_color', 'value' => '#111111']);

    $this->actingAs($admin)
        ->post(route('settings.branding.reset'))
        ->assertRedirect(route('settings.branding.edit'));

    expect(Setting::query()->whereIn('key', ['app_name', 'tagline', 'brand_color'])->count())->toBe(0);
});

test('provider applies the stored app name to config', function () {
    Setting::create(['key' => 'app_name', 'value' => 'Pemilihan OSIS']);

    app(Branding::class)->forget();

    (new BrandingServiceProvider(app()))->boot();

    expect(config('app.name'))->toBe('Pemilihan OSIS');
});
