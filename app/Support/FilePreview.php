<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

final class FilePreview
{
    /** @var array<string, string> */
    private const STREAM_MIME = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'bmp' => 'image/bmp',
        'ico' => 'image/x-icon',
        'avif' => 'image/avif',
        'pdf' => 'application/pdf',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
        'mov' => 'video/quicktime',
        'm4v' => 'video/x-m4v',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
        'flac' => 'audio/flac',
    ];

    private const SPREADSHEET = ['csv', 'xls', 'xlsx', 'xlsm', 'ods'];

    private const TEXT = ['txt', 'md', 'markdown', 'json', 'log', 'xml', 'yml', 'yaml', 'ini', 'conf'];

    private const BINARY_TEXT = ['doc', 'rtf', 'odt', 'ppt', 'pptx'];

    private const MAX_ROWS = 200;

    private const MAX_COLUMNS = 40;

    private const MAX_TEXT_BYTES = 512_000;

    public static function isStreamable(string $extension): bool
    {
        return isset(self::STREAM_MIME[$extension]);
    }

    public static function streamMime(string $extension): string
    {
        return self::STREAM_MIME[$extension] ?? 'application/octet-stream';
    }

    public static function render(string $path, string $extension, string $downloadUrl): string
    {
        return match (true) {
            in_array($extension, self::SPREADSHEET, true) => self::renderSpreadsheet($path),
            $extension === 'docx' => self::renderDocx($path),
            in_array($extension, self::TEXT, true) => self::renderText($path),
            in_array($extension, self::BINARY_TEXT, true) => self::renderBinaryText($path),
            default => self::renderUnsupported($downloadUrl),
        };
    }

    private static function renderSpreadsheet(string $path): string
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Throwable) {
            return self::renderUnsupported('');
        }

        $rows = array_slice($rows, 0, self::MAX_ROWS);
        $html = '<table>';

        foreach ($rows as $index => $row) {
            $row = array_slice((array) $row, 0, self::MAX_COLUMNS);
            $tag = $index === 0 ? 'th' : 'td';
            $html .= '<tr>';

            foreach ($row as $cell) {
                $html .= "<{$tag}>".htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8')."</{$tag}>";
            }

            $html .= '</tr>';
        }

        $html .= '</table>';

        return self::page('Spreadsheet', $html);
    }

    private static function renderDocx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return self::renderUnsupported('');
        }

        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === '') {
            return self::renderUnsupported('');
        }

        $xml = str_replace(['</w:p>', '<w:br/>', '<w:tab/>'], ["\n", "\n", "\t"], $xml);
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return self::page('Dokumen', self::pre(trim($text)));
    }

    private static function renderText(string $path): string
    {
        $text = File::get($path);

        if (strlen($text) > self::MAX_TEXT_BYTES) {
            $text = substr($text, 0, self::MAX_TEXT_BYTES)."\n\n… (dipotong)";
        }

        return self::page('Teks', self::pre($text));
    }

    private static function renderBinaryText(string $path): string
    {
        $raw = substr(File::get($path), 0, self::MAX_TEXT_BYTES);

        if (str_starts_with($raw, '{\\rtf')) {
            $raw = preg_replace('/\\\\[a-z]+-?\d*\s?/i', '', $raw) ?? $raw;
            $raw = str_replace(['{', '}'], '', $raw);
        }

        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]+/', "\n", $raw) ?? '';
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return self::page('Dokumen', self::pre(trim($text)));
    }

    private static function renderUnsupported(string $downloadUrl): string
    {
        $action = $downloadUrl !== ''
            ? '<p><a href="'.htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener">Buka / Unduh berkas</a></p>'
            : '';

        return self::page('Pratinjau', '<p class="muted">Berkas ini tidak dapat dipratinjau di browser.</p>'.$action);
    }

    private static function pre(string $text): string
    {
        return '<pre>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</pre>';
    }

    private static function page(string $title, string $body): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$title}</title>
            <style>
                :root { color-scheme: light dark; }
                body { margin: 0; padding: 16px; font: 13px/1.5 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #111827; background: #ffffff; }
                table { border-collapse: collapse; width: max-content; min-width: 100%; }
                th, td { border: 1px solid #e5e7eb; padding: 4px 8px; text-align: left; vertical-align: top; white-space: pre-wrap; }
                th { background: #f3f4f6; font-weight: 600; position: sticky; top: 0; }
                pre { margin: 0; font: 12px/1.6 ui-monospace, SFMono-Regular, Menlo, monospace; white-space: pre-wrap; word-break: break-word; }
                .muted { color: #6b7280; }
                a { color: #2563eb; }
                @media (prefers-color-scheme: dark) {
                    body { color: #e5e7eb; background: #0b0b0c; }
                    th, td { border-color: #27272a; }
                    th { background: #18181b; }
                    .muted { color: #9ca3af; }
                    a { color: #60a5fa; }
                }
            </style>
        </head>
        <body>{$body}</body>
        </html>
        HTML;
    }
}
