<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentCardController extends Controller
{
    public function print(Request $request): Response
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:500'],
            'student_ids.*' => ['required', 'string', 'exists:users,id'],
        ]);

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

        $filename = 'kartu-siswa-'.now()->format('Y-m-d').'.pdf';

        return $pdf->stream($filename);
    }
}
