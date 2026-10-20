<?php

namespace App\Http\Requests\Settings;

use App\Enums\RoleEnum;
use App\Support\FileScanner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteFileRequest extends FormRequest
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
            'collection' => ['required', 'string', Rule::in(array_column(app(FileScanner::class)->collections(), 'key'))],
            'path' => ['required', 'string'],
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
            'collection.in' => 'Koleksi berkas tidak valid.',
            'path.required' => 'Path berkas wajib diisi.',
        ];
    }
}
