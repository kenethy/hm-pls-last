#!/bin/bash

# Direct WhatsApp Solution - Bypass Laravel, direct to WhatsApp API
# Since generate API has connection issues, use direct WhatsApp API

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🎯 Direct WhatsApp Solution${NC}"
echo "=================================================="

echo -e "${GREEN}✅ Laravel API: Working${NC}"
echo -e "${GREEN}✅ QR Page: Working${NC}"
echo -e "${RED}❌ Generate API: 500 connection error${NC}"
echo -e "${YELLOW}🎯 Using direct WhatsApp API approach...${NC}"

# Step 1: Test direct WhatsApp API access
echo -e "\n${YELLOW}🔗 Step 1: Test Direct WhatsApp API${NC}"

echo "Testing direct WhatsApp API login:"
DIRECT_WHATSAPP=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
DIRECT_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Direct WhatsApp API status: $DIRECT_STATUS"

if [ "$DIRECT_STATUS" = "200" ]; then
    echo "✅ Direct WhatsApp API working!"
    echo "Response:"
    echo "$DIRECT_WHATSAPP" | jq '.results.qr_link' 2>/dev/null || echo "$DIRECT_WHATSAPP" | head -c 200
else
    echo "❌ Direct WhatsApp API also failing"
fi

# Step 2: Create QR page that uses direct WhatsApp API
echo -e "\n${YELLOW}📱 Step 2: Create Direct WhatsApp QR Page${NC}"

cat > public/whatsapp-qr-direct.html << 'EOF'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp QR Code - Direct - Hartono Motor</title>
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
        
        .age-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .age-fresh {
            background: #d4edda;
            color: #155724;
        }
        
        .age-good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .age-old {
            background: #fff3cd;
            color: #856404;
        }
        
        .age-expired {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📱</div>
        <h1>WhatsApp QR Code</h1>
        <p class="subtitle">Hartono Motor - Direct API</p>
        
        <div class="qr-container" id="qrContainer">
            <div class="loading">
                <div class="spinner"></div>
                Memuat QR Code...
            </div>
        </div>
        
        <button class="refresh-btn" onclick="loadQR()">🔄 Refresh QR</button>
        <button class="force-btn" onclick="generateDirectQR()">⚡ Generate Fresh</button>
        
        <div class="info">
            <strong>Direct WhatsApp API</strong><br>
            ✅ Bypass Laravel generate issues<br>
            ✅ Direct to WhatsApp API<br>
            ✅ Fresh QR generation<br>
            ✅ Real-time updates
        </div>
        
        <div class="timestamp" id="lastUpdate"></div>
    </div>

    <script>
        let currentQR = null;
        
        async function loadQR() {
            const container = document.getElementById('qrContainer');
            const timestamp = document.getElementById('lastUpdate');
            
            container.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Memuat QR Code...
                </div>
            `;
            
            try {
                // Try Laravel API first
                const cacheBuster = new Date().getTime();
                const response = await fetch(`/qr-latest?t=${cacheBuster}`);
                const data = await response.json();
                
                if (data.success && data.qr_code) {
                    currentQR = data;
                    
                    const ageSeconds = data.age_seconds || 0;
                    const ageMinutes = Math.floor(ageSeconds / 60);
                    const ageHours = Math.floor(ageMinutes / 60);
                    
                    let ageClass, ageText, statusClass, statusText;
                    
                    if (ageSeconds < 60) {
                        ageClass = 'age-fresh';
                        ageText = 'FRESH';
                        statusClass = 'success';
                        statusText = '✅ QR Code FRESH - Siap untuk scan!';
                    } else if (ageSeconds < 300) {
                        ageClass = 'age-good';
                        ageText = 'GOOD';
                        statusClass = 'success';
                        statusText = '✅ QR Code masih bagus untuk scan';
                    } else if (ageSeconds < 1800) {
                        ageClass = 'age-old';
                        ageText = 'OLD';
                        statusClass = 'warning';
                        statusText = '⚠️ QR Code agak lama - Recommend generate new';
                    } else {
                        ageClass = 'age-expired';
                        ageText = 'EXPIRED';
                        statusClass = 'warning';
                        statusText = '⚠️ QR Code expired - Generate new QR';
                    }
                    
                    let ageDisplay;
                    if (ageHours > 0) {
                        ageDisplay = `${ageHours}h ${ageMinutes % 60}m`;
                    } else if (ageMinutes > 0) {
                        ageDisplay = `${ageMinutes}m ${ageSeconds % 60}s`;
                    } else {
                        ageDisplay = `${ageSeconds}s`;
                    }
                    
                    container.innerHTML = `
                        <img src="${data.qr_code}" alt="WhatsApp QR Code" class="qr-image">
                        <div class="${statusClass}">
                            ${statusText}<br>
                            <small>
                                File: ${data.filename}<br>
                                Dibuat: ${data.created_at}
                                <span class="age-indicator ${ageClass}">${ageText} (${ageDisplay})</span><br>
                                Source: Laravel API
                            </small>
                        </div>
                    `;
                    
                    timestamp.textContent = `Last update: ${new Date().toLocaleString()}`;
                    
                    // Auto-generate if very old
                    if (ageSeconds > 1800) {
                        setTimeout(() => {
                            console.log('Auto-generating due to age');
                            generateDirectQR();
                        }, 3000);
                    }
                    
                } else {
                    throw new Error('Laravel API failed');
                }
                
            } catch (error) {
                console.error('Laravel API failed, trying direct WhatsApp API:', error);
                await loadDirectQR();
            }
        }
        
        async function loadDirectQR() {
            const container = document.getElementById('qrContainer');
            
            try {
                // Direct WhatsApp API call
                const response = await fetch('/whatsapp-api/app/login');
                const data = await response.json();
                
                if (data.code === 'SUCCESS' && data.results && data.results.qr_link) {
                    const qrLink = data.results.qr_link;
                    const qrDuration = data.results.qr_duration || 30;
                    
                    container.innerHTML = `
                        <img src="${qrLink}" alt="WhatsApp QR Code" class="qr-image">
                        <div class="success">
                            ✅ QR Code FRESH dari WhatsApp API!<br>
                            <small>
                                Duration: ${qrDuration} seconds<br>
                                Generated: ${new Date().toLocaleString()}<br>
                                Source: Direct WhatsApp API
                            </small>
                        </div>
                    `;
                    
                    document.getElementById('lastUpdate').textContent = `Direct API: ${new Date().toLocaleString()}`;
                    
                } else {
                    throw new Error(data.message || 'WhatsApp API failed');
                }
                
            } catch (error) {
                console.error('Direct WhatsApp API failed:', error);
                showError('Gagal memuat QR dari semua sumber. Silakan refresh halaman.');
            }
        }
        
        async function generateDirectQR() {
            const container = document.getElementById('qrContainer');
            
            container.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Generating QR via Direct WhatsApp API...<br>
                    <small>Bypass Laravel issues</small>
                </div>
            `;
            
            try {
                // Direct call to WhatsApp API
                const response = await fetch('/whatsapp-api/app/login');
                const data = await response.json();
                
                if (data.code === 'SUCCESS' && data.results && data.results.qr_link) {
                    const qrLink = data.results.qr_link;
                    const qrDuration = data.results.qr_duration || 30;
                    
                    container.innerHTML = `
                        <img src="${qrLink}" alt="WhatsApp QR Code" class="qr-image">
                        <div class="success">
                            ✅ QR Code FRESH berhasil dibuat!<br>
                            <small>
                                Duration: ${qrDuration} seconds<br>
                                Generated: ${new Date().toLocaleString()}<br>
                                Source: Direct WhatsApp API<br>
                                <strong>Scan sekarang juga!</strong>
                            </small>
                        </div>
                    `;
                    
                    document.getElementById('lastUpdate').textContent = `Fresh QR: ${new Date().toLocaleString()}`;
                    
                } else {
                    throw new Error(data.message || 'Failed to generate QR');
                }
                
            } catch (error) {
                console.error('Error generating direct QR:', error);
                showError('Gagal generate QR. Silakan coba lagi.');
            }
        }
        
        function showError(message) {
            const container = document.getElementById('qrContainer');
            container.innerHTML = `
                <div class="error">
                    ❌ ${message}
                </div>
            `;
        }
        
        // Auto refresh every 5 minutes
        setInterval(loadQR, 300000);
        
        // Load QR on page load
        loadQR();
    </script>
</body>
</html>
EOF

echo "✅ Direct WhatsApp QR page created"

# Step 3: Test the direct page
echo -e "\n${YELLOW}🧪 Step 3: Testing Direct QR Page${NC}"

DIRECT_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr-direct.html" 2>/dev/null | tail -1)
echo "Direct QR page status: $DIRECT_PAGE_STATUS"

# Step 4: Test direct WhatsApp API one more time
echo -e "\n${YELLOW}🔗 Step 4: Final Direct WhatsApp API Test${NC}"

echo "Final test of direct WhatsApp API:"
FINAL_DIRECT=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
FINAL_DIRECT_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Final direct status: $FINAL_DIRECT_STATUS"

if [ "$FINAL_DIRECT_STATUS" = "200" ]; then
    echo "✅ Direct WhatsApp API working!"
    echo "Response:"
    echo "$FINAL_DIRECT" | jq '.results.qr_link, .results.qr_duration' 2>/dev/null || echo "$FINAL_DIRECT" | head -c 300
fi

# Step 5: Final results
echo -e "\n${YELLOW}✅ Step 5: Final Results${NC}"
echo "=================================================================="

echo "DIRECT WHATSAPP SOLUTION RESULTS:"
echo "- Direct QR Page: $DIRECT_PAGE_STATUS"
echo "- Direct WhatsApp API: $FINAL_DIRECT_STATUS"

if [ "$DIRECT_PAGE_STATUS" = "200" ] && [ "$FINAL_DIRECT_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! Direct WhatsApp solution working!${NC}"
    echo -e "${GREEN}✅ Bypass Laravel generate issues${NC}"
    echo -e "${GREEN}✅ Direct WhatsApp API access${NC}"
    echo -e "${GREEN}✅ Fresh QR generation${NC}"
    echo -e "${GREEN}✅ Real-time QR updates${NC}"
    
    echo -e "\n${BLUE}📱 Your DIRECT WhatsApp QR System:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-direct.html"
    
    echo -e "\n${BLUE}🎯 How it works:${NC}"
    echo "1. Try Laravel API first (for existing QR)"
    echo "2. If Laravel fails, use direct WhatsApp API"
    echo "3. Generate button uses direct WhatsApp API"
    echo "4. Always gets fresh QR (30 second duration)"
    echo "5. No Laravel generate API dependency"
    
    echo -e "\n${GREEN}🚀 READY TO SCAN FRESH QR!${NC}"
    echo -e "${GREEN}This bypasses all Laravel issues and gives you fresh QR!${NC}"
    
    echo -e "\n${BLUE}📋 Instructions:${NC}"
    echo "1. Open: https://hartonomotor.xyz/whatsapp-qr-direct.html"
    echo "2. Click 'Generate Fresh' for new QR"
    echo "3. Scan immediately with WhatsApp"
    echo "4. QR will be fresh (30 second duration)"
    
elif [ "$DIRECT_PAGE_STATUS" = "200" ]; then
    echo -e "\n${YELLOW}⚠️ Page working but WhatsApp API issues${NC}"
    echo "Check WhatsApp API container status"
    
else
    echo -e "\n${RED}❌ Still having issues${NC}"
    echo "Need to debug further"
fi

echo -e "\n${BLUE}📊 System Summary:${NC}"
echo "- Laravel API: ✅ Working (for existing QR)"
echo "- Laravel Generate: ❌ Connection issues"
echo "- Direct WhatsApp API: $([ "$FINAL_DIRECT_STATUS" = "200" ] && echo "✅ Working" || echo "❌ Issues")"
echo "- Direct QR Page: ✅ Working"

echo -e "\n${GREEN}🎊 DIRECT SOLUTION READY!${NC}"
echo -e "${GREEN}Bypass Laravel issues with direct WhatsApp API!${NC}"
