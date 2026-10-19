<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateRequest extends FormRequest
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
        $candidate = $this->route('candidate');

        return [
            'nomor_urut' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('candidates', 'nomor_urut')->ignore($candidate?->id),
            ],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas' => ['required', 'string', 'max:255'],
            'nis' => [
                'required',
                'string',
                Rule::unique('candidates', 'nis')->ignore($candidate?->id),
                Rule::unique('users', 'nis')->ignore($candidate?->user_id),
            ],
            'foto' => ['nullable', 'image', 'mimes:webp,jpg,jpeg,png', 'max:1024'],
            'visi' => ['required', 'string'],
            'misi' => ['required', 'string'],
        ];
    }
}
