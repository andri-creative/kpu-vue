<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderCandidatesRequest extends FormRequest
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
            'order' => ['required', 'array', 'min:1'],
            'order.*.id' => ['required', 'integer', 'exists:candidates,id'],
            'order.*.nomor_urut' => ['required', 'integer', 'min:1'],
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
            'order.required' => 'Daftar urutan kandidat wajib diisi.',
            'order.array' => 'Daftar urutan kandidat tidak valid.',
            'order.min' => 'Daftar urutan kandidat minimal 1 item.',
            'order.*.id.exists' => 'Kandidat yang dipilih tidak ditemukan.',
            'order.*.nomor_urut.min' => 'Nomor urut minimal 1.',
        ];
    }
}
