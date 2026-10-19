<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Traits\RespondsWith;
use App\Models\Profile;
use App\Models\User;
use App\Support\KodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use RespondsWith;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $kelasFilter = (string) $request->query('kelas', '');
        $statusFilter = (string) $request->query('status', '');

        $users = User::query()
            ->whereNot('role', RoleEnum::Admin)
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('profile', fn ($p) => $p->where('nama_lengkap', 'like', "%{$search}%"))
            ))
            ->when($kelasFilter !== '', fn ($query) => $query->whereHas(
                'profile',
                fn ($p) => $p->where('kelas', $kelasFilter)
            ))
            ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
            ->with('profile')
            // User nonaktif selalu tampil di paling akhir.
            ->orderByRaw('case when status = ? then 1 else 0 end', [StatusEnum::Nonaktif->value])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['id', 'nis', 'kode', 'role', 'status', 'created_at'])
            ->appends($request->query())
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'nis' => $user->nis,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'kode' => $user->kode,
                'created_at' => $user->created_at,
                'nama_lengkap' => $user->profile?->nama_lengkap,
                'kelas' => $user->profile?->kelas,
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'generatedCodes' => session()->pull('generated_codes', []),
            'filters' => [
                'search' => $search,
                'kelas' => $kelasFilter,
                'status' => $statusFilter,
            ],
            'kelasList' => Profile::query()
                ->select('kelas')
                ->distinct()
                ->orderBy('kelas')
                ->pluck('kelas'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // User yang dibuat lewat form ini selalu berperan sebagai pemilih.
        $kode = KodeGenerator::generate();

        $user = User::create([
            'nis' => $validated['nis'],
            'kode' => $kode,
            'password' => Str::password(32),
            'role' => RoleEnum::Pemilih,
            'status' => StatusEnum::Aktif,
        ]);

        $user->profile()->create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'kelas' => $validated['kelas'],
        ]);

        return $this->redirectSuccess('users.index', "User berhasil ditambahkan. Kode login: {$kode}");
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => $user->only(['id', 'nis', 'role']),
            'assignableRoles' => collect(RoleEnum::assignable())->map(fn (RoleEnum $role) => [
                'value' => $role->value,
                'label' => ucfirst($role->value),
            ])->values(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'nis' => $validated['nis'],
            'role' => RoleEnum::from($validated['role']),
        ]);

        return $this->redirectSuccess('users.index', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return $this->redirectSuccess('users.index', 'User berhasil dihapus.');
    }

    public function resetKode(User $user): RedirectResponse
    {
        $kode = KodeGenerator::generate();

        $user->update(['kode' => $kode]);
        session()->flash('generated_codes', [$user->nis => $kode]);

        return $this->redirectSuccess('users.index', "Kode baru untuk NIS {$user->nis}: {$kode}");
    }

    public function resetKodeKosong(): RedirectResponse
    {
        $users = User::query()
            ->whereNull('kode')
            ->whereNot('role', RoleEnum::Admin)
            ->orderBy('created_at')
            ->get(['id', 'nis']);

        if ($users->isEmpty()) {
            return $this->redirectSuccess('users.index', 'Semua user sudah memiliki kode.');
        }

        $codes = KodeGenerator::generateMany($users->count());

        $generated = [];

        DB::transaction(function () use ($users, $codes, &$generated): void {
            foreach ($users as $index => $user) {
                $kode = $codes[$index];

                $user->update(['kode' => $kode]);

                $generated[$user->nis] = $kode;
            }
        });

        session()->flash('generated_codes', $generated);

        return $this->redirectSuccess(
            'users.index',
            count($generated).' kode baru berhasil dibuat untuk user tanpa kode.',
        );
    }
}
