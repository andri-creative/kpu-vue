<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Http\Requests\Fortify\LoginRequest;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

        // Fortify has no loginRequestUsing() hook, so swap the FormRequest via
        // the container to swap `password` for the 6-digit `kode` field.
        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('nis', $request->input('nis'))->first();

            if (! $user || ! $user->verifyKode((string) $request->input('kode'))) {
                return null;
            }

            if ($user->role === RoleEnum::Pemilih) {
                if ($user->status === StatusEnum::Aktif && $user->hasVotingExpired()) {
                    $user->forceFill(['status' => StatusEnum::Kedaluwarsa])->save();
                }

                if ($user->status !== StatusEnum::Aktif) {
                    throw ValidationException::withMessages([
                        'nis' => [$user->status === StatusEnum::Kedaluwarsa
                            ? 'Waktu voting Anda telah habis dan tidak dapat login kembali.'
                            : 'Anda sudah menggunakan hak voting Anda dan tidak dapat login kembali.'],
                    ]);
                }

                if ($user->voting_started_at === null) {
                    $user->forceFill(['voting_started_at' => now()])->save();
                }
            }

            return $user;
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('nis').$request->ip());
        });
    }
}
