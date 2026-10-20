<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Http\Requests\ReorderCandidatesRequest;
use App\Http\Requests\StoreCandidateRequest;
use App\Http\Requests\UpdateCandidateRequest;
use App\Models\Candidate;
use App\Models\User;
use App\Support\KodeGenerator;
use App\Support\UploadStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function __construct(
        private readonly UploadStore $uploads,
    ) {}

    public function index(): Response
    {
        $candidates = Candidate::query()
            ->with('user:id,nis')
            ->orderBy('nomor_urut')
            ->paginate(10);

        return Inertia::render('Candidates/Index', [
            'candidates' => $candidates,
        ]);
    }

    public function store(StoreCandidateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $kode = KodeGenerator::generate();

        DB::transaction(function () use ($request, $validated, $kode): void {
            $filename = $this->uploads->store($request->file('foto'), 'candidates');

            $user = User::create([
                'nis' => $validated['nis'],
                'kode' => $kode,
                'password' => Str::password(32),
                'role' => RoleEnum::Kandidat,
                'status' => StatusEnum::Nonaktif,
            ]);

            $user->profile()->create([
                'nama_lengkap' => $validated['nama_lengkap'],
                'kelas' => $validated['kelas'],
            ]);

            Candidate::create([
                'user_id' => $user->id,
                'foto' => $filename,
                'nama_lengkap' => $validated['nama_lengkap'],
                'nomor_urut' => $validated['nomor_urut'],
                'nis' => $validated['nis'],
                'kelas' => $validated['kelas'],
                'visi' => $validated['visi'],
                'misi' => $validated['misi'],
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Kandidat berhasil ditambahkan. Akun login — NIS: {$validated['nis']}, kode: {$kode}",
        ]);

        return to_route('candidates.index');
    }

    public function update(UpdateCandidateRequest $request, Candidate $candidate): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $candidate): void {
            if ($request->hasFile('foto')) {
                if ($candidate->foto) {
                    $oldPath = $this->candidatesPath().'/'.$candidate->foto;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $candidate->foto = $this->uploads->store($request->file('foto'), 'candidates');
            }

            $candidate->fill([
                'nama_lengkap' => $validated['nama_lengkap'],
                'nomor_urut' => $validated['nomor_urut'],
                'nis' => $validated['nis'],
                'kelas' => $validated['kelas'],
                'visi' => $validated['visi'],
                'misi' => $validated['misi'],
            ])->save();

            $candidate->user?->update([
                'nis' => $validated['nis'],
            ]);

            $candidate->user?->profile()->updateOrCreate(
                ['user_id' => $candidate->user_id],
                [
                    'nama_lengkap' => $validated['nama_lengkap'],
                    'kelas' => $validated['kelas'],
                ],
            );
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kandidat berhasil diperbarui.']);

        return to_route('candidates.index');
    }

    public function reorder(ReorderCandidatesRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Langkah 1: set semua kandidat ke nomor_urut negatif (temporer) agar slot baru bebas.
        // Langkah 2: set ke nomor_urut baru dari yang terbesar ke terkecil, menghindari bentrok UNIQUE.
        DB::transaction(function () use ($validated) {
            // Set sementara ke nilai negatif unik: -(id * 100) - urutan
            $ids = collect($validated['order'])->pluck('id');
            Candidate::whereIn('id', $ids)
                ->update(['nomor_urut' => DB::raw('-(id * 100)')]);

            // Update ke target baru (descending agar slot besar kosong dulu)
            foreach (collect($validated['order'])->sortByDesc('nomor_urut') as $item) {
                Candidate::where('id', $item['id'])
                    ->update(['nomor_urut' => $item['nomor_urut']]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Urutan kandidat berhasil diperbarui.']);

        return to_route('candidates.index');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        DB::transaction(function () use ($candidate): void {
            if ($candidate->foto) {
                $fotoPath = $this->candidatesPath().'/'.$candidate->foto;
                if (file_exists($fotoPath)) {
                    unlink($fotoPath);
                }
            }

            $candidate->user?->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kandidat berhasil dihapus.']);

        return to_route('candidates.index');
    }

    private function candidatesPath(): string
    {
        $path = rtrim((string) config('filesystems.uploads_path'), '/').'/candidates';

        File::ensureDirectoryExists($path);

        return $path;
    }
}
