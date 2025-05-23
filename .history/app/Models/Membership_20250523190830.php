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
        $currentYearPrefix = $prefix . $year;

        // Get all membership numbers for the current year (including soft-deleted ones)
        // to find the highest sequence number that has been used
        $existingNumbers = self::withTrashed()
            ->where('membership_number', 'LIKE', $currentYearPrefix . '%')
            ->pluck('membership_number')
            ->map(function ($number) {
                // Extract the sequence number (last 4 digits)
                return intval(substr($number, 5));
            })
            ->filter() // Remove any invalid numbers (0 or null)
            ->sort()
            ->values();

        // Find the next available number
        $nextNumber = 1;
        if ($existingNumbers->isNotEmpty()) {
            // Get the highest number and add 1
            $highestNumber = $existingNumbers->max();
            $nextNumber = $highestNumber + 1;
        }

        // Double-check that this number doesn't exist (extra safety)
        do {
            $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $candidateNumber = $currentYearPrefix . $formattedNumber;

            // Check if this number exists (including soft-deleted records)
            $exists = self::withTrashed()
                ->where('membership_number', $candidateNumber)
                ->exists();

            if (!$exists) {
                break;
            }

            $nextNumber++;
        } while (true);

        return $candidateNumber;
    }

    /**
     * Check if a membership number exists (including soft-deleted records).
     *
     * @param string $membershipNumber
     * @return bool
     */
    public static function membershipNumberExists(string $membershipNumber): bool
    {
        return self::withTrashed()
            ->where('membership_number', $membershipNumber)
            ->exists();
    }
}
