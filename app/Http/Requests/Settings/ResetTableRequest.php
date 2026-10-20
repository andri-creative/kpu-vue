<?php

namespace App\Http\Requests\Settings;

use App\Enums\RoleEnum;
use App\Support\DatabaseResetter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetTableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === RoleEnum::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'table' => ['required', 'string', Rule::in(DatabaseResetter::resettableTables())],
            'confirmation' => ['required', 'in:RESET'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'table.in' => 'Nama tabel tidak valid.',
            'confirmation.required' => 'Konfirmasi wajib diisi.',
            'confirmation.in' => 'Harap ketik RESET untuk mengonfirmasi.',
        ];
    }
}
