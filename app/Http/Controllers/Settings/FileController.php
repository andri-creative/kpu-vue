<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DeleteFileRequest;
use App\Support\FileScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FileController extends Controller
{
    public function __construct(private readonly FileScanner $scanner) {}

    /**
     * Tampilkan seluruh berkas di public/uploads (gambar, PDF, dokumen, dsb).
     */
    public function index(Request $request): Response
    {
        $collection = $request->string('collection')->toString();
        $search = trim($request->string('search')->toString());
        $type = $request->string('type')->toString();

        $files = $this->scanner->files($collection !== '' ? $collection : null);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $files = array_values(array_filter(
                $files,
                fn (array $file): bool => str_contains(mb_strtolower($file['name']), $needle)
            ));
        }

        if ($type !== '') {
            $files = array_values(array_filter(
                $files,
                fn (array $file): bool => $file['type'] === $type
            ));
        }

        return Inertia::render('settings/Files', [
            'files' => $files,
            'summary' => $this->summary(),
            'collections' => $this->scanner->collections(),
            'filters' => [
                'collection' => $collection,
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Hapus satu berkas dari storage.
     */
    public function destroy(DeleteFileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $deleted = $this->scanner->delete((string) $validated['collection'], (string) $validated['path']);

        Inertia::flash('toast', [
            'type' => $deleted ? 'success' : 'error',
            'message' => $deleted
                ? 'Berkas berhasil dihapus.'
                : 'Berkas tidak ditemukan atau tidak diizinkan dihapus.',
        ]);

        return back();
    }

    /**
     * Ringkasan total berkas dan ukurannya per tipe.
     *
     * @return array{total_files: int, total_bytes: int, total_size_formatted: string, images: int, pdf: int, documents: int, other: int}
     */
    private function summary(): array
    {
        $files = $this->scanner->files();
        $bytes = 0;
        $counts = ['image' => 0, 'pdf' => 0, 'document' => 0, 'other' => 0];

        foreach ($files as $file) {
            $bytes += $file['size'];
            $counts[$file['type']] = ($counts[$file['type']] ?? 0) + 1;
        }

        $other = ($counts['archive'] ?? 0) + $counts['other'];

        return [
            'total_files' => count($files),
            'total_bytes' => $bytes,
            'total_size_formatted' => $this->formatBytes($bytes),
            'images' => $counts['image'],
            'pdf' => $counts['pdf'],
            'documents' => $counts['document'],
            'other' => $other,
        ];
    }

    private function formatBytes(int|float $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) min(floor(log($bytes, 1024)), count($units) - 1);

        return sprintf('%.2f %s', $bytes / (1024 ** $i), $units[$i]);
    }
}
