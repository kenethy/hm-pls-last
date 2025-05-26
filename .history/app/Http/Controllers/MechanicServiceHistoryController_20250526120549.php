<?php

namespace App\Http\Controllers;

use App\Models\MechanicReport;
use App\Models\Service;
use Illuminate\Http\Request;

class MechanicServiceHistoryController extends Controller
{
    /**
     * Display the service history for a mechanic report.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Find the mechanic report with mechanic relationship
        $record = MechanicReport::with('mechanic')->findOrFail($id);

        // Build query for services based on report type
        $servicesQuery = Service::query()
            ->join('mechanic_service', 'services.id', '=', 'mechanic_service.service_id')
            ->where('mechanic_service.mechanic_id', $record->mechanic_id)
            ->select('services.*', 'mechanic_service.invoice_number', 'mechanic_service.labor_cost');

        // Apply period filtering based on report type
        if ($record->is_cumulative) {
            // For cumulative reports, show all services for this mechanic
            // Optionally filter by reset date if it exists
            if ($record->period_reset_at) {
                $servicesQuery->where('services.created_at', '>=', $record->period_reset_at);
            }
        } else {
            // For period-based reports, filter by the specific period
            if ($record->period_start && $record->period_end) {
                $servicesQuery->whereBetween('services.created_at', [
                    $record->period_start,
                    $record->period_end->endOfDay()
                ]);
            } elseif ($record->week_start && $record->week_end) {
                // Legacy weekly reports
                $servicesQuery->where('mechanic_service.week_start', $record->week_start)
                    ->where('mechanic_service.week_end', $record->week_end);
            }
        }

        $allServices = $servicesQuery->orderBy('services.created_at', 'desc')->get();

        // Filter services by status (default: completed)
        $status = request()->query('status', 'completed');

        if ($status === 'all') {
            $services = $allServices;
        } else {
            $services = $allServices->where('status', $status);
        }

        return view('mechanic-services.history', compact('record', 'services', 'status'));
    }
}
