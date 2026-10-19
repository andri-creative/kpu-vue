<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingRequest;
use App\Support\Branding;
use App\Support\UploadStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    /**
     * Peta field upload ke kunci pengaturan yang disimpan.
     *
     * @var array<string, string>
     */
    private const IMAGE_FIELDS = [
        'logo' => 'logo_url',
        'secondary_logo' => 'secondary_logo_url',
        'favicon' => 'favicon_url',
        'background_image' => 'background_image_url',
    ];

    public function __construct(
        private readonly Branding $branding,
        private readonly UploadStore $uploads,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('settings/Branding', [
            'branding' => [
                'name' => config('app.name'),
                'tagline' => $this->branding->get('tagline'),
                'logo_url' => $this->branding->logoUrl(),
                'logo_source' => $this->branding->get('logo_url'),
                'secondary_logo_url' => $this->branding->secondaryLogoUrl(),
                'secondary_logo_source' => $this->branding->get('secondary_logo_url'),
                'favicon_url' => $this->branding->faviconUrl(),
                'favicon_source' => $this->branding->get('favicon_url'),
                'brand_color' => $this->branding->brandColor(),
                'brand_color_alt' => $this->branding->brandColorAlt(),
                'background_image_url' => $this->branding->backgroundImageUrl(),
                'background_image_source' => $this->branding->get('background_image_url'),
                'background_image_width' => $this->branding->backgroundImageWidth() ?? Branding::DEFAULT_BACKGROUND_IMAGE_WIDTH,
                'background_image_height' => $this->branding->backgroundImageHeight() ?? Branding::DEFAULT_BACKGROUND_IMAGE_HEIGHT,
                'login_card_color' => $this->branding->loginCardColor(),
                'voting_duration' => $this->branding->votingDuration(),
            ],
        ]);
    }

    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $values = $this->scalarValues($validated);

        foreach (self::IMAGE_FIELDS as $field => $key) {
            if ($request->hasFile($field)) {
                $this->deletePrevious($this->branding->get($key));
                $values[$key] = 'branding/'.$this->uploads->store($request->file($field), 'branding');

                continue;
            }

            if (array_key_exists($field.'_url', $validated)) {
                $url = $validated[$field.'_url'];
                $values[$key] = $url === null || $url === '' ? null : $url;
            }
        }

        $this->branding->update($values);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Branding berhasil diperbarui.']);

        return to_route('settings.branding.edit');
    }

    public function reset(): RedirectResponse
    {
        $this->deletePrevious($this->branding->get('logo_url'));
        $this->deletePrevious($this->branding->get('secondary_logo_url'));
        $this->deletePrevious($this->branding->get('favicon_url'));
        $this->deletePrevious($this->branding->get('background_image_url'));

        $this->branding->reset();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Branding dikembalikan ke default.']);

        return to_route('settings.branding.edit');
    }

    /**
     * Ambil nilai teks/warna/angka yang benar-benar dikirim pada request.
     * Field yang tidak dikirim tidak disentuh, sehingga menyimpan satu bagian
     * tidak menghapus pengaturan branding lainnya.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, string|null>
     */
    private function scalarValues(array $validated): array
    {
        $keys = [
            'app_name',
            'tagline',
            'brand_color',
            'brand_color_alt',
            'login_card_color',
            'voting_duration',
            'background_image_width',
            'background_image_height',
        ];

        $values = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            $value = $validated[$key];

            $values[$key] = $value === null || $value === '' ? null : (string) $value;
        }

        return $values;
    }

    private function deletePrevious(?string $stored): void
    {
        if ($stored !== null && str_starts_with($stored, 'branding/')) {
            File::delete(public_path('uploads/'.$stored));
        }
    }
}
