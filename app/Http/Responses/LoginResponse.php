<?php

namespace App\Http\Responses;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        $redirectUrl = match ($user?->role) {
            RoleEnum::Pemilih => route('pemilih'),
            RoleEnum::Kandidat => route('kandidat'),
            RoleEnum::Admin => route('dashboard'),
            default => route('dashboard'),
        };

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirectUrl);
    }
}
