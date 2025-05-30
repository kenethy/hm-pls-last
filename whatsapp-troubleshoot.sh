#!/bin/bash

# =============================================================================
# 🚗 HARTONO MOTOR - WhatsApp API Troubleshooting Script
# =============================================================================
# Script untuk troubleshooting dan monitoring WhatsApp API
# =============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

CONTAINER_NAME="hartono-whatsapp-api"

# Functions
log() {
    echo -e "${CYAN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

step() {
    echo -e "${PURPLE}🔄 $1${NC}"
}

# Check container status
check_status() {
    step "Checking WhatsApp API container status..."
    
    if docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        success "Container is running"
        docker ps --filter name="$CONTAINER_NAME" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    else
        error "Container is not running"
        
        if docker ps -a --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
            warning "Container exists but is stopped"
            docker ps -a --filter name="$CONTAINER_NAME" --format "table {{.Names}}\t{{.Status}}"
        else
            error "Container does not exist"
        fi
        return 1
    fi
}

# Show logs
show_logs() {
    step "Showing container logs..."
    
    if docker ps -a --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        echo -e "${CYAN}=== Last 50 lines of logs ===${NC}"
        docker logs "$CONTAINER_NAME" --tail 50
        
        echo -e "${CYAN}=== Follow logs (Ctrl+C to stop) ===${NC}"
        read -p "Follow live logs? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            docker logs "$CONTAINER_NAME" -f
        fi
    else
        error "Container not found"
    fi
}

# Test API endpoints
test_api() {
    step "Testing API endpoints..."
    
    if ! docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        error "Container is not running"
        return 1
    fi
    
    # Test devices endpoint
    log "Testing /app/devices..."
    if docker exec "$CONTAINER_NAME" wget -qO- http://localhost:3000/app/devices 2>/dev/null; then
        success "Devices endpoint working"
    else
        error "Devices endpoint failed"
    fi
    
    echo
    
    # Test fresh login endpoint
    log "Testing /app/login-fresh..."
    if docker exec "$CONTAINER_NAME" wget -qO- http://localhost:3000/app/login-fresh 2>/dev/null; then
        success "Fresh login endpoint working"
    else
        error "Fresh login endpoint failed"
    fi
    
    echo
    
    # Test regular login endpoint
    log "Testing /app/login..."
    if docker exec "$CONTAINER_NAME" wget -qO- http://localhost:3000/app/login 2>/dev/null; then
        success "Regular login endpoint working"
    else
        error "Regular login endpoint failed"
    fi
}

# Clear session data
clear_session() {
    step "Clearing WhatsApp session data..."
    
    if ! docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        error "Container is not running"
        return 1
    fi
    
    warning "This will clear all WhatsApp session data and QR codes"
    read -p "Are you sure? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        info "Operation cancelled"
        return 0
    fi
    
    # Clear QR codes
    log "Clearing QR code files..."
    docker exec "$CONTAINER_NAME" sh -c "rm -f /app/statics/qrcode/scan-qr*.png" || true
    
    # Clear session database
    log "Clearing session database..."
    docker exec "$CONTAINER_NAME" sh -c "rm -f /app/storages/whatsapp.db" || true
    
    # Restart container
    log "Restarting container..."
    docker restart "$CONTAINER_NAME"
    
    # Wait for restart
    sleep 10
    
    success "Session data cleared and container restarted"
}

# Restart container
restart_container() {
    step "Restarting WhatsApp API container..."
    
    if docker ps -a --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        docker restart "$CONTAINER_NAME"
        sleep 10
        
        if docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
            success "Container restarted successfully"
        else
            error "Container failed to restart"
            return 1
        fi
    else
        error "Container not found"
        return 1
    fi
}

# Show container info
show_info() {
    step "Showing container information..."
    
    if docker ps -a --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        echo -e "${CYAN}=== Container Details ===${NC}"
        docker inspect "$CONTAINER_NAME" --format='
Container: {{.Name}}
Status: {{.State.Status}}
Started: {{.State.StartedAt}}
Image: {{.Config.Image}}
Ports: {{range $p, $conf := .NetworkSettings.Ports}}{{$p}} -> {{(index $conf 0).HostPort}} {{end}}
Volumes: {{range .Mounts}}{{.Source}}:{{.Destination}} {{end}}
'
        
        echo -e "${CYAN}=== Resource Usage ===${NC}"
        docker stats "$CONTAINER_NAME" --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}"
        
        echo -e "${CYAN}=== Environment Variables ===${NC}"
        docker exec "$CONTAINER_NAME" env | grep -E "(APP_|WHATSAPP_|DB_)" | sort
    else
        error "Container not found"
    fi
}

# Generate fresh QR code
generate_qr() {
    step "Generating fresh QR code..."
    
    if ! docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        error "Container is not running"
        return 1
    fi
    
    log "Calling fresh login endpoint..."
    response=$(docker exec "$CONTAINER_NAME" wget -qO- http://localhost:3000/app/login-fresh 2>/dev/null)
    
    if echo "$response" | grep -q "SUCCESS"; then
        success "Fresh QR code generated successfully"
        echo "$response" | python3 -m json.tool 2>/dev/null || echo "$response"
    else
        error "Failed to generate QR code"
        echo "$response"
    fi
}

# Show menu
show_menu() {
    echo -e "${GREEN}"
    echo "============================================================================="
    echo "🚗 HARTONO MOTOR - WhatsApp API Troubleshooting"
    echo "============================================================================="
    echo -e "${NC}"
    
    echo "1. Check container status"
    echo "2. Show logs"
    echo "3. Test API endpoints"
    echo "4. Generate fresh QR code"
    echo "5. Clear session data"
    echo "6. Restart container"
    echo "7. Show container info"
    echo "8. Exit"
    echo
}

# Main function
main() {
    while true; do
        show_menu
        read -p "Select option (1-8): " choice
        echo
        
        case $choice in
            1) check_status ;;
            2) show_logs ;;
            3) test_api ;;
            4) generate_qr ;;
            5) clear_session ;;
            6) restart_container ;;
            7) show_info ;;
            8) 
                info "Goodbye!"
                exit 0
                ;;
            *)
                warning "Invalid option. Please select 1-8."
                ;;
        esac
        
        echo
        read -p "Press Enter to continue..."
        clear
    done
}

# Run main function
main "$@"
