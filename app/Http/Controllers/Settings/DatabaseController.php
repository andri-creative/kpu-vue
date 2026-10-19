<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\VoteLog;
use App\Support\DatabaseResetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DatabaseController extends Controller
{
    public function __construct(private readonly DatabaseResetter $resetter) {}

    /**
     * Tampilkan statistik ukuran database, rincian tabel, dan log aktivitas.
     */
    public function index(): Response
    {
        $dbDriver = (string) config('database.default');
        /** @var array<string, mixed> $dbConnection */
        $dbConnection = config("database.connections.{$dbDriver}", []);
        $dbName = (string) ($dbConnection['database'] ?? 'kpu');

        $fileSizeBytes = 0;
        $pageSize = 4096;
        $pageCount = 0;
        $freelistCount = 0;
        $displayPath = $dbName;
        $fullPath = $dbName;

        if ($dbDriver === 'sqlite') {
            $displayPath = basename($dbName);
            $fullPath = $dbName;
            $fileSizeBytes = file_exists($dbName) ? (int) filesize($dbName) : 0;

            try {
                $pageSizeRow = DB::select('PRAGMA page_size');
                $pageCountRow = DB::select('PRAGMA page_count');
                $freelistRow = DB::select('PRAGMA freelist_count');

                $pageSize = (int) ($pageSizeRow[0]->page_size ?? 4096);
                $pageCount = (int) ($pageCountRow[0]->page_count ?? 0);
                $freelistCount = (int) ($freelistRow[0]->freelist_count ?? 0);
            } catch (Throwable) {
                // Fallback default jika PRAGMA tidak tersedia
            }
        } elseif (in_array($dbDriver, ['mysql', 'mariadb'], true)) {
            $host = (string) ($dbConnection['host'] ?? '127.0.0.1');
            $port = (string) ($dbConnection['port'] ?? '3306');
            $displayPath = "{$host}:{$port}/{$dbName}";
            $fullPath = $displayPath;

            try {
                $sizeRow = DB::select('
                    SELECT SUM(data_length + index_length) AS size_bytes
                    FROM information_schema.TABLES
                    WHERE table_schema = ?
                ', [$dbName]);
                $fileSizeBytes = (int) ($sizeRow[0]->size_bytes ?? 0);
            } catch (Throwable) {
                $fileSizeBytes = 0;
            }
        } elseif ($dbDriver === 'pgsql') {
            $host = (string) ($dbConnection['host'] ?? '127.0.0.1');
            $port = (string) ($dbConnection['port'] ?? '5432');
            $displayPath = "{$host}:{$port}/{$dbName}";
            $fullPath = $displayPath;

            try {
                $sizeRow = DB::select('SELECT pg_database_size(current_database()) AS size_bytes');
                $fileSizeBytes = (int) ($sizeRow[0]->size_bytes ?? 0);
            } catch (Throwable) {
                $fileSizeBytes = 0;
            }
        }

        // Definisi tabel dan metadata informatifnya
        $tableDefinitions = [
            'vote_logs' => [
                'label' => 'Log Aktivitas Voting',
                'category' => 'Log & Aktivitas',
                'description' => 'Catatan riwayat setiap aksi voting pemilih lengkap dengan IP address & timestamp.',
            ],
            'votes' => [
                'label' => 'Suara Pemilihan',
                'category' => 'Voting',
                'description' => 'Data surat suara sah yang masuk dari pemilih untuk kandidat.',
            ],
            'users' => [
                'label' => 'Akun Pengguna',
                'category' => 'Pengguna',
                'description' => 'Data akun pemilih, administrator, dan kandidat.',
            ],
            'profiles' => [
                'label' => 'Profil Pengguna',
                'category' => 'Pengguna',
                'description' => 'Identitas nama lengkap dan kelas yang terhubung ke akun user.',
            ],
            'candidates' => [
                'label' => 'Data Kandidat',
                'category' => 'Voting',
                'description' => 'Data pasangan calon kandidat, nomor urut, foto, visi, dan misi.',
            ],
            'vote_logs_archived' => [
                'label' => 'Arsip Log Aktivitas',
                'category' => 'Arsip',
                'description' => 'Riwayat log voting tahun-tahun sebelumnya yang telah diarsipkan.',
            ],
            'votes_archived' => [
                'label' => 'Arsip Suara Pemilihan',
                'category' => 'Arsip',
                'description' => 'Data perolehan suara pemilihan tahun-tahun sebelumnya.',
            ],
            'users_archived' => [
                'label' => 'Arsip Pengguna',
                'category' => 'Arsip',
                'description' => 'Riwayat data pemilih tahun-tahun sebelumnya yang telah diarsipkan.',
            ],
            'sessions' => [
                'label' => 'Sesi Autentikasi',
                'category' => 'Sistem',
                'description' => 'Penyimpanan sesi login aktif pengguna di database.',
            ],
            'migrations' => [
                'label' => 'Riwayat Migrasi',
                'category' => 'Sistem',
                'description' => 'Riwayat eksekusi skema database Laravel.',
            ],
        ];

        // Hitung row count per tabel
        $tableRows = [];
        $totalRows = 0;

        foreach ($tableDefinitions as $tableName => $meta) {
            $count = 0;
            if (Schema::hasTable($tableName)) {
                $count = (int) DB::table($tableName)->count();
            }

            $tableRows[$tableName] = $count;
            $totalRows += $count;
        }

        // Hitung estimasi ukuran per tabel
        $tables = [];
        foreach ($tableDefinitions as $tableName => $meta) {
            $count = $tableRows[$tableName] ?? 0;
            $percentage = $totalRows > 0 ? round(($count / $totalRows) * 100, 1) : 0.0;
            $estimatedBytes = $totalRows > 0 ? (int) round(($count / $totalRows) * $fileSizeBytes) : 0;

            $tables[] = [
                'name' => $tableName,
                'label' => $meta['label'],
                'category' => $meta['category'],
                'description' => $meta['description'],
                'row_count' => $count,
                'size_bytes' => $estimatedBytes,
                'size_formatted' => $this->formatBytes($estimatedBytes),
                'percentage' => $percentage,
                'resettable' => DatabaseResetter::isResettable($tableName),
            ];
        }

        // Ambil 10 log aktivitas voting terbaru
        $recentLogs = VoteLog::query()
            ->with([
                'user.profile:id,user_id,nama_lengkap,kelas',
                'candidate:id,nomor_urut,nama_lengkap',
            ])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (VoteLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'ip_address' => $log->ip_address ?? '-',
                'created_at' => $log->created_at?->format('d M Y, H:i:s') ?? '-',
                'waktu_relatif' => $log->created_at?->diffForHumans() ?? '-',
                'pemilih_nama' => $log->user?->profile?->nama_lengkap ?? $log->user?->nis ?? 'Pemilih Tidak Ditemukan',
                'pemilih_nis' => $log->user?->nis ?? '-',
                'pemilih_kelas' => $log->user?->profile?->kelas ?? '-',
                'kandidat_nomor' => $log->candidate?->nomor_urut,
                'kandidat_nama' => $log->candidate?->nama_lengkap ?? '-',
            ]);

        // Ringkasan metrik database
        $summary = [
            'file_size_bytes' => $fileSizeBytes,
            'file_size_formatted' => $this->formatBytes($fileSizeBytes),
            'database_path' => $displayPath,
            'full_path' => $fullPath,
            'driver' => strtoupper($dbDriver),
            'page_size' => $pageSize,
            'page_count' => $pageCount,
            'freelist_count' => $freelistCount,
            'free_size_formatted' => $this->formatBytes($freelistCount * $pageSize),
            'total_rows' => $totalRows,
            'total_logs' => $tableRows['vote_logs'] ?? 0,
            'total_votes' => $tableRows['votes'] ?? 0,
            'total_users' => $tableRows['users'] ?? 0,
            'total_archives' => ($tableRows['votes_archived'] ?? 0) + ($tableRows['vote_logs_archived'] ?? 0) + ($tableRows['users_archived'] ?? 0),
        ];

        return Inertia::render('settings/Database', [
            'summary' => $summary,
            'tables' => $tables,
            'recentLogs' => $recentLogs,
            'lastUpdated' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Jalankan optimasi database (VACUUM untuk SQLite/PostgreSQL, OPTIMIZE untuk MySQL).
     */
    public function vacuum(): RedirectResponse
    {
        try {
            $dbDriver = (string) config('database.default');

            if ($dbDriver === 'sqlite') {
                DB::statement('VACUUM');
                $message = 'Database SQLite berhasil dioptimalkan (VACUUM) dan ruang kosong telah dipadatkan.';
            } elseif ($dbDriver === 'pgsql') {
                DB::statement('VACUUM ANALYZE');
                $message = 'Database PostgreSQL berhasil dioptimalkan (VACUUM ANALYZE).';
            } elseif (in_array($dbDriver, ['mysql', 'mariadb'], true)) {
                $tables = ['vote_logs', 'votes', 'users', 'profiles', 'candidates', 'sessions'];
                foreach ($tables as $table) {
                    if (Schema::hasTable($table)) {
                        DB::statement("OPTIMIZE TABLE `{$table}`");
                    }
                }
                $message = 'Tabel database MySQL/MariaDB berhasil dioptimalkan (OPTIMIZE TABLE).';
            } else {
                $message = 'Database berhasil diperiksa.';
            }

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal mengoptimalkan database: '.$e->getMessage(),
            ]);
        }

        return back();
    }

    /**
     * Reset seluruh isi satu tabel (diarsipkan lebih dulu bila memungkinkan).
     */
    public function resetTable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'table' => ['required', 'string', Rule::in(DatabaseResetter::resettableTables())],
            'confirmation' => ['required', 'in:RESET'],
        ]);

        $table = (string) $validated['table'];

        try {
            $deleted = $this->resetter->reset($table);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Tabel {$table} berhasil direset. {$deleted} baris dihapus.",
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal mereset tabel: '.$e->getMessage(),
            ]);
        }

        return back();
    }

    /**
     * Reset seluruh data aktif. Akun role admin tetap dipertahankan.
     */
    public function resetAll(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESET'],
        ]);

        try {
            $deleted = $this->resetter->resetAll();
            $total = array_sum($deleted);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Seluruh data berhasil direset ({$total} baris diproses). Akun admin dipertahankan.",
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal mereset seluruh data: '.$e->getMessage(),
            ]);
        }

        return back();
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
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        $value = $bytes / (1024 ** $i);

        return sprintf('%.2f %s', $value, $units[$i]);
    }
}
