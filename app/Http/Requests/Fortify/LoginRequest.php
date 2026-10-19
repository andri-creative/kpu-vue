<?php

namespace App\Http\Requests\Fortify;

use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

/**
 * Replaces Fortify's LoginRequest so the credential field is a 6-digit kode
 * instead of a password. Bound in FortifyServiceProvider::register().
 *
 * Everything else (rate limiting, failed auth handling) keeps working through
 * Fortify's login pipeline.
 */
class LoginRequest extends FortifyLoginRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            Fortify::username() => ['required', 'string'],
            'kode' => ['required', 'digits:6'],
            'remember' => 'sometimes',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode wajib diisi.',
            'kode.digits' => 'Kode harus terdiri dari 6 angka.',
        ];
    }
}
