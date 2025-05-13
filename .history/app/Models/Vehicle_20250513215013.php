<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'model',
        'license_plate',
        'is_active', // Added is_active
        // Add any other fields your vehicle needs
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the customer that owns the vehicle.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the services for this vehicle.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Scope a query to only include active vehicles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the vehicle's full details.
     */
    public function getFullDetailsAttribute(): string
    {
        $details = $this->model;

        if ($this->license_plate) {
            $details .= ' - ' . $this->license_plate;
        }

        if ($this->color) {
            $details .= ' - ' . $this->color;
        }

        if ($this->year) {
            $details .= ' (' . $this->year . ')';
        }

        return $details;
    }

    /**
     * Find or create a vehicle based on customer phone and license plate.
     *
     * @param string $phone Customer phone number
     * @param string $licensePlate License plate number
     * @param array $attributes Additional attributes for the vehicle
     * @return Vehicle
     */
    public static function findOrCreateByPhoneAndPlate(string $phone, string $licensePlate, array $attributes = []): Vehicle
    {
        // Add logging for debugging
        \Illuminate\Support\Facades\Log::info('findOrCreateByPhoneAndPlate called', [
            'phone' => $phone,
            'license_plate' => $licensePlate,
            'attributes' => $attributes,
        ]);

        // Normalize license plate (remove spaces, uppercase)
        $normalizedPlate = strtoupper(str_replace(' ', '', $licensePlate));

        // Find customer by phone
        $customer = Customer::where('phone', $phone)->first();

        if (!$customer) {
            // Create customer if not exists
            try {
                $customer = Customer::create([
                    'name' => $attributes['customer_name'] ?? 'Unknown',
                    'phone' => $phone,
                    'is_active' => true,
                ]);

                \Illuminate\Support\Facades\Log::info('New customer created', [
                    'customer_id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error creating customer', [
                    'error' => $e->getMessage(),
                    'phone' => $phone,
                    'name' => $attributes['customer_name'] ?? 'Unknown',
                ]);
                throw $e;
            }
        }

        // Find vehicle by customer and normalized license plate
        $vehicle = self::where('customer_id', $customer->id)
            ->whereRaw('UPPER(REPLACE(license_plate, " ", "")) = ?', [$normalizedPlate])
            ->first();

        if (!$vehicle) {
            // Create vehicle if not exists
            try {
                $vehicle = self::create([
                    'customer_id' => $customer->id,
                    'model' => $attributes['car_model'] ?? 'Unknown',
                    'license_plate' => $licensePlate, // Store original format
                    'is_active' => true,
                ]);

                \Illuminate\Support\Facades\Log::info('New vehicle created', [
                    'vehicle_id' => $vehicle->id,
                    'customer_id' => $customer->id,
                    'model' => $vehicle->model,
                    'license_plate' => $vehicle->license_plate,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error creating vehicle', [
                    'error' => $e->getMessage(),
                    'customer_id' => $customer->id,
                    'license_plate' => $licensePlate,
                    'model' => $attributes['car_model'] ?? 'Unknown',
                ]);
                throw $e;
            }
        } else {
            \Illuminate\Support\Facades\Log::info('Existing vehicle found', [
                'vehicle_id' => $vehicle->id,
                'customer_id' => $customer->id,
                'model' => $vehicle->model,
                'license_plate' => $vehicle->license_plate,
            ]);
        }

        return $vehicle;
    }
}
