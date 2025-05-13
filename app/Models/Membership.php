<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'membership_number',
        'card_type',
        'points',
        'lifetime_points',
        'join_date',
        'expiry_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'join_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'points' => 'integer',
        'lifetime_points' => 'integer',
    ];

    /**
     * Get the customer that owns the membership.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the point history for this membership.
     */
    public function pointHistory(): HasMany
    {
        return $this->hasMany(MembershipPointHistory::class);
    }

    /**
     * Add points to the membership.
     *
     * @param int $points
     * @param string $type
     * @param string|null $description
     * @param string|null $reference
     * @param int|null $serviceId
     * @return MembershipPointHistory
     */
    public function addPoints(int $points, string $type, ?string $description = null, ?string $reference = null, ?int $serviceId = null): MembershipPointHistory
    {
        // Update membership points
        $this->points += $points;
        $this->lifetime_points += $points;
        $this->save();

        // Create point history record
        return $this->pointHistory()->create([
            'points' => $points,
            'type' => $type,
            'description' => $description,
            'reference' => $reference,
            'service_id' => $serviceId,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Deduct points from the membership.
     *
     * @param int $points
     * @param string $type
     * @param string|null $description
     * @param string|null $reference
     * @return MembershipPointHistory
     */
    public function deductPoints(int $points, string $type, ?string $description = null, ?string $reference = null): MembershipPointHistory
    {
        // Ensure points is positive for consistency in the database
        $positivePoints = abs($points);

        // Update membership points (subtract)
        $this->points -= $positivePoints;
        $this->save();

        // Create point history record (store as negative value)
        return $this->pointHistory()->create([
            'points' => -$positivePoints,
            'type' => $type,
            'description' => $description,
            'reference' => $reference,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Generate a unique membership number.
     *
     * @return string
     */
    public static function generateMembershipNumber(): string
    {
        $prefix = 'HM';
        $year = date('y');

        // Get the last membership number
        $lastMembership = self::orderBy('id', 'desc')->first();

        if ($lastMembership) {
            // Extract the sequence number from the last membership number
            $lastNumber = substr($lastMembership->membership_number, 5);
            $nextNumber = intval($lastNumber) + 1;
        } else {
            $nextNumber = 1;
        }

        // Format the sequence number with leading zeros (4 digits)
        $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . $year . $formattedNumber;
    }
}
