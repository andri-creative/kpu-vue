<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $voting = $user !== null && $user->role === RoleEnum::Pemilih
            ? [
                'deadline' => $user->votingDeadline()?->toIso8601String(),
                'remaining_seconds' => $user->votingRemainingSeconds(),
                'expired' => $user->hasVotingExpired(),
            ]
            : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'branding' => [
                'name' => config('app.name'),
                'tagline' => config('branding.tagline'),
                'logo_url' => config('branding.logo_url'),
                'secondary_logo_url' => config('branding.secondary_logo_url'),
                'favicon_url' => config('branding.favicon_url'),
                'brand_color' => config('branding.brand_color'),
                'brand_color_alt' => config('branding.brand_color_alt'),
                'background_image_url' => config('branding.background_image_url'),
                'background_image_width' => config('branding.background_image_width'),
                'background_image_height' => config('branding.background_image_height'),
                'login_card_color' => config('branding.login_card_color'),
                'voting_duration' => config('branding.voting_duration'),
            ],
            'auth' => [
                'user' => $user?->loadMissing('profile'),
            ],
            'voting' => $voting,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
