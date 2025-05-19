<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'vehicle_type',
        'description',
        'checklist_items',
        'services_performed',
        'warranty_info',
        'recommendations',
        'is_default',
    ];

    protected $casts = [
        'checklist_items' => 'array',
        'services_performed' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Scope a query to only include default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default template.
     */
    public static function getDefault()
    {
        return self::default()->first() ?? self::first();
    }

    /**
     * Create a service report from this template.
     */
    public function createServiceReport(Service $service): ServiceReport
    {
        // Create the service report
        $report = ServiceReport::create([
            'service_id' => $service->id,
            'title' => 'Laporan Digital Paket Napas Baru Premium',
            'customer_name' => $service->customer_name,
            'license_plate' => $service->license_plate,
            'car_model' => $service->car_model,
            'technician_name' => $service->mechanics->first()->name ?? null,
            'warranty_info' => $this->warranty_info,
            'recommendations' => $this->recommendations,
            'services_performed' => $this->services_performed,
            'service_date' => $service->completed_at ?? now(),
        ]);

        // Create the checklist items
        if (is_array($this->checklist_items)) {
            $order = 1;
            foreach ($this->checklist_items as $item) {
                $report->checklistItems()->create([
                    'order' => $order++,
                    'inspection_point' => $item['inspection_point'],
                    'status' => 'ok', // Default status
                    'notes' => '',
                ]);
            }
        }

        return $report;
    }
}
