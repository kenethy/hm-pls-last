<?php

/**
 * Test script to verify the mechanic service history fix
 * Run with: php artisan tinker < test-mechanic-history-fix.php
 */

echo "🧪 Testing Mechanic Service History Fix\n";
echo "======================================\n\n";

// Check if we have cumulative reports to test with
$cumulativeReports = \App\Models\MechanicReport::where('is_cumulative', true)
    ->with('mechanic')
    ->get();

echo "📊 Current System State:\n";
echo "- Cumulative reports: " . $cumulativeReports->count() . "\n";
echo "- Total mechanics: " . \App\Models\Mechanic::count() . "\n";
echo "- Total services: " . \App\Models\Service::count() . "\n";
echo "- Completed services: " . \App\Models\Service::where('status', 'completed')->count() . "\n\n";

if ($cumulativeReports->isEmpty()) {
    echo "❌ No cumulative reports found for testing!\n";
    echo "Creating a test report...\n";
    
    $mechanic = \App\Models\Mechanic::where('is_active', true)->first();
    if (!$mechanic) {
        echo "❌ No active mechanics found! Cannot proceed with test.\n";
        exit(1);
    }
    
    $report = $mechanic->getOrCreateCumulativeReport();
    echo "✅ Created test report for {$mechanic->name}\n\n";
    
    $cumulativeReports = collect([$report]);
}

// Test the controller logic for each cumulative report
foreach ($cumulativeReports as $record) {
    echo "🔍 Testing report for mechanic: {$record->mechanic->name}\n";
    echo "   Report ID: {$record->id}\n";
    echo "   Is cumulative: " . ($record->is_cumulative ? 'Yes' : 'No') . "\n";
    echo "   Services count: {$record->services_count}\n";
    echo "   Total labor cost: Rp " . number_format($record->total_labor_cost, 0, ',', '.') . "\n";
    
    // Test the date display logic
    echo "   Period display: ";
    if ($record->is_cumulative) {
        echo "Kumulatif (semua waktu)";
        if ($record->period_reset_at) {
            echo " - sejak " . $record->period_reset_at->format('d M Y');
        }
    } elseif ($record->week_start && $record->week_end) {
        echo $record->week_start->format('d M Y') . " - " . $record->week_end->format('d M Y');
    } elseif ($record->period_start && $record->period_end) {
        echo $record->period_start->format('d M Y') . " - " . $record->period_end->format('d M Y');
    } else {
        echo "Periode tidak ditentukan";
    }
    echo "\n";
    
    // Test the service query logic
    echo "   Testing service query...\n";
    
    try {
        $servicesQuery = \App\Models\Service::query()
            ->join('mechanic_service', 'services.id', '=', 'mechanic_service.service_id')
            ->where('mechanic_service.mechanic_id', $record->mechanic_id)
            ->select('services.*', 'mechanic_service.invoice_number', 'mechanic_service.labor_cost');

        // Apply period filtering based on report type
        if ($record->is_cumulative) {
            // For cumulative reports, show all services for this mechanic
            if ($record->period_reset_at) {
                $servicesQuery->where('services.created_at', '>=', $record->period_reset_at);
            }
        } else {
            // For period-based reports, filter by the specific period
            if ($record->period_start && $record->period_end) {
                $servicesQuery->whereBetween('services.created_at', [
                    $record->period_start,
                    $record->period_end->endOfDay()
                ]);
            } elseif ($record->week_start && $record->week_end) {
                // Legacy weekly reports
                $servicesQuery->where('mechanic_service.week_start', $record->week_start)
                             ->where('mechanic_service.week_end', $record->week_end);
            }
        }

        $allServices = $servicesQuery->orderBy('services.created_at', 'desc')->get();
        $completedServices = $allServices->where('status', 'completed');
        
        echo "   ✅ Query executed successfully!\n";
        echo "   - Total services found: " . $allServices->count() . "\n";
        echo "   - Completed services: " . $completedServices->count() . "\n";
        
        if ($allServices->count() > 0) {
            $sampleService = $allServices->first();
            echo "   - Sample service: #{$sampleService->id} - {$sampleService->status}\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Query failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test URL generation
echo "🔗 Testing URL Generation:\n";
if ($cumulativeReports->isNotEmpty()) {
    $testReport = $cumulativeReports->first();
    $url = route('mechanic.services.history', $testReport->id);
    echo "- Test URL: {$url}\n";
    echo "- Report ID: {$testReport->id}\n";
    echo "- Mechanic: {$testReport->mechanic->name}\n";
}

echo "\n✅ Test completed!\n";
echo "\nNext steps:\n";
echo "1. Visit the mechanic reports page: /admin/mechanic-reports\n";
echo "2. Click the 'Riwayat Servis' button for any cumulative report\n";
echo "3. Verify the page loads without errors\n";
echo "4. Check that the period displays as 'Kumulatif (semua waktu)'\n";
echo "5. Verify that all services for the mechanic are shown\n";
