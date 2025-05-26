<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MechanicReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'mechanic_id',
        'period_start',
        'period_end',
        'services_count',
        'total_labor_cost',
        'notes',
        'is_paid',
        'paid_at',
        'is_cumulative',
        'last_calculated_at',
        'period_reset_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'services_count' => 'integer',
        'total_labor_cost' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'is_cumulative' => 'boolean',
        'last_calculated_at' => 'datetime',
        'period_reset_at' => 'datetime',
    ];

    /**
     * Get the mechanic that owns the report.
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    /**
     * Scope a query to only include cumulative reports.
     */
    public function scopeCumulative($query)
    {
        return $query->where('is_cumulative', true);
    }

    /**
     * Scope a query to only include period-based reports.
     */
    public function scopePeriodBased($query)
    {
        return $query->where('is_cumulative', false);
    }

    /**
     * Scope a query to only include reports for a specific period.
     */
    public function scopeForPeriod($query, $periodStart, $periodEnd)
    {
        return $query->where('period_start', $periodStart)
            ->where('period_end', $periodEnd);
    }

    /**
     * Get the archived reports for this mechanic.
     */
    public function archives()
    {
        return $this->hasMany(MechanicReportArchive::class, 'mechanic_id', 'mechanic_id');
    }

    /**
     * Calculate and update cumulative statistics from all completed services.
     */
    public function recalculateCumulative()
    {
        if (!$this->is_cumulative) {
            throw new \Exception('This method can only be called on cumulative reports');
        }

        // Use a database transaction to ensure data integrity
        return \Illuminate\Support\Facades\DB::transaction(function () {
            // Calculate cumulative statistics from all completed services
            $stats = \Illuminate\Support\Facades\DB::table('mechanic_service')
                ->join('services', 'mechanic_service.service_id', '=', 'services.id')
                ->where('mechanic_service.mechanic_id', $this->mechanic_id)
                ->where('services.status', 'completed')
                ->selectRaw('
                    COUNT(*) as total_services,
                    COALESCE(SUM(mechanic_service.labor_cost), 0) as total_labor_cost
                ')
                ->first();

            // Log the calculation for debugging
            \Illuminate\Support\Facades\Log::info("Recalculating cumulative report for mechanic {$this->mechanic_id}", [
                'report_id' => $this->id,
                'calculated_services' => $stats->total_services ?? 0,
                'calculated_labor_cost' => $stats->total_labor_cost ?? 0,
                'current_services' => $this->services_count,
                'current_labor_cost' => $this->total_labor_cost,
            ]);

            // Update the report using update() method to ensure it's saved properly
            $this->update([
                'services_count' => $stats->total_services ?? 0,
                'total_labor_cost' => $stats->total_labor_cost ?? 0,
                'last_calculated_at' => now(),
            ]);

            // Refresh the model to get the latest data
            $this->refresh();

            return $this;
        });
    }

    /**
     * Reset cumulative statistics and archive current data.
     */
    public function resetCumulative($reason = 'manual_reset')
    {
        if (!$this->is_cumulative) {
            throw new \Exception('This method can only be called on cumulative reports');
        }

        // Archive current cumulative data if it has meaningful values
        if ($this->services_count > 0 || $this->total_labor_cost > 0) {
            MechanicReportArchive::create([
                'mechanic_id' => $this->mechanic_id,
                'week_start' => $this->period_reset_at ? $this->period_reset_at->toDateString() : now()->subYear()->toDateString(),
                'week_end' => now()->toDateString(),
                'services_count' => $this->services_count,
                'total_labor_cost' => $this->total_labor_cost,
                'notes' => $this->notes,
                'is_paid' => $this->is_paid,
                'paid_at' => $this->paid_at,
                'archived_at' => now(),
                'archive_reason' => $reason,
            ]);
        }

        // Reset cumulative values
        $this->services_count = 0;
        $this->total_labor_cost = 0;
        $this->period_reset_at = now();
        $this->last_calculated_at = now();
        $this->is_paid = false;
        $this->paid_at = null;
        $this->notes = 'Laporan kumulatif - direset pada ' . now()->format('d M Y H:i');
        $this->save();

        return $this;
    }

    /**
     * Get formatted period display.
     */
    public function getPeriodDisplayAttribute(): string
    {
        if ($this->is_cumulative) {
            if ($this->period_reset_at) {
                return 'Kumulatif sejak ' . $this->period_reset_at->format('d M Y');
            }
            return 'Kumulatif (semua waktu)';
        }

        if ($this->period_start && $this->period_end) {
            return $this->period_start->format('d M Y') . ' - ' . $this->period_end->format('d M Y');
        }

        return 'Periode tidak ditentukan';
    }

    /**
     * Get formatted labor cost.
     */
    public function getFormattedLaborCostAttribute(): string
    {
        return 'Rp ' . number_format($this->total_labor_cost, 0, ',', '.');
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_paid) {
            return 'success';
        }

        if ($this->total_labor_cost > 0) {
            return 'warning';
        }

        return 'gray';
    }

    /**
     * Scope a query to only include unpaid reports.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope a query to only include paid reports.
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Mark the report as paid.
     */
    public function markAsPaid()
    {
        $this->is_paid = true;
        $this->paid_at = now();
        $this->save();

        return $this;
    }

    /**
     * Get the services for this mechanic report.
     * For cumulative reports, this returns all completed services for the mechanic.
     * This is a helper method, not a relationship.
     */
    public function services()
    {
        if (!$this->mechanic) {
            return collect();
        }

        if ($this->is_cumulative) {
            // For cumulative reports, return all completed services
            return $this->mechanic->services()
                ->where('services.status', 'completed');
        } else {
            // For period-based reports, filter by period dates
            return $this->mechanic->services()
                ->wherePivot('period_start', $this->period_start)
                ->wherePivot('period_end', $this->period_end);
        }
    }
}
