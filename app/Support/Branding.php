<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class Branding
{
    public const CACHE_KEY = 'branding.settings';

    /**
     * Kunci pengaturan branding.
     *
     * @var list<string>
     */
    public const KEYS = [
        'app_name',
        'tagline',
        'logo_url',
        'secondary_logo_url',
        'favicon_url',
        'brand_color',
        'brand_color_alt',
        'background_image_url',
        'background_image_width',
        'background_image_height',
        'login_card_color',
        'voting_duration',
    ];

    public const DEFAULT_BRAND_COLOR = '#4f46e5';

    public const DEFAULT_BRAND_COLOR_ALT = '#9333ea';

    public const DEFAULT_BACKGROUND_IMAGE_WIDTH = 1920;

    public const DEFAULT_BACKGROUND_IMAGE_HEIGHT = 1080;

    public const DEFAULT_VOTING_DURATION = 3;

    /**
     * Nilai mentah seluruh pengaturan (cached).
     *
     * @return array<string, string|null>
     */
    public function values(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->values()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! in_array($key, self::KEYS, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                Setting::query()->where('key', $key)->delete();

                continue;
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->forget();
    }

    public function reset(): void
    {
        if ($this->tableExists()) {
            Setting::query()->whereIn('key', self::KEYS)->delete();
        }

        $this->forget();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function name(): string
    {
        return (string) config('app.name');
    }

    public function tagline(): ?string
    {
        return $this->get('tagline');
    }

    public function logoUrl(): ?string
    {
        return $this->resolve($this->get('logo_url'));
    }

    public function secondaryLogoUrl(): ?string
    {
        return $this->resolve($this->get('secondary_logo_url'));
    }

    public function faviconUrl(): string
    {
        return $this->resolve($this->get('favicon_url')) ?? asset('favicon.svg');
    }

    public function brandColor(): string
    {
        return $this->get('brand_color', self::DEFAULT_BRAND_COLOR) ?? self::DEFAULT_BRAND_COLOR;
    }

    public function brandColorAlt(): string
    {
        return $this->get('brand_color_alt', self::DEFAULT_BRAND_COLOR_ALT) ?? self::DEFAULT_BRAND_COLOR_ALT;
    }

    public function backgroundImageUrl(): ?string
    {
        return $this->resolve($this->get('background_image_url'));
    }

    public function backgroundImageWidth(): ?int
    {
        return $this->dimension('background_image_width');
    }

    public function backgroundImageHeight(): ?int
    {
        return $this->dimension('background_image_height');
    }

    public function loginCardColor(): ?string
    {
        return $this->get('login_card_color');
    }

    /**
     * Durasi timer pemilihan dalam menit.
     */
    public function votingDuration(): int
    {
        $value = $this->get('voting_duration');

        if ($value === null || ! is_numeric($value)) {
            return self::DEFAULT_VOTING_DURATION;
        }

        $minutes = (int) $value;

        return $minutes > 0 ? $minutes : self::DEFAULT_VOTING_DURATION;
    }

    private function dimension(string $key): ?int
    {
        $value = $this->get($key);

        if ($value === null || ! is_numeric($value)) {
            return null;
        }

        $size = (int) $value;

        return $size > 0 ? $size : null;
    }

    /**
     * Lengkapi path relatif (mis. branding/logo.png) menjadi URL publik.
     */
    private function resolve(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//', 'data:', '/'])) {
            return $value;
        }

        return asset('uploads/'.ltrim($value, '/'));
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }
}
