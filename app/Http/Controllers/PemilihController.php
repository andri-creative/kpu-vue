<?php

namespace App\Http\Controllers;

use App\Enums\StatusEnum;
use App\Models\Candidate;
use App\Models\Vote;
use App\Models\VoteLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PemilihController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->status === StatusEnum::SudahVoting) {
            return Inertia::render('PemilihDone');
        }

        $kandidat = Candidate::with('user.profile')
            ->orderBy('nomor_urut')
            ->get();

        return Inertia::render('Pemilih', [
            'kandidat' => $kandidat,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
        ]);

        $user = $request->user();

        if ($user->status === StatusEnum::SudahVoting) {
            return redirect()->route('pemilih');
        }

        // Pastikan suara, status, dan log tercatat bersamaan (atomik)
        // sebelum halaman terima kasih ditampilkan.
        DB::transaction(function () use ($request, $user, $validated): void {
            Vote::create([
                'user_id' => $user->id,
                'candidate_id' => $validated['candidate_id'],
                'voted_at' => now(),
            ]);

            $user->update(['status' => StatusEnum::SudahVoting]);

            VoteLog::create([
                'user_id' => $user->id,
                'candidate_id' => $validated['candidate_id'],
                'action' => 'vote_cast',
                'ip_address' => $request->ip(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terima kasih! Suara Anda telah berhasil tercatat.',
        ]);

        return redirect()->route('pemilih');
    }
}
