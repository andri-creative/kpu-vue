<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id;

        return [
            'nis' => ['required', 'string', Rule::unique('users', 'nis')->ignore($userId)],
            'role' => ['required', Rule::enum(RoleEnum::class)->only(RoleEnum::assignable())],
        ];
    }
}
