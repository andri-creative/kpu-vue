<?php

namespace App\Providers;

use App\Support\Branding;
use Illuminate\Support\ServiceProvider;

class BrandingServiceProvider extends ServiceProvider
{
    /**
     * Terapkan branding tersimpan ke config aplikasi sehingga seluruh
     * pemakai config('app.name') dan tampilan ikut menyesuaikan.
     */
    public function boot(): void
    {
        $branding = $this->app->make(Branding::class);

        $name = $branding->get('app_name');

        if ($name !== null) {
            config(['app.name' => $name]);
        }

        config([
            'branding.tagline' => $branding->tagline(),
            'branding.logo_url' => $branding->logoUrl(),
            'branding.secondary_logo_url' => $branding->secondaryLogoUrl(),
            'branding.favicon_url' => $branding->faviconUrl(),
            'branding.brand_color' => $branding->brandColor(),
            'branding.brand_color_alt' => $branding->brandColorAlt(),
            'branding.background_image_url' => $branding->backgroundImageUrl(),
            'branding.background_image_width' => $branding->backgroundImageWidth(),
            'branding.background_image_height' => $branding->backgroundImageHeight(),
            'branding.login_card_color' => $branding->loginCardColor(),
            'branding.voting_duration' => $branding->votingDuration(),
        ]);
    }
}
