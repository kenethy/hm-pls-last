<?php

/**
 * Emergency script to restore lost cumulative reports
 * Run with: php artisan tinker < emergency-restore-cumulative-reports.php
 */

echo "🚨 Emergency Cumulative Reports Recovery\n";
echo "=======================================\n\n";

// Check current state
echo "📊 Checking current system state...\n";
$cumulativeCount = \App\Models\MechanicReport::where('is_cumulative', true)->count();
$totalReports = \App\Models\MechanicReport::count();
$activeMechanics = \App\Models\Mechanic::where('is_active', true)->count();
$archiveCount = \App\Models\MechanicReportArchive::count();

echo "- Active mechanics: {$activeMechanics}\n";
echo "- Total reports: {$totalReports}\n";
echo "- Cumulative reports: {$cumulativeCount}\n";
echo "- Archived reports: {$archiveCount}\n\n";

if ($cumulativeCount >= $activeMechanics) {
    echo "✅ System appears to have cumulative reports for most mechanics.\n";
    echo "Do you still want to proceed with recovery? This will recalculate all reports.\n";
} else {
    echo "❌ Missing cumulative reports detected. Proceeding with recovery...\n";
}

echo "\n🔄 Starting recovery process...\n";

$recoveredCount = 0;
$errorCount = 0;

// Get all active mechanics
$mechanics = \App\Models\Mechanic::where('is_active', true)->get();

foreach ($mechanics as $mechanic) {
    try {
        echo "Processing mechanic: {$mechanic->name} (ID: {$mechanic->id})...\n";
        
        // Check if cumulative report exists
        $existingReport = $mechanic->cumulativeReport()->first();
        
        if ($existingReport) {
            echo "  - Found existing cumulative report (ID: {$existingReport->id})\n";
            echo "  - Current: {$existingReport->services_count} services, Rp " . number_format($existingReport->total_labor_cost, 0, ',', '.') . "\n";
            
            // Recalculate the existing report
            $existingReport->recalculateCumulative();
            $existingReport->refresh();
            
            echo "  - Updated: {$existingReport->services_count} services, Rp " . number_format($existingReport->total_labor_cost, 0, ',', '.') . "\n";
        } else {
            echo "  - No cumulative report found, creating new one...\n";
            
            // Create new cumulative report
            $newReport = $mechanic->getOrCreateCumulativeReport();
            
            echo "  - Created: {$newReport->services_count} services, Rp " . number_format($newReport->total_labor_cost, 0, ',', '.') . "\n";
        }
        
        $recoveredCount++;
        
    } catch (\Exception $e) {
        echo "  - ❌ Error: " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo "\n📊 Recovery Summary:\n";
echo "- Mechanics processed: " . count($mechanics) . "\n";
echo "- Successfully recovered: {$recoveredCount}\n";
echo "- Errors encountered: {$errorCount}\n";

// Final verification
$finalCumulativeCount = \App\Models\MechanicReport::where('is_cumulative', true)->count();
$finalTotalReports = \App\Models\MechanicReport::count();

echo "\n📈 Final System State:\n";
echo "- Total reports: {$finalTotalReports} (was: {$totalReports})\n";
echo "- Cumulative reports: {$finalCumulativeCount} (was: {$cumulativeCount})\n";

if ($finalCumulativeCount >= $activeMechanics) {
    echo "✅ Recovery successful! All active mechanics now have cumulative reports.\n";
} else {
    echo "⚠️  Recovery incomplete. Some mechanics may still be missing cumulative reports.\n";
}

echo "\n🔍 Detailed Report Summary:\n";
$reports = \App\Models\MechanicReport::where('is_cumulative', true)
    ->with('mechanic')
    ->get();

foreach ($reports as $report) {
    echo "- {$report->mechanic->name}: {$report->services_count} services, Rp " . number_format($report->total_labor_cost, 0, ',', '.') . "\n";
}

echo "\n✅ Emergency recovery completed!\n";
echo "You can now test the 'Perbarui' button in the Filament admin interface.\n";
