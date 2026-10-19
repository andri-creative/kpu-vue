<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\KodeUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Security', [
            'hasKode' => $request->user()->kode !== null,
        ]);
    }

    /**
     * Update the user's own 6-digit login kode.
     */
    public function update(KodeUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'kode' => $request->validated()['kode_baru'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kode login berhasil diperbarui.',
        ]);

        return back();
    }
}
