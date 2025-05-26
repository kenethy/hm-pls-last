<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MechanicReportArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'mechanic_id',
        'week_start',
        'week_end',
        'services_count',
        'total_labor_cost',
        'notes',
        'is_paid',
        'paid_at',
        'archived_at',
        'archive_reason',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'services_count' => 'integer',
        'total_labor_cost' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the mechanic that owns the archived report.
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    /**
     * Scope a query to only include reports for a specific week.
     */
    public function scopeForWeek($query, $weekStart, $weekEnd)
    {
        return $query->where('week_start', $weekStart)
            ->where('week_end', $weekEnd);
    }

    /**
     * Scope a query to only include reports for a specific archive reason.
     */
    public function scopeByReason($query, $reason)
    {
        return $query->where('archive_reason', $reason);
    }

    /**
     * Get formatted week period.
     */
    public function getWeekPeriodAttribute(): string
    {
        return $this->week_start->format('d M Y') . ' - ' . $this->week_end->format('d M Y');
    }

    /**
     * Get formatted labor cost.
     */
    public function getFormattedLaborCostAttribute(): string
    {
        return 'Rp ' . number_format($this->total_labor_cost, 0, ',', '.');
    }
}
