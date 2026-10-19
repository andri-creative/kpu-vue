<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'nis' => ['required', 'string', 'unique:users,nis'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas' => ['required', 'string', 'max:255'],
        ];
    }
}
