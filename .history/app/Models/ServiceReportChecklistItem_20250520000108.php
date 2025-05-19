<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_report_id',
        'order',
        'inspection_point',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the service report that owns the checklist item.
     */
    public function serviceReport(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class);
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'ok' => 'success',
            'warning' => 'warning',
            'needs_repair' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ok' => 'GOOD',
            'warning' => 'Waspada',
            'needs_repair' => 'Harus Diperbaiki',
            default => 'Tidak Diperiksa',
        };
    }
}
