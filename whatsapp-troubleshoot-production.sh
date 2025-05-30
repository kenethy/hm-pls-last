#!/bin/bash

# =============================================================================
# WhatsApp API Production Troubleshooting Script
# Comprehensive diagnostics and problem resolution for production deployment
# =============================================================================

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
PROJECT_ROOT="/opt/whatsapp-api-production"
CONTAINER_NAME="whatsapp-api-production"
CONFIG_DIR="$PROJECT_ROOT/config"
LOGS_DIR="$PROJECT_ROOT/logs"
DATA_DIR="$PROJECT_ROOT/data"

# Logging functions
log_info() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')] [INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[$(date '+%H:%M:%S')] [SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[$(date '+%H:%M:%S')] [WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[$(date '+%H:%M:%S')] [ERROR]${NC} $1"
}

log_step() {
    echo -e "${PURPLE}[$(date '+%H:%M:%S')] [STEP]${NC} $1"
}

# Check if project exists
check_project_exists() {
    if [[ ! -d "$PROJECT_ROOT" ]]; then
        log_error "Project directory not found: $PROJECT_ROOT"
        log_info "Please run the deployment script first: ./deploy-whatsapp-production.sh"
        exit 1
    fi
}

# Quick status check
quick_status() {
    log_step "Quick Status Check"
    
    echo -e "${CYAN}=== Container Status ===${NC}"
    if docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -q "$CONTAINER_NAME"; then
        docker ps --filter name="$CONTAINER_NAME" --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
        log_success "Container is running"
    else
        log_error "Container is not running"
        if docker ps -a --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
            log_warning "Container exists but is stopped"
            docker ps -a --filter name="$CONTAINER_NAME" --format 'table {{.Names}}\t{{.Status}}'
        else
            log_error "Container does not exist"
        fi
    fi
    echo ""
    
    echo -e "${CYAN}=== API Health Check ===${NC}"
    local api_port=$(grep WHATSAPP_PORT "$CONFIG_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "3000")
    if curl -s -f "http://localhost:$api_port/app/devices" > /dev/null 2>&1; then
        log_success "API is responding on port $api_port"
        echo "Response: $(curl -s "http://localhost:$api_port/app/devices" | jq -r '.message' 2>/dev/null || echo 'API responding')"
    else
        log_error "API is not responding on port $api_port"
    fi
    echo ""
    
    echo -e "${CYAN}=== Resource Usage ===${NC}"
    echo "Memory: $(free -h | awk 'NR==2{printf "%s/%s (%.1f%%)", $3,$2,$3*100/$2}')"
    echo "Disk: $(df -h /opt | awk 'NR==2{printf "%s/%s (%s)", $3,$2,$5}')"
    if docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        echo "Container: $(docker stats "$CONTAINER_NAME" --no-stream --format '{{.CPUPerc}} CPU, {{.MemUsage}} Memory')"
    fi
}

# Show detailed logs
show_logs() {
    log_step "Container Logs Analysis"
    
    if ! docker ps -a --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        log_error "Container not found"
        return 1
    fi
    
    echo -e "${CYAN}=== Recent Logs (Last 50 lines) ===${NC}"
    docker logs "$CONTAINER_NAME" --tail 50
    echo ""
    
    echo -e "${CYAN}=== Error Analysis ===${NC}"
    local error_count=$(docker logs "$CONTAINER_NAME" --tail 100 | grep -i error | wc -l)
    local warning_count=$(docker logs "$CONTAINER_NAME" --tail 100 | grep -i warning | wc -l)
    
    echo "Errors in last 100 lines: $error_count"
    echo "Warnings in last 100 lines: $warning_count"
    
    if [[ $error_count -gt 0 ]]; then
        echo ""
        echo "Recent errors:"
        docker logs "$CONTAINER_NAME" --tail 100 | grep -i error | tail -5
    fi
    
    echo ""
    read -p "Follow live logs? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log_info "Following logs (Ctrl+C to stop)..."
        docker logs "$CONTAINER_NAME" -f
    fi
}

# Test all API endpoints
test_endpoints() {
    log_step "API Endpoints Testing"
    
    if ! docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        log_error "Container is not running"
        return 1
    fi
    
    local api_port=$(grep WHATSAPP_PORT "$CONFIG_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "3000")
    local base_url="http://localhost:$api_port"
    
    echo -e "${CYAN}=== Testing Core Endpoints ===${NC}"
    
    # Test device endpoint
    echo -n "Testing /app/devices... "
    if response=$(curl -s -f "$base_url/app/devices" 2>/dev/null); then
        log_success "OK"
        echo "  Response: $(echo "$response" | jq -r '.message' 2>/dev/null || echo 'Valid response')"
    else
        log_error "FAILED"
    fi
    
    # Test fresh login endpoint
    echo -n "Testing /app/login-fresh... "
    if response=$(curl -s -f "$base_url/app/login-fresh" 2>/dev/null); then
        log_success "OK"
        echo "  QR Generated: $(echo "$response" | jq -r '.results.qr_link' 2>/dev/null | grep -o '[^/]*\.png$' || echo 'Yes')"
        echo "  Processing Time: $(echo "$response" | jq -r '.results.total_time_ms' 2>/dev/null || echo 'N/A')ms"
    else
        log_error "FAILED"
    fi
    
    # Test regular login endpoint
    echo -n "Testing /app/login... "
    if curl -s -f "$base_url/app/login" > /dev/null 2>&1; then
        log_success "OK"
    else
        log_error "FAILED"
    fi
    
    echo ""
    echo -e "${CYAN}=== QR Code Analysis ===${NC}"
    local qr_count=$(find "$DATA_DIR/qrcode" -name "*.png" 2>/dev/null | wc -l)
    echo "QR codes in storage: $qr_count"
    
    if [[ $qr_count -gt 0 ]]; then
        echo "Recent QR codes:"
        find "$DATA_DIR/qrcode" -name "*.png" -printf "%T@ %p\n" 2>/dev/null | sort -n | tail -3 | while read timestamp file; do
            echo "  $(basename "$file") - $(date -d @${timestamp%.*} '+%Y-%m-%d %H:%M:%S')"
        done
    fi
}

# Performance diagnostics
performance_check() {
    log_step "Performance Diagnostics"
    
    echo -e "${CYAN}=== System Performance ===${NC}"
    echo "Load Average: $(uptime | awk -F'load average:' '{print $2}')"
    echo "Memory Usage: $(free | awk 'NR==2{printf "%.1f%%", $3*100/$2}')"
    echo "Disk Usage: $(df /opt | awk 'NR==2{print $5}')"
    echo ""
    
    if docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        echo -e "${CYAN}=== Container Performance ===${NC}"
        docker stats "$CONTAINER_NAME" --no-stream --format 'table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}'
        echo ""
        
        echo -e "${CYAN}=== API Response Time Test ===${NC}"
        local api_port=$(grep WHATSAPP_PORT "$CONFIG_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "3000")
        local start_time=$(date +%s%3N)
        
        if curl -s -f "http://localhost:$api_port/app/devices" > /dev/null 2>&1; then
            local end_time=$(date +%s%3N)
            local response_time=$((end_time - start_time))
            echo "Device endpoint response time: ${response_time}ms"
            
            if [[ $response_time -lt 1000 ]]; then
                log_success "Response time is good"
            elif [[ $response_time -lt 3000 ]]; then
                log_warning "Response time is acceptable"
            else
                log_error "Response time is slow"
            fi
        else
            log_error "API not responding"
        fi
    fi
}

# Security check
security_check() {
    log_step "Security Diagnostics"
    
    echo -e "${CYAN}=== Firewall Status ===${NC}"
    sudo ufw status verbose | head -10
    echo ""
    
    echo -e "${CYAN}=== Open Ports ===${NC}"
    ss -tlnp | grep -E ':3000|:80|:443' || echo "No relevant ports found"
    echo ""
    
    echo -e "${CYAN}=== File Permissions ===${NC}"
    echo "Config directory: $(ls -ld "$CONFIG_DIR" 2>/dev/null || echo 'Not found')"
    echo "Environment file: $(ls -l "$CONFIG_DIR/.env" 2>/dev/null || echo 'Not found')"
    echo "Data directory: $(ls -ld "$DATA_DIR" 2>/dev/null || echo 'Not found')"
    echo ""
    
    echo -e "${CYAN}=== Container Security ===${NC}"
    if docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        echo "Container user: $(docker exec "$CONTAINER_NAME" whoami 2>/dev/null || echo 'Unknown')"
        echo "Container processes: $(docker exec "$CONTAINER_NAME" ps aux | wc -l) processes"
    fi
}

# Quick fixes menu
quick_fixes() {
    log_step "Quick Fix Options"
    
    echo "1. Restart container"
    echo "2. Clear QR codes"
    echo "3. Rebuild container"
    echo "4. Reset session data"
    echo "5. Check disk space"
    echo "6. View container health"
    echo "0. Back to main menu"
    echo ""
    
    read -p "Select fix (0-6): " -n 1 -r
    echo ""
    
    case $REPLY in
        1)
            log_info "Restarting container..."
            cd "$PROJECT_ROOT"
            docker-compose -f docker-compose.production.yml restart
            sleep 5
            log_success "Container restarted"
            ;;
        2)
            log_info "Clearing QR codes..."
            rm -f "$DATA_DIR/qrcode"/*.png 2>/dev/null || true
            log_success "QR codes cleared"
            ;;
        3)
            log_info "Rebuilding container..."
            cd "$PROJECT_ROOT"
            docker-compose -f docker-compose.production.yml down
            docker-compose -f docker-compose.production.yml build --no-cache
            docker-compose -f docker-compose.production.yml up -d
            log_success "Container rebuilt"
            ;;
        4)
            log_warning "This will clear all session data!"
            read -p "Continue? (y/N): " -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                rm -rf "$DATA_DIR/sessions"/* 2>/dev/null || true
                rm -f "$DATA_DIR/qrcode"/*.png 2>/dev/null || true
                cd "$PROJECT_ROOT"
                docker-compose -f docker-compose.production.yml restart
                log_success "Session data reset"
            fi
            ;;
        5)
            log_info "Checking disk space..."
            df -h /opt
            du -sh "$PROJECT_ROOT"/* 2>/dev/null || true
            ;;
        6)
            log_info "Container health check..."
            if docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
                docker inspect "$CONTAINER_NAME" | jq '.[].State.Health' 2>/dev/null || echo "Health check not configured"
            else
                log_error "Container not running"
            fi
            ;;
        0)
            return 0
            ;;
        *)
            log_warning "Invalid option"
            ;;
    esac
    
    echo ""
    read -p "Press Enter to continue..."
}

# Main menu
show_menu() {
    clear
    echo -e "${GREEN}"
    echo "============================================================================="
    echo "🔧 WhatsApp API Production Troubleshooting"
    echo "============================================================================="
    echo -e "${NC}"
    
    echo "1. Quick Status Check"
    echo "2. View Logs"
    echo "3. Test API Endpoints"
    echo "4. Performance Check"
    echo "5. Security Check"
    echo "6. Quick Fixes"
    echo "7. Generate Diagnostic Report"
    echo "8. Exit"
    echo ""
}

# Generate diagnostic report
generate_report() {
    log_step "Generating Diagnostic Report"
    
    local report_file="$LOGS_DIR/diagnostic-$(date +%Y%m%d_%H%M%S).txt"
    
    {
        echo "WhatsApp API Production Diagnostic Report"
        echo "Generated: $(date)"
        echo "=========================================="
        echo ""
        
        echo "=== Quick Status ==="
        quick_status
        echo ""
        
        echo "=== Performance ==="
        performance_check
        echo ""
        
        echo "=== Security ==="
        security_check
        echo ""
        
        echo "=== Recent Logs ==="
        docker logs "$CONTAINER_NAME" --tail 20 2>/dev/null || echo "Container logs not available"
        
    } > "$report_file" 2>&1
    
    log_success "Report saved to: $report_file"
}

# Main function
main() {
    check_project_exists
    
    while true; do
        show_menu
        read -p "Select option (1-8): " choice
        echo ""
        
        case $choice in
            1) quick_status ;;
            2) show_logs ;;
            3) test_endpoints ;;
            4) performance_check ;;
            5) security_check ;;
            6) quick_fixes ;;
            7) generate_report ;;
            8) 
                log_info "Goodbye!"
                exit 0
                ;;
            *)
                log_warning "Invalid option. Please select 1-8."
                ;;
        esac
        
        echo ""
        read -p "Press Enter to continue..."
    done
}

# Run main function
main "$@"
