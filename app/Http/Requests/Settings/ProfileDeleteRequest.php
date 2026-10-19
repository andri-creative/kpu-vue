<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileDeleteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kode' => ['required', 'digits:6'],
        ];
    }

    /**
     * The 6-digit kode is the only credential a user knows, so it replaces
     * the password confirmation normally required by Fortify.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->user()->verifyKode((string) $this->input('kode'))) {
                $validator->errors()->add('kode', 'Kode yang dimasukkan salah.');
            }
        });
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
