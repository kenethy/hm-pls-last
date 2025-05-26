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
        $paymentStatus = request()->query('payment_status', 'all');
        $dateRange = request()->query('date_range', 'all_time');
        $customStartDate = request()->query('start_date');
        $customEndDate = request()->query('end_date');

        // Apply date filtering
        $this->applyDateFiltering($servicesQuery, $record, $dateRange, $customStartDate, $customEndDate);

        // Apply payment status filtering
        $this->applyPaymentStatusFiltering($servicesQuery, $paymentStatus);

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
            'paymentStatus',
            'dateRange',
            'customStartDate',
            'customEndDate'
        ));
    }
}
