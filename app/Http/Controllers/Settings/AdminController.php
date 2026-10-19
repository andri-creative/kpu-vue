<?php

namespace App\Http\Controllers\Settings;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreAdminRequest;
use App\Http\Requests\Settings\UpdateAdminRequest;
use App\Models\User;
use App\Support\KodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $admins = User::query()
            ->where('role', RoleEnum::Admin)
            ->with('profile:id,user_id,nama_lengkap')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['id', 'nis', 'status', 'created_at']);

        return Inertia::render('settings/Admins', [
            'admins' => $admins,
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function store(StoreAdminRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $kode = KodeGenerator::generate();

        $admin = User::create([
            'nis' => $validated['nis'],
            'kode' => $kode,
            'password' => Str::password(32),
            'role' => RoleEnum::Admin,
            'status' => StatusEnum::Nonaktif,
        ]);

        $admin->profile()->create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'kelas' => '-',
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Admin berhasil ditambahkan. Kode login: {$kode}",
        ]);

        return back();
    }

    public function update(UpdateAdminRequest $request, User $admin): RedirectResponse
    {
        $validated = $request->validated();

        $admin->update(['nis' => $validated['nis']]);

        $admin->profile()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'nama_lengkap' => $validated['nama_lengkap'],
                'kelas' => $admin->profile?->kelas ?? '-',
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Admin berhasil diperbarui.']);

        return back();
    }

    public function destroy(Request $request, User $admin): RedirectResponse
    {
        if ($request->user()->id === $admin->id) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Tidak dapat menghapus akun Anda sendiri.']);

            return back();
        }

        $admin->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Admin berhasil dihapus.']);

        return back();
    }
}
