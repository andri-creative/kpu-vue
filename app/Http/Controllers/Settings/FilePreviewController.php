<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\FilePreview;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilePreviewController extends Controller
{
    public function __construct(private readonly ?string $basePath = null) {}

    /**
     * Sajikan pratinjau satu berkas dari public/uploads (inline, tanpa unduh).
     */
    public function show(Request $request): Response
    {
        $path = (string) $request->query('path', '');

        $base = realpath($this->basePath ?? public_path('uploads'));
        $target = realpath($path);

        abort_if($base === false || $target === false, 404);

        $base = rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        abort_unless(str_starts_with($target, $base) && is_file($target), 404);

        $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));

        if (FilePreview::isStreamable($extension)) {
            return response()->file($target, [
                'Content-Type' => FilePreview::streamMime($extension),
                'Content-Disposition' => 'inline; filename="'.basename($target).'"',
            ]);
        }

        $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($target, strlen($base)));
        $downloadUrl = asset('uploads/'.$relative);

        return response(FilePreview::render($target, $extension, $downloadUrl))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
