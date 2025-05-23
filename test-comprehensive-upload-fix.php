<?php

/**
 * Comprehensive Upload Configuration Test
 * 
 * This script verifies that all upload configurations are properly set
 * to handle the "Request Entity Too Large" (HTTP 413) error.
 */

echo "=== Comprehensive Upload Configuration Test ===\n\n";

// Check if we're in a Laravel environment
if (!function_exists('config')) {
    echo "❌ Not running in Laravel environment\n";
    echo "Please run this script from Laravel root directory\n";
    exit(1);
}

// 1. Check all Filament Resources with FileUpload components
echo "1. Filament Resources Upload Configuration:\n";

$resources = [
    'PromoResource' => 'app/Filament/Resources/PromoResource.php',
    'EnhancedGalleryResource' => 'app/Filament/Resources/EnhancedGalleryResource.php',
    'BlogPostResource' => 'app/Filament/Resources/BlogPostResource.php',
    'ServiceResource UpdatesRelationManager' => 'app/Filament/Resources/ServiceResource/RelationManagers/UpdatesRelationManager.php',
];

foreach ($resources as $resourceName => $resourcePath) {
    if (file_exists($resourcePath)) {
        echo "   ✅ $resourceName found\n";
        
        $content = file_get_contents($resourcePath);
        
        // Check for required configurations
        $checks = [
            'maxSize(10240)' => 'File size limit (10MB)',
            'acceptedFileTypes' => 'File type validation',
            'disk(\'public\')' => 'Storage disk',
            'visibility(\'public\')' => 'File visibility',
            'uploadProgressIndicatorPosition' => 'Progress indicator',
            'panelLayout(\'integrated\')' => 'Panel layout',
            'helperText' => 'Helper text',
        ];
        
        foreach ($checks as $check => $description) {
            if (strpos($content, $check) !== false) {
                echo "      ✅ $description configured\n";
            } else {
                echo "      ❌ $description not configured\n";
            }
        }
    } else {
        echo "   ❌ $resourceName not found at $resourcePath\n";
    }
    echo "\n";
}

// 2. Check JavaScript conflicts
echo "2. JavaScript Conflicts Check:\n";
$jsFiles = [
    'custom-upload.js' => 'Custom Upload (should be disabled)',
    'enhanced-gallery-fix.js' => 'Enhanced Gallery Fix (should be disabled)',
    'enhanced-gallery-direct-fix.js' => 'Enhanced Gallery Direct Fix (should be disabled)',
    'promo-upload-fix.js' => 'Promo Upload Fix (should be disabled)',
    'livewire-docker-fix.js' => 'Livewire Docker Fix (should be disabled)',
    'livewire-upload-size-fix.js' => 'Livewire Upload Size Fix (should be disabled)',
    'livewire-upload-fix-v2.js' => 'Livewire Upload Fix V2 (should be disabled)',
    'livewire-upload-fix.js' => 'Livewire Upload Fix (should be disabled)',
    'promo-image-upload.js' => 'Promo Image Upload (should be disabled)',
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

// 3. Check PHP Configuration
echo "\n3. PHP Upload Configuration:\n";
$phpSettings = [
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'memory_limit' => ini_get('memory_limit'),
    'max_input_vars' => ini_get('max_input_vars'),
];

foreach ($phpSettings as $setting => $value) {
    echo "   - $setting: $value\n";
}

// Check if PHP settings are adequate
function convertToBytes($size) {
    if (is_numeric($size)) return $size;
    $size = trim($size);
    $last = strtolower($size[strlen($size)-1]);
    $size = (int) $size;
    
    switch($last) {
        case 'g':
            $size *= 1024;
        case 'm':
            $size *= 1024;
        case 'k':
            $size *= 1024;
    }
    
    return $size;
}

$uploadMaxBytes = convertToBytes($phpSettings['upload_max_filesize']);
$postMaxBytes = convertToBytes($phpSettings['post_max_size']);
$requiredBytes = 100 * 1024 * 1024; // 100MB

echo "\n   Analysis:\n";
if ($uploadMaxBytes >= $requiredBytes) {
    echo "   ✅ upload_max_filesize is sufficient (>= 100MB)\n";
} else {
    echo "   ❌ upload_max_filesize is too small (need at least 100MB)\n";
}

if ($postMaxBytes >= $requiredBytes) {
    echo "   ✅ post_max_size is sufficient (>= 100MB)\n";
} else {
    echo "   ❌ post_max_size is too small (need at least 100MB)\n";
}

// 4. Check Livewire Configuration
echo "\n4. Livewire Configuration:\n";
$livewireConfig = config('livewire.temporary_file_upload');
if ($livewireConfig) {
    echo "   ✅ Livewire upload configuration found\n";
    echo "   - Rules: " . implode(', ', $livewireConfig['rules']) . "\n";
    echo "   - Directory: " . $livewireConfig['directory'] . "\n";
    echo "   - Middleware: " . implode(', ', $livewireConfig['middleware']) . "\n";
    
    // Check if Livewire max size is adequate
    $livewireRules = implode(' ', $livewireConfig['rules']);
    if (strpos($livewireRules, 'max:102400') !== false) {
        echo "   ✅ Livewire max size is set to 100MB\n";
    } else {
        echo "   ❌ Livewire max size needs to be increased\n";
    }
} else {
    echo "   ❌ Livewire upload configuration not found\n";
}

// 5. Check Filament Service Provider
echo "\n5. Filament Service Provider:\n";
$filamentServicePath = app_path('Providers/FilamentServiceProvider.php');
if (file_exists($filamentServicePath)) {
    $content = file_get_contents($filamentServicePath);
    
    if (strpos($content, 'maxSize(10240)') !== false) {
        echo "   ✅ Global Filament upload limit set to 10MB\n";
    } else {
        echo "   ❌ Global Filament upload limit not configured\n";
    }
    
    if (strpos($content, 'acceptedFileTypes') !== false) {
        echo "   ✅ Global file type validation configured\n";
    } else {
        echo "   ❌ Global file type validation not configured\n";
    }
} else {
    echo "   ❌ FilamentServiceProvider not found\n";
}

// 6. Check .htaccess Configuration
echo "\n6. .htaccess Configuration:\n";
$htaccessPath = public_path('.htaccess');
if (file_exists($htaccessPath)) {
    $content = file_get_contents($htaccessPath);
    
    $checks = [
        'upload_max_filesize 100M' => 'PHP upload_max_filesize set to 100M',
        'post_max_size 100M' => 'PHP post_max_size set to 100M',
        'max_execution_time 600' => 'PHP execution time increased',
        'memory_limit 1024M' => 'PHP memory limit increased',
        'LimitRequestBody 104857600' => 'Apache request body limit set',
    ];
    
    foreach ($checks as $check => $description) {
        if (strpos($content, $check) !== false) {
            echo "   ✅ $description\n";
        } else {
            echo "   ❌ $description not configured\n";
        }
    }
} else {
    echo "   ❌ .htaccess file not found\n";
}

// 7. Check Middleware Configuration
echo "\n7. Middleware Configuration:\n";
$middlewarePath = app_path('Http/Middleware/IncreaseUploadLimits.php');
if (file_exists($middlewarePath)) {
    $content = file_get_contents($middlewarePath);
    
    if (strpos($content, "ini_set('upload_max_filesize', '100M')") !== false) {
        echo "   ✅ Middleware sets upload_max_filesize to 100M\n";
    } else {
        echo "   ❌ Middleware upload limits not properly configured\n";
    }
    
    if (strpos($content, 'X-CSRF-TOKEN') !== false) {
        echo "   ✅ Middleware handles CSRF tokens\n";
    } else {
        echo "   ❌ Middleware CSRF handling not configured\n";
    }
} else {
    echo "   ❌ IncreaseUploadLimits middleware not found\n";
}

// 8. Check Docker Nginx Configuration
echo "\n8. Docker Nginx Configuration:\n";
$nginxConfigPath = base_path('docker/nginx/upload-limits.conf');
if (file_exists($nginxConfigPath)) {
    $content = file_get_contents($nginxConfigPath);
    
    if (strpos($content, 'client_max_body_size 100M') !== false) {
        echo "   ✅ Nginx client_max_body_size set to 100M\n";
    } else {
        echo "   ❌ Nginx client_max_body_size not properly configured\n";
    }
} else {
    echo "   ❌ Docker Nginx configuration not found\n";
}

// 9. Check Upload Directories
echo "\n9. Upload Directories:\n";
$directories = ['promos', 'galleries', 'blog-featured', 'service-updates', 'livewire-tmp'];
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

echo "\n=== Summary ===\n";
echo "✅ = Configuration is correct\n";
echo "❌ = Issue that needs to be fixed\n";
echo "⚠️  = Warning - may cause conflicts\n";

echo "\nIf all items show ✅, uploads should work without HTTP 413 errors.\n";
echo "If you see ❌ or ⚠️  items, please address them before testing uploads.\n";

echo "\n=== Test URLs ===\n";
echo "Promotions: /admin/promos/create\n";
echo "Enhanced Gallery: /admin/enhanced-galleries/create\n";
echo "Blog Posts: /admin/blog-posts/create\n";
echo "Service Updates: /admin/services/{id}/edit (Updates tab)\n";

?>
