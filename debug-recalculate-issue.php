<?php

/**
 * Debug script to investigate the recalculate issue
 * Run with: php artisan tinker < debug-recalculate-issue.php
 */

echo "🔍 Debugging Cumulative Report Recalculation Issue...\n";
echo "====================================================\n\n";

// Check current state
echo "📊 Current System State:\n";
$cumulativeCount = \App\Models\MechanicReport::where('is_cumulative', true)->count();
$totalReports = \App\Models\MechanicReport::count();
$activeMechanics = \App\Models\Mechanic::where('is_active', true)->count();

echo "- Total reports: {$totalReports}\n";
echo "- Cumulative reports: {$cumulativeCount}\n";
echo "- Active mechanics: {$activeMechanics}\n\n";

// Check if we have any cumulative reports to test with
if ($cumulativeCount === 0) {
    echo "❌ No cumulative reports found! Let's create one for testing...\n";
    
    $mechanic = \App\Models\Mechanic::where('is_active', true)->first();
    if (!$mechanic) {
        echo "❌ No active mechanics found! Cannot proceed with test.\n";
        exit(1);
    }
    
    echo "Creating cumulative report for mechanic: {$mechanic->name}\n";
    $report = $mechanic->getOrCreateCumulativeReport();
    echo "✅ Created cumulative report ID: {$report->id}\n\n";
} else {
    echo "✅ Found {$cumulativeCount} cumulative reports\n\n";
}

// Get a test report
$testReport = \App\Models\MechanicReport::where('is_cumulative', true)->first();

if (!$testReport) {
    echo "❌ Could not get a test report!\n";
    exit(1);
}

echo "🧪 Testing with report ID: {$testReport->id} for mechanic: {$testReport->mechanic->name}\n";
echo "Before recalculation:\n";
echo "- Services count: {$testReport->services_count}\n";
echo "- Total labor cost: {$testReport->total_labor_cost}\n";
echo "- Last calculated: " . ($testReport->last_calculated_at ? $testReport->last_calculated_at->format('Y-m-d H:i:s') : 'Never') . "\n\n";

// Check the underlying data
echo "🔍 Checking underlying service data for mechanic ID {$testReport->mechanic_id}:\n";

$serviceData = \Illuminate\Support\Facades\DB::table('mechanic_service')
    ->join('services', 'mechanic_service.service_id', '=', 'services.id')
    ->where('mechanic_service.mechanic_id', $testReport->mechanic_id)
    ->where('services.status', 'completed')
    ->selectRaw('
        COUNT(*) as total_services,
        COALESCE(SUM(mechanic_service.labor_cost), 0) as total_labor_cost,
        MIN(services.created_at) as first_service,
        MAX(services.created_at) as last_service
    ')
    ->first();

echo "- Completed services in database: {$serviceData->total_services}\n";
echo "- Total labor cost in database: {$serviceData->total_labor_cost}\n";
echo "- First service: " . ($serviceData->first_service ?? 'None') . "\n";
echo "- Last service: " . ($serviceData->last_service ?? 'None') . "\n\n";

// Test the recalculation
echo "🔄 Testing recalculateCumulative() method...\n";

try {
    // Store original values
    $originalServicesCount = $testReport->services_count;
    $originalLaborCost = $testReport->total_labor_cost;
    
    // Call recalculate
    $result = $testReport->recalculateCumulative();
    
    // Refresh from database
    $testReport->refresh();
    
    echo "✅ Recalculation completed successfully!\n";
    echo "After recalculation:\n";
    echo "- Services count: {$testReport->services_count} (was: {$originalServicesCount})\n";
    echo "- Total labor cost: {$testReport->total_labor_cost} (was: {$originalLaborCost})\n";
    echo "- Last calculated: " . ($testReport->last_calculated_at ? $testReport->last_calculated_at->format('Y-m-d H:i:s') : 'Never') . "\n\n";
    
    // Check if the report still exists
    $reportExists = \App\Models\MechanicReport::find($testReport->id);
    if ($reportExists) {
        echo "✅ Report still exists in database\n";
    } else {
        echo "❌ CRITICAL: Report was deleted from database!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error during recalculation: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🔍 Final system check:\n";
$finalCumulativeCount = \App\Models\MechanicReport::where('is_cumulative', true)->count();
$finalTotalReports = \App\Models\MechanicReport::count();

echo "- Total reports: {$finalTotalReports} (was: {$totalReports})\n";
echo "- Cumulative reports: {$finalCumulativeCount} (was: {$cumulativeCount})\n";

if ($finalCumulativeCount < $cumulativeCount) {
    echo "❌ CRITICAL: Cumulative reports were lost during the test!\n";
    echo "This confirms the bug exists.\n";
} else {
    echo "✅ No reports were lost during the test.\n";
}

echo "\n✅ Debug test completed!\n";
