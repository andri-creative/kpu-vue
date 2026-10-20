<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PrintStudentCardsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1', 'max:500'],
            'student_ids.*' => ['required', 'string', 'exists:users,id'],
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
            'student_ids.required' => 'Silakan pilih minimal 1 siswa.',
            'student_ids.min' => 'Silakan pilih minimal 1 siswa.',
            'student_ids.max' => 'Maksimal 500 siswa per cetak.',
            'student_ids.*.exists' => 'Ada siswa yang tidak ditemukan. Muat ulang halaman dan coba lagi.',
        ];
    }
}
