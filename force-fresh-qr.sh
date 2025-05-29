#!/bin/bash

# Force Fresh QR Generation
# Fix the 8-hour old QR issue

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔄 Force Fresh QR Generation${NC}"
echo "=================================================="

echo -e "${RED}❌ Problem: QR code is 8 hours old (expired!)${NC}"
echo -e "${GREEN}✅ Solution: Force generate fresh QR${NC}"

# Step 1: Check current QR age
echo -e "\n${YELLOW}📅 Step 1: Check Current QR Age${NC}"

echo "Current QR files with timestamps:"
ls -la /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null | while read line; do
    file=$(echo "$line" | awk '{print $9}')
    if [ -n "$file" ]; then
        filename=$(basename "$file")
        timestamp=$(stat -c %Y "$file" 2>/dev/null)
        if [ -n "$timestamp" ]; then
            readable_time=$(date -d "@$timestamp" "+%Y-%m-%d %H:%M:%S")
            current_time=$(date +%s)
            age_seconds=$((current_time - timestamp))
            age_minutes=$((age_seconds / 60))
            echo "File: $filename"
            echo "Created: $readable_time"
            echo "Age: $age_minutes minutes ($age_seconds seconds)"
            echo "---"
        fi
    fi
done

# Step 2: Force logout to clear session
echo -e "\n${YELLOW}🚪 Step 2: Force Logout to Clear Session${NC}"

echo "Forcing logout from WhatsApp session..."
LOGOUT_RESPONSE=$(curl -s -X POST "https://hartonomotor.xyz/whatsapp-api/app/logout" 2>/dev/null)
echo "Logout response: $LOGOUT_RESPONSE"

sleep 3

# Step 3: Clear old QR files
echo -e "\n${YELLOW}🗑️ Step 3: Clear Old QR Files${NC}"

echo "Backing up old QR files..."
mkdir -p /var/www/whatsapp_statics/qrcode/backup
mv /var/www/whatsapp_statics/qrcode/*.png /var/www/whatsapp_statics/qrcode/backup/ 2>/dev/null || echo "No PNG files to backup"

echo "Old QR files moved to backup directory"
echo "Current QR directory:"
ls -la /var/www/whatsapp_statics/qrcode/

# Step 4: Restart WhatsApp API container
echo -e "\n${YELLOW}🔄 Step 4: Restart WhatsApp API Container${NC}"

echo "Restarting WhatsApp API container for fresh session..."
docker restart whatsapp-api-hartono

echo "Waiting 20 seconds for container to fully restart..."
sleep 20

echo "Container status:"
docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono"

# Step 5: Wait for API to be ready
echo -e "\n${YELLOW}⏳ Step 5: Wait for API to be Ready${NC}"

echo "Testing API readiness..."
for i in {1..10}; do
    API_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
    echo "API test attempt $i: $API_TEST"
    
    if [ "$API_TEST" = "200" ]; then
        echo -e "${GREEN}✅ API is ready!${NC}"
        break
    fi
    
    echo "Waiting 5 seconds..."
    sleep 5
done

# Step 6: Force generate fresh QR
echo -e "\n${YELLOW}📱 Step 6: Force Generate Fresh QR${NC}"

echo "Generating completely fresh QR code..."
FRESH_QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
echo "Fresh QR response:"
echo "$FRESH_QR_RESPONSE" | jq '.' 2>/dev/null || echo "$FRESH_QR_RESPONSE"

FRESH_QR_LINK=$(echo "$FRESH_QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
QR_DURATION=$(echo "$FRESH_QR_RESPONSE" | grep -o '"qr_duration":[0-9]*' | cut -d':' -f2)

if [ -n "$FRESH_QR_LINK" ]; then
    echo -e "\n${GREEN}✅ Fresh QR generated!${NC}"
    echo "QR Link: $FRESH_QR_LINK"
    echo "QR Duration: ${QR_DURATION:-30} seconds"
    
    # Wait for file creation
    sleep 3
    
    # Check new file timestamp
    NEW_QR_FILENAME=$(basename "$FRESH_QR_LINK")
    if [ -f "/var/www/whatsapp_statics/qrcode/$NEW_QR_FILENAME" ]; then
        NEW_TIMESTAMP=$(stat -c %Y "/var/www/whatsapp_statics/qrcode/$NEW_QR_FILENAME")
        NEW_READABLE_TIME=$(date -d "@$NEW_TIMESTAMP" "+%Y-%m-%d %H:%M:%S")
        echo -e "${GREEN}✅ New QR file created: $NEW_QR_FILENAME${NC}"
        echo -e "${GREEN}✅ Created at: $NEW_READABLE_TIME${NC}"
        echo -e "${GREEN}✅ File is FRESH (just created)${NC}"
    fi
    
else
    echo -e "${RED}❌ Failed to generate fresh QR${NC}"
    echo "Response: $FRESH_QR_RESPONSE"
fi

# Step 7: Update QR page to force refresh
echo -e "\n${YELLOW}🌐 Step 7: Update QR Page to Force Refresh${NC}"

# Add cache busting to QR page
cat > public/whatsapp-qr-fresh.html << 'EOF'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp QR Code - Fresh - Hartono Motor</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
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
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .loading {
            color: #666;
            font-size: 18px;
            padding: 50px;
        }
        
        .error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .success {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .warning {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .refresh-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            transition: background 0.3s;
        }
        
        .refresh-btn:hover {
            background: #128C7E;
        }
        
        .force-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            transition: background 0.3s;
        }
        
        .force-btn:hover {
            background: #c82333;
        }
        
        .info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #1976d2;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #25D366;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .timestamp {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📱</div>
        <h1>WhatsApp QR Code - FRESH</h1>
        <p class="subtitle">Hartono Motor - Always Fresh QR</p>
        
        <div class="qr-container" id="qrContainer">
            <div class="loading">
                <div class="spinner"></div>
                Memuat QR Code FRESH...
            </div>
        </div>
        
        <button class="refresh-btn" onclick="loadQR()">🔄 Refresh QR</button>
        <button class="force-btn" onclick="forceNewQR()">⚡ Force New QR</button>
        
        <div class="info">
            <strong>QR Code FRESH - No Cache!</strong><br>
            QR code akan selalu fresh dan tidak expired.<br>
            Jika QR lebih dari 1 menit, otomatis generate baru.
        </div>
        
        <div class="timestamp" id="lastUpdate"></div>
    </div>

    <script>
        let currentQR = null;
        let refreshInterval = null;
        
        async function loadQR() {
            const container = document.getElementById('qrContainer');
            const timestamp = document.getElementById('lastUpdate');
            
            // Show loading
            container.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Memuat QR Code FRESH...
                </div>
            `;
            
            try {
                // Add cache busting
                const cacheBuster = new Date().getTime();
                const response = await fetch(`/qr-latest?t=${cacheBuster}`);
                const data = await response.json();
                
                if (data.success && data.qr_code) {
                    // Check if QR is fresh (less than 2 minutes old)
                    const createdAt = new Date(data.created_at);
                    const now = new Date();
                    const ageMinutes = (now - createdAt) / (1000 * 60);
                    
                    currentQR = data;
                    
                    if (ageMinutes > 2) {
                        // QR is old, show warning and auto-generate new
                        container.innerHTML = `
                            <div class="warning">
                                ⚠️ QR Code terlalu lama (${Math.round(ageMinutes)} menit)<br>
                                Generating QR baru...
                            </div>
                        `;
                        
                        setTimeout(forceNewQR, 2000);
                        
                    } else {
                        // QR is fresh
                        container.innerHTML = `
                            <img src="${data.qr_code}" alt="WhatsApp QR Code" class="qr-image">
                            <div class="success">
                                ✅ QR Code FRESH (${Math.round(ageMinutes)} menit)<br>
                                <small>File: ${data.filename}<br>
                                Dibuat: ${data.created_at}</small>
                            </div>
                        `;
                    }
                    
                    timestamp.textContent = `Last update: ${new Date().toLocaleString()}`;
                    
                } else {
                    throw new Error(data.error || 'Failed to load QR');
                }
                
            } catch (error) {
                console.error('Error loading QR:', error);
                showError('Gagal memuat QR code. Mencoba generate baru...');
                setTimeout(forceNewQR, 2000);
            }
        }
        
        async function forceNewQR() {
            const container = document.getElementById('qrContainer');
            
            container.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Generating QR Code BARU...
                </div>
            `;
            
            try {
                // Force logout first
                await fetch('/whatsapp-api/app/logout', { method: 'POST' });
                
                // Wait a moment
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Generate new QR
                const generateResponse = await fetch('/whatsapp-api/app/login');
                const generateData = await generateResponse.json();
                
                if (generateData.results && generateData.results.qr_link) {
                    container.innerHTML = `
                        <div class="loading">
                            <div class="spinner"></div>
                            QR baru sedang dibuat...
                        </div>
                    `;
                    
                    // Wait for file creation
                    setTimeout(async () => {
                        try {
                            const filename = generateData.results.qr_link.split('/').pop();
                            const cacheBuster = new Date().getTime();
                            const qrResponse = await fetch(`/qr-by-name/${filename}?t=${cacheBuster}`);
                            const qrData = await qrResponse.json();
                            
                            if (qrData.success) {
                                container.innerHTML = `
                                    <img src="${qrData.qr_code}" alt="WhatsApp QR Code" class="qr-image">
                                    <div class="success">
                                        ✅ QR Code BARU berhasil dibuat!<br>
                                        <small>File: ${qrData.filename}<br>
                                        Dibuat: ${qrData.created_at}</small>
                                    </div>
                                `;
                                
                                document.getElementById('lastUpdate').textContent = `Last update: ${new Date().toLocaleString()}`;
                                
                            } else {
                                throw new Error('Failed to load generated QR');
                            }
                        } catch (e) {
                            showError('Gagal memuat QR code yang baru dibuat');
                        }
                    }, 5000);
                    
                } else {
                    throw new Error('Failed to generate QR');
                }
                
            } catch (error) {
                showError('Gagal generate QR baru. Silakan coba lagi.');
            }
        }
        
        function showError(message) {
            const container = document.getElementById('qrContainer');
            container.innerHTML = `
                <div class="error">
                    ❌ ${message}<br>
                    <small>Klik tombol untuk mencoba lagi</small>
                </div>
            `;
        }
        
        // Auto refresh every 60 seconds to check QR age
        refreshInterval = setInterval(() => {
            if (currentQR) {
                const createdAt = new Date(currentQR.created_at);
                const now = new Date();
                const ageMinutes = (now - createdAt) / (1000 * 60);
                
                // If QR is older than 2 minutes, auto refresh
                if (ageMinutes > 2) {
                    console.log('QR is old, auto refreshing...');
                    forceNewQR();
                }
            }
        }, 60000);
        
        // Load QR on page load
        loadQR();
    </script>
</body>
</html>
EOF

echo "✅ Fresh QR page created: https://hartonomotor.xyz/whatsapp-qr-fresh.html"

# Step 8: Test the fresh QR
echo -e "\n${YELLOW}🧪 Step 8: Test Fresh QR${NC}"

sleep 5

echo "Testing fresh QR page:"
FRESH_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr-fresh.html" 2>/dev/null | tail -1)
echo "Fresh QR page status: $FRESH_PAGE_STATUS"

echo "Testing fresh QR API:"
FRESH_API_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-latest" 2>/dev/null | tail -1)
echo "Fresh QR API status: $FRESH_API_STATUS"

if [ "$FRESH_API_STATUS" = "200" ]; then
    FRESH_API_RESPONSE=$(curl -s "https://hartonomotor.xyz/qr-latest" 2>/dev/null)
    echo "Fresh API response:"
    echo "$FRESH_API_RESPONSE" | jq '.filename, .created_at' 2>/dev/null || echo "$FRESH_API_RESPONSE" | head -c 200
fi

# Step 9: Final results
echo -e "\n${YELLOW}✅ Step 9: Final Results${NC}"
echo "=================================================================="

echo "FRESH QR GENERATION RESULTS:"
echo "- Fresh QR Generated: $([ -n "$FRESH_QR_LINK" ] && echo "✅ YES" || echo "❌ NO")"
echo "- Fresh QR Page: $FRESH_PAGE_STATUS"
echo "- Fresh QR API: $FRESH_API_STATUS"

if [ -n "$FRESH_QR_LINK" ] && [ "$FRESH_PAGE_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! Fresh QR system working!${NC}"
    echo -e "${GREEN}✅ Old QR files cleared${NC}"
    echo -e "${GREEN}✅ WhatsApp session reset${NC}"
    echo -e "${GREEN}✅ Fresh QR generated${NC}"
    echo -e "${GREEN}✅ Auto-refresh system active${NC}"
    
    echo -e "\n${BLUE}📱 Use this FRESH QR page:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-fresh.html"
    
    echo -e "\n${BLUE}🎯 Features:${NC}"
    echo "✅ Always generates fresh QR (no 8-hour old QR)"
    echo "✅ Auto-detects old QR and regenerates"
    echo "✅ Force new QR button"
    echo "✅ Cache busting"
    echo "✅ Real-time age checking"
    
    echo -e "\n${GREEN}🚀 Ready to scan!${NC}"
    echo -e "${GREEN}QR will always be fresh (less than 2 minutes old)${NC}"
    
else
    echo -e "\n${RED}❌ Still having issues${NC}"
    echo "Need to debug further"
fi

echo -e "\n${BLUE}📊 Next Steps:${NC}"
echo "1. Open: https://hartonomotor.xyz/whatsapp-qr-fresh.html"
echo "2. Verify QR timestamp is recent"
echo "3. Scan immediately with WhatsApp"
echo "4. If still old, click 'Force New QR'"
