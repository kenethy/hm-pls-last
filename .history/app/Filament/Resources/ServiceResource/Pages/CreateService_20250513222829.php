<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Models\Customer;
use App\Models\Vehicle; // Added import for Vehicle model
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('mutateFormDataBeforeCreate called', ['data_before_mutation' => $data]);

        $isNewVehicle = $data['is_new_vehicle'] ?? true;
        $vehicleId = $data['vehicle_id'] ?? null;
        $customerId = $data['customer_id'] ?? null;
        $carModel = $data['car_model'] ?? null;
        $licensePlate = $data['license_plate'] ?? null;

        if ($isNewVehicle && !$vehicleId && $customerId && $carModel && $licensePlate) {
            $existingVehicle = Vehicle::where('license_plate', $licensePlate)->first();

            if (!$existingVehicle) {
                try {
                    $vehicle = Vehicle::create([
                        'customer_id' => $customerId,
                        'model' => $carModel,
                        'license_plate' => $licensePlate,
                        'is_active' => true,
                    ]);
                    $data['vehicle_id'] = $vehicle->id;
                    Log::info('New vehicle created and vehicle_id set', ['vehicle_id' => $vehicle->id, 'data_after_vehicle_creation' => $data]);
                } catch (\Exception $e) {
                    Log::error('Error creating vehicle', ['error' => $e->getMessage(), 'data' => $data]);
                    Notification::make()
                        ->title('Gagal membuat kendaraan baru: ' . $e->getMessage())
                        ->danger()
                        ->send();
                    // Optionally, you might want to prevent service creation if vehicle creation fails
                    // throw \Illuminate\Validation\ValidationException::withMessages([
                    //     'vehicle' => 'Gagal membuat kendaraan baru: ' . $e->getMessage(),
                    // ]);
                }
            } else {
                $data['vehicle_id'] = $existingVehicle->id;
                // If the existing vehicle belongs to a different customer, update it or handle as per business logic
                if ($existingVehicle->customer_id != $customerId) {
                    try {
                        $existingVehicle->update(['customer_id' => $customerId]);
                        Log::info('Existing vehicle customer_id updated', ['vehicle_id' => $existingVehicle->id, 'new_customer_id' => $customerId]);
                    } catch (\Exception $e) {
                        Log::error('Error updating existing vehicle customer_id', ['error' => $e->getMessage(), 'vehicle_id' => $existingVehicle->id]);
                        Notification::make()
                            ->title('Gagal mengupdate customer pada kendaraan yang ada: ' . $e->getMessage())
                            ->warning()
                            ->send();
                    }
                }
                Log::info('Existing vehicle found and vehicle_id set', ['vehicle_id' => $existingVehicle->id, 'data_after_vehicle_assignment' => $data]);
            }
        } elseif ($vehicleId) {
            // Ensure is_new_vehicle is false if an existing vehicle is selected.
            // This might be redundant if UI logic correctly sets is_new_vehicle to false when a vehicle is selected.
            $data['is_new_vehicle'] = false;
            Log::info('Existing vehicle selected, is_new_vehicle set to false', ['vehicle_id' => $vehicleId, 'data_after_is_new_vehicle_update' => $data]);
        }

        // Ensure vehicle_id is set to null if it's empty or not determined
        $data['vehicle_id'] = $data['vehicle_id'] ?? null;

        Log::info('mutateFormDataBeforeCreate finished', ['data_after_mutation' => $data]);
        return $data;
    }

    protected function afterCreate(): void
    {
        $service = $this->record;

        Log::info('afterCreate called for service', [
            'service_id' => $service->id,
            'customer_id' => $service->customer_id,
            'customer_name' => $service->customer_name,
            'phone' => $service->phone,
        ]);

        // Check if customer_id is not set but we have customer_name and phone
        if (!$service->customer_id && $service->customer_name && $service->phone) {
            // Check if customer exists with this phone number
            $customer = Customer::where('phone', $service->phone)->first();

            Log::info('Checking for existing customer', [
                'phone' => $service->phone,
                'customer_exists' => $customer ? 'yes' : 'no',
                'customer_id' => $customer ? $customer->id : null,
            ]);

            if ($customer) {
                // If customer exists, associate service with this customer
                $service->customer_id = $customer->id;
                $service->save();

                Notification::make()
                    ->title('Service berhasil dikaitkan dengan pelanggan yang sudah ada')
                    ->success()
                    ->send();
            } else {
                // If customer doesn't exist, create a new one
                try {
                    $customer = Customer::create([
                        'name' => $service->customer_name,
                        'phone' => $service->phone,
                        'is_active' => true,
                    ]);

                    Log::info('New customer created', [
                        'customer_id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error creating customer', [
                        'error' => $e->getMessage(),
                        'name' => $service->customer_name,
                        'phone' => $service->phone,
                    ]);

                    Notification::make()
                        ->title('Error membuat pelanggan baru: ' . $e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                // Associate service with the new customer
                $service->customer_id = $customer->id;
                $service->save();

                Notification::make()
                    ->title('Pelanggan baru berhasil dibuat dan dikaitkan dengan service')
                    ->success()
                    ->send();
            }
        }
    }
}
