<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCandidateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidateProfileController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Kandidat', [
            'candidate' => $request->user()->candidate,
        ]);
    }

    public function update(UpdateCandidateProfileRequest $request): RedirectResponse
    {
        $candidate = $request->user()->candidate;

        $candidate->update($request->safe()->only('visi', 'misi'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Visi dan misi berhasil diperbarui.',
        ]);

        return to_route('kandidat');
    }
}
