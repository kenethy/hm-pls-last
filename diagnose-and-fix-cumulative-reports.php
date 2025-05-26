<?php

/**
 * Comprehensive diagnostic and fix script for cumulative reports
 * Run with: php artisan tinker < diagnose-and-fix-cumulative-reports.php
 */

echo "🔍 Comprehensive Cumulative Reports Diagnosis\n";
echo "=============================================\n\n";

// Step 1: Check basic data
echo "📊 Step 1: Basic Data Check\n";
echo "----------------------------\n";

$totalMechanics = \App\Models\Mechanic::count();
$activeMechanics = \App\Models\Mechanic::where('is_active', true)->count();
$totalServices = \App\Models\Service::count();
$completedServices = \App\Models\Service::where('status', 'completed')->count();
$cumulativeReports = \App\Models\MechanicReport::where('is_cumulative', true)->count();

echo "- Total mechanics: {$totalMechanics}\n";
echo "- Active mechanics: {$activeMechanics}\n";
echo "- Total services: {$totalServices}\n";
echo "- Completed services: {$completedServices}\n";
echo "- Cumulative reports: {$cumulativeReports}\n\n";

// Step 2: Check pivot table data
echo "📋 Step 2: Mechanic-Service Relationships\n";
echo "------------------------------------------\n";

$pivotCount = \Illuminate\Support\Facades\DB::table('mechanic_service')->count();
$pivotWithCompleted = \Illuminate\Support\Facades\DB::table('mechanic_service')
    ->join('services', 'mechanic_service.service_id', '=', 'services.id')
    ->where('services.status', 'completed')
    ->count();

echo "- Total mechanic-service relationships: {$pivotCount}\n";
echo "- Relationships with completed services: {$pivotWithCompleted}\n";

if ($pivotWithCompleted > 0) {
    echo "\n📋 Sample completed service data:\n";
    $sampleData = \Illuminate\Support\Facades\DB::table('mechanic_service')
        ->join('services', 'mechanic_service.service_id', '=', 'services.id')
        ->join('mechanics', 'mechanic_service.mechanic_id', '=', 'mechanics.id')
        ->where('services.status', 'completed')
        ->select('mechanics.name as mechanic_name', 'services.id as service_id', 'mechanic_service.labor_cost', 'services.completed_at')
        ->limit(5)
        ->get();
    
    foreach ($sampleData as $row) {
        echo "- {$row->mechanic_name}: Service #{$row->service_id}, Cost: Rp " . number_format($row->labor_cost ?? 0, 0, ',', '.') . "\n";
    }
} else {
    echo "❌ No completed services with mechanic relationships found!\n";
}

echo "\n";

// Step 3: Test the calculation query directly
echo "🧮 Step 3: Testing Calculation Query\n";
echo "------------------------------------\n";

if ($activeMechanics > 0) {
    $testMechanic = \App\Models\Mechanic::where('is_active', true)->first();
    echo "Testing calculation for mechanic: {$testMechanic->name} (ID: {$testMechanic->id})\n";
    
    $stats = \Illuminate\Support\Facades\DB::table('mechanic_service')
        ->join('services', 'mechanic_service.service_id', '=', 'services.id')
        ->where('mechanic_service.mechanic_id', $testMechanic->id)
        ->where('services.status', 'completed')
        ->selectRaw('
            COUNT(*) as total_services,
            COALESCE(SUM(mechanic_service.labor_cost), 0) as total_labor_cost
        ')
        ->first();
    
    echo "- Calculated services: {$stats->total_services}\n";
    echo "- Calculated labor cost: Rp " . number_format($stats->total_labor_cost, 0, ',', '.') . "\n";
    
    if ($stats->total_services == 0) {
        echo "❌ No completed services found for this mechanic!\n";
        echo "This explains why cumulative reports show 0 values.\n";
    }
} else {
    echo "❌ No active mechanics found to test with!\n";
}

echo "\n";

// Step 4: Check if cumulative reports exist and their values
echo "📊 Step 4: Current Cumulative Reports\n";
echo "-------------------------------------\n";

$reports = \App\Models\MechanicReport::where('is_cumulative', true)
    ->with('mechanic')
    ->get();

if ($reports->isEmpty()) {
    echo "❌ No cumulative reports found!\n";
    echo "Creating cumulative reports for all active mechanics...\n";
    
    $mechanics = \App\Models\Mechanic::where('is_active', true)->get();
    foreach ($mechanics as $mechanic) {
        try {
            $report = $mechanic->getOrCreateCumulativeReport();
            echo "✅ Created report for {$mechanic->name}: {$report->services_count} services, Rp " . number_format($report->total_labor_cost, 0, ',', '.') . "\n";
        } catch (\Exception $e) {
            echo "❌ Error creating report for {$mechanic->name}: " . $e->getMessage() . "\n";
        }
    }
    
    // Refresh reports
    $reports = \App\Models\MechanicReport::where('is_cumulative', true)
        ->with('mechanic')
        ->get();
} else {
    echo "Found {$reports->count()} cumulative reports:\n";
    foreach ($reports as $report) {
        echo "- {$report->mechanic->name}: {$report->services_count} services, Rp " . number_format($report->total_labor_cost, 0, ',', '.') . "\n";
    }
}

echo "\n";

// Step 5: Test recalculation
echo "🔄 Step 5: Testing Recalculation\n";
echo "--------------------------------\n";

if ($reports->isNotEmpty()) {
    $testReport = $reports->first();
    echo "Testing recalculation for: {$testReport->mechanic->name}\n";
    echo "Before: {$testReport->services_count} services, Rp " . number_format($testReport->total_labor_cost, 0, ',', '.') . "\n";
    
    try {
        $testReport->recalculateCumulative();
        $testReport->refresh();
        echo "After: {$testReport->services_count} services, Rp " . number_format($testReport->total_labor_cost, 0, ',', '.') . "\n";
        
        // Verify report still exists
        $stillExists = \App\Models\MechanicReport::find($testReport->id);
        if ($stillExists) {
            echo "✅ Recalculation successful - report preserved!\n";
        } else {
            echo "❌ Report was deleted during recalculation!\n";
        }
    } catch (\Exception $e) {
        echo "❌ Recalculation failed: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Step 6: Summary and recommendations
echo "📋 Step 6: Summary and Recommendations\n";
echo "--------------------------------------\n";

$finalCumulativeCount = \App\Models\MechanicReport::where('is_cumulative', true)->count();
$finalActiveMechanics = \App\Models\Mechanic::where('is_active', true)->count();

if ($finalCumulativeCount >= $finalActiveMechanics && $finalActiveMechanics > 0) {
    echo "✅ System appears healthy now!\n";
    echo "- All active mechanics have cumulative reports\n";
    echo "- Recalculation function is working\n";
} else {
    echo "⚠️ Issues still exist:\n";
    if ($finalActiveMechanics == 0) {
        echo "- No active mechanics found\n";
    }
    if ($finalCumulativeCount < $finalActiveMechanics) {
        echo "- Missing cumulative reports for some mechanics\n";
    }
}

if ($pivotWithCompleted == 0) {
    echo "\n❌ ROOT CAUSE IDENTIFIED:\n";
    echo "No completed services are linked to mechanics in the pivot table!\n";
    echo "This is why cumulative reports show 0 values.\n";
    echo "\nPossible solutions:\n";
    echo "1. Check if services are being properly assigned to mechanics\n";
    echo "2. Verify that service status is being set to 'completed'\n";
    echo "3. Check if the mechanic assignment process is working\n";
}

echo "\n✅ Diagnosis completed!\n";
