#!/bin/bash

# Fix WhatsApp Linking Issues
# Troubleshoot "unable to link device" problem

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fix WhatsApp Linking Issues${NC}"
echo "=================================================="

echo -e "${GREEN}✅ QR display working!${NC}"
echo -e "${RED}❌ WhatsApp scan shows 'unable to link device'${NC}"

# Step 1: Check WhatsApp API status
echo -e "\n${YELLOW}📱 Step 1: WhatsApp API Status Check${NC}"

echo "Checking WhatsApp API container status:"
CONTAINER_STATUS=$(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")
echo "Container status: $CONTAINER_STATUS"

echo -e "\nChecking API health:"
API_DEVICES=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null)
echo "Devices API response: $API_DEVICES"

# Step 2: Check WhatsApp session status
echo -e "\n${YELLOW}🔍 Step 2: WhatsApp Session Status${NC}"

echo "Checking if WhatsApp is already connected:"
if echo "$API_DEVICES" | grep -q "device_id"; then
    echo -e "${YELLOW}⚠️ WhatsApp might already be connected to another device${NC}"
    echo "Current devices:"
    echo "$API_DEVICES" | jq '.' 2>/dev/null || echo "$API_DEVICES"
    
    echo -e "\n${BLUE}💡 Solution: Logout from existing session${NC}"
    echo "Logging out from current session..."
    LOGOUT_RESPONSE=$(curl -s -X POST "https://hartonomotor.xyz/whatsapp-api/app/logout" 2>/dev/null)
    echo "Logout response: $LOGOUT_RESPONSE"
    
    sleep 5
else
    echo "✅ No active WhatsApp session found"
fi

# Step 3: Generate fresh QR with proper settings
echo -e "\n${YELLOW}📱 Step 3: Generate Fresh QR${NC}"

echo "Generating new QR code with optimal settings..."
QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
echo "QR generation response:"
echo "$QR_RESPONSE" | jq '.' 2>/dev/null || echo "$QR_RESPONSE"

QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
QR_DURATION=$(echo "$QR_RESPONSE" | grep -o '"qr_duration":[0-9]*' | cut -d':' -f2)

if [ -n "$QR_LINK" ]; then
    echo -e "\n${GREEN}✅ Fresh QR generated!${NC}"
    echo "QR Link: $QR_LINK"
    echo "QR Duration: ${QR_DURATION:-30} seconds"
    
    echo -e "\n${BLUE}📱 Quick scan instructions:${NC}"
    echo "1. Open WhatsApp on your phone"
    echo "2. Go to Settings > Linked Devices"
    echo "3. Tap 'Link a Device'"
    echo "4. Scan the QR code IMMEDIATELY (within ${QR_DURATION:-30} seconds)"
    
else
    echo -e "${RED}❌ Failed to generate QR${NC}"
fi

# Step 4: Check container logs for errors
echo -e "\n${YELLOW}📋 Step 4: Check Container Logs${NC}"

echo "Recent WhatsApp API container logs:"
docker logs --tail=20 whatsapp-api-hartono 2>/dev/null | tail -10

# Step 5: Common solutions
echo -e "\n${YELLOW}💡 Step 5: Common Solutions${NC}"

echo -e "${BLUE}🔧 Try these solutions:${NC}"

echo -e "\n${YELLOW}Solution 1: Update WhatsApp${NC}"
echo "- Update WhatsApp to latest version on your phone"
echo "- WhatsApp Web requires recent versions for multi-device support"

echo -e "\n${YELLOW}Solution 2: Clear WhatsApp Cache${NC}"
echo "- Go to phone Settings > Apps > WhatsApp > Storage"
echo "- Clear Cache (NOT Clear Data)"
echo "- Restart WhatsApp"

echo -e "\n${YELLOW}Solution 3: Check Network${NC}"
echo "- Ensure phone and server have stable internet"
echo "- Try using mobile data instead of WiFi (or vice versa)"

echo -e "\n${YELLOW}Solution 4: Restart WhatsApp API${NC}"
echo "- Sometimes the API needs a fresh restart"

# Step 6: Restart WhatsApp API container
echo -e "\n${YELLOW}🔄 Step 6: Restart WhatsApp API Container${NC}"

echo "Restarting WhatsApp API container for fresh session..."
docker restart whatsapp-api-hartono

echo "Waiting for container to be ready..."
sleep 15

echo "Container status after restart:"
docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono"

# Step 7: Generate QR after restart
echo -e "\n${YELLOW}📱 Step 7: Generate QR After Restart${NC}"

echo "Waiting additional 10 seconds for API to be fully ready..."
sleep 10

echo "Generating fresh QR after restart..."
FRESH_QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
FRESH_QR_LINK=$(echo "$FRESH_QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)

if [ -n "$FRESH_QR_LINK" ]; then
    echo -e "${GREEN}✅ Fresh QR ready after restart!${NC}"
    echo "New QR Link: $FRESH_QR_LINK"
    
    echo -e "\n${BLUE}📱 Try scanning this fresh QR:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr-base64.html"
    
else
    echo -e "${RED}❌ Still having issues generating QR${NC}"
    echo "Response: $FRESH_QR_RESPONSE"
fi

# Step 8: Advanced troubleshooting
echo -e "\n${YELLOW}🔧 Step 8: Advanced Troubleshooting${NC}"

echo -e "${BLUE}If still not working, try:${NC}"

echo -e "\n${YELLOW}Option A: Use Different Phone${NC}"
echo "- Try scanning with a different WhatsApp account"
echo "- Some accounts have restrictions"

echo -e "\n${YELLOW}Option B: Check WhatsApp Business${NC}"
echo "- If using WhatsApp Business, ensure it supports Web linking"
echo "- Some business accounts have different restrictions"

echo -e "\n${YELLOW}Option C: Manual Session Reset${NC}"
echo "- Delete any existing WhatsApp Web sessions manually"
echo "- Go to WhatsApp > Settings > Linked Devices"
echo "- Remove all existing linked devices"

echo -e "\n${YELLOW}Option D: Container Environment${NC}"
echo "- Check if container has proper timezone"
echo "- Ensure container has internet access"

# Step 9: Create monitoring script
echo -e "\n${YELLOW}📊 Step 9: Create Monitoring Script${NC}"

cat > monitor-whatsapp.sh << 'EOF'
#!/bin/bash

# Monitor WhatsApp API Status
echo "=== WhatsApp API Monitor ==="
echo "Time: $(date)"

echo -e "\n1. Container Status:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep whatsapp

echo -e "\n2. API Health:"
curl -s "https://hartonomotor.xyz/whatsapp-api/app/devices" | jq '.' 2>/dev/null || echo "API not responding"

echo -e "\n3. Recent Logs:"
docker logs --tail=5 whatsapp-api-hartono 2>/dev/null

echo -e "\n4. Generate Test QR:"
curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" | jq '.results.qr_link' 2>/dev/null || echo "QR generation failed"

echo -e "\n=== End Monitor ==="
EOF

chmod +x monitor-whatsapp.sh
echo "✅ Monitoring script created: ./monitor-whatsapp.sh"

# Step 10: Final recommendations
echo -e "\n${YELLOW}✅ Step 10: Final Recommendations${NC}"
echo "=================================================================="

echo -e "${BLUE}📱 Immediate Actions:${NC}"
echo "1. ✅ Refresh your QR page: https://hartonomotor.xyz/whatsapp-qr-base64.html"
echo "2. ✅ Update WhatsApp to latest version"
echo "3. ✅ Clear WhatsApp cache on phone"
echo "4. ✅ Try scanning the fresh QR immediately"

echo -e "\n${BLUE}🔧 If Still Not Working:${NC}"
echo "1. Run: ./monitor-whatsapp.sh"
echo "2. Try different phone/WhatsApp account"
echo "3. Check WhatsApp server status online"
echo "4. Consider using official WhatsApp Business API"

echo -e "\n${BLUE}📊 Success Indicators:${NC}"
echo "- QR scan should show 'Linking device...' message"
echo "- Phone should show notification about linked device"
echo "- API should return device info in /app/devices"

if [ -n "$FRESH_QR_LINK" ]; then
    echo -e "\n${GREEN}🎯 Ready to test!${NC}"
    echo -e "${GREEN}Fresh QR is available - try scanning now!${NC}"
    echo -e "${GREEN}QR Page: https://hartonomotor.xyz/whatsapp-qr-base64.html${NC}"
else
    echo -e "\n${YELLOW}⚠️ Need to debug API further${NC}"
    echo "Run ./monitor-whatsapp.sh for detailed status"
fi
