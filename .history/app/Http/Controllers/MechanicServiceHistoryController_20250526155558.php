<?php

namespace App\Http\Controllers;

use App\Models\MechanicReport;
use App\Models\Service;

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

        // Get filter parameters
        $status = request()->query('status', 'completed');
        $dateRange = request()->query('date_range', 'all_time');
        $customStartDate = request()->query('start_date');
        $customEndDate = request()->query('end_date');

        // Apply date filtering
        $this->applyDateFiltering($servicesQuery, $record, $dateRange, $customStartDate, $customEndDate);

        $allServices = $servicesQuery->orderBy('services.created_at', 'desc')->get();

        // Filter services by status
        if ($status === 'all') {
            $services = $allServices;
        } else {
            $services = $allServices->where('status', $status);
        }

        return view('mechanic-services.history', compact(
            'record',
            'services',
            'status',
            'dateRange',
            'customStartDate',
            'customEndDate'
        ));
    }

    /**
     * Apply date filtering to the services query
     */
    private function applyDateFiltering($query, $record, $dateRange, $customStartDate, $customEndDate)
    {
        if ($record->is_cumulative) {
            // For cumulative reports, apply user-selected date filtering
            switch ($dateRange) {
                case 'last_7_days':
                    $query->where('services.created_at', '>=', now()->subDays(7));
                    break;
                case 'last_30_days':
                    $query->where('services.created_at', '>=', now()->subDays(30));
                    break;
                case 'last_3_months':
                    $query->where('services.created_at', '>=', now()->subMonths(3));
                    break;
                case 'custom':
                    if ($customStartDate) {
                        $query->whereDate('services.created_at', '>=', $customStartDate);
                    }
                    if ($customEndDate) {
                        $query->whereDate('services.created_at', '<=', $customEndDate);
                    }
                    break;
                case 'all_time':
                default:
                    // Show all services for cumulative reports - no date filtering
                    break;
            }
        } else {
            // For period-based reports, filter by the specific period
            if ($record->period_start && $record->period_end) {
                $query->whereBetween('services.created_at', [
                    $record->period_start,
                    $record->period_end->endOfDay()
                ]);
            } elseif ($record->week_start && $record->week_end) {
                // Legacy weekly reports
                $query->where('mechanic_service.week_start', $record->week_start)
                    ->where('mechanic_service.week_end', $record->week_end);
            }
        }
    }

    /**
     * Apply payment status filtering to the services query
     * Note: Payment status is tracked at the mechanic report level, not individual services
     */
    private function applyPaymentStatusFiltering($query, $paymentStatus)
    {
        // Since payment status is tracked at the report level, not service level,
        // we'll show a note about this in the view instead of filtering here
        // This method is kept for future enhancement if needed
    }
}
