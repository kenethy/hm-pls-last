<?php

/**
 * Test Script for Enhanced Gallery Upload Configuration
 * 
 * This script verifies that the Enhanced Gallery upload functionality is properly configured
 * using the same clean approach as the promotion upload fix.
 */

echo "=== Enhanced Gallery Upload Configuration Test ===\n\n";

// Check if we're in a Laravel environment
if (!function_exists('config')) {
    echo "❌ Not running in Laravel environment\n";
    echo "Please run this script from Laravel root directory\n";
    exit(1);
}

// Check Enhanced Gallery Resource
echo "1. Enhanced Gallery Resource Check:\n";
$resourcePath = app_path('Filament/Resources/EnhancedGalleryResource.php');
if (file_exists($resourcePath)) {
    echo "   ✅ EnhancedGalleryResource.php found\n";
    
    $resourceContent = file_get_contents($resourcePath);
    
    // Check for standard FileUpload component
    if (strpos($resourceContent, 'Forms\Components\FileUpload::make(\'image_path\')') !== false) {
        echo "   ✅ Standard Filament FileUpload component found\n";
    } else {
        echo "   ❌ Standard FileUpload component not found\n";
    }
    
    // Check for proper configuration
    $configChecks = [
        'maxSize(10240)' => 'File size limit (10MB)',
        'acceptedFileTypes' => 'File type validation',
        'directory(\'galleries\')' => 'Upload directory',
        'disk(\'public\')' => 'Storage disk',
        'visibility(\'public\')' => 'File visibility',
    ];
    
    foreach ($configChecks as $check => $description) {
        if (strpos($resourceContent, $check) !== false) {
            echo "   ✅ $description configured\n";
        } else {
            echo "   ❌ $description not configured\n";
        }
    }
} else {
    echo "   ❌ EnhancedGalleryResource.php not found\n";
}

// Check Gallery Model
echo "\n2. Gallery Model Check:\n";
$modelPath = app_path('Models/Gallery.php');
if (file_exists($modelPath)) {
    echo "   ✅ Gallery model found\n";
    
    $modelContent = file_get_contents($modelPath);
    
    // Check fillable fields
    if (strpos($modelContent, '\'image_path\'') !== false) {
        echo "   ✅ image_path field is fillable\n";
    } else {
        echo "   ❌ image_path field not in fillable array\n";
    }
} else {
    echo "   ❌ Gallery model not found\n";
}

// Check for conflicting JavaScript files
echo "\n3. JavaScript Conflicts Check:\n";
$jsFiles = [
    'enhanced-gallery-fix.js' => 'Enhanced Gallery Fix (should be disabled)',
    'enhanced-gallery-direct-fix.js' => 'Enhanced Gallery Direct Fix (should be disabled)',
    'custom-upload.js' => 'Custom Upload (should be disabled)',
];

foreach ($jsFiles as $jsFile => $description) {
    $activePath = public_path('js/' . $jsFile);
    $disabledPath = public_path('js/' . $jsFile . '.disabled');
    
    if (file_exists($activePath)) {
        echo "   ⚠️  $description is still active (should be disabled)\n";
    } elseif (file_exists($disabledPath)) {
        echo "   ✅ $description is properly disabled\n";
    } else {
        echo "   ✅ $description not found (good)\n";
    }
}

// Check Filament layout for clean configuration
echo "\n4. Filament Layout Check:\n";
$layoutPath = resource_path('views/vendor/filament-panels/components/layout/base.blade.php');
if (file_exists($layoutPath)) {
    $layoutContent = file_get_contents($layoutPath);
    
    if (strpos($layoutContent, 'Clean Filament Upload Configuration') !== false) {
        echo "   ✅ Clean upload configuration found in layout\n";
    } else {
        echo "   ❌ Clean upload configuration not found in layout\n";
    }
    
    // Check for conflicting scripts in layout
    $conflictingScripts = [
        'enhanced-gallery-fix.js',
        'enhanced-gallery-direct-fix.js',
        'custom-upload.js'
    ];
    
    $hasConflicts = false;
    foreach ($conflictingScripts as $script) {
        if (strpos($layoutContent, $script) !== false) {
            echo "   ⚠️  Conflicting script found in layout: $script\n";
            $hasConflicts = true;
        }
    }
    
    if (!$hasConflicts) {
        echo "   ✅ No conflicting scripts found in layout\n";
    }
} else {
    echo "   ❌ Filament layout file not found\n";
}

// Check upload directories
echo "\n5. Upload Directories Check:\n";
$directories = ['galleries', 'livewire-tmp'];
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

// Check storage link
echo "\n6. Storage Link Check:\n";
$storageLinkPath = public_path('storage');
if (is_link($storageLinkPath) || is_dir($storageLinkPath)) {
    echo "   ✅ Storage link exists\n";
} else {
    echo "   ❌ Storage link not found - run 'php artisan storage:link'\n";
}

// Check Livewire configuration
echo "\n7. Livewire Configuration Check:\n";
$livewireConfig = config('livewire.temporary_file_upload');
if ($livewireConfig) {
    echo "   ✅ Livewire upload configuration found\n";
    echo "   - Max file size: " . implode(', ', $livewireConfig['rules']) . "\n";
    echo "   - Upload directory: " . $livewireConfig['directory'] . "\n";
    echo "   - Middleware: " . implode(', ', $livewireConfig['middleware']) . "\n";
} else {
    echo "   ❌ Livewire upload configuration not found\n";
}

// Check middleware configuration
echo "\n8. Middleware Configuration Check:\n";
$kernelPath = app_path('Http/Kernel.php');
if (file_exists($kernelPath)) {
    $kernelContent = file_get_contents($kernelPath);
    
    if (strpos($kernelContent, 'IncreaseUploadLimits') !== false) {
        echo "   ✅ IncreaseUploadLimits middleware is registered\n";
    } else {
        echo "   ❌ IncreaseUploadLimits middleware not found\n";
    }
} else {
    echo "   ❌ Kernel.php file not found\n";
}

// Check routes for conflicts
echo "\n9. Routes Check:\n";
$routesPath = base_path('routes/web.php');
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);
    
    // Check for conflicting custom upload routes
    $conflictingRoutes = [
        '/enhanced-gallery/upload',
        '/custom-upload',
    ];
    
    $hasConflictingRoutes = false;
    foreach ($conflictingRoutes as $route) {
        if (strpos($routesContent, $route) !== false) {
            echo "   ⚠️  Conflicting route found: $route\n";
            $hasConflictingRoutes = true;
        }
    }
    
    if (!$hasConflictingRoutes) {
        echo "   ✅ No conflicting custom upload routes found\n";
    }
} else {
    echo "   ❌ Routes file not found\n";
}

// Check PHP configuration
echo "\n10. PHP Upload Configuration:\n";
$phpSettings = [
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
];

foreach ($phpSettings as $setting => $value) {
    echo "    - $setting: $value\n";
}

echo "\n=== Summary ===\n";
echo "✅ = Configuration is correct\n";
echo "❌ = Issue that needs to be fixed\n";
echo "⚠️  = Warning - may cause conflicts\n";

echo "\nIf all items show ✅, the Enhanced Gallery upload should work correctly.\n";
echo "If you see ❌ or ⚠️  items, please address them before testing uploads.\n";

echo "\n=== Test URLs ===\n";
echo "Enhanced Gallery List: /admin/enhanced-galleries\n";
echo "Create New Gallery: /admin/enhanced-galleries/create\n";

?>
