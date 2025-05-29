#!/bin/bash

# Fix Unhealthy WhatsApp Container
# Specific fix for websocket and health check issues

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing Unhealthy WhatsApp Container${NC}"
echo "=================================================="

# Step 1: Check current container status
echo -e "\n${YELLOW}📦 Step 1: Analyzing unhealthy container...${NC}"

WHATSAPP_CONTAINER="whatsapp-api-hartono"
echo "Container: $WHATSAPP_CONTAINER"

# Get detailed container info
echo "Container health status:"
docker inspect --format='{{.State.Health.Status}}' $WHATSAPP_CONTAINER 2>/dev/null || echo "No health check"

echo "Health check logs:"
docker inspect --format='{{range .State.Health.Log}}{{.Output}}{{end}}' $WHATSAPP_CONTAINER 2>/dev/null || echo "No health logs"

# Check container logs for errors
echo -e "\nContainer logs (last 20 lines):"
docker logs --tail=20 $WHATSAPP_CONTAINER

# Step 2: Test API with authentication
echo -e "\n${YELLOW}🔐 Step 2: Testing API with authentication...${NC}"

echo "Testing API with basic auth:"
curl -s -u "admin:HartonoMotor2025!" "http://localhost:3000/app/devices" || echo "Auth test failed"

echo -e "\nTesting login endpoint with auth:"
curl -s -u "admin:HartonoMotor2025!" "http://localhost:3000/app/login" || echo "Login with auth failed"

# Step 3: Check container environment and config
echo -e "\n${YELLOW}⚙️ Step 3: Checking container configuration...${NC}"

echo "Container environment variables:"
docker exec $WHATSAPP_CONTAINER env | grep -E "(APP_|WHATSAPP_|DB_)" || echo "No env vars found"

echo -e "\nContainer internal file structure:"
docker exec $WHATSAPP_CONTAINER ls -la /app/ || echo "Cannot access /app"

echo "Checking statics directory in container:"
docker exec $WHATSAPP_CONTAINER ls -la /app/statics/ || echo "Cannot access /app/statics"

# Step 4: Fix Nginx configuration for authentication
echo -e "\n${YELLOW}🌐 Step 4: Fixing Nginx proxy configuration...${NC}"

# Check current nginx config
NGINX_CONTAINER="hartono-webserver"
echo "Current Nginx configuration for WhatsApp API:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 20 -B 5 "whatsapp-api" || echo "No WhatsApp config found"

# Create corrected nginx config
echo "Creating corrected Nginx configuration..."

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Update nginx config to include basic auth for proxy
cat > /tmp/nginx_whatsapp_fix.conf << 'EOF'
    # WhatsApp API Reverse Proxy with Authentication
    location /whatsapp-api/ {
        # Add basic auth header for backend
        proxy_set_header Authorization "Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=";
        
        proxy_pass http://whatsapp-api-hartono:3000/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
        
        # Disable proxy buffering for real-time responses
        proxy_buffering off;
        proxy_request_buffering off;

        # CORS headers for WhatsApp API
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
        
        # Handle preflight requests
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization';
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain; charset=utf-8';
            add_header 'Content-Length' 0;
            return 204;
        }
    }
EOF

# Apply the nginx fix
echo "Applying Nginx configuration fix..."
docker exec $NGINX_CONTAINER bash -c "
    # Find and replace the WhatsApp API location block
    sed -i '/location \/whatsapp-api\//,/^    }$/c\
    # WhatsApp API Reverse Proxy with Authentication\
    location /whatsapp-api/ {\
        # Add basic auth header for backend\
        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";\
        \
        proxy_pass http://whatsapp-api-hartono:3000/;\
        proxy_http_version 1.1;\
        proxy_set_header Upgrade \$http_upgrade;\
        proxy_set_header Connection \"upgrade\";\
        proxy_set_header Host \$host;\
        proxy_set_header X-Real-IP \$remote_addr;\
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;\
        proxy_set_header X-Forwarded-Proto \$scheme;\
        proxy_cache_bypass \$http_upgrade;\
        proxy_read_timeout 300s;\
        proxy_connect_timeout 75s;\
        \
        # Disable proxy buffering for real-time responses\
        proxy_buffering off;\
        proxy_request_buffering off;\
\
        # CORS headers for WhatsApp API\
        add_header \"Access-Control-Allow-Origin\" \"*\" always;\
        add_header \"Access-Control-Allow-Methods\" \"GET, POST, OPTIONS\" always;\
        add_header \"Access-Control-Allow-Headers\" \"DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization\" always;\
        \
        # Handle preflight requests\
        if (\$request_method = \"OPTIONS\") {\
            add_header \"Access-Control-Allow-Origin\" \"*\";\
            add_header \"Access-Control-Allow-Methods\" \"GET, POST, OPTIONS\";\
            add_header \"Access-Control-Allow-Headers\" \"DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization\";\
            add_header \"Access-Control-Max-Age\" 1728000;\
            add_header \"Content-Type\" \"text/plain; charset=utf-8\";\
            add_header \"Content-Length\" 0;\
            return 204;\
        }\
    }' /etc/nginx/conf.d/app.conf
"

# Test nginx config
echo "Testing Nginx configuration:"
docker exec $NGINX_CONTAINER nginx -t || echo "Nginx config test failed"

# Reload nginx
echo "Reloading Nginx..."
docker exec $NGINX_CONTAINER nginx -s reload || echo "Nginx reload failed"

# Step 5: Fix container health check
echo -e "\n${YELLOW}🏥 Step 5: Fixing container health check...${NC}"

# Go to the correct directory
if [ -d "go-whatsapp-web-multidevice-main" ]; then
    cd go-whatsapp-web-multidevice-main
fi

# Update docker-compose with better health check
echo "Updating docker-compose with improved health check..."
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  whatsapp-api:
    build:
      context: .
      dockerfile: ./docker/golang.Dockerfile
    container_name: whatsapp-api-hartono
    restart: unless-stopped
    ports:
      - "3000:3000"
    volumes:
      - /var/www/whatsapp_statics:/app/statics
      - whatsapp_sessions:/app/storages
    environment:
      - APP_PORT=3000
      - APP_DEBUG=false
      - APP_OS=HartonoMotor
      - APP_BASIC_AUTH=admin:HartonoMotor2025!
      - WHATSAPP_WEBHOOK=https://hartonomotor.xyz/api/whatsapp/webhook
      - WHATSAPP_WEBHOOK_SECRET=HartonoMotorWebhookSecret2025
      - DB_URI=file:storages/whatsapp.db?_foreign_keys=on
    networks:
      - default
    healthcheck:
      test: ["CMD", "curl", "-f", "-u", "admin:HartonoMotor2025!", "http://localhost:3000/app/devices"]
      interval: 30s
      timeout: 10s
      retries: 5
      start_period: 60s

volumes:
  whatsapp_sessions:
EOF

# Restart container with new config
echo "Restarting container with improved configuration..."
docker-compose down
sleep 5
docker-compose up -d

# Wait for container to start
echo "Waiting for container to start..."
sleep 30

# Step 6: Test everything
echo -e "\n${YELLOW}🧪 Step 6: Testing fixed configuration...${NC}"

# Test direct API with auth
echo "Testing direct API with auth:"
curl -s -u "admin:HartonoMotor2025!" -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Direct API test failed"

# Test through nginx
echo "Testing through Nginx:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" || echo "Nginx proxy test failed"

# Test login endpoint
echo "Testing login endpoint:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "Login endpoint test failed"

# Check container health
echo -e "\nContainer health status:"
sleep 10
docker inspect --format='{{.State.Health.Status}}' whatsapp-api-hartono 2>/dev/null || echo "No health check"

# Step 7: Final verification
echo -e "\n${YELLOW}✅ Step 7: Final verification...${NC}"

echo "Container status:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep whatsapp

echo -e "\nRecent container logs:"
docker logs --tail=5 whatsapp-api-hartono

echo -e "\n${GREEN}🎉 Fix completed!${NC}"
echo -e "\n${BLUE}📋 Test the QR page now:${NC}"
echo "https://hartonomotor.xyz/whatsapp-qr.html"

echo -e "\n${BLUE}📊 Monitoring:${NC}"
echo "- Container health: docker inspect --format='{{.State.Health.Status}}' whatsapp-api-hartono"
echo "- Container logs: docker logs -f whatsapp-api-hartono"
echo "- API test: curl -u 'admin:HartonoMotor2025!' http://localhost:3000/app/devices"
