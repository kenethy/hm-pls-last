<?php

/**
 * Favicon Generator Script
 * 
 * This script generates favicon files from the logoputih.png image.
 * It creates various sizes of favicon files required for different devices and browsers.
 */

// Check if GD extension is available
if (!extension_loaded('gd')) {
    die("Error: GD extension is required to run this script.\n");
}

// Source image path
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

// Create favicon sizes
$sizes = [
    'favicon-16x16.png' => 16,
    'favicon-32x32.png' => 32,
    'favicon-96x96.png' => 96,
    'apple-touch-icon.png' => 180,
    'android-chrome-192x192.png' => 192,
    'android-chrome-512x512.png' => 512,
];

// Generate favicon files
foreach ($sizes as $filename => $size) {
    // Create a new true color image
    $resized = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
    imagefilledrectangle($resized, 0, 0, $size, $size, $transparent);
    
    // Resize the image
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, $width, $height);
    
    // Save the image
    $outputPath = $outputDir . $filename;
    imagepng($resized, $outputPath);
    imagedestroy($resized);
    
    echo "Generated: $outputPath\n";
}

// Generate favicon.ico (16x16, 32x32, 48x48)
$icoSizes = [16, 32, 48];
$icoPath = $outputDir . 'favicon.ico';

// We'll use the GD library to create the ICO file
// This is a simplified approach - for production, consider using a dedicated ICO library

// Create a temporary file for each size
$tempFiles = [];
foreach ($icoSizes as $size) {
    $resized = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
    imagefilledrectangle($resized, 0, 0, $size, $size, $transparent);
    
    // Resize the image
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, $width, $height);
    
    // Save to temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'ico');
    imagepng($resized, $tempFile);
    $tempFiles[] = $tempFile;
    imagedestroy($resized);
}

// Copy the 32x32 version to the root public directory
copy($outputDir . 'favicon-32x32.png', __DIR__ . '/public/favicon.ico');
echo "Generated: " . __DIR__ . '/public/favicon.ico' . "\n";

// Clean up
imagedestroy($image);
foreach ($tempFiles as $tempFile) {
    unlink($tempFile);
}

echo "Favicon generation complete!\n";
