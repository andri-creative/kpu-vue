<?php

namespace App\Http\Requests\Settings;

use App\Enums\RoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
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
        $adminId = $this->route('admin')?->id;

        return [
            'nis' => ['required', 'string', Rule::unique('users', 'nis')->ignore($adminId)],
            'nama_lengkap' => ['required', 'string', 'max:255'],
        ];
    }
}
