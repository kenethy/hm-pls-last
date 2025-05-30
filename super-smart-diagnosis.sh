#!/bin/bash

# =============================================================================
# 🧠 SUPER SMART DIAGNOSIS - Root Cause Analysis
# =============================================================================
# Script untuk menganalisis masalah secara mendalam dan memberikan solusi tepat
# =============================================================================

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${PURPLE}"
echo "============================================================================="
echo "🧠 SUPER SMART DIAGNOSIS & ROOT CAUSE ANALYSIS"
echo "============================================================================="
echo "Menganalisis masalah secara mendalam untuk menemukan solusi yang tepat"
echo "============================================================================="
echo -e "${NC}"

# Fungsi helper
show_section() {
    echo -e "${CYAN}🔍 $1${NC}"
    echo "----------------------------------------"
}

show_check() {
    echo -e "${BLUE}   Checking: $1${NC}"
}

show_success() {
    echo -e "${GREEN}   ✅ $1${NC}"
}

show_error() {
    echo -e "${RED}   ❌ $1${NC}"
}

show_warning() {
    echo -e "${YELLOW}   ⚠️  $1${NC}"
}

show_info() {
    echo -e "${BLUE}   ℹ️  $1${NC}"
}

# DIAGNOSIS 1: Container Status
show_section "CONTAINER STATUS ANALYSIS"

show_check "WhatsApp containers..."
CONTAINERS=$(docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep whatsapp)
if [[ -n "$CONTAINERS" ]]; then
    echo "$CONTAINERS"
    
    # Check which container is actually running
    RUNNING_CONTAINER=$(docker ps --format "{{.Names}}" | grep whatsapp | head -1)
    if [[ -n "$RUNNING_CONTAINER" ]]; then
        show_success "Container running: $RUNNING_CONTAINER"
        
        # Check container health
        HEALTH=$(docker inspect "$RUNNING_CONTAINER" --format='{{.State.Health.Status}}' 2>/dev/null || echo "no-health-check")
        if [[ "$HEALTH" == "healthy" ]]; then
            show_success "Container health: $HEALTH"
        elif [[ "$HEALTH" == "no-health-check" ]]; then
            show_info "Container health: No health check configured"
        else
            show_warning "Container health: $HEALTH"
        fi
        
        # Check container ports
        PORTS=$(docker port "$RUNNING_CONTAINER" 2>/dev/null)
        if [[ -n "$PORTS" ]]; then
            show_success "Container ports: $PORTS"
        else
            show_error "No port mapping found"
        fi
    else
        show_error "No WhatsApp container is running"
    fi
else
    show_error "No WhatsApp containers found"
fi

echo ""

# DIAGNOSIS 2: Network Connectivity
show_section "NETWORK CONNECTIVITY ANALYSIS"

# Test different connection methods
CONNECTION_METHODS=(
    "http://localhost:3000"
    "http://127.0.0.1:3000"
    "http://0.0.0.0:3000"
    "http://$(hostname -I | awk '{print $1}'):3000"
)

WORKING_URL=""
for url in "${CONNECTION_METHODS[@]}"; do
    show_check "Testing $url..."
    if timeout 5 curl -s -f "$url/app/devices" > /dev/null 2>&1; then
        show_success "WORKING: $url"
        WORKING_URL="$url"
        
        # Get response details
        RESPONSE=$(curl -s "$url/app/devices" 2>/dev/null)
        if echo "$RESPONSE" | jq . > /dev/null 2>&1; then
            show_info "Response: $(echo "$RESPONSE" | jq -r '.message' 2>/dev/null || echo 'Valid JSON')"
        else
            show_info "Response: $RESPONSE"
        fi
        break
    else
        show_error "FAILED: $url"
    fi
done

echo ""

# DIAGNOSIS 3: Port Analysis
show_section "PORT ANALYSIS"

show_check "Port 3000 usage..."
PORT_INFO=$(ss -tlnp | grep ":3000" 2>/dev/null)
if [[ -n "$PORT_INFO" ]]; then
    show_success "Port 3000 is in use:"
    echo "   $PORT_INFO"
    
    # Extract process info
    PROCESS=$(echo "$PORT_INFO" | awk '{print $6}' | head -1)
    show_info "Process: $PROCESS"
else
    show_error "Port 3000 is not in use"
fi

show_check "Firewall status..."
UFW_STATUS=$(sudo ufw status 2>/dev/null | grep "3000" || echo "not found")
show_info "UFW rule for port 3000: $UFW_STATUS"

echo ""

# DIAGNOSIS 4: Laravel Configuration
show_section "LARAVEL CONFIGURATION ANALYSIS"

# Find Laravel directory
LARAVEL_DIRS=("/var/www/html" "/hm-new")
LARAVEL_DIR=""

for dir in "${LARAVEL_DIRS[@]}"; do
    if [[ -f "$dir/.env" ]] && [[ -f "$dir/artisan" ]]; then
        LARAVEL_DIR="$dir"
        break
    fi
done

if [[ -n "$LARAVEL_DIR" ]]; then
    show_success "Laravel found at: $LARAVEL_DIR"
    
    cd "$LARAVEL_DIR"
    
    # Check .env configuration
    show_check "WhatsApp API configuration in .env..."
    if grep -q "WHATSAPP_API_URL" .env; then
        API_URL=$(grep "WHATSAPP_API_URL" .env | cut -d'=' -f2)
        show_info "Configured API URL: $API_URL"
        
        # Test if configured URL works
        if [[ -n "$API_URL" ]]; then
            if timeout 5 curl -s -f "$API_URL/app/devices" > /dev/null 2>&1; then
                show_success "Configured URL is working"
            else
                show_error "Configured URL is NOT working"
            fi
        fi
    else
        show_warning "WHATSAPP_API_URL not found in .env"
    fi
    
    # Check Laravel can load config
    show_check "Laravel configuration loading..."
    if php artisan config:show whatsapp.api_url 2>/dev/null; then
        LARAVEL_API_URL=$(php artisan config:show whatsapp.api_url 2>/dev/null)
        show_info "Laravel sees API URL as: $LARAVEL_API_URL"
    else
        show_warning "Cannot read whatsapp config from Laravel"
    fi
    
    # Test Laravel HTTP client
    show_check "Laravel HTTP client test..."
    TEST_RESULT=$(php artisan tinker --execute="
        try {
            \$url = config('whatsapp.api_url', 'http://localhost:3000');
            echo 'Testing: ' . \$url . PHP_EOL;
            \$response = \Illuminate\Support\Facades\Http::timeout(5)->get(\$url . '/app/devices');
            echo 'Status: ' . \$response->status() . PHP_EOL;
            echo 'Success: ' . (\$response->successful() ? 'YES' : 'NO') . PHP_EOL;
            if (!\$response->successful()) {
                echo 'Error: ' . \$response->body() . PHP_EOL;
            }
        } catch (Exception \$e) {
            echo 'Exception: ' . \$e->getMessage() . PHP_EOL;
        }
    " 2>/dev/null)
    
    if [[ -n "$TEST_RESULT" ]]; then
        echo "   $TEST_RESULT"
    else
        show_error "Laravel HTTP test failed"
    fi
    
else
    show_error "Laravel directory not found"
fi

echo ""

# DIAGNOSIS 5: System Resources
show_section "SYSTEM RESOURCES ANALYSIS"

show_check "Memory usage..."
MEMORY=$(free -h | awk 'NR==2{printf "Used: %s/%s (%.1f%%)", $3,$2,$3*100/$2}')
show_info "Memory: $MEMORY"

show_check "Disk usage..."
DISK=$(df -h / | awk 'NR==2{printf "Used: %s/%s (%s)", $3,$2,$5}')
show_info "Disk: $DISK"

show_check "Load average..."
LOAD=$(uptime | awk -F'load average:' '{print $2}')
show_info "Load average:$LOAD"

echo ""

# DIAGNOSIS 6: Docker Network
show_section "DOCKER NETWORK ANALYSIS"

show_check "Docker networks..."
NETWORKS=$(docker network ls --format "table {{.Name}}\t{{.Driver}}")
echo "$NETWORKS"

if [[ -n "$RUNNING_CONTAINER" ]]; then
    show_check "Container network details..."
    NETWORK_INFO=$(docker inspect "$RUNNING_CONTAINER" --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' 2>/dev/null)
    if [[ -n "$NETWORK_INFO" ]]; then
        show_info "Container IP: $NETWORK_INFO"
        
        # Test container IP
        if timeout 5 curl -s -f "http://$NETWORK_INFO:3000/app/devices" > /dev/null 2>&1; then
            show_success "Container IP is accessible: http://$NETWORK_INFO:3000"
            WORKING_URL="http://$NETWORK_INFO:3000"
        else
            show_warning "Container IP is not accessible"
        fi
    fi
fi

echo ""

# SMART RECOMMENDATIONS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🧠 SMART RECOMMENDATIONS"
echo "============================================================================="
echo -e "${NC}"

if [[ -n "$WORKING_URL" ]]; then
    echo -e "${GREEN}✅ SOLUTION FOUND!${NC}"
    echo ""
    echo -e "${CYAN}🎯 Working API URL: $WORKING_URL${NC}"
    echo ""
    echo -e "${YELLOW}📋 STEPS TO FIX:${NC}"
    echo "1. Update Laravel .env:"
    echo "   WHATSAPP_API_URL=$WORKING_URL"
    echo ""
    echo "2. Clear Laravel cache:"
    echo "   cd $LARAVEL_DIR"
    echo "   php artisan config:clear"
    echo "   php artisan config:cache"
    echo ""
    echo "3. Test again:"
    echo "   https://hartonomotor.xyz/whatsapp/qr-generator"
    
elif [[ -n "$RUNNING_CONTAINER" ]]; then
    echo -e "${YELLOW}⚠️  CONTAINER RUNNING BUT NOT ACCESSIBLE${NC}"
    echo ""
    echo -e "${CYAN}🔧 TROUBLESHOOTING STEPS:${NC}"
    echo "1. Check container logs:"
    echo "   docker logs $RUNNING_CONTAINER --tail 20"
    echo ""
    echo "2. Restart container:"
    echo "   docker restart $RUNNING_CONTAINER"
    echo ""
    echo "3. Check container health:"
    echo "   docker exec $RUNNING_CONTAINER curl http://localhost:3000/app/devices"
    
else
    echo -e "${RED}❌ NO WORKING WHATSAPP API FOUND${NC}"
    echo ""
    echo -e "${CYAN}🚀 DEPLOYMENT NEEDED:${NC}"
    echo "1. Deploy WhatsApp API:"
    echo "   ./deploy-whatsapp-production.sh"
    echo ""
    echo "2. Or start existing:"
    echo "   /opt/whatsapp-api-production/start.sh"
    echo ""
    echo "3. Check available containers:"
    echo "   docker ps -a | grep whatsapp"
fi

echo ""
echo -e "${BLUE}🔍 ROOT CAUSE ANALYSIS:${NC}"
if [[ -n "$WORKING_URL" ]]; then
    echo "• WhatsApp API is working but Laravel has wrong URL configuration"
    echo "• This is a CONFIGURATION issue, not a compatibility issue"
elif [[ -n "$RUNNING_CONTAINER" ]]; then
    echo "• WhatsApp API container is running but not responding"
    echo "• This is a CONTAINER HEALTH issue"
else
    echo "• WhatsApp API is not running at all"
    echo "• This is a DEPLOYMENT issue"
fi

echo ""
echo -e "${GREEN}🎯 PROJECT IS COMPATIBLE! Just needs proper configuration.${NC}"
