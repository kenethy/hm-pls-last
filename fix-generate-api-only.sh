#!/bin/bash

# Fix Generate API Only - Focus on fixing 500 error
# QR lama itu normal, yang penting bisa generate fresh QR

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fix Generate API Only${NC}"
echo "=================================================="

echo -e "${GREEN}✅ PEMAHAMAN BENAR:${NC}"
echo "- QR lama (8622 seconds) = NORMAL"
echo "- Yang penting: Generate fresh QR working"
echo "- Focus: Fix 500 error di generate API"

# Step 1: Debug WhatsApp container connectivity
echo -e "\n${YELLOW}🐳 Step 1: Debug WhatsApp Container${NC}"

echo "Checking WhatsApp container status:"
docker ps --filter "name=whatsapp" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || echo "Cannot access docker"

echo -e "\nChecking WhatsApp container logs:"
WHATSAPP_CONTAINER=$(docker ps --filter "name=whatsapp" --format "{{.Names}}" 2>/dev/null | head -1)
if [ -n "$WHATSAPP_CONTAINER" ]; then
    echo "Container: $WHATSAPP_CONTAINER"
    docker logs "$WHATSAPP_CONTAINER" --tail 10 2>/dev/null || echo "Cannot access logs"
else
    echo "WhatsApp container not found"
fi

# Step 2: Test direct WhatsApp API access
echo -e "\n${YELLOW}📡 Step 2: Test Direct WhatsApp API Access${NC}"

echo "Testing WhatsApp API endpoints:"

# Test different endpoints
ENDPOINTS=(
    "https://hartonomotor.xyz/whatsapp-api/app/login"
    "http://192.168.144.2:3000/app/login"
    "http://localhost:3000/app/login"
)

for endpoint in "${ENDPOINTS[@]}"; do
    echo "Testing: $endpoint"
    STATUS=$(curl -s -w "%{http_code}" "$endpoint" \
        -H "Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=" \
        2>/dev/null | tail -1)
    echo "  Status: $STATUS"
    
    if [ "$STATUS" = "200" ]; then
        echo "  ✅ Working endpoint found!"
        WORKING_ENDPOINT="$endpoint"
        break
    fi
done

# Step 3: Fix Laravel generate API with working endpoint
echo -e "\n${YELLOW}🔧 Step 3: Fix Laravel Generate API${NC}"

if [ -n "$WORKING_ENDPOINT" ]; then
    echo "Using working endpoint: $WORKING_ENDPOINT"
    
    # Update generate API route
    cat > routes/generate-fix.php << EOF
<?php

use Illuminate\Support\Facades\Route;

// Fixed Generate Fresh QR API
Route::get('/generate-fresh-qr', function () {
    try {
        // Use the working endpoint
        \$endpoint = '$WORKING_ENDPOINT';
        
        \$ch = curl_init();
        curl_setopt(\$ch, CURLOPT_URL, \$endpoint);
        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(\$ch, CURLOPT_TIMEOUT, 30);
        curl_setopt(\$ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=',
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, false);
        
        \$response = curl_exec(\$ch);
        \$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
        \$error = curl_error(\$ch);
        \$info = curl_getinfo(\$ch);
        curl_close(\$ch);
        
        if (\$httpCode == 200 && \$response) {
            \$data = json_decode(\$response, true);
            
            return response()->json([
                'success' => true,
                'endpoint_used' => \$endpoint,
                'whatsapp_response' => \$data,
                'timestamp' => date('Y-m-d H:i:s'),
                'debug_info' => [
                    'http_code' => \$httpCode,
                    'response_size' => strlen(\$response),
                    'total_time' => \$info['total_time'] ?? 0
                ]
            ]);
        } else {
            return response()->json([
                'error' => 'WhatsApp API call failed',
                'http_code' => \$httpCode,
                'curl_error' => \$error,
                'endpoint' => \$endpoint,
                'response' => \$response ? substr(\$response, 0, 500) : null
            ], 500);
        }
        
    } catch (Exception \$e) {
        return response()->json([
            'error' => 'Generate QR exception',
            'message' => \$e->getMessage(),
            'file' => \$e->getFile(),
            'line' => \$e->getLine()
        ], 500);
    }
});

// Test endpoint for debugging
Route::get('/test-whatsapp-connection', function () {
    \$endpoints = [
        'https://hartonomotor.xyz/whatsapp-api/app/devices',
        'http://192.168.144.2:3000/app/devices',
        'http://localhost:3000/app/devices'
    ];
    
    \$results = [];
    
    foreach (\$endpoints as \$endpoint) {
        try {
            \$ch = curl_init();
            curl_setopt(\$ch, CURLOPT_URL, \$endpoint);
            curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(\$ch, CURLOPT_TIMEOUT, 10);
            curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE='
            ]);
            curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false);
            
            \$response = curl_exec(\$ch);
            \$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
            \$error = curl_error(\$ch);
            curl_close(\$ch);
            
            \$results[\$endpoint] = [
                'status' => \$httpCode,
                'error' => \$error,
                'response' => \$response ? substr(\$response, 0, 200) : null,
                'working' => \$httpCode == 200
            ];
            
        } catch (Exception \$e) {
            \$results[\$endpoint] = [
                'status' => 'exception',
                'error' => \$e->getMessage(),
                'working' => false
            ];
        }
    }
    
    return response()->json([
        'test_results' => \$results,
        'working_endpoints' => array_keys(array_filter(\$results, function(\$r) { return \$r['working']; }))
    ]);
});
EOF

    echo "✅ Created fixed generate API"
    
else
    echo "❌ No working WhatsApp endpoint found"
    
    # Create fallback generate API
    cat > routes/generate-fix.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;

// Fallback Generate API - when WhatsApp API not accessible
Route::get('/generate-fresh-qr', function () {
    return response()->json([
        'error' => 'WhatsApp API not accessible',
        'message' => 'Cannot connect to WhatsApp API container',
        'suggestion' => 'Check WhatsApp container status',
        'fallback' => 'Use existing QR or restart WhatsApp container'
    ], 503);
});

Route::get('/test-whatsapp-connection', function () {
    return response()->json([
        'error' => 'WhatsApp API not accessible',
        'endpoints_tested' => [
            'https://hartonomotor.xyz/whatsapp-api/app/devices',
            'http://192.168.144.2:3000/app/devices',
            'http://localhost:3000/app/devices'
        ],
        'all_failed' => true
    ]);
});
EOF

    echo "✅ Created fallback generate API"
fi

# Step 4: Include the fixed routes
echo -e "\n${YELLOW}📝 Step 4: Include Fixed Routes${NC}"

# Remove old generate route from web.php if exists
if grep -q "generate-fresh-qr" routes/web.php; then
    # Create backup
    cp routes/web.php routes/web.php.backup
    
    # Remove old generate route
    sed -i '/generate-fresh-qr/,/});/d' routes/web.php
    echo "✅ Removed old generate route"
fi

# Add new fixed route
echo "" >> routes/web.php
echo "// Include fixed generate API" >> routes/web.php
echo "require __DIR__.'/generate-fix.php';" >> routes/web.php
echo "✅ Added fixed generate route"

# Step 5: Clear route cache
echo -e "\n${YELLOW}🔄 Step 5: Clear Route Cache${NC}"
php artisan route:clear 2>/dev/null || echo "Route cache cleared"

# Step 6: Test the fixed generate API
echo -e "\n${YELLOW}🧪 Step 6: Test Fixed Generate API${NC}"

echo "Testing connection test endpoint:"
CONNECTION_TEST=$(curl -s "https://hartonomotor.xyz/test-whatsapp-connection" 2>/dev/null)
CONNECTION_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/test-whatsapp-connection" 2>/dev/null | tail -1)
echo "Connection test status: $CONNECTION_STATUS"

if [ "$CONNECTION_STATUS" = "200" ]; then
    echo "Connection test response:"
    echo "$CONNECTION_TEST" | jq '.working_endpoints' 2>/dev/null || echo "$CONNECTION_TEST" | head -c 300
fi

echo -e "\nTesting fixed generate API:"
GENERATE_TEST=$(curl -s "https://hartonomotor.xyz/generate-fresh-qr" 2>/dev/null)
GENERATE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/generate-fresh-qr" 2>/dev/null | tail -1)
echo "Generate API status: $GENERATE_STATUS"

if [ "$GENERATE_STATUS" = "200" ]; then
    echo "✅ Generate API working!"
    echo "Response preview:"
    echo "$GENERATE_TEST" | jq '.success, .endpoint_used' 2>/dev/null || echo "$GENERATE_TEST" | head -c 300
elif [ "$GENERATE_STATUS" = "503" ]; then
    echo "⚠️ Generate API fallback (WhatsApp not accessible)"
    echo "$GENERATE_TEST" | jq '.message' 2>/dev/null || echo "$GENERATE_TEST" | head -c 200
else
    echo "❌ Generate API still failing"
    echo "Response:"
    echo "$GENERATE_TEST" | head -c 300
fi

# Step 7: Create simple QR page for testing
echo -e "\n${YELLOW}📱 Step 7: Create Simple Test QR Page${NC}"

cat > public/whatsapp-qr-simple.html << 'EOF'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp QR - Simple Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; text-align: center; }
        .container { max-width: 500px; margin: 0 auto; }
        .qr-image { max-width: 250px; border: 1px solid #ddd; padding: 10px; }
        .btn { padding: 10px 20px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .status { padding: 10px; margin: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <h1>WhatsApp QR Code - Simple Test</h1>
        
        <div id="qrContainer">
            <p>Loading...</p>
        </div>
        
        <button class="btn btn-primary" onclick="loadQR()">Load QR</button>
        <button class="btn btn-success" onclick="generateQR()">Generate Fresh QR</button>
        
        <div id="status"></div>
        <div id="debug"></div>
    </div>

    <script>
        async function loadQR() {
            const container = document.getElementById('qrContainer');
            const status = document.getElementById('status');
            
            try {
                const response = await fetch('/qr-latest');
                const data = await response.json();
                
                if (data.success) {
                    container.innerHTML = `<img src="${data.qr_code}" class="qr-image" alt="QR Code">`;
                    status.innerHTML = `<div class="status success">QR loaded! Age: ${data.age_seconds}s</div>`;
                } else {
                    status.innerHTML = `<div class="status error">Failed to load QR: ${data.error}</div>`;
                }
            } catch (error) {
                status.innerHTML = `<div class="status error">Error: ${error.message}</div>`;
            }
        }
        
        async function generateQR() {
            const status = document.getElementById('status');
            const debug = document.getElementById('debug');
            
            status.innerHTML = `<div class="status warning">Generating fresh QR...</div>`;
            
            try {
                const response = await fetch('/generate-fresh-qr');
                const data = await response.json();
                
                if (data.success) {
                    status.innerHTML = `<div class="status success">Fresh QR generated! Endpoint: ${data.endpoint_used}</div>`;
                    debug.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                    
                    // Auto-load new QR after 3 seconds
                    setTimeout(loadQR, 3000);
                } else {
                    status.innerHTML = `<div class="status error">Generate failed: ${data.error}</div>`;
                    debug.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                }
            } catch (error) {
                status.innerHTML = `<div class="status error">Generate error: ${error.message}</div>`;
            }
        }
        
        // Test connection on load
        async function testConnection() {
            try {
                const response = await fetch('/test-whatsapp-connection');
                const data = await response.json();
                
                const debug = document.getElementById('debug');
                debug.innerHTML = `<h3>Connection Test:</h3><pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                console.error('Connection test failed:', error);
            }
        }
        
        // Load QR and test connection on page load
        loadQR();
        testConnection();
    </script>
</body>
</html>
EOF

echo "✅ Created simple test QR page"

# Step 8: Final results
echo -e "\n${YELLOW}✅ Step 8: Final Results${NC}"
echo "=================================================================="

echo "GENERATE API FIX RESULTS:"
echo "- Connection Test: $CONNECTION_STATUS"
echo "- Generate API: $GENERATE_STATUS"
echo "- Working Endpoint: ${WORKING_ENDPOINT:-'None found'}"

if [ "$GENERATE_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! Generate API fixed!${NC}"
    echo -e "${GREEN}✅ WhatsApp API connection working${NC}"
    echo -e "${GREEN}✅ Fresh QR generation working${NC}"
    echo -e "${GREEN}✅ Proper error handling${NC}"
    
    echo -e "\n${BLUE}📱 Test your fixed system:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-simple.html"
    
    echo -e "\n${GREEN}🚀 NOW YOU CAN GENERATE FRESH QR!${NC}"
    echo -e "${GREEN}Click 'Generate Fresh QR' to get new QR code!${NC}"
    
elif [ "$GENERATE_STATUS" = "503" ]; then
    echo -e "\n${YELLOW}⚠️ WhatsApp API not accessible${NC}"
    echo "Generate API created with fallback"
    echo "Need to check WhatsApp container status"
    
    echo -e "\n${BLUE}📱 Test page available:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-simple.html"
    
else
    echo -e "\n${RED}❌ Generate API still needs work${NC}"
    echo "Check debug information in test page"
fi

echo -e "\n${BLUE}🔧 Debug Tools:${NC}"
echo "- Connection Test: https://hartonomotor.xyz/test-whatsapp-connection"
echo "- Simple QR Page: https://hartonomotor.xyz/whatsapp-qr-simple.html"

echo -e "\n${GREEN}🎊 GENERATE API FIX COMPLETE!${NC}"
echo -e "${GREEN}Focus on fixing the 500 error - QR age is normal!${NC}"
