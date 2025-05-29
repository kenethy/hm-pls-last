#!/bin/bash

# Deep Debug Analysis - Find the Real Problem
# Let's see what's actually happening

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔍 Deep Debug Analysis - Find the Real Problem${NC}"
echo "=================================================="

echo -e "${YELLOW}Let's find out what's REALLY happening...${NC}"

# Step 1: Check what's in the nginx config volume RIGHT NOW
echo -e "\n${YELLOW}📄 Step 1: Current Nginx Config in Volume${NC}"
echo "Let's see the ACTUAL config that nginx is trying to load:"

docker run --rm -v hm-new_nginx-config:/config alpine sh -c "
echo '=== FULL NGINX CONFIG ==='
cat /config/app.conf
echo ''
echo '=== END OF CONFIG ==='
"

# Step 2: Check if there are multiple config files
echo -e "\n${YELLOW}📂 Step 2: All Files in Nginx Config Volume${NC}"
docker run --rm -v hm-new_nginx-config:/config alpine sh -c "
echo 'All files in nginx config volume:'
ls -la /config/
echo ''
echo 'Looking for any .conf files:'
find /config/ -name '*.conf' -type f
"

# Step 3: Check nginx container filesystem
echo -e "\n${YELLOW}🐳 Step 3: Nginx Container Filesystem${NC}"
echo "Checking what nginx container actually sees:"

# Start nginx temporarily to check its filesystem
docker start hartono-webserver >/dev/null 2>&1 || echo "Container already running or failed to start"
sleep 5

echo "Files in /etc/nginx/conf.d/ inside nginx container:"
docker exec hartono-webserver ls -la /etc/nginx/conf.d/ 2>/dev/null || echo "Cannot access nginx container"

echo -e "\nContent of app.conf inside nginx container:"
docker exec hartono-webserver cat /etc/nginx/conf.d/app.conf 2>/dev/null || echo "Cannot read app.conf from container"

# Step 4: Check for any default.conf or other configs
echo -e "\n${YELLOW}📋 Step 4: Check for Conflicting Configs${NC}"
docker exec hartono-webserver sh -c "
echo 'All .conf files in nginx:'
find /etc/nginx/ -name '*.conf' -type f 2>/dev/null || echo 'Cannot find conf files'

echo ''
echo 'Checking for default.conf:'
ls -la /etc/nginx/conf.d/default.conf 2>/dev/null || echo 'No default.conf found'

echo ''
echo 'Checking nginx.conf main file:'
grep -n 'include.*conf.d' /etc/nginx/nginx.conf 2>/dev/null || echo 'Cannot check nginx.conf'
" 2>/dev/null || echo "Cannot execute commands in nginx container"

# Step 5: Check docker-compose configuration
echo -e "\n${YELLOW}🐳 Step 5: Docker Compose Configuration${NC}"
echo "Current docker-compose.yml nginx service:"
grep -A 20 "hartono-webserver:" docker-compose.yml || echo "Cannot find nginx service in docker-compose.yml"

# Step 6: Check volume mounts
echo -e "\n${YELLOW}💾 Step 6: Volume Mount Analysis${NC}"
echo "Nginx container volume mounts:"
docker inspect hartono-webserver --format '{{range .Mounts}}{{.Source}} -> {{.Destination}} ({{.Type}}){{"\n"}}{{end}}' 2>/dev/null || echo "Cannot inspect nginx container"

# Step 7: Check if there's a volume conflict
echo -e "\n${YELLOW}🔍 Step 7: Volume Conflict Check${NC}"
echo "Docker volumes related to nginx:"
docker volume ls | grep nginx || echo "No nginx volumes found"

echo -e "\nChecking hm-new_nginx-config volume:"
docker volume inspect hm-new_nginx-config 2>/dev/null || echo "Volume not found"

# Step 8: Check the actual error in detail
echo -e "\n${YELLOW}❌ Step 8: Detailed Error Analysis${NC}"
echo "Stopping nginx to get clean logs..."
docker stop hartono-webserver >/dev/null 2>&1 || echo "Already stopped"

echo "Starting nginx and capturing startup logs:"
docker start hartono-webserver >/dev/null 2>&1 || echo "Failed to start"
sleep 3

echo "Recent nginx logs with timestamps:"
docker logs --timestamps --tail=30 hartono-webserver 2>/dev/null || echo "Cannot get logs"

# Step 9: Check if the problem is in a different file
echo -e "\n${YELLOW}🔍 Step 9: Search for Hidden whatsapp-api References${NC}"
docker run --rm -v hm-new_nginx-config:/config alpine sh -c "
echo 'Searching for ALL whatsapp-api references in config directory:'
grep -r 'whatsapp-api' /config/ 2>/dev/null || echo 'No whatsapp-api references found in volume'

echo ''
echo 'Searching for line 32 specifically:'
sed -n '32p' /config/app.conf 2>/dev/null || echo 'Cannot read line 32'

echo ''
echo 'Lines 30-35:'
sed -n '30,35p' /config/app.conf 2>/dev/null || echo 'Cannot read lines 30-35'
"

# Step 10: Check if there's a backup or cache issue
echo -e "\n${YELLOW}💾 Step 10: Backup and Cache Analysis${NC}"
docker run --rm -v hm-new_nginx-config:/config alpine sh -c "
echo 'All files in config directory:'
find /config/ -type f -exec echo '=== {} ===' \; -exec cat {} \; -exec echo '' \;
"

# Step 11: Final diagnosis
echo -e "\n${YELLOW}🎯 Step 11: Diagnosis Summary${NC}"
echo "=================================================================="

NGINX_STATUS=$(docker ps --format "{{.Status}}" --filter "name=hartono-webserver" 2>/dev/null || echo "Unknown")
echo "Current nginx status: $NGINX_STATUS"

echo -e "\n${BLUE}📋 What we need to check:${NC}"
echo "1. Is the config file actually being updated in the volume?"
echo "2. Is nginx reading from a different location?"
echo "3. Is there a volume mount issue?"
echo "4. Is there a cached config somewhere?"
echo "5. Is docker-compose overriding our changes?"

echo -e "\n${BLUE}🔧 Possible Solutions:${NC}"
echo "1. Recreate the nginx container completely"
echo "2. Remove and recreate the volume"
echo "3. Use docker-compose down/up instead of restart"
echo "4. Check if there's a bind mount overriding the volume"

echo -e "\n${RED}🚨 CRITICAL QUESTION:${NC}"
echo "Is the config file we're editing the same one nginx is actually reading?"

# Step 12: Test direct config replacement
echo -e "\n${YELLOW}🧪 Step 12: Direct Config Test${NC}"
echo "Let's try to directly replace config while nginx is running:"

docker exec hartono-webserver sh -c "
echo 'Current config that nginx is actually using:'
cat /etc/nginx/conf.d/app.conf | grep -n whatsapp-api || echo 'No whatsapp-api found in running container'
" 2>/dev/null || echo "Cannot access running container config"
