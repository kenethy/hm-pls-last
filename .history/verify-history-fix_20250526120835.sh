#!/bin/bash

# Simple verification script for the mechanic history fix

echo "🔧 Verifying Mechanic Service History Fix"
echo "========================================="
echo ""

echo "Step 1: Clearing caches..."
php artisan view:clear
php artisan route:clear
php artisan config:clear

echo ""
echo "Step 2: Checking route registration..."
php artisan route:list | grep "mechanic.services.history" || echo "Route not found!"

echo ""
echo "Step 3: Checking if MechanicReport model exists..."
php artisan tinker --execute="
try {
    \$count = \App\Models\MechanicReport::count();
    echo 'MechanicReport model works - found ' . \$count . ' reports' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'MechanicReport model error: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "Step 4: Checking view file..."
if [ -f "resources/views/mechanic-services/history.blade.php" ]; then
    echo "✅ View file exists"
    echo "Checking for problematic code..."
    if grep -q "week_start->format" resources/views/mechanic-services/history.blade.php; then
        echo "❌ Still contains problematic week_start->format code"
    else
        echo "✅ Problematic code has been fixed"
    fi
else
    echo "❌ View file not found"
fi

echo ""
echo "Step 5: Checking controller file..."
if [ -f "app/Http/Controllers/MechanicServiceHistoryController.php" ]; then
    echo "✅ Controller file exists"
    echo "Checking for problematic query..."
    if grep -q "week_start.*week_end" app/Http/Controllers/MechanicServiceHistoryController.php; then
        echo "⚠️  Controller still contains week_start/week_end references (may be legacy support)"
    else
        echo "✅ Controller has been updated"
    fi
else
    echo "❌ Controller file not found"
fi

echo ""
echo "✅ Verification completed!"
echo ""
echo "The fix should resolve the following issues:"
echo "1. ❌ Call to a member function format() on null"
echo "2. ✅ Proper handling of cumulative vs period-based reports"
echo "3. ✅ Display 'Kumulatif (semua waktu)' for cumulative reports"
echo "4. ✅ Show all services for cumulative reports"
echo ""
echo "To test:"
echo "1. Go to /admin/mechanic-reports"
echo "2. Click 'Riwayat Servis' button for any cumulative report"
echo "3. Page should load without errors"
echo "4. Should show 'Periode: Kumulatif (semua waktu)'"
