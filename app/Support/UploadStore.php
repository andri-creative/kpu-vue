<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class UploadStore
{
    private string $base;

    public function __construct(?string $basePath = null)
    {
        $this->base = rtrim($basePath ?? public_path('uploads'), DIRECTORY_SEPARATOR);
    }

    /**
     * Simpan file upload ke subfolder public/uploads dan kembalikan nama filenya.
     */
    public function store(UploadedFile $file, string $folder): string
    {
        $directory = $this->base.DIRECTORY_SEPARATOR.$folder;

        File::ensureDirectoryExists($directory);

        $extension = $file->getClientOriginalExtension();
        $slug = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $suffix = $extension !== '' ? '.'.$extension : '';

        $filename = $this->uniqueName($directory, $slug, $suffix);

        $file->move($directory, $filename);

        return $filename;
    }

    private function uniqueName(string $directory, string $slug, string $suffix): string
    {
        $timestamp = now()->format('Ymd-His');
        $filename = $timestamp.'-'.$slug.$suffix;
        $counter = 1;

        while (File::exists($directory.DIRECTORY_SEPARATOR.$filename)) {
            $filename = $timestamp.'-'.$slug.'-'.$counter.$suffix;
            $counter++;
        }

        return $filename;
    }
}
