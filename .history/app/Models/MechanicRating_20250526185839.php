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
