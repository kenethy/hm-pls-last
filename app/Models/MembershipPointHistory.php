<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPointHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'membership_id',
        'service_id',
        'points',
        'type',
        'description',
        'reference',
        'created_by',
    ];

    /**
     * Get the membership that owns the point history.
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /**
     * Get the service associated with the point history.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who created the point history.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
