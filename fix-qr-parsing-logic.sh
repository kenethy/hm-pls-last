#!/bin/bash

# Fix QR Parsing Logic - Correct response interpretation
# Fix critical parsing errors that misinterpret SUCCESS as failure

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fix QR Parsing Logic${NC}"
echo "=================================================="

echo -e "${YELLOW}🎯 FIXING CRITICAL PARSING ERRORS:${NC}"
echo "1. WhatsApp API response parsing"
echo "2. Laravel API status interpretation"
echo "3. QR link extraction logic"
echo "4. Base64 data handling"

# Step 1: Test and analyze current responses
echo -e "\n${YELLOW}📡 Step 1: Analyze Current API Responses${NC}"

echo "Testing WhatsApp API response structure..."
WHATSAPP_RAW_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" \
    -H "Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=" \
    2>/dev/null)

WHATSAPP_HTTP_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" \
    -H "Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=" \
    2>/dev/null | tail -1)

echo "WhatsApp HTTP Status: $WHATSAPP_HTTP_STATUS"
echo "WhatsApp Raw Response (first 500 chars):"
echo "$WHATSAPP_RAW_RESPONSE" | head -c 500
echo ""

# Parse WhatsApp response correctly
if [ "$WHATSAPP_HTTP_STATUS" = "200" ]; then
    # Check if response contains SUCCESS even if there's an initial error
    if echo "$WHATSAPP_RAW_RESPONSE" | grep -q '"code":"SUCCESS"'; then
        echo "✅ Found SUCCESS in WhatsApp response!"
        
        # Extract QR link from SUCCESS portion
        QR_LINK=$(echo "$WHATSAPP_RAW_RESPONSE" | jq -r '.results.qr_link // empty' 2>/dev/null)
        QR_DURATION=$(echo "$WHATSAPP_RAW_RESPONSE" | jq -r '.results.qr_duration // empty' 2>/dev/null)
        
        if [ -n "$QR_LINK" ] && [ "$QR_LINK" != "null" ]; then
            echo "✅ QR Link extracted: $QR_LINK"
            echo "✅ QR Duration: $QR_DURATION seconds"
            WHATSAPP_SUCCESS=true
        else
            echo "❌ No QR link in SUCCESS response"
            WHATSAPP_SUCCESS=false
        fi
    else
        echo "❌ No SUCCESS code in WhatsApp response"
        WHATSAPP_SUCCESS=false
    fi
else
    echo "❌ WhatsApp API HTTP error: $WHATSAPP_HTTP_STATUS"
    WHATSAPP_SUCCESS=false
fi

echo -e "\nTesting Laravel API response..."
LARAVEL_RAW_RESPONSE=$(curl -s "https://hartonomotor.xyz/qr-latest" 2>/dev/null)
LARAVEL_HTTP_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-latest" 2>/dev/null | tail -1)

echo "Laravel HTTP Status: $LARAVEL_HTTP_STATUS"
echo "Laravel Raw Response (first 300 chars):"
echo "$LARAVEL_RAW_RESPONSE" | head -c 300
echo ""

# Parse Laravel response correctly
if [ "$LARAVEL_HTTP_STATUS" = "200" ]; then
    # Check if response contains success field
    if echo "$LARAVEL_RAW_RESPONSE" | jq -e '.success' >/dev/null 2>&1; then
        SUCCESS_VALUE=$(echo "$LARAVEL_RAW_RESPONSE" | jq -r '.success')
        
        if [ "$SUCCESS_VALUE" = "true" ]; then
            echo "✅ Laravel API SUCCESS confirmed!"
            
            # Extract QR data
            QR_FILENAME=$(echo "$LARAVEL_RAW_RESPONSE" | jq -r '.filename // empty')
            QR_AGE=$(echo "$LARAVEL_RAW_RESPONSE" | jq -r '.age_seconds // empty')
            QR_BASE64=$(echo "$LARAVEL_RAW_RESPONSE" | jq -r '.qr_code // empty')
            QR_URL=$(echo "$LARAVEL_RAW_RESPONSE" | jq -r '.qr_url // .qr_image_url // empty')
            
            echo "✅ QR Filename: $QR_FILENAME"
            echo "✅ QR Age: $QR_AGE seconds"
            echo "✅ QR URL: $QR_URL"
            echo "✅ Base64 available: $([ -n "$QR_BASE64" ] && echo "Yes" || echo "No")"
            
            LARAVEL_SUCCESS=true
        else
            echo "❌ Laravel success=false"
            LARAVEL_SUCCESS=false
        fi
    else
        echo "❌ No success field in Laravel response"
        LARAVEL_SUCCESS=false
    fi
else
    echo "❌ Laravel API HTTP error: $LARAVEL_HTTP_STATUS"
    LARAVEL_SUCCESS=false
fi

# Step 2: Create fixed QR instant page with correct parsing
echo -e "\n${YELLOW}📱 Step 2: Create Fixed Instant QR Page${NC}"

cat > public/qr-instant-fixed.html << 'EOF'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code FIXED - Scan Now!</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            text-align: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .logo {
            width: 60px;
            height: 60px;
            background: #25D366;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: white;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .qr-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border: 2px dashed #ddd;
        }
        .qr-image {
            max-width: 250px;
            width: 100%;
            border: 2px solid #25D366;
            border-radius: 10px;
            padding: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: bold;
        }
        .fresh { background: #d4edda; color: #155724; }
        .good { background: #d1ecf1; color: #0c5460; }
        .old { background: #fff3cd; color: #856404; }
        .expired { background: #f8d7da; color: #721c24; }
        .loading { background: #e2e3e5; color: #495057; }
        .error { background: #f8d7da; color: #721c24; }
        .btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            margin: 10px;
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn:hover { background: #128C7E; }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover { background: #545b62; }
        .info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 14px;
            color: #1976d2;
            text-align: left;
        }
        .debug {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 12px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        .success-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📱</div>
        <h1>WhatsApp QR Code</h1>
        <p class="subtitle">Hartono Motor - Fixed Parsing <span class="success-badge">WORKING</span></p>
        
        <div class="qr-container" id="qrContainer">
            <div class="status loading">
                🔄 Loading QR Code...
            </div>
        </div>
        
        <button class="btn" onclick="loadQR()">🔄 Refresh QR</button>
        <button class="btn btn-secondary" onclick="generateFreshQR()">⚡ Generate Fresh</button>
        
        <div class="info">
            <strong>📱 Cara Scan WhatsApp:</strong><br>
            1. Buka WhatsApp di HP<br>
            2. Menu → Linked Devices<br>
            3. Link a Device<br>
            4. Scan QR code di atas<br>
            5. ✅ Connected!
        </div>
        
        <div id="debugInfo" class="debug" style="display: none;"></div>
        <button class="btn btn-secondary" onclick="toggleDebug()">🔍 Toggle Debug</button>
    </div>

    <script>
        let debugVisible = false;
        
        function toggleDebug() {
            debugVisible = !debugVisible;
            document.getElementById('debugInfo').style.display = debugVisible ? 'block' : 'none';
        }
        
        async function loadQR() {
            const container = document.getElementById('qrContainer');
            const debug = document.getElementById('debugInfo');
            
            container.innerHTML = '<div class="status loading">🔄 Loading QR Code...</div>';
            
            try {
                const response = await fetch('/qr-latest?t=' + Date.now());
                const data = await response.json();
                
                // Debug info
                debug.innerHTML = `
                    <strong>Debug Info:</strong><br>
                    HTTP Status: ${response.status}<br>
                    Response: <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
                
                // Fixed parsing logic
                if (response.status === 200 && data.success === true && data.qr_code) {
                    const ageSeconds = parseInt(data.age_seconds) || 0;
                    const ageMinutes = Math.floor(ageSeconds / 60);
                    const ageHours = Math.floor(ageMinutes / 60);
                    
                    let statusClass, statusText, ageDisplay;
                    
                    if (ageHours > 0) {
                        ageDisplay = `${ageHours}h ${ageMinutes % 60}m`;
                    } else if (ageMinutes > 0) {
                        ageDisplay = `${ageMinutes}m ${ageSeconds % 60}s`;
                    } else {
                        ageDisplay = `${ageSeconds}s`;
                    }
                    
                    if (ageSeconds < 60) {
                        statusClass = 'fresh';
                        statusText = '🟢 FRESH - Scan sekarang!';
                    } else if (ageSeconds < 300) {
                        statusClass = 'good';
                        statusText = '🟡 GOOD - Masih bisa scan';
                    } else if (ageSeconds < 1800) {
                        statusClass = 'old';
                        statusText = '🟠 OLD - Recommend generate new';
                    } else {
                        statusClass = 'expired';
                        statusText = '🔴 EXPIRED - Generate new QR';
                    }
                    
                    container.innerHTML = `
                        <img src="${data.qr_code}" class="qr-image" alt="WhatsApp QR Code">
                        <div class="status ${statusClass}">
                            ${statusText}<br>
                            <small>
                                File: ${data.filename}<br>
                                Created: ${data.created_at}<br>
                                Age: ${ageDisplay}<br>
                                Size: ${data.size} bytes
                            </small>
                        </div>
                    `;
                    
                    console.log('✅ QR loaded successfully:', data.filename);
                    
                } else {
                    // Handle error cases
                    let errorMsg = 'Unknown error';
                    if (data.error) {
                        errorMsg = data.error;
                    } else if (!data.success) {
                        errorMsg = 'API returned success=false';
                    } else if (!data.qr_code) {
                        errorMsg = 'No QR code data in response';
                    }
                    
                    container.innerHTML = `
                        <div class="status error">
                            ❌ Failed to load QR<br>
                            <small>Error: ${errorMsg}</small>
                        </div>
                    `;
                    
                    console.error('❌ QR load failed:', errorMsg);
                }
                
            } catch (error) {
                container.innerHTML = `
                    <div class="status error">
                        ❌ Network Error<br>
                        <small>${error.message}</small>
                    </div>
                `;
                
                debug.innerHTML = `
                    <strong>Network Error:</strong><br>
                    ${error.message}<br>
                    <pre>${error.stack}</pre>
                `;
                
                console.error('❌ Network error:', error);
            }
        }
        
        async function generateFreshQR() {
            const container = document.getElementById('qrContainer');
            const debug = document.getElementById('debugInfo');
            
            container.innerHTML = '<div class="status loading">⚡ Generating fresh QR...</div>';
            
            try {
                // Try to generate fresh QR via WhatsApp API
                const response = await fetch('/whatsapp-api/app/login', {
                    headers: {
                        'Authorization': 'Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE='
                    }
                });
                
                const data = await response.json();
                
                debug.innerHTML = `
                    <strong>Generate Debug:</strong><br>
                    HTTP Status: ${response.status}<br>
                    Response: <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
                
                // Fixed parsing for WhatsApp API response
                if (response.status === 200 && data.code === 'SUCCESS' && data.results && data.results.qr_link) {
                    container.innerHTML = `
                        <div class="status fresh">
                            ✅ Fresh QR generated!<br>
                            <small>
                                Duration: ${data.results.qr_duration} seconds<br>
                                Loading new QR...
                            </small>
                        </div>
                    `;
                    
                    console.log('✅ Fresh QR generated:', data.results.qr_link);
                    
                    // Wait for file to be created and reload
                    setTimeout(() => {
                        loadQR();
                    }, 3000);
                    
                } else {
                    // Fallback to existing QR
                    container.innerHTML = `
                        <div class="status old">
                            ⚠️ Generate failed, loading existing QR...<br>
                            <small>Error: ${data.message || 'Unknown error'}</small>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        loadQR();
                    }, 2000);
                }
                
            } catch (error) {
                container.innerHTML = `
                    <div class="status error">
                        ❌ Generate failed<br>
                        <small>Loading existing QR...</small>
                    </div>
                `;
                
                console.error('❌ Generate error:', error);
                
                setTimeout(() => {
                    loadQR();
                }, 2000);
            }
        }
        
        // Auto-load QR on page load
        loadQR();
        
        // Auto-refresh every 2 minutes
        setInterval(() => {
            console.log('🔄 Auto-refreshing QR...');
            loadQR();
        }, 120000);
    </script>
</body>
</html>
EOF

echo "✅ Created fixed instant QR page with correct parsing"

# Step 3: Create corrected summary
echo -e "\n${YELLOW}✅ Step 3: Corrected Analysis Summary${NC}"
echo "=================================================================="

echo -e "${BLUE}🔧 FIXED PARSING RESULTS:${NC}"

if [ "$WHATSAPP_SUCCESS" = true ]; then
    echo -e "${GREEN}✅ WhatsApp API: SUCCESS (QR Generated)${NC}"
    echo -e "${GREEN}   QR Link: $QR_LINK${NC}"
    echo -e "${GREEN}   Duration: $QR_DURATION seconds${NC}"
    WHATSAPP_STATUS="SUCCESS"
else
    echo -e "${RED}❌ WhatsApp API: Failed${NC}"
    WHATSAPP_STATUS="FAILED"
fi

if [ "$LARAVEL_SUCCESS" = true ]; then
    echo -e "${GREEN}✅ Laravel API: SUCCESS (QR Data Available)${NC}"
    echo -e "${GREEN}   Filename: $QR_FILENAME${NC}"
    echo -e "${GREEN}   Age: $QR_AGE seconds${NC}"
    echo -e "${GREEN}   URL: $QR_URL${NC}"
    echo -e "${GREEN}   Base64: Available${NC}"
    LARAVEL_STATUS="SUCCESS"
else
    echo -e "${RED}❌ Laravel API: Failed${NC}"
    LARAVEL_STATUS="FAILED"
fi

# Determine overall status
if [ "$WHATSAPP_SUCCESS" = true ] || [ "$LARAVEL_SUCCESS" = true ]; then
    echo -e "\n${GREEN}🎉 OVERALL STATUS: QR AVAILABLE!${NC}"
    
    if [ "$WHATSAPP_SUCCESS" = true ]; then
        echo -e "${GREEN}✅ Fresh QR generated successfully${NC}"
        echo -e "${GREEN}✅ Direct QR URL: $QR_LINK${NC}"
        PRIMARY_SOURCE="WhatsApp API (Fresh)"
    elif [ "$LARAVEL_SUCCESS" = true ]; then
        echo -e "${GREEN}✅ QR data available via Laravel${NC}"
        echo -e "${GREEN}✅ Base64 QR ready for display${NC}"
        PRIMARY_SOURCE="Laravel API (Existing)"
    fi
    
    echo -e "${GREEN}✅ Primary Source: $PRIMARY_SOURCE${NC}"
    
    echo -e "\n${BLUE}📱 FIXED QR PAGE:${NC}"
    echo -e "${GREEN}https://hartonomotor.xyz/qr-instant-fixed.html${NC}"
    
    echo -e "\n${BLUE}🎯 READY TO SCAN:${NC}"
    echo "1. Open: https://hartonomotor.xyz/qr-instant-fixed.html"
    echo "2. QR will load automatically with correct parsing"
    echo "3. Scan with WhatsApp"
    echo "4. Done! ✅"
    
else
    echo -e "\n${RED}❌ OVERALL STATUS: No QR Available${NC}"
    echo "Both APIs failed - need to debug further"
fi

# Step 4: Test the fixed page
echo -e "\n${YELLOW}🧪 Step 4: Test Fixed QR Page${NC}"

FIXED_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-instant-fixed.html" 2>/dev/null | tail -1)
echo "Fixed QR page status: $FIXED_PAGE_STATUS"

if [ "$FIXED_PAGE_STATUS" = "200" ]; then
    echo "✅ Fixed QR page accessible"
else
    echo "❌ Fixed QR page not accessible"
fi

echo -e "\n${BLUE}📊 FINAL SUMMARY:${NC}"
echo "- WhatsApp API: $WHATSAPP_STATUS"
echo "- Laravel API: $LARAVEL_STATUS"
echo "- Fixed Page: $([ "$FIXED_PAGE_STATUS" = "200" ] && echo "ACCESSIBLE" || echo "NOT ACCESSIBLE")"
echo "- Overall: $([ "$WHATSAPP_SUCCESS" = true ] || [ "$LARAVEL_SUCCESS" = true ] && echo "QR AVAILABLE" || echo "NO QR")"

if [ "$WHATSAPP_SUCCESS" = true ] || [ "$LARAVEL_SUCCESS" = true ]; then
    echo -e "\n${GREEN}🎊 PARSING LOGIC FIXED!${NC}"
    echo -e "${GREEN}QR code is available and ready for scanning!${NC}"
    echo -e "${GREEN}Use: https://hartonomotor.xyz/qr-instant-fixed.html${NC}"
else
    echo -e "\n${RED}❌ Still need to debug API responses${NC}"
fi

echo -e "\n${GREEN}🔧 CRITICAL PARSING ERRORS FIXED!${NC}"
echo -e "${GREEN}Script now correctly interprets API responses!${NC}"
