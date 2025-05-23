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
        // E-Certificate fields
        'certificate_number',
        'certificate_issued_date',
        'certificate_valid_until',
        'certificate_verification_code',
        'health_status',
        'overall_condition_score',
    ];

    protected $casts = [
        'services_performed' => 'array',
        'additional_services' => 'array',
        'service_date' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'certificate_issued_date' => 'datetime',
        'certificate_valid_until' => 'datetime',
        'overall_condition_score' => 'integer',
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
     * Generate a unique certificate number.
     */
    public static function generateCertificateNumber(): string
    {
        $prefix = 'HM-CERT';
        $year = date('Y');
        $month = date('m');

        // Get the next sequential number for this month
        $lastCert = self::whereYear('certificate_issued_date', $year)
            ->whereMonth('certificate_issued_date', $month)
            ->whereNotNull('certificate_number')
            ->orderBy('certificate_number', 'desc')
            ->first();

        if ($lastCert && preg_match('/(\d+)$/', $lastCert->certificate_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }

    /**
     * Generate a verification code for the certificate.
     */
    public static function generateVerificationCode(): string
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    }

    /**
     * Calculate overall condition score based on checklist items.
     */
    public function calculateConditionScore(): int
    {
        $items = $this->checklistItems;

        if ($items->count() === 0) {
            return 85; // Default score if no checklist items
        }

        $totalItems = $items->count();
        $okItems = $items->where('status', 'ok')->count();
        $warningItems = $items->where('status', 'warning')->count();

        // Calculate score: OK = 100%, Warning = 70%, Needs Repair = 0%
        $score = (($okItems * 100) + ($warningItems * 70)) / $totalItems;

        return max(0, min(100, round($score)));
    }

    /**
     * Determine health status based on condition score.
     */
    public function determineHealthStatus(): string
    {
        $score = $this->overall_condition_score ?? $this->calculateConditionScore();

        if ($score >= 90) {
            return 'Sangat Sehat';
        } elseif ($score >= 80) {
            return 'Sehat';
        } elseif ($score >= 70) {
            return 'Cukup Sehat';
        } elseif ($score >= 60) {
            return 'Perlu Perhatian';
        } else {
            return 'Perlu Perbaikan';
        }
    }

    /**
     * Get the health status color for display.
     */
    public function getHealthStatusColor(): string
    {
        $status = $this->health_status ?? $this->determineHealthStatus();

        return match ($status) {
            'Sangat Sehat' => 'text-green-600',
            'Sehat' => 'text-green-500',
            'Cukup Sehat' => 'text-yellow-500',
            'Perlu Perhatian' => 'text-orange-500',
            'Perlu Perbaikan' => 'text-red-500',
            default => 'text-gray-500',
        };
    }

    /**
     * Initialize certificate data if not already set.
     */
    public function initializeCertificate(): void
    {
        if (!$this->certificate_number) {
            $this->certificate_number = self::generateCertificateNumber();
        }

        if (!$this->certificate_issued_date) {
            $this->certificate_issued_date = $this->service_date ?? now();
        }

        if (!$this->certificate_valid_until) {
            $this->certificate_valid_until = ($this->certificate_issued_date ?? now())->addYear();
        }

        if (!$this->certificate_verification_code) {
            $this->certificate_verification_code = self::generateVerificationCode();
        }

        if (!$this->overall_condition_score) {
            $this->overall_condition_score = $this->calculateConditionScore();
        }

        if (!$this->health_status) {
            $this->health_status = $this->determineHealthStatus();
        }

        $this->save();
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
