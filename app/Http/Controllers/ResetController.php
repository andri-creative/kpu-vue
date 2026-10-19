<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Candidate;
use App\Models\User;
use App\Models\UsersArchived;
use App\Models\Vote;
use App\Models\VoteLog;
use App\Models\VoteLogsArchived;
use App\Models\VotesArchived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ResetController extends Controller
{
    public function index(): InertiaResponse|Response
    {
        return Inertia::render('Reset/Pemilih', [
            'stats' => [
                'total_pemilih' => User::where('role', RoleEnum::Pemilih)->count(),
                'total_votes' => Vote::count(),
                'total_vote_logs' => VoteLog::count(),
                'total_kandidat' => Candidate::count(),
            ],
            'archives' => $this->archiveSummary(),
        ]);
    }

    public function reset_voting(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESET'],
        ]);

        $tahun = date('Y');

        $archivedVotes = DB::transaction(function () use ($tahun) {
            Vote::with(['user.profile', 'candidate'])->chunkById(200, function ($votes) use ($tahun) {
                VotesArchived::insert($votes->map(fn (Vote $vote) => [
                    'id' => (string) Str::uuid(),
                    'user_nis' => $vote->user->nis ?? '',
                    'user_name' => $vote->user->profile?->nama_lengkap ?? $vote->user->nis ?? null,
                    'candidate_id' => $vote->candidate_id,
                    'candidate_name' => $vote->candidate?->nama_lengkap,
                    'voted_at' => $vote->voted_at,
                    'tahun' => $tahun,
                    'archived_at' => now(),
                ])->all());
            });

            VoteLog::with('user')->chunkById(200, function ($logs) use ($tahun) {
                VoteLogsArchived::insert($logs->map(fn (VoteLog $log) => [
                    'user_nis' => $log->user->nis ?? '',
                    'user_name' => $log->user->nis ?? null,
                    'candidate_id' => $log->candidate_id,
                    'action' => $log->action,
                    'detail' => $log->detail,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                    'tahun' => $tahun,
                    'archived_at' => now(),
                ])->all());
            });

            User::where('role', '!=', RoleEnum::Admin)
                ->chunkById(200, function ($users) use ($tahun) {
                    UsersArchived::insert($users->map(fn (User $user) => [
                        'id' => $user->id,
                        'nis' => $user->nis,
                        'role' => $user->role->value,
                        'status' => $user->status->value,
                        'tahun' => $tahun,
                        'archived_at' => now(),
                    ])->all());
                });

            $totalVotes = Vote::count();

            VoteLog::query()->delete();
            Vote::query()->delete();
            Candidate::query()->delete();
            User::where('role', '!=', RoleEnum::Admin)->delete();

            return $totalVotes;
        });

        return redirect()->route('reset.index')->with('toast', [
            'type' => 'success',
            'message' => "{$archivedVotes} suara berhasil diarsipkan ke tahun {$tahun}. Data pemilih dan kandidat telah dibersihkan.",
        ]);
    }

    /**
     * @return array<int, array{tahun: string, votes: int, users: int}>
     */
    private function archiveSummary(): array
    {
        return VotesArchived::selectRaw('tahun, COUNT(*) as votes')
            ->groupBy('tahun')
            ->orderByDesc('tahun')
            ->get()
            ->map(fn ($row) => [
                'tahun' => $row->tahun,
                'votes' => (int) $row->votes,
                'users' => UsersArchived::where('tahun', $row->tahun)->count(),
            ])
            ->toArray();
    }
}
