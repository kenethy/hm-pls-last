<?php

/**
 * Test script to verify that all Heroicons used in MechanicReportResource are valid
 * Run this with: php artisan tinker < test-mechanic-reports-icons.php
 */

echo "🔍 Testing Heroicons in Mechanic Reports...\n";

// List of all icons used in MechanicReportResource
$iconsToTest = [
    'heroicon-o-chart-bar',              // Navigation icon & cumulative reports
    'heroicon-o-calendar-days',          // Period reports
    'heroicon-o-check-circle',           // Paid status
    'heroicon-o-x-circle',               // Unpaid status
    'heroicon-o-arrow-path',             // Recalculate action
    'heroicon-o-arrow-uturn-left',       // Reset action
    'heroicon-o-clipboard-document-list', // View services action
    'heroicon-o-archive-box',            // Archive resource navigation
];

echo "Testing " . count($iconsToTest) . " icons...\n\n";

$allValid = true;

foreach ($iconsToTest as $icon) {
    try {
        // Try to resolve the icon using Filament's icon resolver
        $iconHtml = \Filament\Support\Facades\FilamentIcon::resolve($icon);
        
        if ($iconHtml) {
            echo "✅ {$icon} - Valid\n";
        } else {
            echo "❌ {$icon} - Not found\n";
            $allValid = false;
        }
    } catch (Exception $e) {
        echo "❌ {$icon} - Error: " . $e->getMessage() . "\n";
        $allValid = false;
    }
}

echo "\n";

if ($allValid) {
    echo "🎉 All icons are valid! The mechanic reports page should load without icon errors.\n";
} else {
    echo "⚠️  Some icons are invalid. Please check the icons marked with ❌ above.\n";
}

echo "\n📊 Testing MechanicReport model access...\n";

try {
    $reportCount = \App\Models\MechanicReport::count();
    echo "✅ MechanicReport model accessible - {$reportCount} reports found\n";
} catch (Exception $e) {
    echo "❌ MechanicReport model error: " . $e->getMessage() . "\n";
}

echo "\n📦 Testing MechanicReportArchive model access...\n";

try {
    $archiveCount = \App\Models\MechanicReportArchive::count();
    echo "✅ MechanicReportArchive model accessible - {$archiveCount} archived reports found\n";
} catch (Exception $e) {
    echo "❌ MechanicReportArchive model error: " . $e->getMessage() . "\n";
}

echo "\n🔧 Testing cumulative system status...\n";

try {
    $cumulativeReports = \App\Models\MechanicReport::where('is_cumulative', true)->count();
    $weeklyReports = \App\Models\MechanicReport::where('is_cumulative', false)->count();
    
    echo "✅ Cumulative reports: {$cumulativeReports}\n";
    echo "✅ Weekly reports (legacy): {$weeklyReports}\n";
    
    if ($cumulativeReports > 0 && $weeklyReports === 0) {
        echo "🎉 Migration to cumulative system completed successfully!\n";
    } elseif ($weeklyReports > 0) {
        echo "⚠️  Still have weekly reports - migration may not be complete\n";
    } else {
        echo "ℹ️  No reports found - system is ready for new cumulative reports\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking cumulative system: " . $e->getMessage() . "\n";
}

echo "\n✅ Icon test completed!\n";
echo "You can now access the mechanic reports at: /admin/mechanic-reports\n";
