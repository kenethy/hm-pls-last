<?php

/**
 * Test Script for Upload Limits
 * 
 * This script helps verify that upload limits are properly configured
 * Run this script to check current PHP upload settings
 */

echo "=== PHP Upload Configuration Test ===\n\n";

// Check current PHP settings
$settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'memory_limit' => ini_get('memory_limit'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'max_file_uploads' => ini_get('max_file_uploads'),
];

echo "Current PHP Settings:\n";
echo "---------------------\n";
foreach ($settings as $setting => $value) {
    echo sprintf("%-20s: %s\n", $setting, $value);
}

echo "\n=== Recommendations ===\n";
echo "For promotional image uploads, ensure:\n";
echo "• upload_max_filesize >= 10M\n";
echo "• post_max_size >= 10M\n";
echo "• max_execution_time >= 300 (or 0 for unlimited)\n";
echo "• memory_limit >= 512M\n";
echo "• file_uploads = On\n";

// Convert sizes to bytes for comparison
function convertToBytes($size) {
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

echo "\n=== Analysis ===\n";

$uploadMaxBytes = convertToBytes($settings['upload_max_filesize']);
$postMaxBytes = convertToBytes($settings['post_max_size']);
$requiredBytes = 10 * 1024 * 1024; // 10MB

if ($uploadMaxBytes >= $requiredBytes) {
    echo "✅ upload_max_filesize is sufficient\n";
} else {
    echo "❌ upload_max_filesize is too small (need at least 10M)\n";
}

if ($postMaxBytes >= $requiredBytes) {
    echo "✅ post_max_size is sufficient\n";
} else {
    echo "❌ post_max_size is too small (need at least 10M)\n";
}

if ($settings['file_uploads'] === 'Enabled') {
    echo "✅ File uploads are enabled\n";
} else {
    echo "❌ File uploads are disabled\n";
}

echo "\n=== Laravel/Livewire Configuration ===\n";

// Check if we're in a Laravel environment
if (file_exists('config/livewire.php')) {
    echo "✅ Livewire config file found\n";
    
    // Try to load Laravel configuration
    if (function_exists('config')) {
        $livewireRules = config('livewire.temporary_file_upload.rules', []);
        echo "Livewire upload rules: " . implode(', ', $livewireRules) . "\n";
    }
} else {
    echo "ℹ️  Run this script from Laravel root directory for full analysis\n";
}

echo "\n=== Web Server Notes ===\n";
echo "If you still get 'Request Entity Too Large' errors:\n";
echo "• Check Nginx: client_max_body_size setting\n";
echo "• Check Apache: LimitRequestBody directive\n";
echo "• Check Docker: container resource limits\n";
echo "• Check reverse proxy: upload size limits\n";

echo "\nTest completed.\n";

?>
