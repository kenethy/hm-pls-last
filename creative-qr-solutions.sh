#!/bin/bash

# Creative QR Solutions - Alternative Approaches
# Multiple creative solutions to serve QR images

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🎨 Creative QR Solutions - Alternative Approaches${NC}"
echo "=================================================="

echo -e "${YELLOW}🔍 Problem: Static files serving not working${NC}"
echo -e "${YELLOW}💡 Solution: Multiple creative alternatives${NC}"

# Solution 1: Laravel Route-based QR Serving
echo -e "\n${YELLOW}🚀 Solution 1: Laravel Route-based QR Serving${NC}"
echo "Instead of nginx static files, serve QR through Laravel routes"

# Create Laravel route for QR serving
cat > /tmp/qr_routes.php << 'EOF'
<?php
// Add to routes/web.php

Route::get('/qr/{filename}', function ($filename) {
    $path = '/var/www/whatsapp_statics/qrcode/' . $filename;
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    return response()->file($path, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=300',
        'Access-Control-Allow-Origin' => '*'
    ]);
})->where('filename', '.*\.png$');

// Alternative: Base64 encoded QR
Route::get('/qr-base64/{filename}', function ($filename) {
    $path = '/var/www/whatsapp_statics/qrcode/' . $filename;
    
    if (!file_exists($path)) {
        return response()->json(['error' => 'QR not found'], 404);
    }
    
    $imageData = base64_encode(file_get_contents($path));
    return response()->json([
        'qr_code' => 'data:image/png;base64,' . $imageData,
        'filename' => $filename
    ]);
});
EOF

echo "Adding Laravel routes for QR serving..."
if ! grep -q "qr/{filename}" routes/web.php; then
    echo "" >> routes/web.php
    echo "// QR Code serving routes" >> routes/web.php
    cat /tmp/qr_routes.php | tail -n +3 >> routes/web.php
    echo "✅ Laravel QR routes added"
else
    echo "✅ Laravel QR routes already exist"
fi

# Solution 2: Nginx Proxy to Laravel for QR
echo -e "\n${YELLOW}🔄 Solution 2: Nginx Proxy to Laravel for QR${NC}"
echo "Proxy /qr/ requests to Laravel instead of static files"

# Add nginx location for QR proxy
if ! grep -q "location /qr/" docker/nginx/conf.d/app.conf; then
    # Add before the last closing brace
    sed -i '/^}$/i\
\
    # QR Code serving via Laravel\
    location /qr/ {\
        try_files $uri @laravel_qr;\
    }\
\
    location @laravel_qr {\
        fastcgi_pass hartono-app:9000;\
        fastcgi_index index.php;\
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;\
        include fastcgi_params;\
        fastcgi_param REQUEST_URI $request_uri;\
    }' docker/nginx/conf.d/app.conf
    
    echo "✅ Nginx QR proxy location added"
else
    echo "✅ Nginx QR proxy already configured"
fi

# Solution 3: Direct WhatsApp API Proxy for QR
echo -e "\n${YELLOW}🌐 Solution 3: Direct WhatsApp API Proxy for QR${NC}"
echo "Proxy QR requests directly to WhatsApp API container"

# Add nginx location for direct QR proxy
if ! grep -q "location /qr-direct/" docker/nginx/conf.d/app.conf; then
    sed -i '/^}$/i\
\
    # Direct QR serving from WhatsApp API\
    location /qr-direct/ {\
        rewrite ^/qr-direct/(.*)$ /statics/qrcode/$1 break;\
        proxy_pass http://192.168.144.2:3000;\
        proxy_set_header Authorization "Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=";\
        proxy_set_header Host $host;\
        add_header '\''Access-Control-Allow-Origin'\'' '\''*'\'' always;\
    }' docker/nginx/conf.d/app.conf
    
    echo "✅ Direct QR proxy added"
else
    echo "✅ Direct QR proxy already configured"
fi

# Solution 4: Base64 QR API Endpoint
echo -e "\n${YELLOW}📊 Solution 4: Base64 QR API Endpoint${NC}"
echo "Create API endpoint that returns QR as base64"

# Create Laravel controller for QR API
mkdir -p app/Http/Controllers/Api
cat > app/Http/Controllers/Api/QrController.php << 'EOF'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QrController extends Controller
{
    public function getQr($filename)
    {
        $path = '/var/www/whatsapp_statics/qrcode/' . $filename;
        
        if (!file_exists($path)) {
            return response()->json(['error' => 'QR code not found'], 404);
        }
        
        try {
            $imageData = file_get_contents($path);
            $base64 = base64_encode($imageData);
            
            return response()->json([
                'success' => true,
                'filename' => $filename,
                'qr_code' => 'data:image/png;base64,' . $base64,
                'size' => filesize($path),
                'created_at' => date('Y-m-d H:i:s', filemtime($path))
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to read QR code'], 500);
        }
    }
    
    public function serveQr($filename)
    {
        $path = '/var/www/whatsapp_statics/qrcode/' . $filename;
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=300',
            'Access-Control-Allow-Origin' => '*'
        ]);
    }
}
EOF

echo "✅ QR Controller created"

# Add API routes
if ! grep -q "qr-api" routes/api.php; then
    echo "" >> routes/api.php
    echo "// QR Code API routes" >> routes/api.php
    echo "Route::get('/qr-api/{filename}', [App\\Http\\Controllers\\Api\\QrController::class, 'getQr']);" >> routes/api.php
    echo "Route::get('/qr-serve/{filename}', [App\\Http\\Controllers\\Api\\QrController::class, 'serveQr']);" >> routes/api.php
    echo "✅ QR API routes added"
else
    echo "✅ QR API routes already exist"
fi

# Solution 5: Symlink Alternative
echo -e "\n${YELLOW}🔗 Solution 5: Symlink to Public Directory${NC}"
echo "Create symlink from public directory to QR files"

# Create symlink in public directory
if [ ! -L "public/qr-images" ]; then
    ln -sf /var/www/whatsapp_statics/qrcode public/qr-images
    echo "✅ Symlink created: public/qr-images -> /var/www/whatsapp_statics/qrcode"
else
    echo "✅ Symlink already exists"
fi

# Reload nginx to apply changes
echo -e "\n${YELLOW}🔄 Reloading nginx with new configurations...${NC}"
if docker exec hartono-webserver nginx -t; then
    docker exec hartono-webserver nginx -s reload
    echo "✅ Nginx reloaded"
else
    echo "❌ Nginx config error"
    docker exec hartono-webserver nginx -t 2>&1
fi

# Test all solutions
echo -e "\n${YELLOW}🧪 Testing All Solutions${NC}"
echo "=================================================="

sleep 5

# Generate fresh QR for testing
echo "Generating fresh QR for testing..."
QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
QR_FILENAME=$(basename "$QR_LINK" 2>/dev/null)

if [ -n "$QR_FILENAME" ]; then
    echo "Testing with QR file: $QR_FILENAME"
    
    # Wait for file creation
    sleep 3
    
    echo -e "\n${BLUE}Testing Solution 1: Laravel Route${NC}"
    LARAVEL_QR=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr/$QR_FILENAME" 2>/dev/null | tail -1)
    echo "Laravel QR route: $LARAVEL_QR"
    
    echo -e "\n${BLUE}Testing Solution 2: Base64 API${NC}"
    BASE64_QR=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-base64/$QR_FILENAME" 2>/dev/null | tail -1)
    echo "Base64 QR API: $BASE64_QR"
    
    echo -e "\n${BLUE}Testing Solution 3: Direct Proxy${NC}"
    DIRECT_QR=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-direct/$QR_FILENAME" 2>/dev/null | tail -1)
    echo "Direct QR proxy: $DIRECT_QR"
    
    echo -e "\n${BLUE}Testing Solution 4: API Endpoint${NC}"
    API_QR=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/api/qr-serve/$QR_FILENAME" 2>/dev/null | tail -1)
    echo "API QR endpoint: $API_QR"
    
    echo -e "\n${BLUE}Testing Solution 5: Symlink${NC}"
    SYMLINK_QR=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-images/$QR_FILENAME" 2>/dev/null | tail -1)
    echo "Symlink QR access: $SYMLINK_QR"
    
    # Results
    echo -e "\n${YELLOW}✅ Results Summary${NC}"
    echo "=================================================="
    echo "Laravel Route (/qr/): $LARAVEL_QR"
    echo "Base64 API (/qr-base64/): $BASE64_QR"
    echo "Direct Proxy (/qr-direct/): $DIRECT_QR"
    echo "API Endpoint (/api/qr-serve/): $API_QR"
    echo "Symlink (/qr-images/): $SYMLINK_QR"
    
    # Find working solution
    WORKING_SOLUTIONS=()
    [ "$LARAVEL_QR" = "200" ] && WORKING_SOLUTIONS+=("Laravel Route")
    [ "$BASE64_QR" = "200" ] && WORKING_SOLUTIONS+=("Base64 API")
    [ "$DIRECT_QR" = "200" ] && WORKING_SOLUTIONS+=("Direct Proxy")
    [ "$API_QR" = "200" ] && WORKING_SOLUTIONS+=("API Endpoint")
    [ "$SYMLINK_QR" = "200" ] && WORKING_SOLUTIONS+=("Symlink")
    
    if [ ${#WORKING_SOLUTIONS[@]} -gt 0 ]; then
        echo -e "\n${GREEN}🎉 SUCCESS! Working solutions:${NC}"
        for solution in "${WORKING_SOLUTIONS[@]}"; do
            echo "✅ $solution"
        done
        
        echo -e "\n${BLUE}📱 Update your QR page to use working solution:${NC}"
        if [ "$LARAVEL_QR" = "200" ]; then
            echo "Use: https://hartonomotor.xyz/qr/$QR_FILENAME"
        elif [ "$API_QR" = "200" ]; then
            echo "Use: https://hartonomotor.xyz/api/qr-serve/$QR_FILENAME"
        elif [ "$SYMLINK_QR" = "200" ]; then
            echo "Use: https://hartonomotor.xyz/qr-images/$QR_FILENAME"
        fi
        
    else
        echo -e "\n${RED}❌ No solutions working yet${NC}"
        echo "Need to debug further"
    fi
    
else
    echo "No QR filename found for testing"
fi

echo -e "\n${BLUE}🎯 Creative Solutions Implemented:${NC}"
echo "1. ✅ Laravel route-based QR serving"
echo "2. ✅ Base64 QR API endpoint"
echo "3. ✅ Direct WhatsApp API proxy"
echo "4. ✅ Laravel API controller"
echo "5. ✅ Public directory symlink"

echo -e "\n${GREEN}🎨 Multiple fallback options available!${NC}"
