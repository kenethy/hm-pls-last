<?php

/**
 * E-Certificate Functionality Test Script
 * 
 * This script tests the complete e-certificate implementation including:
 * - Database schema updates
 * - Model methods
 * - Certificate generation
 * - Health status calculation
 * - PDF generation capabilities
 */

echo "=== E-Certificate Functionality Test ===\n\n";

// Check if we're in a Laravel environment
if (!function_exists('config')) {
    echo "❌ Not running in Laravel environment\n";
    echo "Please run this script from Laravel root directory\n";
    exit(1);
}

// 1. Database Schema Verification
echo "1. Database Schema Verification:\n";
try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('service_reports');
    
    $requiredColumns = [
        'certificate_number',
        'certificate_issued_date', 
        'certificate_valid_until',
        'certificate_verification_code',
        'health_status',
        'overall_condition_score'
    ];
    
    foreach ($requiredColumns as $column) {
        if (in_array($column, $columns)) {
            echo "   ✅ Column '$column' exists\n";
        } else {
            echo "   ❌ Column '$column' missing\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Database schema check failed: " . $e->getMessage() . "\n";
}

// 2. Model Methods Verification
echo "\n2. ServiceReport Model Methods:\n";
$modelClass = \App\Models\ServiceReport::class;

$requiredMethods = [
    'generateCertificateNumber',
    'generateVerificationCode', 
    'calculateConditionScore',
    'determineHealthStatus',
    'getHealthStatusColor',
    'initializeCertificate'
];

foreach ($requiredMethods as $method) {
    if (method_exists($modelClass, $method)) {
        echo "   ✅ Method '$method' exists\n";
    } else {
        echo "   ❌ Method '$method' missing\n";
    }
}

// 3. Certificate Number Generation Test
echo "\n3. Certificate Number Generation:\n";
try {
    $certNumber1 = \App\Models\ServiceReport::generateCertificateNumber();
    $certNumber2 = \App\Models\ServiceReport::generateCertificateNumber();
    
    echo "   ✅ Certificate number generated: $certNumber1\n";
    echo "   ✅ Second certificate number: $certNumber2\n";
    
    if (preg_match('/^HM-CERT-\d{6}-\d{4}$/', $certNumber1)) {
        echo "   ✅ Certificate number format is correct\n";
    } else {
        echo "   ❌ Certificate number format is incorrect\n";
    }
    
    if ($certNumber1 !== $certNumber2) {
        echo "   ✅ Certificate numbers are unique\n";
    } else {
        echo "   ⚠️  Certificate numbers are identical (may be expected)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Certificate number generation failed: " . $e->getMessage() . "\n";
}

// 4. Verification Code Generation Test
echo "\n4. Verification Code Generation:\n";
try {
    $verCode1 = \App\Models\ServiceReport::generateVerificationCode();
    $verCode2 = \App\Models\ServiceReport::generateVerificationCode();
    
    echo "   ✅ Verification code generated: $verCode1\n";
    echo "   ✅ Second verification code: $verCode2\n";
    
    if (strlen($verCode1) === 8 && ctype_alnum($verCode1)) {
        echo "   ✅ Verification code format is correct (8 alphanumeric)\n";
    } else {
        echo "   ❌ Verification code format is incorrect\n";
    }
    
    if ($verCode1 !== $verCode2) {
        echo "   ✅ Verification codes are unique\n";
    } else {
        echo "   ❌ Verification codes are identical\n";
    }
} catch (Exception $e) {
    echo "   ❌ Verification code generation failed: " . $e->getMessage() . "\n";
}

// 5. Health Status System Test
echo "\n5. Health Status System:\n";
try {
    // Test different score ranges
    $testScores = [95, 85, 75, 65, 45];
    $expectedStatuses = ['Sangat Sehat', 'Sehat', 'Cukup Sehat', 'Perlu Perhatian', 'Perlu Perbaikan'];
    
    // Create a mock report for testing
    $mockReport = new \App\Models\ServiceReport();
    
    foreach ($testScores as $index => $score) {
        $mockReport->overall_condition_score = $score;
        $status = $mockReport->determineHealthStatus();
        $color = $mockReport->getHealthStatusColor();
        
        echo "   Score $score%: $status ($color)\n";
        
        if ($status === $expectedStatuses[$index]) {
            echo "      ✅ Status mapping correct\n";
        } else {
            echo "      ❌ Status mapping incorrect (expected: {$expectedStatuses[$index]})\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Health status system test failed: " . $e->getMessage() . "\n";
}

// 6. Route Verification
echo "\n6. Route Verification:\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    
    $requiredRoutes = [
        'service-reports.show',
        'service-reports.download', 
        'service-reports.certificate',
        'service-reports.expired'
    ];
    
    foreach ($requiredRoutes as $routeName) {
        if ($routes->hasNamedRoute($routeName)) {
            echo "   ✅ Route '$routeName' exists\n";
        } else {
            echo "   ❌ Route '$routeName' missing\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Route verification failed: " . $e->getMessage() . "\n";
}

// 7. Controller Method Verification
echo "\n7. Controller Method Verification:\n";
$controllerClass = \App\Http\Controllers\ServiceReportController::class;

$requiredControllerMethods = [
    'show',
    'download',
    'downloadCertificate',
    'expired'
];

foreach ($requiredControllerMethods as $method) {
    if (method_exists($controllerClass, $method)) {
        echo "   ✅ Controller method '$method' exists\n";
    } else {
        echo "   ❌ Controller method '$method' missing\n";
    }
}

// 8. View Template Verification
echo "\n8. View Template Verification:\n";
$viewPaths = [
    'service-reports.show' => 'resources/views/service-reports/show.blade.php',
    'service-reports.certificate-pdf' => 'resources/views/service-reports/certificate-pdf.blade.php',
    'service-reports.pdf' => 'resources/views/service-reports/pdf.blade.php',
    'service-reports.expired' => 'resources/views/service-reports/expired.blade.php'
];

foreach ($viewPaths as $viewName => $viewPath) {
    if (file_exists($viewPath)) {
        echo "   ✅ View template '$viewName' exists\n";
        
        // Check for certificate-specific content in show view
        if ($viewName === 'service-reports.show') {
            $content = file_get_contents($viewPath);
            if (strpos($content, 'E-Certificate Mobil Sehat') !== false) {
                echo "      ✅ Certificate section found in show view\n";
            } else {
                echo "      ❌ Certificate section not found in show view\n";
            }
            
            if (strpos($content, 'service-reports.certificate') !== false) {
                echo "      ✅ Certificate download link found\n";
            } else {
                echo "      ❌ Certificate download link not found\n";
            }
        }
    } else {
        echo "   ❌ View template '$viewName' missing at $viewPath\n";
    }
}

// 9. PDF Generation Test (Basic)
echo "\n9. PDF Generation Capability:\n";
try {
    // Check if DomPDF is available
    if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        echo "   ✅ DomPDF facade is available\n";
        
        // Test basic PDF creation (without actual data)
        try {
            $testHtml = '<html><body><h1>Test PDF</h1></body></html>';
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($testHtml);
            echo "   ✅ PDF generation capability confirmed\n";
        } catch (Exception $e) {
            echo "   ❌ PDF generation test failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ❌ DomPDF facade not available\n";
    }
} catch (Exception $e) {
    echo "   ❌ PDF capability check failed: " . $e->getMessage() . "\n";
}

// 10. Service Report Integration Test
echo "\n10. Service Report Integration:\n";
try {
    // Check if we can find any existing service reports
    $reportCount = \App\Models\ServiceReport::count();
    echo "   ✅ Service reports table accessible (found $reportCount reports)\n";
    
    if ($reportCount > 0) {
        $sampleReport = \App\Models\ServiceReport::first();
        echo "   ✅ Sample report found (ID: {$sampleReport->id})\n";
        
        // Test certificate initialization on existing report
        try {
            $sampleReport->initializeCertificate();
            echo "   ✅ Certificate initialization successful\n";
            
            if ($sampleReport->certificate_number) {
                echo "      ✅ Certificate number: {$sampleReport->certificate_number}\n";
            }
            if ($sampleReport->certificate_verification_code) {
                echo "      ✅ Verification code: {$sampleReport->certificate_verification_code}\n";
            }
            if ($sampleReport->health_status) {
                echo "      ✅ Health status: {$sampleReport->health_status}\n";
            }
            if ($sampleReport->overall_condition_score !== null) {
                echo "      ✅ Condition score: {$sampleReport->overall_condition_score}%\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Certificate initialization failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  No existing service reports found for testing\n";
    }
} catch (Exception $e) {
    echo "   ❌ Service report integration test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "✅ = Feature working correctly\n";
echo "❌ = Issue that needs to be fixed\n";
echo "⚠️  = Warning or informational\n";

echo "\n=== Next Steps ===\n";
echo "1. Create a test service report in the admin panel\n";
echo "2. Mark a service as completed to generate a digital report\n";
echo "3. Visit the report URL to see the integrated e-certificate\n";
echo "4. Test the certificate PDF download functionality\n";
echo "5. Verify mobile responsiveness of the certificate display\n";

echo "\n=== Test URLs (replace {code} with actual report code) ===\n";
echo "View Report: /laporan/{code}\n";
echo "Download Report: /laporan/{code}/download\n";
echo "Download Certificate: /laporan/{code}/certificate\n";

?>
