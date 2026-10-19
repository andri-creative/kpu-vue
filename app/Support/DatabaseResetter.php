<?php

namespace App\Support;

use App\Enums\RoleEnum;
use App\Models\Candidate;
use App\Models\Profile;
use App\Models\User;
use App\Models\UsersArchived;
use App\Models\Vote;
use App\Models\VoteLog;
use App\Models\VoteLogsArchived;
use App\Models\VotesArchived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DatabaseResetter
{
    /**
     * Tabel yang tidak boleh direset karena dapat merusak sistem.
     */
    public const PROTECTED_TABLES = ['migrations'];

    /**
     * Daftar tabel yang boleh direset (semua kecuali tabel yang dilindungi).
     *
     * @return list<string>
     */
    public static function resettableTables(): array
    {
        return [
            'vote_logs',
            'votes',
            'users',
            'profiles',
            'candidates',
            'vote_logs_archived',
            'votes_archived',
            'users_archived',
            'sessions',
        ];
    }

    public static function isResettable(string $table): bool
    {
        return in_array($table, self::resettableTables(), true);
    }

    /**
     * Arsipkan (bila ada tabel arsipnya) lalu hapus seluruh isi satu tabel.
     *
     * @return int jumlah baris yang dihapus dari tabel aktif
     */
    public function reset(string $table): int
    {
        if (! self::isResettable($table)) {
            throw new InvalidArgumentException("Tabel [{$table}] tidak boleh direset.");
        }

        return DB::transaction(fn (): int => match ($table) {
            'votes' => $this->resetVotes(),
            'vote_logs' => $this->resetVoteLogs(),
            'users' => $this->resetUsers(),
            'profiles' => $this->resetProfiles(),
            'candidates' => $this->resetCandidates(),
            'sessions' => (int) DB::table('sessions')->delete(),
            'votes_archived' => (int) VotesArchived::query()->delete(),
            'vote_logs_archived' => (int) VoteLogsArchived::query()->delete(),
            'users_archived' => (int) UsersArchived::query()->delete(),
            default => 0,
        });
    }

    /**
     * Reset seluruh data aktif (arsip tetap dipertahankan sebagai tujuan arsip).
     *
     * @return array<string, int>
     */
    public function resetAll(): array
    {
        return DB::transaction(function (): array {
            $this->archiveVotes();
            $this->archiveVoteLogs();
            $this->archiveUsers();

            $deleted = [
                'votes' => (int) Vote::query()->delete(),
                'vote_logs' => (int) VoteLog::query()->delete(),
                'candidates' => (int) Candidate::query()->delete(),
                'users' => (int) User::query()->where('role', '!=', RoleEnum::Admin)->delete(),
                'sessions' => (int) DB::table('sessions')->delete(),
            ];

            return $deleted;
        });
    }

    private function resetVotes(): int
    {
        $this->archiveVotes();

        return (int) Vote::query()->delete();
    }

    private function resetVoteLogs(): int
    {
        $this->archiveVoteLogs();

        return (int) VoteLog::query()->delete();
    }

    /**
     * Arsipkan user non-admin beserta vote/log miliknya, lalu hapus (admin tetap).
     */
    private function resetUsers(): int
    {
        $userIds = User::query()
            ->where('role', '!=', RoleEnum::Admin)
            ->pluck('id')
            ->all();

        if ($userIds !== []) {
            $this->archiveVotes(userIds: $userIds);
            $this->archiveVoteLogs(userIds: $userIds);
        }

        $this->archiveUsers();

        return (int) User::query()->where('role', '!=', RoleEnum::Admin)->delete();
    }

    /**
     * Hapus profil milik user non-admin (profil admin tetap).
     */
    private function resetProfiles(): int
    {
        $userIds = User::query()
            ->where('role', '!=', RoleEnum::Admin)
            ->pluck('id');

        return (int) Profile::query()->whereIn('user_id', $userIds)->delete();
    }

    /**
     * Arsipkan vote/log milik kandidat lalu hapus semua kandidat.
     */
    private function resetCandidates(): int
    {
        $candidateIds = Candidate::query()->pluck('id')->all();

        if ($candidateIds !== []) {
            $this->archiveVotes(candidateIds: $candidateIds);
            $this->archiveVoteLogs(candidateIds: $candidateIds);
        }

        return (int) Candidate::query()->delete();
    }

    /**
     * @param  list<string>|null  $userIds
     * @param  list<int>|null  $candidateIds
     */
    private function archiveVotes(?array $userIds = null, ?array $candidateIds = null): void
    {
        $tahun = date('Y');

        $query = Vote::query()->with(['user.profile', 'candidate']);

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        if ($candidateIds !== null) {
            $query->whereIn('candidate_id', $candidateIds);
        }

        $query->chunkById(200, function ($votes) use ($tahun): void {
            VotesArchived::insert($votes->map(fn (Vote $vote): array => [
                'id' => (string) Str::uuid(),
                'user_nis' => $vote->user?->nis ?? '',
                'user_name' => $vote->user?->profile?->nama_lengkap ?? $vote->user?->nis,
                'candidate_id' => $vote->candidate_id,
                'candidate_name' => $vote->candidate?->nama_lengkap,
                'voted_at' => $vote->voted_at,
                'tahun' => $tahun,
                'archived_at' => now(),
            ])->all());
        });
    }

    /**
     * @param  list<string>|null  $userIds
     * @param  list<int>|null  $candidateIds
     */
    private function archiveVoteLogs(?array $userIds = null, ?array $candidateIds = null): void
    {
        $tahun = date('Y');

        $query = VoteLog::query()->with('user');

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        if ($candidateIds !== null) {
            $query->whereIn('candidate_id', $candidateIds);
        }

        $query->chunkById(200, function ($logs) use ($tahun): void {
            VoteLogsArchived::insert($logs->map(fn (VoteLog $log): array => [
                'user_nis' => $log->user?->nis ?? '',
                'user_name' => $log->user?->nis,
                'candidate_id' => $log->candidate_id,
                'action' => $log->action,
                'detail' => $log->detail,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
                'tahun' => $tahun,
                'archived_at' => now(),
            ])->all());
        });
    }

    private function archiveUsers(): void
    {
        $tahun = date('Y');

        User::query()
            ->where('role', '!=', RoleEnum::Admin)
            ->chunkById(200, function ($users) use ($tahun): void {
                UsersArchived::insert($users->map(fn (User $user): array => [
                    'id' => $user->id,
                    'nis' => $user->nis,
                    'role' => $user->role->value,
                    'status' => $user->status->value,
                    'tahun' => $tahun,
                    'archived_at' => now(),
                ])->all());
            });
    }
}
