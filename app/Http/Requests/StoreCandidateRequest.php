<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nomor_urut' => ['required', 'integer', 'min:1', 'unique:candidates,nomor_urut'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'unique:candidates,nis', 'unique:users,nis'],
            'foto' => ['required', 'image', 'mimes:webp,jpg,jpeg,png', 'max:1024'],
            'visi' => ['required', 'string'],
            'misi' => ['required', 'string'],
        ];
    }
}
