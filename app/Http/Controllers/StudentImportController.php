<?php

namespace App\Http\Controllers;

use App\Exports\StudentTemplateExport;
use App\Http\Requests\ImportStudentRequest;
use App\Imports\StudentImport;
use App\Support\UploadStore;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentImportController extends Controller
{
    public function __construct(private readonly UploadStore $uploads) {}

    public function create(): Response
    {
        return Inertia::render('Users/Import', [
            'preview' => session()->pull('student_import_preview'),
        ]);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new StudentTemplateExport, now()->format('Ymd-His').'.xlsx');
    }

    public function preview(ImportStudentRequest $request): RedirectResponse
    {
        $import = new StudentImport;

        Excel::import($import, $request->file('file'));

        session()->put('student_import_preview', $import->preview());

        return to_route('users.import');
    }

    public function store(ImportStudentRequest $request): RedirectResponse
    {
        $import = new StudentImport;

        Excel::import($import, $request->file('file'));

        $preview = $import->preview();

        if (! $preview['ok']) {
            return back()->withErrors([
                'file' => $preview['message'] ?? 'Data siswa tidak valid. Periksa kembali file Excel Anda.',
            ]);
        }

        $this->uploads->store($request->file('file'), 'imports');

        $count = $import->persist();

        session()->flash('generated_codes', $import->generatedCodes);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Import berhasil. {$count} data siswa berhasil ditambahkan.",
        ]);

        return to_route('users.index');
    }
}
