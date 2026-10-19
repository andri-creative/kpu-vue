<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap' => $validated['nama_lengkap'],
                'kelas' => $validated['kelas'],
            ],
        );

        // Kandidat dibaca dari tabel candidates oleh halaman pemilih,
        // jadi nama dan kelas harus selalu sama dengan profiles.
        if ($user->candidate) {
            $user->candidate->update([
                'nama_lengkap' => $validated['nama_lengkap'],
                'kelas' => $validated['kelas'],
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
