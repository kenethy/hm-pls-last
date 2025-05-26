<?php

/**
 * Test script to verify Laravel storage permissions are working
 * Run with: php test-permissions.php
 */

echo "🧪 Testing Laravel Storage Permissions\n";
echo "=====================================\n\n";

// Test 1: Check if storage directories exist and are writable
echo "📁 Testing storage directories:\n";

$directories = [
    'storage/framework/views',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/logs',
    'storage/app',
    'bootstrap/cache'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "❌ Directory missing: {$dir}\n";
        mkdir($dir, 0755, true);
        echo "✅ Created directory: {$dir}\n";
    } else {
        echo "✅ Directory exists: {$dir}\n";
    }
    
    if (is_writable($dir)) {
        echo "✅ Directory writable: {$dir}\n";
    } else {
        echo "❌ Directory not writable: {$dir}\n";
    }
}

echo "\n";

// Test 2: Test actual file writing
echo "📝 Testing file write operations:\n";

$testFiles = [
    'storage/framework/views/test_write.tmp',
    'storage/framework/cache/test_write.tmp',
    'storage/logs/test_write.tmp',
    'bootstrap/cache/test_write.tmp'
];

foreach ($testFiles as $testFile) {
    try {
        $content = "Test write at " . date('Y-m-d H:i:s');
        $result = file_put_contents($testFile, $content);
        
        if ($result !== false) {
            echo "✅ Write test successful: {$testFile}\n";
            unlink($testFile); // Clean up
        } else {
            echo "❌ Write test failed: {$testFile}\n";
        }
    } catch (Exception $e) {
        echo "❌ Write test error for {$testFile}: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 3: Test Laravel view compilation
echo "🔧 Testing Laravel view compilation:\n";

try {
    // Try to compile a simple Blade view
    $viewContent = "<?php echo 'Test view compiled at ' . date('Y-m-d H:i:s'); ?>";
    $compiledPath = 'storage/framework/views/test_compiled_view.php';
    
    $result = file_put_contents($compiledPath, $viewContent);
    
    if ($result !== false) {
        echo "✅ View compilation test successful\n";
        
        // Test if we can include the compiled view
        ob_start();
        include $compiledPath;
        $output = ob_get_clean();
        
        echo "✅ Compiled view output: {$output}\n";
        
        unlink($compiledPath); // Clean up
    } else {
        echo "❌ View compilation test failed\n";
    }
} catch (Exception $e) {
    echo "❌ View compilation error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check current permissions
echo "🔍 Current permission status:\n";

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $perms = fileperms($dir);
        $octal = substr(sprintf('%o', $perms), -4);
        echo "Directory {$dir}: {$octal}\n";
    }
}

echo "\n";

// Test 5: Simulate the exact error scenario
echo "🎯 Simulating the exact error scenario:\n";

try {
    // This simulates what Laravel does when compiling Blade views
    $viewHash = '2d866a4203fc9cf3f82662ec5b055e3e'; // From the error message
    $compiledViewPath = "storage/framework/views/{$viewHash}.php";
    
    $bladeContent = "<?php echo 'Simulated Blade view compilation'; ?>";
    
    $result = file_put_contents($compiledViewPath, $bladeContent);
    
    if ($result !== false) {
        echo "✅ Exact error scenario test PASSED\n";
        echo "✅ Laravel should now be able to compile Blade views\n";
        unlink($compiledViewPath); // Clean up
    } else {
        echo "❌ Exact error scenario test FAILED\n";
        echo "❌ The original error may still occur\n";
    }
} catch (Exception $e) {
    echo "❌ Exact error scenario test ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Summary
echo "📊 Test Summary:\n";
echo "================\n";

$allDirectoriesExist = true;
$allDirectoriesWritable = true;

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        $allDirectoriesExist = false;
    }
    if (!is_writable($dir)) {
        $allDirectoriesWritable = false;
    }
}

if ($allDirectoriesExist && $allDirectoriesWritable) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "✅ Storage permissions are correctly configured\n";
    echo "✅ Laravel should work without permission errors\n";
    echo "✅ Blade view compilation should work\n";
    echo "✅ The website should load properly\n";
} else {
    echo "⚠️  SOME TESTS FAILED!\n";
    if (!$allDirectoriesExist) {
        echo "❌ Some directories are missing\n";
    }
    if (!$allDirectoriesWritable) {
        echo "❌ Some directories are not writable\n";
    }
    echo "🔧 Run the permission fix script to resolve issues\n";
}

echo "\n";
echo "Next steps:\n";
echo "1. If tests passed: Visit hartonomotor.xyz to verify\n";
echo "2. If tests failed: Run ./fix-docker-permissions.sh on your VPS\n";
echo "3. Test the mechanic service history functionality\n";

?>
