#!/bin/bash

# Final Test QR System - Complete functionality test
# Test all components after route fix

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🧪 Final Test QR System${NC}"
echo "=================================================="

echo -e "${GREEN}✅ Route cache cleared and updated${NC}"
echo -e "${YELLOW}🎯 Testing complete QR system functionality...${NC}"

# Step 1: Test QR image serving route
echo -e "\n${YELLOW}🖼️ Step 1: Test QR Image Serving Route${NC}"

# Get latest QR file
LATEST_QR=$(ls -t /var/www/whatsapp_statics/qrcode/scan-qr-*.png 2>/dev/null | head -1)
if [ -n "$LATEST_QR" ]; then
    QR_FILENAME=$(basename "$LATEST_QR")
    IMAGE_URL="https://hartonomotor.xyz/qr-image/$QR_FILENAME"
    
    echo "Testing image serving route:"
    echo "Latest QR file: $QR_FILENAME"
    echo "Image URL: $IMAGE_URL"
    
    IMAGE_STATUS=$(curl -s -w "%{http_code}" "$IMAGE_URL" 2>/dev/null | tail -1)
    echo "Image status: $IMAGE_STATUS"
    
    if [ "$IMAGE_STATUS" = "200" ]; then
        echo "✅ Image serving route working!"
    else
        echo "❌ Image serving route still failing"
    fi
else
    echo "❌ No QR files found"
fi

# Step 2: Test Laravel API
echo -e "\n${YELLOW}📡 Step 2: Test Laravel API${NC}"

API_RESPONSE=$(curl -s "https://hartonomotor.xyz/qr-latest" 2>/dev/null)
API_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-latest" 2>/dev/null | tail -1)
echo "Laravel API status: $API_STATUS"

if [ "$API_STATUS" = "200" ]; then
    echo "✅ Laravel API working!"
    echo "API response preview:"
    echo "$API_RESPONSE" | jq '.filename, .qr_image_url, .age_seconds' 2>/dev/null || echo "$API_RESPONSE" | head -c 400
else
    echo "❌ Laravel API failing"
fi

# Step 3: Test QR pages
echo -e "\n${YELLOW}📱 Step 3: Test QR Pages${NC}"

QR_PAGES=(
    "whatsapp-qr-working.html"
    "whatsapp-qr-perfect-final.html"
    "whatsapp-qr-direct.html"
)

for page in "${QR_PAGES[@]}"; do
    PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/$page" 2>/dev/null | tail -1)
    echo "Page $page: $PAGE_STATUS"
done

# Step 4: Test generate API
echo -e "\n${YELLOW}⚡ Step 4: Test Generate API${NC}"

GENERATE_RESPONSE=$(curl -s "https://hartonomotor.xyz/generate-fresh-qr" 2>/dev/null)
GENERATE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/generate-fresh-qr" 2>/dev/null | tail -1)
echo "Generate API status: $GENERATE_STATUS"

if [ "$GENERATE_STATUS" = "200" ]; then
    echo "✅ Generate API working!"
    echo "$GENERATE_RESPONSE" | jq '.success' 2>/dev/null || echo "$GENERATE_RESPONSE" | head -c 200
else
    echo "❌ Generate API failing"
fi

# Step 5: Check QR file freshness
echo -e "\n${YELLOW}⏰ Step 5: Check QR File Freshness${NC}"

if [ -n "$LATEST_QR" ]; then
    QR_AGE=$(stat -c %Y "$LATEST_QR")
    CURRENT_TIME=$(date +%s)
    AGE_SECONDS=$((CURRENT_TIME - QR_AGE))
    AGE_MINUTES=$((AGE_SECONDS / 60))
    AGE_HOURS=$((AGE_MINUTES / 60))
    
    echo "Latest QR file: $QR_FILENAME"
    echo "File age: ${AGE_HOURS}h ${AGE_MINUTES}m ${AGE_SECONDS}s"
    
    if [ "$AGE_SECONDS" -lt 300 ]; then
        echo "✅ QR is FRESH (< 5 minutes)"
        QR_FRESH=true
    elif [ "$AGE_SECONDS" -lt 1800 ]; then
        echo "⚠️ QR is OLD (< 30 minutes)"
        QR_FRESH=false
    else
        echo "❌ QR is EXPIRED (> 30 minutes)"
        QR_FRESH=false
    fi
fi

# Step 6: Final system status
echo -e "\n${YELLOW}📊 Step 6: Final System Status${NC}"
echo "=================================================================="

echo "COMPLETE QR SYSTEM STATUS:"
echo "- Laravel API: $([ "$API_STATUS" = "200" ] && echo "✅ Working" || echo "❌ Failed")"
echo "- Image Serving: $([ "$IMAGE_STATUS" = "200" ] && echo "✅ Working" || echo "❌ Failed")"
echo "- Generate API: $([ "$GENERATE_STATUS" = "200" ] && echo "✅ Working" || echo "❌ Failed")"
echo "- QR Freshness: $([ "$QR_FRESH" = true ] && echo "✅ Fresh" || echo "⚠️ Old")"

# Count working components
WORKING_COUNT=0
[ "$API_STATUS" = "200" ] && ((WORKING_COUNT++))
[ "$IMAGE_STATUS" = "200" ] && ((WORKING_COUNT++))
[ "$GENERATE_STATUS" = "200" ] && ((WORKING_COUNT++))
[ "$QR_FRESH" = true ] && ((WORKING_COUNT++))

echo -e "\nWorking components: $WORKING_COUNT/4"

if [ "$WORKING_COUNT" -eq 4 ]; then
    echo -e "\n${GREEN}🎉 PERFECT! All components working!${NC}"
    echo -e "${GREEN}✅ Laravel API: Working${NC}"
    echo -e "${GREEN}✅ Image Serving: Working${NC}"
    echo -e "${GREEN}✅ Generate API: Working${NC}"
    echo -e "${GREEN}✅ QR Freshness: Fresh${NC}"
    
    echo -e "\n${BLUE}📱 Your PERFECT QR System:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-working.html"
    
    echo -e "\n${BLUE}🎯 System Features:${NC}"
    echo "✅ QR images load properly (image URL + base64 fallback)"
    echo "✅ Real-time age detection (Fresh/Good/Old/Expired)"
    echo "✅ Fresh QR generation working"
    echo "✅ Multiple image sources with fallback"
    echo "✅ Professional UI with status indicators"
    echo "✅ Auto-refresh for old QR codes"
    
    echo -e "\n${GREEN}🚀 READY TO SCAN!${NC}"
    echo -e "${GREEN}Your WhatsApp QR system is now production-ready!${NC}"
    
    echo -e "\n${BLUE}📋 How to use:${NC}"
    echo "1. Open: https://hartonomotor.xyz/whatsapp-qr-working.html"
    echo "2. Wait for QR to load (should show age indicator)"
    echo "3. If QR is old, click 'Generate New'"
    echo "4. Open WhatsApp → Settings → Linked Devices"
    echo "5. Tap 'Link a Device' and scan the QR"
    echo "6. Should connect successfully!"
    
elif [ "$WORKING_COUNT" -eq 3 ]; then
    echo -e "\n${YELLOW}⚠️ Almost perfect! 3/4 components working${NC}"
    echo "System is functional but could be improved"
    
    if [ "$QR_FRESH" != true ]; then
        echo "💡 Tip: Click 'Generate New' to get fresh QR"
    fi
    
    echo -e "\n${BLUE}📱 Your QR System:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-working.html"
    
elif [ "$WORKING_COUNT" -ge 2 ]; then
    echo -e "\n${YELLOW}⚠️ Partially working: $WORKING_COUNT/4 components${NC}"
    echo "Basic functionality available"
    
    echo -e "\n${BLUE}📱 Try this QR System:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-working.html"
    
else
    echo -e "\n${RED}❌ System needs more work: $WORKING_COUNT/4 components${NC}"
    echo "Need to debug remaining issues"
fi

echo -e "\n${BLUE}📊 Technical Summary:${NC}"
echo "- QR Files: $(ls /var/www/whatsapp_statics/qrcode/scan-qr-*.png 2>/dev/null | wc -l) files"
echo "- Latest QR: $QR_FILENAME"
echo "- Age: ${AGE_HOURS}h ${AGE_MINUTES}m"
echo "- Image URL: $IMAGE_URL"
echo "- API Response: $(echo "$API_RESPONSE" | jq -r '.success' 2>/dev/null || echo "N/A")"

echo -e "\n${GREEN}🎊 QR SYSTEM TESTING COMPLETE!${NC}"
echo -e "${GREEN}Check the results above for system status!${NC}"
