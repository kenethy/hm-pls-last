<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class MechanicRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'mechanic_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'rating',
        'comment',
        'service_type',
        'vehicle_info',
        'service_date',
    ];

    protected $casts = [
        'rating' => 'integer',
        'service_date' => 'datetime',
    ];

    /**
     * Get the service that was rated
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the mechanic that was rated
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    /**
     * Get the customer who gave the rating
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope to filter ratings by date range
     */
    public function scopeDateRange(Builder $query, $startDate = null, $endDate = null): Builder
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope to filter ratings by mechanic
     */
    public function scopeForMechanic(Builder $query, $mechanicId): Builder
    {
        return $query->where('mechanic_id', $mechanicId);
    }

    /**
     * Scope to filter ratings by rating value
     */
    public function scopeWithRating(Builder $query, $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get average rating for a mechanic
     */
    public static function getAverageRatingForMechanic($mechanicId, $startDate = null, $endDate = null): float
    {
        $query = static::forMechanic($mechanicId);

        if ($startDate || $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return round($query->avg('rating') ?? 0, 2);
    }

    /**
     * Get rating distribution for a mechanic
     */
    public static function getRatingDistributionForMechanic($mechanicId, $startDate = null, $endDate = null): array
    {
        $query = static::forMechanic($mechanicId);

        if ($startDate || $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $query->clone()->withRating($i)->count();
        }

        return $distribution;
    }

    /**
     * Check if a rating already exists for a service-mechanic-customer combination
     */
    public static function ratingExists($serviceId, $mechanicId, $customerPhone): bool
    {
        return static::where('service_id', $serviceId)
            ->where('mechanic_id', $mechanicId)
            ->where('customer_phone', $customerPhone)
            ->exists();
    }

    /**
     * Get star display for rating
     */
    public function getStarDisplayAttribute(): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $this->rating ? '★' : '☆';
        }
        return $stars;
    }
}
