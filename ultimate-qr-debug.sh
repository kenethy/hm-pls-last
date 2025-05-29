#!/bin/bash

# Ultimate QR Debug - Find the Real Issue
# Deep dive into why ALL solutions are failing

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔍 Ultimate QR Debug - Find the Real Issue${NC}"
echo "=================================================="

echo -e "${RED}Problem: ALL 5 creative solutions returning 404${NC}"
echo -e "${YELLOW}This suggests a deeper issue...${NC}"

# Step 1: Check if QR files actually exist
echo -e "\n${YELLOW}📂 Step 1: Verify QR files exist${NC}"

echo "QR files in directory:"
ls -la /var/www/whatsapp_statics/qrcode/ || echo "Directory not found"

echo -e "\nLatest QR files:"
ls -t /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null | head -3 || echo "No PNG files found"

# Step 2: Check Laravel routes
echo -e "\n${YELLOW}🛣️ Step 2: Check Laravel routes${NC}"

echo "Testing if Laravel is working:"
LARAVEL_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/" 2>/dev/null | tail -1)
echo "Laravel main page: $LARAVEL_TEST"

echo -e "\nChecking route cache:"
php artisan route:clear 2>/dev/null || echo "Cannot clear routes"
php artisan config:clear 2>/dev/null || echo "Cannot clear config"

echo -e "\nListing QR routes:"
php artisan route:list | grep -i qr || echo "No QR routes found"

# Step 3: Test direct file access
echo -e "\n${YELLOW}📁 Step 3: Test direct file access${NC}"

# Get a QR file
QR_FILE=$(ls /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null | head -1)
if [ -n "$QR_FILE" ]; then
    QR_FILENAME=$(basename "$QR_FILE")
    echo "Testing file: $QR_FILENAME"
    
    echo "File permissions:"
    ls -la "$QR_FILE"
    
    echo "File readable test:"
    if [ -r "$QR_FILE" ]; then
        echo "✅ File is readable"
        echo "File size: $(stat -c%s "$QR_FILE") bytes"
    else
        echo "❌ File not readable"
    fi
    
    # Test if Laravel can read the file
    echo -e "\nTesting Laravel file access:"
    php -r "
    \$file = '$QR_FILE';
    if (file_exists(\$file)) {
        echo 'File exists: YES\n';
        echo 'File readable: ' . (is_readable(\$file) ? 'YES' : 'NO') . '\n';
        echo 'File size: ' . filesize(\$file) . ' bytes\n';
    } else {
        echo 'File exists: NO\n';
    }
    " || echo "Cannot test with PHP"
fi

# Step 4: Test symlink
echo -e "\n${YELLOW}🔗 Step 4: Test symlink${NC}"

echo "Symlink status:"
ls -la public/qr-images || echo "Symlink not found"

if [ -L "public/qr-images" ]; then
    echo "Symlink target:"
    readlink public/qr-images
    
    echo "Files in symlink target:"
    ls -la public/qr-images/ || echo "Cannot access symlink target"
fi

# Step 5: Test nginx configuration
echo -e "\n${YELLOW}⚙️ Step 5: Test nginx configuration${NC}"

echo "Current nginx QR configurations:"
grep -A 5 -B 2 "location.*qr" docker/nginx/conf.d/app.conf || echo "No QR locations found"

# Step 6: RADICAL SOLUTION - Copy files to public
echo -e "\n${YELLOW}💥 Step 6: RADICAL SOLUTION - Copy to Public${NC}"

echo "Creating public QR directory..."
mkdir -p public/qr-codes

echo "Copying QR files to public directory..."
if [ -d "/var/www/whatsapp_statics/qrcode" ]; then
    cp /var/www/whatsapp_statics/qrcode/*.png public/qr-codes/ 2>/dev/null || echo "No PNG files to copy"
    
    echo "Files copied to public:"
    ls -la public/qr-codes/ || echo "No files in public directory"
    
    # Set proper permissions
    chmod 644 public/qr-codes/*.png 2>/dev/null || echo "No PNG files to chmod"
    
    echo "✅ QR files copied to public directory"
else
    echo "❌ Source QR directory not found"
fi

# Step 7: Test public access
echo -e "\n${YELLOW}🧪 Step 7: Test public access${NC}"

if [ -n "$QR_FILENAME" ] && [ -f "public/qr-codes/$QR_FILENAME" ]; then
    echo "Testing public QR access:"
    PUBLIC_QR=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-codes/$QR_FILENAME" 2>/dev/null | tail -1)
    echo "Public QR access: $PUBLIC_QR"
    
    if [ "$PUBLIC_QR" = "200" ]; then
        echo -e "${GREEN}🎉 SUCCESS! Public directory works!${NC}"
    else
        echo -e "${RED}❌ Even public directory not working${NC}"
    fi
fi

# Step 8: NUCLEAR OPTION - Embed QR in HTML
echo -e "\n${YELLOW}☢️ Step 8: NUCLEAR OPTION - Embed QR in HTML${NC}"

if [ -n "$QR_FILE" ] && [ -f "$QR_FILE" ]; then
    echo "Creating base64 embedded QR..."
    
    # Create base64 version
    QR_BASE64=$(base64 -w 0 "$QR_FILE" 2>/dev/null || base64 "$QR_FILE" | tr -d '\n')
    
    if [ -n "$QR_BASE64" ]; then
        echo "Base64 QR created (length: ${#QR_BASE64} chars)"
        
        # Create test HTML with embedded QR
        cat > public/qr-test.html << EOF
<!DOCTYPE html>
<html>
<head>
    <title>QR Test - Base64 Embedded</title>
</head>
<body>
    <h1>QR Code Test - Base64 Embedded</h1>
    <img src="data:image/png;base64,$QR_BASE64" alt="QR Code" style="max-width: 300px;">
    <p>Filename: $QR_FILENAME</p>
    <p>This QR is embedded as base64 - no file serving needed!</p>
</body>
</html>
EOF
        
        echo "✅ Base64 embedded QR HTML created"
        echo "Test at: https://hartonomotor.xyz/qr-test.html"
        
        # Test the HTML page
        HTML_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-test.html" 2>/dev/null | tail -1)
        echo "HTML test page: $HTML_TEST"
        
        if [ "$HTML_TEST" = "200" ]; then
            echo -e "${GREEN}🎉 BASE64 SOLUTION WORKS!${NC}"
        fi
    fi
fi

# Step 9: Create Laravel API that returns base64
echo -e "\n${YELLOW}🔧 Step 9: Laravel Base64 API${NC}"

# Create simple API route
cat > routes/qr-api.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/qr-latest', function () {
    $qrDir = '/var/www/whatsapp_statics/qrcode/';
    
    if (!is_dir($qrDir)) {
        return response()->json(['error' => 'QR directory not found'], 404);
    }
    
    $files = glob($qrDir . '*.png');
    if (empty($files)) {
        return response()->json(['error' => 'No QR files found'], 404);
    }
    
    // Get latest file
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestFile = $files[0];
    $filename = basename($latestFile);
    
    if (!file_exists($latestFile)) {
        return response()->json(['error' => 'QR file not found'], 404);
    }
    
    $imageData = base64_encode(file_get_contents($latestFile));
    
    return response()->json([
        'success' => true,
        'filename' => $filename,
        'qr_code' => 'data:image/png;base64,' . $imageData,
        'created_at' => date('Y-m-d H:i:s', filemtime($latestFile))
    ]);
});
EOF

# Include the API routes
if ! grep -q "qr-api.php" routes/web.php; then
    echo "" >> routes/web.php
    echo "require __DIR__.'/qr-api.php';" >> routes/web.php
    echo "✅ QR API routes included"
fi

# Clear cache
php artisan route:clear 2>/dev/null || echo "Cannot clear routes"

# Test the API
echo "Testing Laravel QR API:"
API_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-latest" 2>/dev/null | tail -1)
echo "Laravel QR API: $API_TEST"

if [ "$API_TEST" = "200" ]; then
    echo -e "${GREEN}🎉 LARAVEL API WORKS!${NC}"
    
    # Get the actual response
    API_RESPONSE=$(curl -s "https://hartonomotor.xyz/qr-latest" 2>/dev/null)
    echo "API Response preview:"
    echo "$API_RESPONSE" | head -c 200
    echo "..."
fi

# Step 10: Final results
echo -e "\n${YELLOW}✅ Step 10: Final Results${NC}"
echo "=================================================================="

echo "WORKING SOLUTIONS FOUND:"
[ "$PUBLIC_QR" = "200" ] && echo "✅ Public directory: https://hartonomotor.xyz/qr-codes/"
[ "$HTML_TEST" = "200" ] && echo "✅ Base64 HTML: https://hartonomotor.xyz/qr-test.html"
[ "$API_TEST" = "200" ] && echo "✅ Laravel API: https://hartonomotor.xyz/qr-latest"

if [ "$PUBLIC_QR" = "200" ] || [ "$HTML_TEST" = "200" ] || [ "$API_TEST" = "200" ]; then
    echo -e "\n${GREEN}🎊 SUCCESS! At least one solution working!${NC}"
    
    echo -e "\n${BLUE}📱 Recommended approach:${NC}"
    if [ "$API_TEST" = "200" ]; then
        echo "Use Laravel API endpoint for dynamic QR serving"
        echo "Endpoint: https://hartonomotor.xyz/qr-latest"
    elif [ "$PUBLIC_QR" = "200" ]; then
        echo "Use public directory for direct file access"
        echo "URL pattern: https://hartonomotor.xyz/qr-codes/{filename}"
    elif [ "$HTML_TEST" = "200" ]; then
        echo "Use base64 embedded approach"
        echo "Test page: https://hartonomotor.xyz/qr-test.html"
    fi
    
else
    echo -e "\n${RED}❌ All solutions still failing${NC}"
    echo "This indicates a fundamental issue with the server setup"
fi

echo -e "\n${BLUE}🎯 Next Steps:${NC}"
echo "1. Test the working solutions above"
echo "2. Update your QR page to use working approach"
echo "3. Consider base64 embedding for reliability"
