<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureVotingNotExpired
{
    /**
     * Tolak pemilih yang waktu votingnya sudah habis: tandai kedaluwarsa,
     * akhiri sesi, lalu arahkan kembali ke halaman login.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== RoleEnum::Pemilih) {
            return $next($request);
        }

        if ($user->status !== StatusEnum::Aktif || ! $user->hasVotingExpired()) {
            return $next($request);
        }

        $user->forceFill(['status' => StatusEnum::Kedaluwarsa])->save();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->withErrors([
            'nis' => 'Waktu voting Anda telah habis dan tidak dapat login kembali.',
        ]);
    }
}
