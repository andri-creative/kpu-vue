<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

final class FileScanner
{
    /**
     * Label & deskripsi khusus untuk subfolder yang dikenal di public/uploads.
     *
     * @var array<string, array{label: string, description: string}>
     */
    private const KNOWN_FOLDERS = [
        'candidates' => [
            'label' => 'Foto Kandidat',
            'description' => 'Foto kandidat yang diunggah melalui aplikasi.',
        ],
        'imports' => [
            'label' => 'Import Excel',
            'description' => 'File Excel yang diunggah saat import data siswa.',
        ],
        'files' => [
            'label' => 'File Umum',
            'description' => 'Berkas umum di dalam folder public/uploads/files.',
        ],
    ];

    private const ROOT_KEY = 'root';

    private string $base;

    public function __construct(?string $uploadsPath = null)
    {
        $this->base = rtrim($uploadsPath ?? public_path('uploads'), DIRECTORY_SEPARATOR);
    }

    /**
     * Ringkasan tiap subfolder public/uploads (jumlah file & ukuran).
     *
     * @return list<array{key: string, label: string, description: string, file_count: int, bytes: int, bytes_formatted: string}>
     */
    public function collections(): array
    {
        $grouped = [];

        foreach ($this->files() as $file) {
            $key = $file['collection'];
            $grouped[$key] ??= ['count' => 0, 'bytes' => 0];
            $grouped[$key]['count']++;
            $grouped[$key]['bytes'] += $file['size'];
        }

        $collections = [];

        foreach ($grouped as $key => $data) {
            $collections[] = [
                'key' => $key,
                'label' => $this->label($key),
                'description' => $this->description($key),
                'file_count' => $data['count'],
                'bytes' => $data['bytes'],
                'bytes_formatted' => self::formatBytes($data['bytes']),
            ];
        }

        usort($collections, static fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $collections;
    }

    /**
     * Seluruh file dari public/uploads (rekursif), opsional difilter per subfolder.
     *
     * @return list<array{name: string, path: string, url: string, collection: string, collection_label: string, extension: string, type: string, size: int, size_formatted: string, modified_at: string, modified_timestamp: int}>
     */
    public function files(?string $collection = null): array
    {
        if (! is_dir($this->base)) {
            return [];
        }

        $splFiles = File::allFiles($this->base);
        $files = [];

        foreach ($splFiles as $splFile) {
            if (! $splFile instanceof SplFileInfo || str_starts_with($splFile->getFilename(), '.')) {
                continue;
            }

            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $splFile->getRelativePathname());
            $key = $this->collectionFor($relative);

            if ($collection !== null && $collection !== '' && $collection !== $key) {
                continue;
            }

            $extension = strtolower($splFile->getExtension());
            $size = $splFile->getSize() ?: 0;

            $files[] = [
                'name' => $splFile->getFilename(),
                'path' => $splFile->getPathname(),
                'url' => asset('uploads/'.$relative),
                'collection' => $key,
                'collection_label' => $this->label($key),
                'extension' => $extension,
                'type' => self::typeFor($extension),
                'size' => $size,
                'size_formatted' => self::formatBytes($size),
                'modified_at' => date('d M Y, H:i', $splFile->getMTime()),
                'modified_timestamp' => $splFile->getMTime(),
            ];
        }

        usort($files, static fn (array $a, array $b): int => $b['modified_timestamp'] <=> $a['modified_timestamp']);

        return $files;
    }

    public function delete(string $collection, string $path): bool
    {
        $base = realpath($this->base);
        $target = realpath($path);

        if ($base === false || $target === false) {
            return false;
        }

        $base = rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (! str_starts_with($target, $base) || ! is_file($target)) {
            return false;
        }

        if ($this->collectionFor(str_replace(DIRECTORY_SEPARATOR, '/', substr($target, strlen($base)))) !== $collection) {
            return false;
        }

        return @unlink($target);
    }

    public static function typeFor(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'ico', 'avif' => 'image',
            'pdf' => 'pdf',
            'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'md', 'ppt', 'pptx', 'rtf', 'odt' => 'document',
            'zip', 'rar', '7z', 'tar', 'gz' => 'archive',
            default => 'other',
        };
    }

    private function collectionFor(string $relativePath): string
    {
        $segments = explode('/', $relativePath);

        return count($segments) > 1 ? $segments[0] : self::ROOT_KEY;
    }

    private function label(string $key): string
    {
        return self::KNOWN_FOLDERS[$key]['label']
            ?? ($key === self::ROOT_KEY ? 'Lainnya' : Str::headline($key));
    }

    private function description(string $key): string
    {
        if (isset(self::KNOWN_FOLDERS[$key]['description'])) {
            return self::KNOWN_FOLDERS[$key]['description'];
        }

        return $key === self::ROOT_KEY
            ? 'Berkas yang berada langsung di dalam public/uploads.'
            : 'Berkas di dalam folder public/uploads/'.$key.'.';
    }

    private static function formatBytes(int|float $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) min(floor(log($bytes, 1024)), count($units) - 1);

        return sprintf('%.2f %s', $bytes / (1024 ** $i), $units[$i]);
    }
}
