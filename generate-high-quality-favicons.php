<?php

/**
 * High-Quality Favicon Generator Script
 * Optimized for Google Search Results Display
 *
 * This script generates high-quality favicon files specifically optimized
 * for crisp display in Google search results and browser tabs.
 */

// Check if GD extension is available
if (!extension_loaded('gd')) {
    die("Error: GD extension is required to run this script.\n");
}

// Source image path - use the high-quality logo
$sourceImage = __DIR__ . '/public/images/logo/logoputih.png';

// Check if source image exists
if (!file_exists($sourceImage)) {
    die("Error: Source image not found at $sourceImage\n");
}

// Output directory
$outputDir = __DIR__ . '/public/favicon/';

// Create output directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Load the source image
$image = @imagecreatefrompng($sourceImage);
if (!$image) {
    die("Error: Failed to load source image.\n");
}

// Get image dimensions
$width = imagesx($image);
$height = imagesy($image);

echo "Source image: {$width}x{$height}\n";

// Define favicon sizes with quality settings optimized for Google search
$sizes = [
    'favicon-16x16.png' => ['size' => 16, 'quality' => 0, 'priority' => 'critical'], // Google search results
    'favicon-32x32.png' => ['size' => 32, 'quality' => 0, 'priority' => 'high'],     // Browser tabs
    'favicon-48x48.png' => ['size' => 48, 'quality' => 0, 'priority' => 'high'],     // Better scaling
    'favicon-96x96.png' => ['size' => 96, 'quality' => 1, 'priority' => 'medium'],   // High DPI
    'apple-touch-icon.png' => ['size' => 180, 'quality' => 2, 'priority' => 'low'],
    'android-chrome-192x192.png' => ['size' => 192, 'quality' => 2, 'priority' => 'low'],
    'android-chrome-512x512.png' => ['size' => 512, 'quality' => 3, 'priority' => 'low'],
    'mstile-150x150.png' => ['size' => 150, 'quality' => 2, 'priority' => 'low'],
];

// Generate high-quality favicon files
foreach ($sizes as $filename => $config) {
    $size = $config['size'];
    $quality = $config['quality'];
    $priority = $config['priority'];
    
    echo "Generating {$filename} ({$size}x{$size}) - Priority: {$priority}...\n";
    
    // Create a new true color image
    $resized = imagecreatetruecolor($size, $size);
    
    // Enable high-quality resampling
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    
    // Create transparent background
    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
    imagefill($resized, 0, 0, $transparent);
    
    // Use high-quality resampling for critical sizes
    if ($priority === 'critical' || $priority === 'high') {
        // For small, critical favicons, use the best possible quality
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, $width, $height);
    } else {
        // Standard resampling for larger sizes
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, $width, $height);
    }
    
    // Save the image with specified quality
    $outputPath = $outputDir . $filename;
    if (imagepng($resized, $outputPath, $quality)) {
        $fileSize = filesize($outputPath);
        echo "✓ Generated: $outputPath ({$fileSize} bytes)\n";
    } else {
        echo "✗ Failed to generate: $outputPath\n";
    }
    
    imagedestroy($resized);
}

// Generate optimized ICO file for maximum compatibility
echo "\nGenerating optimized favicon.ico...\n";

$icoSizes = [16, 32, 48]; // Multiple sizes for better scaling
$icoData = '';

// ICO header: Reserved(2) + Type(2) + Count(2)
$icoData .= pack('vvv', 0, 1, count($icoSizes));

$offset = 6 + (count($icoSizes) * 16); // Header + Directory entries
$pngDataArray = [];

// First pass: create PNG data and directory entries
foreach ($icoSizes as $size) {
    echo "Creating {$size}x{$size} ICO entry...\n";
    
    $resized = imagecreatetruecolor($size, $size);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    
    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
    imagefill($resized, 0, 0, $transparent);
    
    // High-quality resampling for ICO
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, $width, $height);
    
    // Convert to PNG data with highest quality
    ob_start();
    imagepng($resized, null, 0); // 0 = no compression, highest quality
    $pngData = ob_get_clean();
    $pngDataArray[$size] = $pngData;
    
    // ICO directory entry
    $icoData .= pack('CCCCvvVV',
        $size,           // Width
        $size,           // Height  
        0,               // Color count (0 for PNG)
        0,               // Reserved
        1,               // Planes
        32,              // Bits per pixel
        strlen($pngData), // Size of image data
        $offset          // Offset to image data
    );
    
    $offset += strlen($pngData);
    imagedestroy($resized);
}

// Second pass: append PNG data
foreach ($icoSizes as $size) {
    $icoData .= $pngDataArray[$size];
}

// Save ICO file
$icoPath = $outputDir . 'favicon.ico';
if (file_put_contents($icoPath, $icoData)) {
    $fileSize = filesize($icoPath);
    echo "✓ Generated: $icoPath ({$fileSize} bytes)\n";
} else {
    echo "✗ Failed to generate: $icoPath\n";
}

// Copy favicon.ico to root public directory
$rootIcoPath = __DIR__ . '/public/favicon.ico';
if (copy($icoPath, $rootIcoPath)) {
    echo "✓ Copied favicon.ico to root: $rootIcoPath\n";
} else {
    echo "✗ Failed to copy favicon.ico to root\n";
}

// Clean up
imagedestroy($image);

echo "\n🎉 High-quality favicon generation complete!\n";
echo "\nOptimizations applied:\n";
echo "• 16x16px: Maximum quality for Google search results\n";
echo "• 32x32px: High quality for browser tabs\n";
echo "• 48x48px: Added for better scaling on various displays\n";
echo "• ICO file: Contains multiple sizes (16, 32, 48px)\n";
echo "• All critical sizes use zero compression\n";
echo "\nRecommendations:\n";
echo "• Clear browser cache to see updated favicon\n";
echo "• Update version parameter in HTML to force refresh\n";
echo "• Test favicon visibility in Google search results\n";

?>
