<?php

namespace App\Support;

final class LogTailer
{
    /**
     * Baca sejumlah baris terakhir dari file log tanpa memuat seluruh isi file.
     *
     * @return list<string>
     */
    public function lastLines(string $path, int $limit): array
    {
        if ($limit <= 0 || ! is_file($path)) {
            return [];
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        $buffer = '';

        try {
            $stat = fstat($handle);
            $position = $stat !== false ? (int) $stat['size'] : 0;
            $chunkSize = 8192;

            while ($position > 0 && substr_count($buffer, "\n") <= $limit) {
                $read = (int) min($chunkSize, $position);
                $position -= $read;

                if (fseek($handle, $position) !== 0) {
                    break;
                }

                $buffer = (string) fread($handle, $read).$buffer;
            }
        } finally {
            fclose($handle);
        }

        $lines = explode("\n", rtrim($buffer, "\n"));

        if (count($lines) > $limit) {
            $lines = array_slice($lines, -$limit);
        }

        return array_map(fn (string $line): string => rtrim($line, "\r"), $lines);
    }

    public function fileSize(string $path): int
    {
        return is_file($path) ? (int) filesize($path) : 0;
    }

    /**
     * Baca baris lengkap baru sejak posisi (cursor) tertentu.
     *
     * Hanya baris yang sudah lengkap (diakhiri newline) yang dikembalikan.
     * Bila belum ada newline baru, cursor tidak digeser agar baris parsial
     * tidak terpotong.
     *
     * @return array{0: list<string>, 1: int} [baris, cursor baru]
     */
    public function readFrom(string $path, int $cursor): array
    {
        if (! is_file($path)) {
            return [[], 0];
        }

        clearstatcache(true, $path);
        $size = (int) filesize($path);

        if ($cursor < 0 || $cursor > $size) {
            // File di-truncate / dirotasi: minta pemanggil memuat ulang backlog.
            return [[], 0];
        }

        if ($size === $cursor) {
            return [[], $cursor];
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return [[], $cursor];
        }

        try {
            if (fseek($handle, $cursor) !== 0) {
                return [[], 0];
            }

            $data = (string) stream_get_contents($handle);
        } finally {
            fclose($handle);
        }

        $lastNewline = strrpos($data, "\n");

        if ($lastNewline === false) {
            // Belum ada baris lengkap baru.
            return [[], $cursor];
        }

        $complete = substr($data, 0, $lastNewline);
        $newCursor = $cursor + $lastNewline + 1;

        $lines = array_map(
            fn (string $line): string => rtrim($line, "\r"),
            explode("\n", $complete),
        );

        return [$lines, $newCursor];
    }
}
