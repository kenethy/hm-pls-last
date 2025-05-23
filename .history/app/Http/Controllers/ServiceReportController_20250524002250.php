<?php

namespace App\Http\Controllers;

use App\Models\ServiceReport;
use Barryvdh\DomPDF\Facade\Pdf;
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

        // Initialize certificate data if not already set
        $report->initializeCertificate();

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

        // Initialize certificate data if not already set
        $report->initializeCertificate();

        // Generate PDF
        $pdf = Pdf::loadView('service-reports.pdf', compact('report'));

        return $pdf->download("laporan-servis-{$report->license_plate}.pdf");
    }

    /**
     * Download the e-certificate as PDF.
     */
    public function downloadCertificate(string $code)
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

        // Initialize certificate data if not already set
        $report->initializeCertificate();

        // Generate certificate PDF
        $pdf = Pdf::loadView('service-reports.certificate-pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        $filename = "E-Certificate_{$report->license_plate}_{$report->certificate_issued_date->format('Y-m-d')}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Show the expired page.
     */
    public function expired()
    {
        return view('service-reports.expired');
    }
}
