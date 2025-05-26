#!/bin/bash

# Comprehensive fix script for the cumulative reports bug
# This script addresses the data loss issue and fixes the recalculation problem

set -e

echo "🚨 Fixing Cumulative Reports Bug"
echo "================================="
echo ""

echo "Step 1: Checking current system state..."
php artisan mechanic:manage-reports status

echo ""
echo "Step 2: Clearing all caches to ensure clean state..."
php artisan optimize:clear

echo ""
echo "Step 3: Checking for active mechanics..."
php artisan tinker --execute="
\$activeMechanics = \App\Models\Mechanic::where('is_active', true)->count();
\$totalMechanics = \App\Models\Mechanic::count();
echo 'Active mechanics: ' . \$activeMechanics . PHP_EOL;
echo 'Total mechanics: ' . \$totalMechanics . PHP_EOL;

if (\$activeMechanics === 0 && \$totalMechanics > 0) {
    echo 'WARNING: No active mechanics found, but mechanics exist.' . PHP_EOL;
    echo 'This might be causing the issue.' . PHP_EOL;
}
"

echo ""
echo "Step 4: Regenerating cumulative reports for all mechanics..."
php artisan tinker --execute="
echo 'Regenerating cumulative reports...' . PHP_EOL;

// Get all mechanics (both active and inactive to be safe)
\$mechanics = \App\Models\Mechanic::all();
\$created = 0;
\$updated = 0;
\$errors = 0;

foreach (\$mechanics as \$mechanic) {
    try {
        echo 'Processing: ' . \$mechanic->name . ' (Active: ' . (\$mechanic->is_active ? 'Yes' : 'No') . ')' . PHP_EOL;
        
        // Check if cumulative report exists
        \$existingReport = \$mechanic->cumulativeReport()->first();
        
        if (\$existingReport) {
            echo '  - Updating existing report...' . PHP_EOL;
            \$existingReport->recalculateCumulative();
            \$updated++;
        } else {
            echo '  - Creating new cumulative report...' . PHP_EOL;
            \$newReport = \$mechanic->getOrCreateCumulativeReport();
            \$created++;
        }
        
        // Verify the report exists after operation
        \$verifyReport = \$mechanic->cumulativeReport()->first();
        if (\$verifyReport) {
            echo '  - ✅ Report verified: ' . \$verifyReport->services_count . ' services, Rp ' . number_format(\$verifyReport->total_labor_cost, 0, ',', '.') . PHP_EOL;
        } else {
            echo '  - ❌ Report missing after operation!' . PHP_EOL;
            \$errors++;
        }
        
    } catch (\Exception \$e) {
        echo '  - ❌ Error: ' . \$e->getMessage() . PHP_EOL;
        \$errors++;
    }
}

echo PHP_EOL . 'Summary:' . PHP_EOL;
echo '- Created: ' . \$created . PHP_EOL;
echo '- Updated: ' . \$updated . PHP_EOL;
echo '- Errors: ' . \$errors . PHP_EOL;
"

echo ""
echo "Step 5: Final verification..."
php artisan mechanic:manage-reports status

echo ""
echo "Step 6: Testing the recalculation function..."
php artisan tinker --execute="
echo 'Testing recalculation function...' . PHP_EOL;

\$testReport = \App\Models\MechanicReport::where('is_cumulative', true)->first();

if (!\$testReport) {
    echo '❌ No cumulative reports found for testing!' . PHP_EOL;
    exit(1);
}

echo 'Testing with report ID: ' . \$testReport->id . ' for mechanic: ' . \$testReport->mechanic->name . PHP_EOL;

\$originalId = \$testReport->id;
\$originalCount = \$testReport->services_count;
\$originalCost = \$testReport->total_labor_cost;

echo 'Before: ' . \$originalCount . ' services, Rp ' . number_format(\$originalCost, 0, ',', '.') . PHP_EOL;

try {
    \$testReport->recalculateCumulative();
    
    // Check if report still exists
    \$reportExists = \App\Models\MechanicReport::find(\$originalId);
    
    if (\$reportExists) {
        \$reportExists->refresh();
        echo 'After: ' . \$reportExists->services_count . ' services, Rp ' . number_format(\$reportExists->total_labor_cost, 0, ',', '.') . PHP_EOL;
        echo '✅ Recalculation test PASSED - report preserved!' . PHP_EOL;
    } else {
        echo '❌ Recalculation test FAILED - report was deleted!' . PHP_EOL;
        echo 'The bug still exists and needs further investigation.' . PHP_EOL;
    }
    
} catch (\Exception \$e) {
    echo '❌ Recalculation test ERROR: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🎉 Fix script completed!"
echo ""
echo "Next steps:"
echo "1. Test the 'Perbarui' button in Filament admin"
echo "2. Monitor the application logs for any errors"
echo "3. If the issue persists, check the logs at storage/logs/laravel.log"
echo ""
echo "The recalculation method now includes:"
echo "- Database transaction for data integrity"
echo "- Detailed logging for debugging"
echo "- Proper error handling"
echo "- Model refresh after updates"
