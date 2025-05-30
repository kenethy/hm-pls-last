#!/bin/bash

# =============================================================================
# WhatsApp API Production Deployment Verification Script
# Quick verification of successful deployment
# =============================================================================

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
PROJECT_ROOT="/opt/whatsapp-api-production"
CONTAINER_NAME="whatsapp-api-production"
CONFIG_DIR="$PROJECT_ROOT/config"

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_TOTAL=0

# Test function
run_test() {
    local test_name="$1"
    local test_command="$2"
    
    ((TESTS_TOTAL++))
    echo -n "Testing $test_name... "
    
    if eval "$test_command" >/dev/null 2>&1; then
        log_success "PASSED"
        ((TESTS_PASSED++))
        return 0
    else
        log_error "FAILED"
        ((TESTS_FAILED++))
        return 1
    fi
}

# Verification tests
verify_deployment() {
    echo -e "${CYAN}"
    echo "============================================================================="
    echo "🔍 WhatsApp API Production Deployment Verification"
    echo "============================================================================="
    echo -e "${NC}"
    
    log_info "Starting deployment verification..."
    echo ""
    
    # Test 1: Project directory exists
    run_test "Project directory" "[[ -d '$PROJECT_ROOT' ]]"
    
    # Test 2: Configuration file exists
    run_test "Configuration file" "[[ -f '$CONFIG_DIR/.env' ]]"
    
    # Test 3: Docker is installed
    run_test "Docker installation" "command -v docker"
    
    # Test 4: Docker service is running
    run_test "Docker service" "systemctl is-active --quiet docker"
    
    # Test 5: Container exists
    run_test "Container exists" "docker ps -a --format 'table {{.Names}}' | grep -q '$CONTAINER_NAME'"
    
    # Test 6: Container is running
    run_test "Container running" "docker ps --format 'table {{.Names}}' | grep -q '$CONTAINER_NAME'"
    
    # Test 7: API port is open
    local api_port=$(grep WHATSAPP_PORT "$CONFIG_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "3000")
    run_test "API port ($api_port) open" "ss -tlnp | grep -q ':$api_port'"
    
    # Test 8: API responds to health check
    run_test "API health check" "curl -s -f http://localhost:$api_port/app/devices"
    
    # Test 9: Fresh QR endpoint works
    run_test "Fresh QR endpoint" "curl -s -f http://localhost:$api_port/app/login-fresh"
    
    # Test 10: Data directories exist
    run_test "Data directories" "[[ -d '$PROJECT_ROOT/data/sessions' && -d '$PROJECT_ROOT/data/qrcode' ]]"
    
    # Test 11: Log directories exist
    run_test "Log directories" "[[ -d '$PROJECT_ROOT/logs' ]]"
    
    # Test 12: Management scripts exist
    run_test "Management scripts" "[[ -f '$PROJECT_ROOT/start.sh' && -f '$PROJECT_ROOT/stop.sh' ]]"
    
    # Test 13: Firewall is configured
    run_test "Firewall configuration" "sudo ufw status | grep -q 'Status: active'"
    
    # Test 14: Backup system is configured
    run_test "Backup system" "[[ -f '$PROJECT_ROOT/backup.sh' ]]"
    
    # Test 15: Monitoring is configured
    run_test "Monitoring system" "[[ -f '$PROJECT_ROOT/monitor.sh' ]]"
    
    echo ""
    echo -e "${CYAN}=== Test Results ===${NC}"
    echo "Tests Passed: $TESTS_PASSED"
    echo "Tests Failed: $TESTS_FAILED"
    echo "Total Tests: $TESTS_TOTAL"
    echo ""
    
    if [[ $TESTS_FAILED -eq 0 ]]; then
        log_success "All tests passed! Deployment is successful."
        echo ""
        show_deployment_info
        return 0
    else
        log_error "Some tests failed. Please check the deployment."
        echo ""
        show_troubleshooting_tips
        return 1
    fi
}

# Show deployment information
show_deployment_info() {
    echo -e "${CYAN}=== Deployment Information ===${NC}"
    
    # Get configuration
    local api_port=$(grep WHATSAPP_PORT "$CONFIG_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "3000")
    local username=$(grep WHATSAPP_BASIC_AUTH_USERNAME "$CONFIG_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "admin")
    
    echo "📁 Project Directory: $PROJECT_ROOT"
    echo "🐳 Container Name: $CONTAINER_NAME"
    echo "🌐 API Port: $api_port"
    echo "👤 Basic Auth Username: $username"
    echo "🔐 Basic Auth Password: [Check $CONFIG_DIR/.env]"
    echo ""
    
    echo -e "${CYAN}=== API Endpoints ===${NC}"
    echo "Device Status: http://localhost:$api_port/app/devices"
    echo "Fresh QR Login: http://localhost:$api_port/app/login-fresh"
    echo "Regular QR Login: http://localhost:$api_port/app/login"
    echo ""
    
    echo -e "${CYAN}=== Management Commands ===${NC}"
    echo "Start API: $PROJECT_ROOT/start.sh"
    echo "Stop API: $PROJECT_ROOT/stop.sh"
    echo "Restart API: $PROJECT_ROOT/restart.sh"
    echo "View Logs: $PROJECT_ROOT/logs.sh"
    echo "Check Status: $PROJECT_ROOT/status.sh"
    echo "Troubleshoot: ./whatsapp-troubleshoot-production.sh"
    echo ""
    
    echo -e "${CYAN}=== Container Status ===${NC}"
    docker ps --filter name="$CONTAINER_NAME" --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
    echo ""
    
    echo -e "${CYAN}=== Quick API Test ===${NC}"
    echo "Testing API response..."
    if response=$(curl -s "http://localhost:$api_port/app/devices" 2>/dev/null); then
        echo "API Response: $(echo "$response" | jq -r '.message' 2>/dev/null || echo 'API is responding')"
    else
        echo "API is not responding"
    fi
    echo ""
    
    echo -e "${GREEN}✅ Deployment verification completed successfully!${NC}"
    echo ""
    echo -e "${YELLOW}Next Steps:${NC}"
    echo "1. Test QR code generation: curl http://localhost:$api_port/app/login-fresh"
    echo "2. Scan QR code with WhatsApp mobile app"
    echo "3. Verify device linking works properly"
    echo "4. Configure your application to use this API"
    echo "5. Set up external monitoring if needed"
}

# Show troubleshooting tips
show_troubleshooting_tips() {
    echo -e "${YELLOW}=== Troubleshooting Tips ===${NC}"
    echo ""
    echo "If tests failed, try these steps:"
    echo ""
    echo "1. Check container logs:"
    echo "   docker logs $CONTAINER_NAME"
    echo ""
    echo "2. Restart the container:"
    echo "   $PROJECT_ROOT/restart.sh"
    echo ""
    echo "3. Run the troubleshooting script:"
    echo "   ./whatsapp-troubleshoot-production.sh"
    echo ""
    echo "4. Check system resources:"
    echo "   free -h"
    echo "   df -h"
    echo ""
    echo "5. Verify firewall settings:"
    echo "   sudo ufw status"
    echo ""
    echo "6. Re-run deployment if needed:"
    echo "   ./deploy-whatsapp-production.sh"
}

# Performance test
performance_test() {
    echo -e "${CYAN}=== Performance Test ===${NC}"
    
    local api_port=$(grep WHATSAPP_PORT "$CONFIG_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "3000")
    
    echo "Testing API response times..."
    
    # Test device endpoint
    echo -n "Device endpoint: "
    local start_time=$(date +%s%3N)
    if curl -s -f "http://localhost:$api_port/app/devices" > /dev/null 2>&1; then
        local end_time=$(date +%s%3N)
        local response_time=$((end_time - start_time))
        echo "${response_time}ms"
        
        if [[ $response_time -lt 1000 ]]; then
            log_success "Excellent response time"
        elif [[ $response_time -lt 3000 ]]; then
            log_warning "Acceptable response time"
        else
            log_error "Slow response time"
        fi
    else
        log_error "No response"
    fi
    
    # Test fresh QR endpoint
    echo -n "Fresh QR endpoint: "
    local start_time=$(date +%s%3N)
    if response=$(curl -s -f "http://localhost:$api_port/app/login-fresh" 2>/dev/null); then
        local end_time=$(date +%s%3N)
        local response_time=$((end_time - start_time))
        local server_time=$(echo "$response" | jq -r '.results.total_time_ms' 2>/dev/null || echo "N/A")
        echo "${response_time}ms (server: ${server_time}ms)"
        
        if [[ $response_time -lt 2000 ]]; then
            log_success "Excellent QR generation time"
        elif [[ $response_time -lt 5000 ]]; then
            log_warning "Acceptable QR generation time"
        else
            log_error "Slow QR generation time"
        fi
    else
        log_error "No response"
    fi
    
    echo ""
}

# Main function
main() {
    # Check if project exists
    if [[ ! -d "$PROJECT_ROOT" ]]; then
        log_error "Project directory not found: $PROJECT_ROOT"
        log_info "Please run the deployment script first: ./deploy-whatsapp-production.sh"
        exit 1
    fi
    
    # Run verification
    if verify_deployment; then
        echo ""
        performance_test
        exit 0
    else
        exit 1
    fi
}

# Run main function
main "$@"
