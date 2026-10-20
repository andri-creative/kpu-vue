<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === RoleEnum::Kandidat
            && $this->user()->candidate !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visi' => ['required', 'string', 'max:5000'],
            'misi' => ['required', 'string', 'max:5000'],
        ];
    }
}
