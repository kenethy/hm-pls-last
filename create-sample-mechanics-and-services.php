<?php

/**
 * Create sample mechanics and services for testing cumulative reports
 * Run with: php artisan tinker < create-sample-mechanics-and-services.php
 */

echo "🔧 Creating Sample Mechanics and Services\n";
echo "=========================================\n\n";

// Check if we already have data
$existingMechanics = \App\Models\Mechanic::count();
$existingServices = \App\Models\Service::count();

echo "Current data:\n";
echo "- Mechanics: {$existingMechanics}\n";
echo "- Services: {$existingServices}\n\n";

if ($existingMechanics > 0) {
    echo "✅ Mechanics already exist. Skipping mechanic creation.\n";
} else {
    echo "🔧 Creating sample mechanics...\n";
    
    $mechanics = [
        ['name' => 'Ahmad Wijaya', 'phone' => '081234567890', 'specialization' => 'Engine Specialist'],
        ['name' => 'Budi Santoso', 'phone' => '081234567891', 'specialization' => 'Transmission Expert'],
        ['name' => 'Candra Kusuma', 'phone' => '081234567892', 'specialization' => 'Electrical Systems'],
        ['name' => 'Dedi Pratama', 'phone' => '081234567893', 'specialization' => 'Brake Systems'],
        ['name' => 'Eko Saputra', 'phone' => '081234567894', 'specialization' => 'General Maintenance'],
    ];
    
    foreach ($mechanics as $mechanicData) {
        $mechanic = \App\Models\Mechanic::create([
            'name' => $mechanicData['name'],
            'phone' => $mechanicData['phone'],
            'specialization' => $mechanicData['specialization'],
            'is_active' => true,
            'notes' => 'Sample mechanic for testing cumulative reports',
        ]);
        echo "✅ Created mechanic: {$mechanic->name}\n";
    }
}

// Check if we have customers
$existingCustomers = \App\Models\Customer::count();
if ($existingCustomers === 0) {
    echo "\n👥 Creating sample customers...\n";
    
    $customers = [
        ['name' => 'John Doe', 'phone' => '081111111111'],
        ['name' => 'Jane Smith', 'phone' => '081111111112'],
        ['name' => 'Bob Johnson', 'phone' => '081111111113'],
    ];
    
    foreach ($customers as $customerData) {
        $customer = \App\Models\Customer::create([
            'name' => $customerData['name'],
            'phone' => $customerData['phone'],
        ]);
        echo "✅ Created customer: {$customer->name}\n";
    }
}

// Create sample services if needed
if ($existingServices < 10) {
    echo "\n🔧 Creating sample completed services...\n";
    
    $mechanics = \App\Models\Mechanic::where('is_active', true)->get();
    $customers = \App\Models\Customer::take(3)->get();
    
    if ($mechanics->isEmpty() || $customers->isEmpty()) {
        echo "❌ Cannot create services - no mechanics or customers available!\n";
        exit(1);
    }
    
    for ($i = 1; $i <= 15; $i++) {
        $customer = $customers->random();
        
        $service = \App\Models\Service::create([
            'customer_id' => $customer->id,
            'vehicle_license_plate' => 'B ' . (1000 + $i) . ' ABC',
            'vehicle_model' => ['Toyota Avanza', 'Honda Jazz', 'Suzuki Ertiga', 'Daihatsu Xenia', 'Mitsubishi Xpander'][array_rand(['Toyota Avanza', 'Honda Jazz', 'Suzuki Ertiga', 'Daihatsu Xenia', 'Mitsubishi Xpander'])],
            'service_type' => ['maintenance', 'repair', 'inspection'][array_rand(['maintenance', 'repair', 'inspection'])],
            'description' => 'Sample service #' . $i . ' for testing cumulative reports',
            'status' => 'completed',
            'completed_at' => now()->subDays(rand(1, 30)),
            'total_cost' => rand(100000, 500000),
        ]);
        
        // Assign 1-2 random mechanics to this service
        $assignedMechanics = $mechanics->random(rand(1, 2));
        
        foreach ($assignedMechanics as $mechanic) {
            $laborCost = rand(50000, 200000);
            
            $service->mechanics()->attach($mechanic->id, [
                'labor_cost' => $laborCost,
                'notes' => 'Sample work by ' . $mechanic->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        echo "✅ Created service #{$service->id} with " . $assignedMechanics->count() . " mechanics\n";
    }
}

echo "\n📊 Final data summary:\n";
$finalMechanics = \App\Models\Mechanic::count();
$finalActiveMechanics = \App\Models\Mechanic::where('is_active', true)->count();
$finalServices = \App\Models\Service::count();
$finalCompletedServices = \App\Models\Service::where('status', 'completed')->count();
$finalPivotCount = \Illuminate\Support\Facades\DB::table('mechanic_service')->count();

echo "- Total mechanics: {$finalMechanics}\n";
echo "- Active mechanics: {$finalActiveMechanics}\n";
echo "- Total services: {$finalServices}\n";
echo "- Completed services: {$finalCompletedServices}\n";
echo "- Mechanic-service relationships: {$finalPivotCount}\n";

echo "\n🔄 Now creating cumulative reports...\n";

$mechanics = \App\Models\Mechanic::where('is_active', true)->get();
foreach ($mechanics as $mechanic) {
    try {
        $report = $mechanic->getOrCreateCumulativeReport();
        echo "✅ Created cumulative report for {$mechanic->name}: {$report->services_count} services, Rp " . number_format($report->total_labor_cost, 0, ',', '.') . "\n";
    } catch (\Exception $e) {
        echo "❌ Error creating report for {$mechanic->name}: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Sample data creation completed!\n";
echo "You can now test the cumulative reports in the Filament admin interface.\n";
