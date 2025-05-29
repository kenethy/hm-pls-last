#!/bin/bash

# Quick Test Routes - Test if routes are working after fix

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 Quick Test Routes${NC}"
echo "=================================================="

echo -e "${YELLOW}🎯 Testing routes after consolidation to web.php...${NC}"

# Test 1: QR Latest API
echo -e "\n${YELLOW}📡 Test 1: QR Latest API${NC}"
QR_LATEST_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-latest" 2>/dev/null | tail -1)
echo "QR Latest API status: $QR_LATEST_STATUS"

if [ "$QR_LATEST_STATUS" = "200" ]; then
    echo "✅ QR Latest API working!"
    QR_LATEST_RESPONSE=$(curl -s "https://hartonomotor.xyz/qr-latest" 2>/dev/null)
    echo "Response preview:"
    echo "$QR_LATEST_RESPONSE" | jq '.filename, .age_seconds' 2>/dev/null || echo "$QR_LATEST_RESPONSE" | head -c 200
else
    echo "❌ QR Latest API still failing"
fi

# Test 2: Generate Fresh QR API
echo -e "\n${YELLOW}⚡ Test 2: Generate Fresh QR API${NC}"
GENERATE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/generate-fresh-qr" 2>/dev/null | tail -1)
echo "Generate API status: $GENERATE_STATUS"

if [ "$GENERATE_STATUS" = "200" ]; then
    echo "✅ Generate API working!"
else
    echo "❌ Generate API still failing"
fi

# Test 3: QR Image Serving
echo -e "\n${YELLOW}🖼️ Test 3: QR Image Serving${NC}"
LATEST_QR=$(ls -t /var/www/whatsapp_statics/qrcode/scan-qr-*.png 2>/dev/null | head -1)
if [ -n "$LATEST_QR" ]; then
    QR_FILENAME=$(basename "$LATEST_QR")
    IMAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/qr-image/$QR_FILENAME" 2>/dev/null | tail -1)
    echo "QR Image serving status: $IMAGE_STATUS"
    
    if [ "$IMAGE_STATUS" = "200" ]; then
        echo "✅ QR Image serving working!"
    else
        echo "❌ QR Image serving still failing"
    fi
else
    echo "❌ No QR files found"
fi

# Test 4: QR Page
echo -e "\n${YELLOW}📱 Test 4: QR Page${NC}"
PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr-working.html" 2>/dev/null | tail -1)
echo "QR Page status: $PAGE_STATUS"

if [ "$PAGE_STATUS" = "200" ]; then
    echo "✅ QR Page working!"
else
    echo "❌ QR Page failing"
fi

# Summary
echo -e "\n${YELLOW}📊 Summary${NC}"
echo "=================================================================="

WORKING_APIS=0
[ "$QR_LATEST_STATUS" = "200" ] && ((WORKING_APIS++))
[ "$GENERATE_STATUS" = "200" ] && ((WORKING_APIS++))
[ "$IMAGE_STATUS" = "200" ] && ((WORKING_APIS++))
[ "$PAGE_STATUS" = "200" ] && ((WORKING_APIS++))

echo "Working components: $WORKING_APIS/4"

if [ "$WORKING_APIS" -eq 4 ]; then
    echo -e "\n${GREEN}🎉 PERFECT! All routes working!${NC}"
    echo -e "${GREEN}✅ QR Latest API: $QR_LATEST_STATUS${NC}"
    echo -e "${GREEN}✅ Generate API: $GENERATE_STATUS${NC}"
    echo -e "${GREEN}✅ Image Serving: $IMAGE_STATUS${NC}"
    echo -e "${GREEN}✅ QR Page: $PAGE_STATUS${NC}"
    
    echo -e "\n${BLUE}📱 Your WORKING QR System:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-working.html"
    
    echo -e "\n${GREEN}🚀 READY TO USE!${NC}"
    echo -e "${GREEN}All APIs and routes are now working properly!${NC}"
    
elif [ "$WORKING_APIS" -ge 3 ]; then
    echo -e "\n${YELLOW}⚠️ Almost there! $WORKING_APIS/4 working${NC}"
    echo "System is mostly functional"
    
    echo -e "\n${BLUE}📱 Try your QR System:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-working.html"
    
elif [ "$WORKING_APIS" -ge 2 ]; then
    echo -e "\n${YELLOW}⚠️ Partially working: $WORKING_APIS/4${NC}"
    echo "Basic functionality available"
    
else
    echo -e "\n${RED}❌ Need more work: $WORKING_APIS/4${NC}"
    echo "Routes still need debugging"
fi

echo -e "\n${BLUE}🔧 Technical Details:${NC}"
echo "- QR Latest API: $QR_LATEST_STATUS"
echo "- Generate API: $GENERATE_STATUS"
echo "- Image Serving: $IMAGE_STATUS"
echo "- QR Page: $PAGE_STATUS"

if [ -n "$LATEST_QR" ]; then
    QR_AGE=$(stat -c %Y "$LATEST_QR")
    CURRENT_TIME=$(date +%s)
    AGE_SECONDS=$((CURRENT_TIME - QR_AGE))
    echo "- Latest QR Age: ${AGE_SECONDS}s"
fi

echo -e "\n${GREEN}🎊 ROUTE TESTING COMPLETE!${NC}"

if [ "$WORKING_APIS" -eq 4 ]; then
    echo -e "${GREEN}Your WhatsApp QR system is now fully functional!${NC}"
elif [ "$WORKING_APIS" -ge 3 ]; then
    echo -e "${YELLOW}Your WhatsApp QR system is mostly working!${NC}"
else
    echo -e "${RED}Your WhatsApp QR system needs more debugging.${NC}"
fi
