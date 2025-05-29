#!/bin/bash

# Debug WhatsApp API - Why QR is 539 minutes old
# Deep dive into WhatsApp API container and configuration

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔍 Debug WhatsApp API - Why QR is 539 minutes old${NC}"
echo "=================================================="

echo -e "${RED}❌ Problem: QR still 539 minutes old (not generating fresh)${NC}"
echo -e "${YELLOW}🎯 Investigating WhatsApp API container and configuration...${NC}"

# Step 1: Check container status and logs
echo -e "\n${YELLOW}📊 Step 1: Container Status and Logs${NC}"

echo "WhatsApp API container status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep whatsapp

echo -e "\nContainer health:"
docker inspect whatsapp-api-hartono --format '{{.State.Health.Status}}' 2>/dev/null || echo "No health check"

echo -e "\nRecent container logs (last 20 lines):"
docker logs --tail=20 whatsapp-api-hartono

# Step 2: Check API endpoints directly
echo -e "\n${YELLOW}🌐 Step 2: Direct API Testing${NC}"

echo "Testing direct API connection:"
DIRECT_DEVICES=$(curl -s -u "admin:HartonoMotor2025!" "http://localhost:3000/app/devices" 2>/dev/null)
echo "Direct devices response: $DIRECT_DEVICES"

echo -e "\nTesting direct login:"
DIRECT_LOGIN=$(curl -s -u "admin:HartonoMotor2025!" "http://localhost:3000/app/login" 2>/dev/null)
echo "Direct login response: $DIRECT_LOGIN"

# Step 3: Check if API is actually generating new QR
echo -e "\n${YELLOW}📱 Step 3: QR Generation Test${NC}"

echo "Current QR files before test:"
ls -la /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null | tail -3

echo -e "\nGenerating new QR via API:"
NEW_QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
echo "New QR response: $NEW_QR_RESPONSE"

# Wait and check if new file was created
sleep 5

echo -e "\nQR files after API call:"
ls -la /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null | tail -3

# Check if any new files were created in last 2 minutes
echo -e "\nFiles created in last 2 minutes:"
find /var/www/whatsapp_statics/qrcode/ -name "*.png" -mmin -2 2>/dev/null || echo "No recent files"

# Step 4: Check WhatsApp API configuration
echo -e "\n${YELLOW}⚙️ Step 4: WhatsApp API Configuration${NC}"

echo "Checking container environment variables:"
docker exec whatsapp-api-hartono env | grep -E "(WHATSAPP|QR|AUTH)" || echo "No relevant env vars"

echo -e "\nChecking container filesystem:"
docker exec whatsapp-api-hartono ls -la /app/ 2>/dev/null || echo "Cannot access /app/"

echo -e "\nChecking if container can write to QR directory:"
docker exec whatsapp-api-hartono ls -la /app/statics/qrcode/ 2>/dev/null || echo "Cannot access QR directory in container"

# Step 5: Check docker-compose configuration
echo -e "\n${YELLOW}🐳 Step 5: Docker Compose Configuration${NC}"

echo "WhatsApp service in docker-compose.yml:"
grep -A 20 "whatsapp-api:" docker-compose.yml || echo "WhatsApp service not found in docker-compose.yml"

# Step 6: Check volume mounts
echo -e "\n${YELLOW}💾 Step 6: Volume Mount Analysis${NC}"

echo "Container volume mounts:"
docker inspect whatsapp-api-hartono --format '{{range .Mounts}}{{.Source}} -> {{.Destination}} ({{.Type}}){{"\n"}}{{end}}'

echo -e "\nChecking if volume mount is working:"
echo "Host side: /var/www/whatsapp_statics/qrcode/"
ls -la /var/www/whatsapp_statics/qrcode/ | head -5

echo -e "\nContainer side (if accessible):"
docker exec whatsapp-api-hartono ls -la /app/statics/qrcode/ 2>/dev/null | head -5 || echo "Cannot access container QR directory"

# Step 7: Test manual QR generation
echo -e "\n${YELLOW}🧪 Step 7: Manual QR Generation Test${NC}"

echo "Trying to force new session:"
# Try logout first
LOGOUT_RESULT=$(curl -s -u "admin:HartonoMotor2025!" "http://localhost:3000/app/logout" 2>/dev/null)
echo "Logout result: $LOGOUT_RESULT"

sleep 3

echo -e "\nForcing new login:"
FORCE_LOGIN=$(curl -s -u "admin:HartonoMotor2025!" "http://localhost:3000/app/login" 2>/dev/null)
echo "Force login result: $FORCE_LOGIN"

# Step 8: Check if it's a caching issue
echo -e "\n${YELLOW}🔄 Step 8: Cache Investigation${NC}"

echo "Checking if API is returning cached responses:"
for i in {1..3}; do
    echo "Login attempt $i:"
    CACHE_TEST=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
    QR_LINK=$(echo "$CACHE_TEST" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    echo "QR Link: $QR_LINK"
    sleep 2
done

# Step 9: Check go-whatsapp-web-multidevice source
echo -e "\n${YELLOW}📂 Step 9: Source Code Investigation${NC}"

echo "Checking go-whatsapp source directory:"
ls -la go-whatsapp-web-multidevice-main/ | head -10

echo -e "\nChecking main configuration files:"
find go-whatsapp-web-multidevice-main/ -name "*.go" -o -name "*.yml" -o -name "*.yaml" -o -name "*.json" | head -10

# Step 10: Container restart and fresh test
echo -e "\n${YELLOW}🔄 Step 10: Container Restart and Fresh Test${NC}"

echo "Stopping WhatsApp container:"
docker stop whatsapp-api-hartono

echo "Removing old QR files:"
mv /var/www/whatsapp_statics/qrcode/*.png /var/www/whatsapp_statics/qrcode/backup/ 2>/dev/null || echo "No PNG files to move"

echo "Starting WhatsApp container:"
docker start whatsapp-api-hartono

echo "Waiting 30 seconds for container to be ready:"
sleep 30

echo "Container status after restart:"
docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono"

echo -e "\nTesting fresh QR generation:"
FRESH_TEST=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
echo "Fresh test response: $FRESH_TEST"

sleep 5

echo -e "\nChecking for new QR files:"
ls -la /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null || echo "No PNG files found"

# Step 11: Final diagnosis
echo -e "\n${YELLOW}🎯 Step 11: Final Diagnosis${NC}"
echo "=================================================================="

echo "DIAGNOSIS SUMMARY:"

# Check if new files were created
NEW_FILES=$(find /var/www/whatsapp_statics/qrcode/ -name "*.png" -mmin -5 2>/dev/null | wc -l)
echo "- New QR files created in last 5 minutes: $NEW_FILES"

# Check API responses
if echo "$FRESH_TEST" | grep -q "qr_link"; then
    echo "- API QR generation: ✅ Working"
else
    echo "- API QR generation: ❌ Not working"
fi

# Check container status
CONTAINER_STATUS=$(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")
if [[ "$CONTAINER_STATUS" == *"Up"* ]]; then
    echo "- Container status: ✅ Running"
else
    echo "- Container status: ❌ $CONTAINER_STATUS"
fi

echo -e "\n${BLUE}🔍 Possible Issues:${NC}"
echo "1. WhatsApp API container not generating new QR files"
echo "2. Volume mount issue - files not being written to host"
echo "3. WhatsApp API stuck in old session"
echo "4. Container configuration problem"
echo "5. go-whatsapp-web-multidevice source issue"

echo -e "\n${BLUE}🔧 Recommended Actions:${NC}"
if [ "$NEW_FILES" -gt 0 ]; then
    echo "✅ New files created - API is working"
    echo "Issue might be with Laravel API caching old files"
else
    echo "❌ No new files created - WhatsApp API issue"
    echo "Need to fix container or rebuild from source"
fi

echo -e "\n${BLUE}📋 Next Steps:${NC}"
echo "1. Check container logs for errors"
echo "2. Verify volume mount configuration"
echo "3. Consider rebuilding container from source"
echo "4. Check go-whatsapp-web-multidevice documentation"
