<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\PrintStudentCardsRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class StudentCardController extends Controller
{
    public function print(PrintStudentCardsRequest $request): Response
    {
        $validated = $request->validated();

        $students = User::query()
            ->with('profile')
            ->where('role', RoleEnum::Pemilih)
            ->whereIn('id', $validated['student_ids'])
            ->orderBy('nis')
            ->get();

        if ($students->isEmpty()) {
            abort(404, 'Tidak ada siswa yang ditemukan.');
        }

        $schoolName = config('app.name', 'Sekolah');

        $pdf = Pdf::loadView('pdf.student-cards', [
            'students' => $students,
            'schoolName' => $schoolName,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = now()->format('Ymd-His').'.pdf';

        return $pdf->stream($filename);
    }
}
