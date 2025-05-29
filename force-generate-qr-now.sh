#!/bin/bash

# FORCE GENERATE QR NOW - Langsung dapat QR untuk scan
# No web, no complicated setup - just get fresh QR!

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}⚡ FORCE GENERATE QR NOW${NC}"
echo "=================================================="

echo -e "${YELLOW}🎯 LANGSUNG GENERATE QR FRESH UNTUK SCAN!${NC}"

# Step 1: Force generate via WhatsApp API directly
echo -e "\n${YELLOW}📱 Step 1: Force Generate via WhatsApp API${NC}"

echo "Generating fresh QR via WhatsApp API..."

# Try direct WhatsApp API call
FRESH_QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" \
    -H "Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=" \
    2>/dev/null)

FRESH_QR_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" \
    -H "Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=" \
    2>/dev/null | tail -1)

echo "WhatsApp API status: $FRESH_QR_STATUS"

if [ "$FRESH_QR_STATUS" = "200" ]; then
    echo "✅ Fresh QR generated successfully!"
    
    # Extract QR link from response
    QR_LINK=$(echo "$FRESH_QR_RESPONSE" | jq -r '.results.qr_link' 2>/dev/null)
    QR_DURATION=$(echo "$FRESH_QR_RESPONSE" | jq -r '.results.qr_duration' 2>/dev/null)
    
    if [ "$QR_LINK" != "null" ] && [ -n "$QR_LINK" ]; then
        echo -e "\n${GREEN}🎉 FRESH QR READY!${NC}"
        echo -e "${GREEN}QR Link: $QR_LINK${NC}"
        echo -e "${GREEN}Duration: $QR_DURATION seconds${NC}"
        echo -e "${GREEN}Generated: $(date)${NC}"
        
        # Wait for file to be created
        sleep 3
        
        # Get the latest QR file
        LATEST_QR_FILE=$(ls -t /var/www/whatsapp_statics/qrcode/scan-qr-*.png 2>/dev/null | head -1)
        
        if [ -n "$LATEST_QR_FILE" ]; then
            QR_FILENAME=$(basename "$LATEST_QR_FILE")
            QR_AGE=$(stat -c %Y "$LATEST_QR_FILE")
            CURRENT_TIME=$(date +%s)
            AGE_SECONDS=$((CURRENT_TIME - QR_AGE))
            
            echo -e "\n${BLUE}📄 QR File Info:${NC}"
            echo "Filename: $QR_FILENAME"
            echo "Age: $AGE_SECONDS seconds"
            echo "File path: $LATEST_QR_FILE"
            echo "File size: $(stat -c %s "$LATEST_QR_FILE") bytes"
            
            if [ "$AGE_SECONDS" -lt 60 ]; then
                echo -e "${GREEN}✅ QR is FRESH (< 1 minute old)${NC}"
                QR_STATUS="FRESH"
            else
                echo -e "${YELLOW}⚠️ QR is $AGE_SECONDS seconds old${NC}"
                QR_STATUS="OLD"
            fi
        fi
        
    else
        echo -e "${RED}❌ No QR link in response${NC}"
        echo "Response: $FRESH_QR_RESPONSE"
    fi
    
else
    echo -e "${RED}❌ WhatsApp API failed: $FRESH_QR_STATUS${NC}"
    echo "Response: $FRESH_QR_RESPONSE"
fi

# Step 2: Get QR via Laravel API (base64)
echo -e "\n${YELLOW}📡 Step 2: Get QR via Laravel API${NC}"

LARAVEL_QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/qr-latest" 2>/dev/null)
LARAVEL_QR_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-latest" 2>/dev/null | tail -1)

echo "Laravel API status: $LARAVEL_QR_STATUS"

if [ "$LARAVEL_QR_STATUS" = "200" ]; then
    echo "✅ Laravel API working!"
    
    # Extract QR data
    QR_FILENAME_LARAVEL=$(echo "$LARAVEL_QR_RESPONSE" | jq -r '.filename' 2>/dev/null)
    QR_AGE_LARAVEL=$(echo "$LARAVEL_QR_RESPONSE" | jq -r '.age_seconds' 2>/dev/null)
    QR_BASE64=$(echo "$LARAVEL_QR_RESPONSE" | jq -r '.qr_code' 2>/dev/null)
    
    echo "Laravel QR filename: $QR_FILENAME_LARAVEL"
    echo "Laravel QR age: $QR_AGE_LARAVEL seconds"
    
    if [ "$QR_AGE_LARAVEL" != "null" ] && [ "$QR_AGE_LARAVEL" -lt 60 ]; then
        echo -e "${GREEN}✅ Laravel QR is FRESH${NC}"
        LARAVEL_QR_STATUS_TEXT="FRESH"
    else
        echo -e "${YELLOW}⚠️ Laravel QR is OLD ($QR_AGE_LARAVEL seconds)${NC}"
        LARAVEL_QR_STATUS_TEXT="OLD"
    fi
else
    echo -e "${RED}❌ Laravel API failed${NC}"
fi

# Step 3: Create instant QR display page
echo -e "\n${YELLOW}📱 Step 3: Create Instant QR Display${NC}"

cat > public/qr-instant.html << EOF
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code INSTANT - Scan Now!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .qr-image {
            max-width: 250px;
            width: 100%;
            border: 2px solid #25D366;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }
        .fresh { color: #28a745; font-weight: bold; }
        .old { color: #ffc107; font-weight: bold; }
        .expired { color: #dc3545; font-weight: bold; }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
        }
        .btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px;
            font-size: 16px;
        }
        .btn:hover { background: #128C7E; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔥 QR Code INSTANT</h1>
        <p><strong>Generated: $(date)</strong></p>
        
        <div id="qrContainer">
            <p>Loading QR...</p>
        </div>
        
        <button class="btn" onclick="loadFreshQR()">🔄 Refresh QR</button>
        
        <div class="info">
            <strong>📱 Cara Scan:</strong><br>
            1. Buka WhatsApp<br>
            2. Settings → Linked Devices<br>
            3. Link a Device<br>
            4. Scan QR di atas<br>
            5. Done! ✅
        </div>
        
        <div id="debug"></div>
    </div>

    <script>
        async function loadFreshQR() {
            const container = document.getElementById('qrContainer');
            const debug = document.getElementById('debug');
            
            container.innerHTML = '<p>Loading fresh QR...</p>';
            
            try {
                const response = await fetch('/qr-latest?t=' + Date.now());
                const data = await response.json();
                
                if (data.success && data.qr_code) {
                    const ageSeconds = data.age_seconds || 0;
                    const ageMinutes = Math.floor(ageSeconds / 60);
                    
                    let statusClass, statusText;
                    if (ageSeconds < 60) {
                        statusClass = 'fresh';
                        statusText = '🟢 FRESH - Scan sekarang!';
                    } else if (ageSeconds < 300) {
                        statusClass = 'old';
                        statusText = '🟡 Masih OK - Bisa scan';
                    } else {
                        statusClass = 'expired';
                        statusText = '🔴 EXPIRED - Generate baru';
                    }
                    
                    container.innerHTML = \`
                        <img src="\${data.qr_code}" class="qr-image" alt="WhatsApp QR Code">
                        <p class="\${statusClass}">\${statusText}</p>
                        <p>Age: \${ageMinutes}m \${ageSeconds % 60}s</p>
                        <p>File: \${data.filename}</p>
                    \`;
                    
                    debug.innerHTML = \`
                        <div style="text-align: left; font-size: 12px; margin-top: 20px;">
                            <strong>Debug Info:</strong><br>
                            Filename: \${data.filename}<br>
                            Created: \${data.created_at}<br>
                            Age: \${ageSeconds} seconds<br>
                            Size: \${data.size} bytes<br>
                            Total files: \${data.total_qr_files || 'N/A'}
                        </div>
                    \`;
                } else {
                    container.innerHTML = '<p style="color: red;">❌ Failed to load QR</p>';
                    debug.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                }
            } catch (error) {
                container.innerHTML = '<p style="color: red;">❌ Error: ' + error.message + '</p>';
            }
        }
        
        // Auto-load QR on page load
        loadFreshQR();
        
        // Auto-refresh every 30 seconds
        setInterval(loadFreshQR, 30000);
    </script>
</body>
</html>
EOF

echo "✅ Created instant QR display page"

# Step 4: Final summary and direct links
echo -e "\n${YELLOW}✅ FINAL SUMMARY${NC}"
echo "=================================================================="

echo -e "${BLUE}🎯 QR CODE READY FOR SCAN:${NC}"

if [ "$FRESH_QR_STATUS" = "200" ] && [ -n "$QR_LINK" ]; then
    echo -e "${GREEN}✅ FRESH QR GENERATED!${NC}"
    echo -e "${GREEN}Direct QR URL: $QR_LINK${NC}"
    echo -e "${GREEN}Duration: $QR_DURATION seconds${NC}"
    echo -e "${GREEN}Status: FRESH & READY TO SCAN${NC}"
elif [ "$LARAVEL_QR_STATUS" = "200" ]; then
    echo -e "${YELLOW}⚠️ Using existing QR from Laravel${NC}"
    echo -e "${YELLOW}Age: $QR_AGE_LARAVEL seconds${NC}"
    echo -e "${YELLOW}Status: $LARAVEL_QR_STATUS_TEXT${NC}"
else
    echo -e "${RED}❌ No QR available${NC}"
fi

echo -e "\n${BLUE}📱 INSTANT QR PAGE:${NC}"
echo -e "${GREEN}https://hartonomotor.xyz/qr-instant.html${NC}"

echo -e "\n${BLUE}🔗 DIRECT LINKS:${NC}"
if [ -n "$QR_LINK" ]; then
    echo -e "${GREEN}Fresh QR Image: $QR_LINK${NC}"
fi
echo -e "${GREEN}Instant QR Page: https://hartonomotor.xyz/qr-instant.html${NC}"

echo -e "\n${BLUE}📱 CARA SCAN:${NC}"
echo "1. Buka link: https://hartonomotor.xyz/qr-instant.html"
echo "2. Buka WhatsApp → Settings → Linked Devices"
echo "3. Tap 'Link a Device'"
echo "4. Scan QR code yang muncul"
echo "5. Done! ✅"

echo -e "\n${GREEN}🎉 QR SIAP UNTUK SCAN!${NC}"
echo -e "${GREEN}Langsung buka link dan scan QR code!${NC}"

# Show current QR file info
if [ -n "$LATEST_QR_FILE" ]; then
    echo -e "\n${BLUE}📄 Current QR File:${NC}"
    echo "File: $(basename "$LATEST_QR_FILE")"
    echo "Age: $AGE_SECONDS seconds"
    echo "Status: $QR_STATUS"
    echo "Path: $LATEST_QR_FILE"
fi

echo -e "\n${GREEN}🚀 MISSION COMPLETE!${NC}"
echo -e "${GREEN}QR code ready - no complicated setup needed!${NC}"
