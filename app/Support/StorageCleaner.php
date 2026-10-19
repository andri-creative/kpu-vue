<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

final class StorageCleaner
{
    /**
     * Daftar kategori penyimpanan yang boleh dibersihkan.
     *
     * @var array<string, array{label: string, description: string, paths: list<string>, recursive: bool, pattern: ?string}>
     */
    private const CATEGORIES = [
        'logs' => [
            'label' => 'Log Aplikasi',
            'description' => 'File log Laravel (laravel.log) dan browser (browser.log).',
            'paths' => ['logs'],
            'recursive' => false,
            'pattern' => '*.log',
        ],
        'pail' => [
            'label' => 'Cache Pail',
            'description' => 'Cache Laravel Pail yang menumpuk saat menjalankan perintah pail.',
            'paths' => ['pail'],
            'recursive' => false,
            'pattern' => '*.pail',
        ],
        'debugbar' => [
            'label' => 'Debugbar',
            'description' => 'Snapshot debugbar tiap request yang menumpuk saat pengembangan.',
            'paths' => ['debugbar'],
            'recursive' => false,
            'pattern' => '*.json',
        ],
        'inertia-devtools' => [
            'label' => 'Inertia Devtools',
            'description' => 'Rekaman request Inertia devtools.',
            'paths' => ['inertia-devtools'],
            'recursive' => false,
            'pattern' => '*.json',
        ],
        'framework' => [
            'label' => 'Cache Framework',
            'description' => 'Cache blade view dan cache data aplikasi (dibuat ulang otomatis).',
            'paths' => ['framework/views', 'framework/cache'],
            'recursive' => true,
            'pattern' => null,
        ],
    ];

    private string $base;

    public function __construct(?string $basePath = null)
    {
        $this->base = rtrim($basePath ?? storage_path(), DIRECTORY_SEPARATOR);
    }

    /**
     * Ringkasan tiap kategori beserta ukuran dan jumlah filenya.
     *
     * @return list<array{key: string, label: string, description: string, file_count: int, bytes: int, bytes_formatted: string}>
     */
    public function categories(): array
    {
        $categories = [];

        foreach (self::CATEGORIES as $key => $meta) {
            $files = $this->filesFor($key);
            $bytes = 0;

            foreach ($files as $file) {
                $bytes += (int) @filesize($file);
            }

            $categories[] = [
                'key' => $key,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'file_count' => count($files),
                'bytes' => $bytes,
                'bytes_formatted' => self::formatBytes($bytes),
            ];
        }

        return $categories;
    }

    /**
     * Bersihkan satu kategori.
     *
     * @return array{files: int, bytes: int}
     */
    public function clean(string $key): array
    {
        if (! array_key_exists($key, self::CATEGORIES)) {
            throw new InvalidArgumentException("Kategori [{$key}] tidak dikenal.");
        }

        $files = 0;
        $bytes = 0;

        foreach ($this->filesFor($key) as $file) {
            $size = (int) @filesize($file);

            if (@unlink($file)) {
                $files++;
                $bytes += $size;
            }
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    /**
     * Bersihkan seluruh kategori.
     *
     * @return array{files: int, bytes: int, categories: int}
     */
    public function cleanAll(): array
    {
        $files = 0;
        $bytes = 0;

        foreach (array_keys(self::CATEGORIES) as $key) {
            $result = $this->clean($key);
            $files += $result['files'];
            $bytes += $result['bytes'];
        }

        return ['files' => $files, 'bytes' => $bytes, 'categories' => count(self::CATEGORIES)];
    }

    /**
     * @return list<string>
     */
    private function filesFor(string $key): array
    {
        $meta = self::CATEGORIES[$key];
        $files = [];

        foreach ($meta['paths'] as $relative) {
            $directory = $this->base.DIRECTORY_SEPARATOR.$relative;

            if (! is_dir($directory)) {
                continue;
            }

            if ($meta['recursive']) {
                foreach (File::allFiles($directory) as $file) {
                    if (! str_starts_with($file->getFilename(), '.')) {
                        $files[] = $file->getPathname();
                    }
                }

                continue;
            }

            $pattern = $directory.DIRECTORY_SEPARATOR.($meta['pattern'] ?? '*');

            foreach (glob($pattern) ?: [] as $file) {
                if (is_file($file) && ! str_starts_with(basename($file), '.')) {
                    $files[] = $file;
                }
            }
        }

        return $files;
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
