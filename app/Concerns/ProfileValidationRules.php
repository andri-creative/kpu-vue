<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait ProfileValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas' => ['required', 'string', 'max:255'],
        ];
    }
}
