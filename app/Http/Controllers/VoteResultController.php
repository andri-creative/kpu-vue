<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Candidate;
use App\Models\User;
use App\Models\UsersArchived;
use App\Models\Vote;
use App\Models\VotesArchived;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VoteResultController extends Controller
{
    public function index(Request $request): InertiaResponse|Response
    {
        $tahun = $request->query('tahun');
        $currentYear = date('Y');

        $isArsip = filled($tahun) && $tahun !== $currentYear;
        $tahun = $isArsip ? (string) $tahun : null;

        $tahunList = collect([$currentYear])
            ->merge(VotesArchived::distinct()->pluck('tahun'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        if ($isArsip) {
            return $this->arsipResponse($tahun, $tahunList);
        }

        return $this->aktifResponse($tahunList);
    }

    /**
     * @param  array<int, string>  $tahunList
     */
    private function aktifResponse($tahunList): InertiaResponse|Response
    {
        $totalPemilih = User::where('role', RoleEnum::Pemilih)->count();
        $sudahVoting = Vote::count();
        $partisipasi = $totalPemilih > 0 ? round(($sudahVoting / $totalPemilih) * 100, 1) : 0;

        $totalVotes = $sudahVoting > 0 ? $sudahVoting : 1;

        $results = Candidate::withCount('votes')
            ->orderByDesc('votes_count')
            ->get()
            ->map(fn (Candidate $c) => [
                'id' => $c->id,
                'nama_lengkap' => $c->nama_lengkap,
                'nomor_urut' => $c->nomor_urut,
                'foto_url' => $c->foto_url,
                'votes_count' => $c->votes_count,
                'percentage' => round(($c->votes_count / $totalVotes) * 100, 1),
            ]);

        $voters = Vote::with(['user.profile', 'candidate'])
            ->orderByDesc('voted_at')
            ->paginate(20)
            ->appends(request()->query())
            ->through(fn (Vote $vote) => [
                'id' => $vote->id,
                'user_name' => $vote->user->profile?->nama_lengkap ?? $vote->user->nis,
                'nis' => $vote->user->nis,
                'kelas' => $vote->user->profile?->kelas ?? '-',
                'candidate_name' => $vote->candidate->nama_lengkap,
                'candidate_nomor' => $vote->candidate->nomor_urut,
                'candidate_foto_url' => $vote->candidate->foto_url,
                'voted_at' => $vote->voted_at->format('d M Y H:i'),
            ]);

        return Inertia::render('Hasil', [
            'stats' => [
                'total_pemilih' => $totalPemilih,
                'sudah_voting' => $sudahVoting,
                'partisipasi' => $partisipasi,
            ],
            'results' => $results,
            'voters' => $voters,
            'tahun' => null,
            'tahun_list' => $tahunList,
        ]);
    }

    /**
     * @param  array<int, string>  $tahunList
     */
    private function arsipResponse(string $tahun, $tahunList): InertiaResponse|Response
    {
        $totalSuara = VotesArchived::where('tahun', $tahun)->count();
        $totalVotes = $totalSuara > 0 ? $totalSuara : 1;

        $results = VotesArchived::where('tahun', $tahun)
            ->selectRaw('candidate_id, candidate_name, COUNT(*) as votes_count')
            ->groupBy('candidate_id', 'candidate_name')
            ->orderByDesc('votes_count')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->candidate_id,
                'nama_lengkap' => $row->candidate_name ?? 'Kandidat',
                'nomor_urut' => $row->candidate_id,
                'foto_url' => null,
                'votes_count' => (int) $row->votes_count,
                'percentage' => round(($row->votes_count / $totalVotes) * 100, 1),
            ]);

        $voters = VotesArchived::where('tahun', $tahun)
            ->orderByDesc('voted_at')
            ->paginate(20)
            ->appends(['tahun' => $tahun])
            ->through(fn (VotesArchived $vote) => [
                'id' => $vote->id,
                'user_name' => $vote->user_name,
                'nis' => $vote->user_nis,
                'kelas' => '-',
                'candidate_name' => $vote->candidate_name,
                'candidate_nomor' => $vote->candidate_id,
                'candidate_foto_url' => null,
                'voted_at' => $vote->voted_at->format('d M Y H:i'),
            ]);

        return Inertia::render('Hasil', [
            'stats' => [
                'total_pemilih' => UsersArchived::where('tahun', $tahun)->count(),
                'sudah_voting' => $totalSuara,
                'partisipasi' => 100,
            ],
            'results' => $results,
            'voters' => $voters,
            'tahun' => $tahun,
            'tahun_list' => $tahunList,
        ]);
    }
}
