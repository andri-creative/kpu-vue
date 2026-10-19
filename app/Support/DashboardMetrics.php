<?php

namespace App\Support;

use App\Enums\RoleEnum;
use App\Models\Candidate;
use App\Models\Profile;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteLog;

/**
 * Membangun data dashboard. Dipakai bersama oleh halaman Inertia (Dashboard.vue)
 * dan proses export (Excel & PDF) agar sumber datanya selalu konsisten.
 */
class DashboardMetrics
{
    /**
     * Jumlah entri riwayat aktivitas yang ditampilkan di dashboard.
     */
    private const RECENT_LOG_LIMIT = 10;

    /**
     * Seluruh data dashboard yang siap diserialisasi ke Inertia maupun export.
     *
     * @return array{
     *     stats: array{total_pemilih: int, sudah_voting: int, belum_voting: int, partisipasi: float},
     *     perolehan: array<int, array{nomor_urut: int, nama_lengkap: string, votes: int, percentage: float}>,
     *     kelas: array<int, array{kelas: string, sudah: int, belum: int, total: int, persentase: float}>,
     *     aktivitas: array<int, array{
     *         id: int,
     *         aksi: string,
     *         detail: string|null,
     *         ip_address: string|null,
     *         waktu: string,
     *         nama_pemilih: string,
     *         kelas: string|null,
     *         nomor_urut: int|null,
     *         nama_kandidat: string|null
     *     }>
     * }
     */
    public function all(): array
    {
        $totalPemilih = $this->totalPemilih();
        $sudahVoting = Vote::count();

        return [
            'stats' => [
                'total_pemilih' => $totalPemilih,
                'sudah_voting' => $sudahVoting,
                'belum_voting' => max($totalPemilih - $sudahVoting, 0),
                'partisipasi' => $totalPemilih > 0 ? round($sudahVoting / $totalPemilih * 100, 1) : 0.0,
            ],
            'perolehan' => $this->perolehan($sudahVoting),
            'kelas' => $this->kelasDistribution(),
            'aktivitas' => $this->recentActivity(),
        ];
    }

    /**
     * Jumlah pemilih yang terdaftar (dipakai statistik).
     */
    public function totalPemilih(): int
    {
        return User::query()->where('role', RoleEnum::Pemilih)->count();
    }

    /**
     * Perolehan suara per kandidat, diurutkan nomor urut untuk pie chart.
     *
     * @return array<int, array{nomor_urut: int, nama_lengkap: string, votes: int, percentage: float}>
     */
    public function perolehan(int $sudahVoting): array
    {
        return Candidate::query()
            ->withCount('votes')
            ->orderBy('nomor_urut')
            ->get(['id', 'nomor_urut', 'nama_lengkap'])
            ->map(fn (Candidate $candidate): array => [
                'nomor_urut' => (int) $candidate->nomor_urut,
                'nama_lengkap' => (string) $candidate->nama_lengkap,
                'votes' => (int) $candidate->votes_count,
                'percentage' => $sudahVoting > 0
                    ? round($candidate->votes_count / $sudahVoting * 100, 1)
                    : 0.0,
            ])
            ->all();
    }

    /**
     * Tingkat partisipasi per kelas; hanya menghitung role pemilih dengan kelas terisi.
     * Diurutkan persentase partisipasi menurun (tie-break jumlah sudah, lalu total).
     *
     * @return array<int, array{kelas: string, sudah: int, belum: int, total: int, persentase: float}>
     */
    public function kelasDistribution(): array
    {
        return Profile::query()
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->leftJoin('votes', 'votes.user_id', '=', 'profiles.user_id')
            ->where('users.role', RoleEnum::Pemilih)
            ->whereNotNull('profiles.kelas')
            ->where('profiles.kelas', '!=', '')
            ->select('profiles.kelas')
            ->selectRaw('count(distinct profiles.user_id) as total')
            ->selectRaw('count(distinct votes.user_id) as sudah')
            ->groupBy('profiles.kelas')
            ->get()
            ->map(fn (Profile $profile): array => [
                'kelas' => (string) $profile->kelas,
                'sudah' => (int) $profile->sudah,
                'belum' => (int) $profile->total - (int) $profile->sudah,
                'total' => (int) $profile->total,
                'persentase' => (int) $profile->total > 0
                    ? round((int) $profile->sudah / (int) $profile->total * 100, 1)
                    : 0.0,
            ])
            ->sortByDesc(fn (array $kelas): array => [$kelas['persentase'], $kelas['sudah'], $kelas['total']])
            ->values()
            ->all();
    }

    /**
     * Riwayat aktivitas voting terbaru, terbaru di atas.
     *
     * @return array<int, array{
     *     id: int,
     *     aksi: string,
     *     detail: string|null,
     *     ip_address: string|null,
     *     waktu: string,
     *     nama_pemilih: string,
     *     kelas: string|null,
     *     nomor_urut: int|null,
     *     nama_kandidat: string|null
     * }>
     */
    public function recentActivity(): array
    {
        return VoteLog::query()
            ->with(['user.profile', 'candidate'])
            ->latest('id')
            ->limit(self::RECENT_LOG_LIMIT)
            ->get()
            ->map(fn (VoteLog $log): array => [
                'id' => $log->id,
                'aksi' => $log->action,
                'detail' => $log->detail,
                'ip_address' => $log->ip_address,
                'waktu' => $log->created_at?->toDateTimeString() ?? '-',
                'nama_pemilih' => $log->user?->profile?->nama_lengkap ?? $log->user?->nis ?? '-',
                'kelas' => $log->user?->profile?->kelas,
                'nomor_urut' => $log->candidate?->nomor_urut,
                'nama_kandidat' => $log->candidate?->nama_lengkap,
            ])
            ->values()
            ->all();
    }
}
