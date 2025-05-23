<?php

/**
 * Test Script for Services Search Functionality Fix
 * 
 * This script verifies that the Services search functionality works correctly
 * after removing the 'full_details' column reference that was causing SQL errors.
 */

echo "=== Services Search Functionality Test ===\n\n";

// Check if we're in a Laravel environment
if (!function_exists('config')) {
    echo "❌ Not running in Laravel environment\n";
    echo "Please run this script from Laravel root directory\n";
    exit(1);
}

// 1. Check ServiceResource configuration
echo "1. ServiceResource Configuration Check:\n";
$resourcePath = app_path('Filament/Resources/ServiceResource.php');
if (file_exists($resourcePath)) {
    echo "   ✅ ServiceResource.php found\n";
    
    $content = file_get_contents($resourcePath);
    
    // Check if the problematic searchable vehicle.full_details is removed
    if (strpos($content, "Tables\Columns\TextColumn::make('vehicle.full_details')\n                    ->label('Kendaraan')\n                    ->searchable()") !== false) {
        echo "   ❌ vehicle.full_details still has ->searchable() (this will cause SQL errors)\n";
    } else {
        echo "   ✅ vehicle.full_details ->searchable() has been removed\n";
    }
    
    // Check if vehicle.full_details column still exists (should exist but without searchable)
    if (strpos($content, "Tables\Columns\TextColumn::make('vehicle.full_details')") !== false) {
        echo "   ✅ vehicle.full_details column still exists (for display only)\n";
    } else {
        echo "   ❌ vehicle.full_details column was completely removed\n";
    }
    
    // Check other searchable columns that should still work
    $searchableColumns = [
        'customer_name' => 'Customer name search',
        'phone' => 'Phone number search',
        'car_model' => 'Car model search',
        'license_plate' => 'License plate search',
        'service_type' => 'Service type search',
        'invoice_number' => 'Invoice number search',
    ];
    
    echo "\n   Searchable Columns Check:\n";
    foreach ($searchableColumns as $column => $description) {
        if (strpos($content, "Tables\Columns\TextColumn::make('$column')") !== false && 
            strpos($content, "->searchable()") !== false) {
            echo "      ✅ $description is searchable\n";
        } else {
            echo "      ⚠️  $description may not be searchable\n";
        }
    }
    
} else {
    echo "   ❌ ServiceResource.php not found\n";
}

// 2. Check Vehicle Model
echo "\n2. Vehicle Model Check:\n";
$vehicleModelPath = app_path('Models/Vehicle.php');
if (file_exists($vehicleModelPath)) {
    echo "   ✅ Vehicle model found\n";
    
    $content = file_get_contents($vehicleModelPath);
    
    // Check if full_details accessor exists
    if (strpos($content, 'getFullDetailsAttribute') !== false) {
        echo "   ✅ full_details accessor exists in Vehicle model\n";
    } else {
        echo "   ❌ full_details accessor not found in Vehicle model\n";
    }
    
    // Check Vehicle model fields
    $vehicleFields = ['model', 'license_plate', 'color', 'year'];
    echo "\n   Vehicle Model Fields Check:\n";
    foreach ($vehicleFields as $field) {
        if (strpos($content, "'$field'") !== false) {
            echo "      ✅ $field field exists\n";
        } else {
            echo "      ⚠️  $field field may not exist\n";
        }
    }
    
} else {
    echo "   ❌ Vehicle model not found\n";
}

// 3. Test Database Connection and Basic Query
echo "\n3. Database Connection Test:\n";
try {
    // Test basic database connection
    $servicesCount = \App\Models\Service::count();
    echo "   ✅ Database connection successful\n";
    echo "   ✅ Total services in database: $servicesCount\n";
    
    // Test if we can query services with vehicle relationship
    $servicesWithVehicles = \App\Models\Service::with('vehicle')->limit(1)->get();
    if ($servicesWithVehicles->count() > 0) {
        echo "   ✅ Services with vehicle relationship query successful\n";
        
        $service = $servicesWithVehicles->first();
        if ($service->vehicle) {
            echo "   ✅ Vehicle relationship is working\n";
            
            // Test full_details accessor
            try {
                $fullDetails = $service->vehicle->full_details;
                echo "   ✅ full_details accessor is working: '$fullDetails'\n";
            } catch (Exception $e) {
                echo "   ❌ full_details accessor error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ⚠️  Service found but no vehicle relationship\n";
        }
    } else {
        echo "   ⚠️  No services with vehicles found in database\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Database connection error: " . $e->getMessage() . "\n";
}

// 4. Test Search Query Simulation
echo "\n4. Search Query Simulation:\n";
try {
    // Simulate the search query that was causing the error
    $searchTerm = 'test';
    
    echo "   Testing search for term: '$searchTerm'\n";
    
    // This should work now without the full_details column in WHERE clause
    $query = \App\Models\Service::query()
        ->where(function ($query) use ($searchTerm) {
            $query->where('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%")
                  ->orWhere('car_model', 'like', "%{$searchTerm}%")
                  ->orWhere('license_plate', 'like', "%{$searchTerm}%")
                  ->orWhere('service_type', 'like', "%{$searchTerm}%")
                  ->orWhere('invoice_number', 'like', "%{$searchTerm}%");
        });
    
    $searchResults = $query->count();
    echo "   ✅ Search query executed successfully\n";
    echo "   ✅ Search results count: $searchResults\n";
    
    // Test the problematic query that was causing the error (should not be used anymore)
    echo "\n   Testing if problematic query still exists:\n";
    try {
        $problematicQuery = \App\Models\Service::query()
            ->whereHas('vehicle', function ($query) use ($searchTerm) {
                $query->where('full_details', 'like', "%{$searchTerm}%");
            });
        
        $problematicResults = $problematicQuery->count();
        echo "   ❌ Problematic query still works (this should not happen): $problematicResults results\n";
    } catch (Exception $e) {
        echo "   ✅ Problematic query correctly fails: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Search query error: " . $e->getMessage() . "\n";
}

// 5. Check Filament Table Configuration
echo "\n5. Filament Table Configuration:\n";
try {
    // Check if we can access the ServiceResource table configuration
    $resourceClass = \App\Filament\Resources\ServiceResource::class;
    if (class_exists($resourceClass)) {
        echo "   ✅ ServiceResource class exists\n";
        
        // Check if table method exists
        if (method_exists($resourceClass, 'table')) {
            echo "   ✅ table() method exists\n";
        } else {
            echo "   ❌ table() method not found\n";
        }
    } else {
        echo "   ❌ ServiceResource class not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Filament configuration error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "✅ = Configuration is correct\n";
echo "❌ = Issue that needs to be fixed\n";
echo "⚠️  = Warning - may need attention\n";

echo "\nIf all critical items show ✅, the Services search should work without SQL errors.\n";
echo "The vehicle.full_details column should still be visible but not searchable.\n";

echo "\n=== Test Instructions ===\n";
echo "1. Go to /admin/services in your browser\n";
echo "2. Try searching for various terms (customer names, phone numbers, etc.)\n";
echo "3. Verify that search works without 'Column not found: full_details' errors\n";
echo "4. Confirm that the 'Kendaraan' column still displays vehicle information\n";

echo "\n=== Expected Behavior ===\n";
echo "- Search should work for: customer_name, phone, car_model, license_plate, service_type, invoice_number\n";
echo "- Vehicle details should still be visible in the 'Kendaraan' column\n";
echo "- No SQL errors should occur when searching\n";
echo "- The 'Kendaraan' column should not be searchable (this is correct)\n";

?>
