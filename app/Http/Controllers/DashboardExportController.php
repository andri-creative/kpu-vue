<?php

namespace App\Http\Controllers;

use App\Exports\DashboardExport;
use App\Support\DashboardMetrics;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardExportController extends Controller
{
    /**
     * Unduh data dashboard sebagai file Excel (.xlsx).
     */
    public function excel(DashboardMetrics $metrics): BinaryFileResponse
    {
        return Excel::download(
            new DashboardExport($metrics->all()),
            'dashboard-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * Unduh data dashboard sebagai file PDF.
     */
    public function pdf(DashboardMetrics $metrics): Response
    {
        $pdf = Pdf::loadView('pdf.dashboard', [
            'data' => $metrics->all(),
            'schoolName' => config('app.name', 'Sekolah'),
        ]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('dashboard-'.now()->format('Y-m-d').'.pdf');
    }
}
