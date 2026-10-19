<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KodeUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'kode_lama' => ['required', 'digits:6'],
            'kode_baru' => [
                'required',
                'digits:6',
                'different:kode_lama',
                'confirmed',
                Rule::unique('users', 'kode')->ignore($this->user()?->id),
            ],
            'kode_baru_confirmation' => ['required', 'digits:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_lama.required' => 'Kode lama wajib diisi.',
            'kode_lama.digits' => 'Kode lama harus terdiri dari 6 angka.',
            'kode_baru.required' => 'Kode baru wajib diisi.',
            'kode_baru.digits' => 'Kode baru harus terdiri dari 6 angka.',
            'kode_baru.different' => 'Kode baru harus berbeda dari kode lama.',
            'kode_baru.confirmed' => 'Konfirmasi kode baru tidak cocok.',
            'kode_baru.unique' => 'Kode baru sudah dipakai akun lain.',
            'kode_baru_confirmation.required' => 'Konfirmasi kode baru wajib diisi.',
            'kode_baru_confirmation.digits' => 'Konfirmasi kode baru harus terdiri dari 6 angka.',
        ];
    }

    /**
     * Pastikan kode lama benar-benar milik user yang sedang login.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user && ! $user->verifyKode((string) $this->input('kode_lama'))) {
                $validator->errors()->add('kode_lama', 'Kode lama salah.');
            }
        });
    }
}
