<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('home');
        }

        $allowedRoles = array_map(
            fn (string $role) => RoleEnum::from($role),
            $roles,
        );

        if (! in_array($user->role, $allowedRoles, strict: true)) {
            return match ($user->role) {
                RoleEnum::Admin => redirect()->route('candidates.index'),
                RoleEnum::Pemilih => redirect()->route('pemilih'),
                default => redirect()->route('kandidat'),
            };
        }

        return $next($request);
    }
}
