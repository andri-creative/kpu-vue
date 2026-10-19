<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Documentation
{
    /**
     * Baca seluruh halaman dokumentasi dari file Markdown di resources/docs.
     *
     * @return array<string, array{slug: string, title: string, group: string, order: int, body: string}>
     */
    public static function pages(): array
    {
        $directory = resource_path('docs');

        if (! is_dir($directory)) {
            return [];
        }

        $files = glob($directory.'/*.md') ?: [];

        usort($files, static function (string $a, string $b): int {
            $frontA = self::frontMatter(self::read($a));
            $frontB = self::frontMatter(self::read($b));

            return [$frontA['order'], $frontA['title']] <=> [$frontB['order'], $frontB['title']];
        });

        $pages = [];

        foreach ($files as $file) {
            $contents = self::read($file);
            $front = self::frontMatter($contents);
            $slug = Str::slug(pathinfo($file, PATHINFO_FILENAME));

            if ($slug === '') {
                continue;
            }

            $pages[$slug] = [
                'slug' => $slug,
                'title' => $front['title'] ?? self::titleFromBody($front['body']),
                'group' => $front['group'] ?? 'Umum',
                'order' => $front['order'],
                'body' => $front['body'],
            ];
        }

        return $pages;
    }

    /**
     * Susun grup navigasi dari halaman yang tersedia.
     *
     * @return array<int, array{group: string, items: array<int, array{slug: string, title: string}>}>
     */
    public static function navigation(): array
    {
        $groups = [];

        foreach (self::pages() as $page) {
            $groups[$page['group']] ??= [
                'group' => $page['group'],
                'items' => [],
            ];

            $groups[$page['group']]['items'][] = [
                'slug' => $page['slug'],
                'title' => $page['title'],
            ];
        }

        return array_values($groups);
    }

    private static function read(string $file): string
    {
        $contents = file_get_contents($file);

        return $contents === false ? '' : $contents;
    }

    /**
     * Pisahkan front matter YAML sederhana dari isi markdown.
     *
     * @return array{title: ?string, group: ?string, order: int, body: string}
     */
    private static function frontMatter(string $contents): array
    {
        $defaults = [
            'title' => null,
            'group' => null,
            'order' => PHP_INT_MAX,
            'body' => $contents,
        ];

        if (! str_starts_with($contents, '---')) {
            return $defaults;
        }

        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

        $closing = null;

        foreach ($lines as $index => $line) {
            if ($index === 0) {
                continue;
            }

            if (trim($line) === '---') {
                $closing = $index;
                break;
            }
        }

        if ($closing === null) {
            return $defaults;
        }

        $body = implode("\n", array_slice($lines, $closing + 1));

        $meta = [
            'title' => null,
            'group' => null,
            'order' => PHP_INT_MAX,
        ];

        foreach (array_slice($lines, 1, $closing - 1) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);

            $key = trim($key);
            $value = trim($value);

            if ($key === 'title') {
                $meta['title'] = $value;
            } elseif ($key === 'group') {
                $meta['group'] = $value;
            } elseif ($key === 'order') {
                $meta['order'] = is_numeric($value) ? (int) $value : PHP_INT_MAX;
            }
        }

        $meta['body'] = $body;

        return $meta;
    }

    private static function titleFromBody(string $body): string
    {
        foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
            if (preg_match('/^#\s+(.+)$/', trim($line), $matches) === 1) {
                return trim($matches[1]);
            }
        }

        return 'Dokumentasi';
    }
}
