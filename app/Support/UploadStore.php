<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

final class UploadStore
{
    /**
     * Sisi panjang maksimum hasil konversi (meniru compressImage.ts).
     */
    private const int MAX_DIMENSION = 1280;

    /**
     * Target ukuran file dalam byte (meniru compressImage.ts).
     */
    private const int TARGET_BYTES = 307200;

    private const float QUALITY_START = 0.9;

    private const float QUALITY_MIN = 0.4;

    private const float QUALITY_STEP = 0.05;

    private string $base;

    public function __construct(?string $basePath = null)
    {
        $this->base = rtrim($basePath ?? (string) config('filesystems.uploads_path', public_path('uploads')), DIRECTORY_SEPARATOR);
    }

    /**
     * Simpan file upload ke subfolder uploads dan kembalikan nama filenya.
     *
     * Gambar raster (JPG/PNG/WebP) dikonversi ke WebP dan dikompres agar
     * ukurannya kecil; format lain (SVG, ICO, XLSX, dll.) disimpan apa adanya.
     */
    public function store(UploadedFile $file, string $folder): string
    {
        $directory = $this->base.DIRECTORY_SEPARATOR.$folder;

        File::ensureDirectoryExists($directory);

        $converted = $this->isConvertible($file) ? $this->convertToWebp((string) $file->getRealPath()) : null;

        if ($converted !== null) {
            $filename = $this->uniqueName($directory, '.webp');

            File::move($converted, $directory.DIRECTORY_SEPARATOR.$filename);

            return $filename;
        }

        $extension = $file->getClientOriginalExtension();
        $suffix = $extension !== '' ? '.'.$extension : '';

        $filename = $this->uniqueName($directory, $suffix);

        $file->move($directory, $filename);

        return $filename;
    }

    /**
     * Hanya gambar raster yang didukung GD yang boleh dikonversi ke WebP.
     */
    private function isConvertible(UploadedFile $file): bool
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return false;
        }

        return in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    /**
     * Ubah gambar menjadi WebP maksimal 1280px dengan target ≤300 KB.
     *
     * Kembalikan path file hasil konversi, atau null bila gagal (file asli
     * dipakai apa adanya agar upload tidak pernah gagal karena konversi).
     */
    private function convertToWebp(string $source): ?string
    {
        $binary = @file_get_contents($source);

        if ($binary === false) {
            return null;
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return null;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $image = $this->resize($image);

        $target = $source.'.webp.tmp';
        $quality = self::QUALITY_START;

        while ($quality >= self::QUALITY_MIN) {
            if (! imagewebp($image, $target, (int) round($quality * 100))) {
                break;
            }

            if ((int) filesize($target) <= self::TARGET_BYTES) {
                break;
            }

            $quality -= self::QUALITY_STEP;
        }

        // Masih kebesaran: persempit dimensi seperti fallback di compressImage.ts.
        if (File::exists($target) && (int) filesize($target) > self::TARGET_BYTES) {
            $image = $this->resize($image, 0.7);
            imagewebp($image, $target, 60);
        }

        imagedestroy($image);

        if (! File::exists($target) || (int) filesize($target) === 0) {
            File::delete($target);

            return null;
        }

        return $target;
    }

    /**
     * Perkecil gambar menjaga rasio aspek, maksimal MAX_DIMENSION piksel.
     */
    private function resize(\GdImage $image, float $scale = 1.0): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $maxWidth = (int) round(self::MAX_DIMENSION * $scale);
        $maxHeight = (int) round(self::MAX_DIMENSION * $scale);

        if ($width <= $maxWidth && $height <= $maxHeight) {
            return $image;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Bangun nama file unik hanya dari timestamp Ymd-His, tanpa nama asli.
     */
    private function uniqueName(string $directory, string $suffix): string
    {
        $timestamp = now()->format('Ymd-His');
        $filename = $timestamp.$suffix;
        $counter = 1;

        while (File::exists($directory.DIRECTORY_SEPARATOR.$filename)) {
            $filename = $timestamp.'-'.$counter.$suffix;
            $counter++;
        }

        return $filename;
    }
}
