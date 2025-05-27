<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Service;
use App\Models\Mechanic;
use App\Models\MechanicRating;

echo "Creating test rating data...\n";

// Get first service and mechanic
$service = Service::first();
$mechanic = Mechanic::first();

if ($service && $mechanic) {
    // Check if rating already exists
    $existingRating = MechanicRating::where('service_id', $service->id)
        ->where('mechanic_id', $mechanic->id)
        ->where('customer_phone', $service->phone)
        ->first();
    
    if ($existingRating) {
        echo "Rating already exists for this service-mechanic combination.\n";
        echo "Rating ID: " . $existingRating->id . "\n";
    } else {
        // Create test rating
        $rating = MechanicRating::create([
            'service_id' => $service->id,
            'mechanic_id' => $mechanic->id,
            'customer_id' => $service->customer_id,
            'customer_name' => $service->customer_name,
            'customer_phone' => $service->phone,
            'rating' => 5,
            'comment' => 'Montir sangat profesional dan ramah. Pekerjaan rapi dan cepat.',
            'service_type' => $service->service_type,
            'vehicle_info' => $service->license_plate . ' - ' . $service->car_model,
            'service_date' => $service->created_at,
        ]);
        
        echo "Test rating created successfully!\n";
        echo "Rating ID: " . $rating->id . "\n";
        echo "Mechanic: " . $mechanic->name . "\n";
        echo "Customer: " . $service->customer_name . "\n";
        echo "Rating: " . $rating->rating . " stars\n";
    }
    
    // Create a few more test ratings
    $services = Service::take(3)->get();
    $mechanics = Mechanic::take(2)->get();
    
    foreach ($services as $svc) {
        foreach ($mechanics as $mech) {
            $exists = MechanicRating::where('service_id', $svc->id)
                ->where('mechanic_id', $mech->id)
                ->where('customer_phone', $svc->phone)
                ->exists();
                
            if (!$exists) {
                MechanicRating::create([
                    'service_id' => $svc->id,
                    'mechanic_id' => $mech->id,
                    'customer_id' => $svc->customer_id,
                    'customer_name' => $svc->customer_name,
                    'customer_phone' => $svc->phone,
                    'rating' => rand(3, 5),
                    'comment' => 'Pelayanan ' . ['baik', 'sangat baik', 'memuaskan', 'excellent'][rand(0, 3)],
                    'service_type' => $svc->service_type,
                    'vehicle_info' => $svc->license_plate . ' - ' . $svc->car_model,
                    'service_date' => $svc->created_at,
                ]);
                echo "Created additional rating for service {$svc->id} and mechanic {$mech->name}\n";
            }
        }
    }
    
} else {
    echo "No service or mechanic found. Please create some test data first.\n";
    echo "Services count: " . Service::count() . "\n";
    echo "Mechanics count: " . Mechanic::count() . "\n";
}

echo "\nTotal ratings in database: " . MechanicRating::count() . "\n";
echo "Done!\n";

?>
