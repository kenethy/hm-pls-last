<?php

/**
 * Test Script for Upload CSRF Configuration
 * 
 * This script helps verify that CSRF tokens and upload settings are properly configured
 */

echo "=== Upload CSRF Configuration Test ===\n\n";

// Check if we're in a Laravel environment
if (!function_exists('config')) {
    echo "❌ Not running in Laravel environment\n";
    echo "Please run this script from the Laravel root directory using: php test-upload-csrf.php\n";
    exit(1);
}

// Check CSRF token configuration
echo "1. CSRF Token Configuration:\n";
echo "   - Session driver: " . config('session.driver') . "\n";
echo "   - CSRF token lifetime: " . config('session.lifetime') . " minutes\n";

// Check Livewire configuration
echo "\n2. Livewire Upload Configuration:\n";
$livewireConfig = config('livewire.temporary_file_upload');
if ($livewireConfig) {
    echo "   - Disk: " . $livewireConfig['disk'] . "\n";
    echo "   - Rules: " . implode(', ', $livewireConfig['rules']) . "\n";
    echo "   - Directory: " . $livewireConfig['directory'] . "\n";
    echo "   - Middleware: " . implode(', ', $livewireConfig['middleware']) . "\n";
    echo "   - Max upload time: " . $livewireConfig['max_upload_time'] . " minutes\n";
} else {
    echo "   ❌ Livewire upload configuration not found\n";
}

// Check Filament configuration
echo "\n3. Filament Configuration:\n";
try {
    $filamentConfig = config('filament');
    if ($filamentConfig) {
        echo "   ✅ Filament configuration found\n";
    } else {
        echo "   ❌ Filament configuration not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error loading Filament configuration: " . $e->getMessage() . "\n";
}

// Check middleware configuration
echo "\n4. Middleware Configuration:\n";
$kernelPath = app_path('Http/Kernel.php');
if (file_exists($kernelPath)) {
    $kernelContent = file_get_contents($kernelPath);
    
    if (strpos($kernelContent, 'IncreaseUploadLimits') !== false) {
        echo "   ✅ IncreaseUploadLimits middleware is registered\n";
    } else {
        echo "   ❌ IncreaseUploadLimits middleware not found in Kernel.php\n";
    }
    
    if (strpos($kernelContent, 'VerifyCsrfToken') !== false) {
        echo "   ✅ CSRF verification middleware is registered\n";
    } else {
        echo "   ❌ CSRF verification middleware not found\n";
    }
} else {
    echo "   ❌ Kernel.php file not found\n";
}

// Check storage configuration
echo "\n5. Storage Configuration:\n";
try {
    $publicDisk = config('filesystems.disks.public');
    if ($publicDisk) {
        echo "   - Public disk driver: " . $publicDisk['driver'] . "\n";
        echo "   - Public disk root: " . $publicDisk['root'] . "\n";
        echo "   - Public disk URL: " . $publicDisk['url'] . "\n";
        echo "   - Public disk visibility: " . ($publicDisk['visibility'] ?? 'public') . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking storage configuration: " . $e->getMessage() . "\n";
}

// Check if storage link exists
echo "\n6. Storage Link:\n";
$storageLinkPath = public_path('storage');
if (is_link($storageLinkPath)) {
    echo "   ✅ Storage link exists\n";
} else {
    echo "   ❌ Storage link not found - run 'php artisan storage:link'\n";
}

// Check upload directories
echo "\n7. Upload Directories:\n";
$directories = ['promos', 'galleries', 'livewire-tmp'];
foreach ($directories as $dir) {
    $path = storage_path('app/public/' . $dir);
    if (is_dir($path)) {
        echo "   ✅ Directory exists: $dir\n";
        if (is_writable($path)) {
            echo "      - Writable: Yes\n";
        } else {
            echo "      - Writable: No ❌\n";
        }
    } else {
        echo "   ❌ Directory missing: $dir\n";
    }
}

// Check PHP configuration
echo "\n8. PHP Upload Configuration:\n";
$phpSettings = [
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
];

foreach ($phpSettings as $setting => $value) {
    echo "   - $setting: $value\n";
}

// Check for conflicting JavaScript files
echo "\n9. JavaScript Files Check:\n";
$jsFiles = [
    'custom-upload.js',
    'enhanced-gallery-fix.js',
    'promo-upload-fix.js',
    'livewire-docker-fix.js',
    'livewire-upload-size-fix.js'
];

foreach ($jsFiles as $jsFile) {
    $path = public_path('js/' . $jsFile);
    if (file_exists($path)) {
        echo "   ⚠️  Conflicting file exists: $jsFile (should be removed or disabled)\n";
    } else {
        echo "   ✅ No conflict: $jsFile not found\n";
    }
}

// Check Filament layout
echo "\n10. Filament Layout Check:\n";
$layoutPath = resource_path('views/vendor/filament-panels/components/layout/base.blade.php');
if (file_exists($layoutPath)) {
    $layoutContent = file_get_contents($layoutPath);
    
    if (strpos($layoutContent, 'csrf-token') !== false) {
        echo "    ✅ CSRF token meta tag found in layout\n";
    } else {
        echo "    ❌ CSRF token meta tag not found in layout\n";
    }
    
    if (strpos($layoutContent, 'Clean Filament Upload Configuration') !== false) {
        echo "    ✅ Clean upload configuration script found\n";
    } else {
        echo "    ❌ Clean upload configuration script not found\n";
    }
    
    // Check for conflicting scripts
    $conflictingScripts = [
        'custom-upload.js',
        'enhanced-gallery-fix.js',
        'promo-upload-fix.js'
    ];
    
    $hasConflicts = false;
    foreach ($conflictingScripts as $script) {
        if (strpos($layoutContent, $script) !== false) {
            echo "    ⚠️  Conflicting script found in layout: $script\n";
            $hasConflicts = true;
        }
    }
    
    if (!$hasConflicts) {
        echo "    ✅ No conflicting scripts found in layout\n";
    }
} else {
    echo "    ❌ Filament layout file not found\n";
}

echo "\n=== Summary ===\n";
echo "✅ = Configuration is correct\n";
echo "❌ = Issue that needs to be fixed\n";
echo "⚠️  = Warning - may cause conflicts\n";

echo "\nIf all items show ✅, the upload configuration should work correctly.\n";
echo "If you see ❌ or ⚠️  items, please address them before testing uploads.\n";

?>
