<?php

/**
 * Safe test script for the recalculate function
 * Run with: php artisan tinker < test-recalculate-safely.php
 */

echo "🧪 Safe Recalculation Test\n";
echo "=========================\n\n";

// First, let's check if we have any cumulative reports
$cumulativeReports = \App\Models\MechanicReport::where('is_cumulative', true)->get();

if ($cumulativeReports->isEmpty()) {
    echo "❌ No cumulative reports found. Running emergency recovery first...\n\n";
    
    // Create reports for all active mechanics
    $mechanics = \App\Models\Mechanic::where('is_active', true)->get();
    foreach ($mechanics as $mechanic) {
        echo "Creating cumulative report for: {$mechanic->name}\n";
        $report = $mechanic->getOrCreateCumulativeReport();
        echo "  - Created with {$report->services_count} services\n";
    }
    
    // Refresh the collection
    $cumulativeReports = \App\Models\MechanicReport::where('is_cumulative', true)->get();
    echo "\n✅ Created " . $cumulativeReports->count() . " cumulative reports\n\n";
}

echo "📊 Current Cumulative Reports:\n";
foreach ($cumulativeReports as $report) {
    echo "- ID {$report->id}: {$report->mechanic->name} - {$report->services_count} services, Rp " . number_format($report->total_labor_cost, 0, ',', '.') . "\n";
}

// Test with the first report
$testReport = $cumulativeReports->first();
if (!$testReport) {
    echo "❌ No test report available!\n";
    exit(1);
}

echo "\n🧪 Testing recalculation with report ID {$testReport->id} ({$testReport->mechanic->name})\n";

// Store original values
$originalId = $testReport->id;
$originalServicesCount = $testReport->services_count;
$originalLaborCost = $testReport->total_labor_cost;
$originalLastCalculated = $testReport->last_calculated_at;

echo "Before recalculation:\n";
echo "- ID: {$originalId}\n";
echo "- Services: {$originalServicesCount}\n";
echo "- Labor Cost: Rp " . number_format($originalLaborCost, 0, ',', '.') . "\n";
echo "- Last Calculated: " . ($originalLastCalculated ? $originalLastCalculated->format('Y-m-d H:i:s') : 'Never') . "\n";

// Check underlying data
echo "\nChecking underlying service data...\n";
$serviceData = \Illuminate\Support\Facades\DB::table('mechanic_service')
    ->join('services', 'mechanic_service.service_id', '=', 'services.id')
    ->where('mechanic_service.mechanic_id', $testReport->mechanic_id)
    ->where('services.status', 'completed')
    ->selectRaw('
        COUNT(*) as total_services,
        COALESCE(SUM(mechanic_service.labor_cost), 0) as total_labor_cost
    ')
    ->first();

echo "- Database shows: {$serviceData->total_services} services, Rp " . number_format($serviceData->total_labor_cost, 0, ',', '.') . "\n";

// Perform the test
echo "\n🔄 Performing recalculation...\n";

try {
    // Call the recalculate method
    $result = $testReport->recalculateCumulative();
    
    // Check if the report still exists
    $reportStillExists = \App\Models\MechanicReport::find($originalId);
    
    if (!$reportStillExists) {
        echo "❌ CRITICAL ERROR: Report was deleted during recalculation!\n";
        echo "This confirms the bug exists.\n";
        
        // Try to recreate the report
        echo "Attempting to recreate the report...\n";
        $mechanic = \App\Models\Mechanic::find($testReport->mechanic_id);
        if ($mechanic) {
            $newReport = $mechanic->getOrCreateCumulativeReport();
            echo "✅ Recreated report with ID {$newReport->id}\n";
        }
        
    } else {
        // Refresh the report to get latest data
        $testReport->refresh();
        
        echo "✅ Recalculation completed successfully!\n";
        echo "After recalculation:\n";
        echo "- ID: {$testReport->id} (unchanged: " . ($testReport->id === $originalId ? 'YES' : 'NO') . ")\n";
        echo "- Services: {$testReport->services_count} (was: {$originalServicesCount})\n";
        echo "- Labor Cost: Rp " . number_format($testReport->total_labor_cost, 0, ',', '.') . " (was: Rp " . number_format($originalLaborCost, 0, ',', '.') . ")\n";
        echo "- Last Calculated: " . ($testReport->last_calculated_at ? $testReport->last_calculated_at->format('Y-m-d H:i:s') : 'Never') . "\n";
        
        // Verify the data matches what we expect
        if ($testReport->services_count == $serviceData->total_services && 
            $testReport->total_labor_cost == $serviceData->total_labor_cost) {
            echo "✅ Data matches database calculations - recalculation is working correctly!\n";
        } else {
            echo "⚠️  Data mismatch detected:\n";
            echo "   Expected: {$serviceData->total_services} services, Rp " . number_format($serviceData->total_labor_cost, 0, ',', '.') . "\n";
            echo "   Got: {$testReport->services_count} services, Rp " . number_format($testReport->total_labor_cost, 0, ',', '.') . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error during recalculation: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Final system check
echo "\n📊 Final System Check:\n";
$finalCumulativeCount = \App\Models\MechanicReport::where('is_cumulative', true)->count();
echo "- Cumulative reports after test: {$finalCumulativeCount}\n";

if ($finalCumulativeCount < $cumulativeReports->count()) {
    echo "❌ CRITICAL: Reports were lost during the test!\n";
} else {
    echo "✅ No reports were lost during the test.\n";
}

echo "\n✅ Safe test completed!\n";
