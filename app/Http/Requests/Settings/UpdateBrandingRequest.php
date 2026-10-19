<?php

namespace App\Http\Requests\Settings;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === RoleEnum::Admin;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'secondary_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'secondary_logo_url' => ['nullable', 'string', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,ico,webp', 'max:1024'],
            'favicon_url' => ['nullable', 'string', 'max:2048'],
            'brand_color' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'brand_color_alt' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'login_card_color' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'background_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:4096'],
            'background_image_url' => ['nullable', 'string', 'max:2048'],
            'background_image_width' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'background_image_height' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'voting_duration' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'app_name.required' => 'Nama aplikasi wajib diisi.',
            'app_name.max' => 'Nama aplikasi maksimal 60 karakter.',
            'tagline.max' => 'Tagline maksimal 160 karakter.',
            'logo.mimes' => 'Logo harus berformat PNG, JPG, SVG, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'secondary_logo.mimes' => 'Logo kedua harus berformat PNG, JPG, SVG, atau WebP.',
            'secondary_logo.max' => 'Ukuran logo kedua maksimal 2 MB.',
            'favicon.mimes' => 'Favicon harus berformat PNG, JPG, SVG, ICO, atau WebP.',
            'favicon.max' => 'Ukuran favicon maksimal 1 MB.',
            'brand_color.regex' => 'Warna utama harus berupa kode heksadesimal (mis. #4f46e5).',
            'brand_color_alt.regex' => 'Warna gradasi harus berupa kode heksadesimal (mis. #9333ea).',
            'login_card_color.regex' => 'Warna card harus berupa kode heksadesimal (mis. #ffffff).',
            'background_image.mimes' => 'Gambar latar harus berformat PNG, JPG, SVG, atau WebP.',
            'background_image.max' => 'Ukuran gambar latar maksimal 4 MB.',
            'background_image_width.integer' => 'Lebar gambar harus berupa angka.',
            'background_image_width.min' => 'Lebar gambar minimal 1 piksel.',
            'background_image_width.max' => 'Lebar gambar maksimal 10000 piksel.',
            'background_image_height.integer' => 'Tinggi gambar harus berupa angka.',
            'background_image_height.min' => 'Tinggi gambar minimal 1 piksel.',
            'background_image_height.max' => 'Tinggi gambar maksimal 10000 piksel.',
            'voting_duration.integer' => 'Durasi timer harus berupa angka (menit).',
            'voting_duration.min' => 'Durasi timer minimal 1 menit.',
            'voting_duration.max' => 'Durasi timer maksimal 120 menit.',
        ];
    }
}
