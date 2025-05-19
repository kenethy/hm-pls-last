<?php

namespace App\Http\Controllers;

use App\Models\ServiceReport;
use Illuminate\Http\Request;

class ServiceReportController extends Controller
{
    /**
     * Display the specified service report.
     */
    public function show(string $code)
    {
        // Find the report by its code
        $report = ServiceReport::where('code', $code)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('checklistItems')
            ->first();

        if (!$report) {
            return view('service-reports.expired');
        }

        return view('service-reports.show', compact('report'));
    }

    /**
     * Download the service report as PDF.
     */
    public function download(string $code)
    {
        // Find the report by its code
        $report = ServiceReport::where('code', $code)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('checklistItems')
            ->first();

        if (!$report) {
            return redirect()->route('service-reports.expired');
        }

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('service-reports.pdf', compact('report'));

        return $pdf->download("laporan-servis-{$report->license_plate}.pdf");
    }

    /**
     * Show the expired page.
     */
    public function expired()
    {
        return view('service-reports.expired');
    }
}
