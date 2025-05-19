<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'title',
        'code',
        'customer_name',
        'license_plate',
        'car_model',
        'technician_name',
        'summary',
        'recommendations',
        'warranty_info',
        'services_performed',
        'additional_services',
        'service_date',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'services_performed' => 'array',
        'additional_services' => 'array',
        'service_date' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the service that owns the report.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the checklist items for the report.
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(ServiceReportChecklistItem::class)->orderBy('order');
    }

    /**
     * Generate a unique code for the report.
     */
    public static function generateUniqueCode(): string
    {
        $code = strtoupper(Str::random(8));

        // Check if the code already exists
        while (self::where('code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }

        return $code;
    }

    /**
     * Get the URL for the report.
     */
    public function getUrl(): string
    {
        return route('service-reports.show', $this->code);
    }

    /**
     * Check if the report has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope a query to only include active reports.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('expires_at', '>', now());
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            // Generate a unique code if not provided
            if (empty($report->code)) {
                $report->code = self::generateUniqueCode();
            }

            // Set expiration date if not provided (7 days from now)
            if (empty($report->expires_at)) {
                $report->expires_at = now()->addDays(7);
            }
        });
    }
}
