<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\LogTailer;
use App\Support\StorageCleaner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function __construct(
        private readonly LogTailer $tailer,
        private readonly StorageCleaner $storage,
    ) {}

    /**
     * Tampilkan halaman terminal log untuk administrator.
     */
    public function index(Request $request): Response
    {
        /** @var array<string, string> $configured */
        $configured = (array) config('log_viewer.sources', []);

        $sources = collect($configured)
            ->map(function (string $path, string $key): array {
                $size = $this->tailer->fileSize($path);

                return [
                    'key' => $key,
                    'label' => $this->label($key),
                    'path' => $path,
                    'exists' => is_file($path),
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'resettable' => true,
                ];
            })
            ->values()
            ->all();

        $activeSource = $request->string('source')->toString();

        if (! in_array($activeSource, array_column($sources, 'key'), true)) {
            $activeSource = array_key_first($configured) ?? 'laravel';
        }

        return Inertia::render('settings/Logs', [
            'sources' => $sources,
            'activeSource' => $activeSource,
            'tailLines' => (int) config('log_viewer.tail_lines', 300),
            'refreshIntervalMs' => (int) config('log_viewer.refresh_interval_ms', 1500),
            'storage' => $this->storage->categories(),
        ]);
    }

    /**
     * Kembalikan baris log baru sejak cursor tertentu (polling request pendek).
     */
    public function tail(Request $request): JsonResponse
    {
        $source = $request->string('source')->toString();
        /** @var array<string, string> $sources */
        $sources = (array) config('log_viewer.sources', []);

        abort_unless(array_key_exists($source, $sources), 404);

        $path = (string) $sources[$source];
        $tailLines = (int) config('log_viewer.tail_lines', 300);
        $cursor = (int) $request->integer('cursor', -1);

        $exists = is_file($path);
        $size = $this->tailer->fileSize($path);

        if (! $exists) {
            return response()->json([
                'exists' => false,
                'reset' => false,
                'lines' => [],
                'cursor' => 0,
                'size' => 0,
            ]);
        }

        // Cursor tidak valid (awal muat, file di-reset, atau dirotasi):
        // kirim ulang backlog dan mulai dari posisi akhir file.
        if ($cursor < 0 || $cursor > $size) {
            return response()->json([
                'exists' => true,
                'reset' => true,
                'lines' => $this->tailer->lastLines($path, $tailLines),
                'cursor' => $size,
                'size' => $size,
            ]);
        }

        [$lines, $newCursor] = $this->tailer->readFrom($path, $cursor);

        if ($newCursor === 0 && $cursor !== 0) {
            return response()->json([
                'exists' => true,
                'reset' => true,
                'lines' => $this->tailer->lastLines($path, $tailLines),
                'cursor' => $size,
                'size' => $size,
            ]);
        }

        return response()->json([
            'exists' => true,
            'reset' => false,
            'lines' => $lines,
            'cursor' => $newCursor,
            'size' => $size,
        ]);
    }

    /**
     * Kosongkan file log yang dipilih.
     */
    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', Rule::in(array_keys((array) config('log_viewer.sources', [])))],
        ]);

        $source = (string) $validated['source'];
        $path = (string) config("log_viewer.sources.{$source}");

        File::put($path, '');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Log '.$this->label($source).' berhasil dikosongkan.',
        ]);

        return back();
    }

    /**
     * Bersihkan satu kategori penyimpanan (log, cache, dsb).
     */
    public function cleanStorage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(array_column($this->storage->categories(), 'key'))],
        ]);

        $result = $this->storage->clean((string) $validated['category']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['files'].' file ('.$this->formatBytes($result['bytes']).') berhasil dibersihkan.',
        ]);

        return back();
    }

    /**
     * Bersihkan seluruh kategori penyimpanan sekaligus.
     */
    public function cleanAllStorage(): RedirectResponse
    {
        $result = $this->storage->cleanAll();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['categories'].' kategori dibersihkan: '.$result['files'].' file ('.$this->formatBytes($result['bytes']).') dihapus.',
        ]);

        return back();
    }

    /**
     * Label yang mudah dibaca untuk setiap sumber log.
     */
    private function label(string $key): string
    {
        return match ($key) {
            'laravel' => 'Laravel',
            'browser' => 'Browser',
            default => ucfirst($key),
        };
    }

    /**
     * Format angka bytes menjadi string yang mudah dibaca (B, KB, MB, GB).
     */
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
