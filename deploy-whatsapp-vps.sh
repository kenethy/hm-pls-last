#!/bin/bash

# =============================================================================
# 🚗 HARTONO MOTOR - WhatsApp API VPS Deployment Script
# =============================================================================
# Script super menyeluruh untuk deployment WhatsApp API di VPS yang sensitif
# Dibuat khusus untuk mengatasi masalah QR code caching/expiration
# =============================================================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
CONTAINER_NAME="hartono-whatsapp-api"
SERVICE_NAME="whatsapp-api"
BACKUP_DIR="./backups/whatsapp-$(date +%Y%m%d_%H%M%S)"
LOG_FILE="./deploy-whatsapp-$(date +%Y%m%d_%H%M%S).log"

# Functions
log() {
    echo -e "${CYAN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}ℹ️  $1${NC}" | tee -a "$LOG_FILE"
}

step() {
    echo -e "${PURPLE}🔄 $1${NC}" | tee -a "$LOG_FILE"
}

# Check if running as root or with sudo
check_permissions() {
    if [[ $EUID -eq 0 ]]; then
        warning "Running as root. This is generally not recommended."
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# Check prerequisites
check_prerequisites() {
    step "Checking prerequisites..."
    
    # Check if docker is installed
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    # Check if docker-compose is installed
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    # Check if we're in the right directory
    if [[ ! -f "docker-compose.yml" ]]; then
        error "docker-compose.yml not found. Please run this script from the project root directory."
        exit 1
    fi
    
    # Check if go-whatsapp directory exists
    if [[ ! -d "go-whatsapp-web-multidevice-main" ]]; then
        error "go-whatsapp-web-multidevice-main directory not found."
        exit 1
    fi
    
    success "All prerequisites met"
}

# Create backup
create_backup() {
    step "Creating backup..."
    
    mkdir -p "$BACKUP_DIR"
    
    # Backup current container if exists
    if docker ps -a --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        log "Backing up current container..."
        docker commit "$CONTAINER_NAME" "hartono-whatsapp-backup:$(date +%Y%m%d_%H%M%S)" || true
    fi
    
    # Backup volumes
    if docker volume ls | grep -q "whatsapp_data"; then
        log "Backing up WhatsApp data volume..."
        docker run --rm -v hm-cukupya_whatsapp_data:/data -v "$(pwd)/$BACKUP_DIR":/backup alpine tar czf /backup/whatsapp_data.tar.gz -C /data . || true
    fi
    
    if docker volume ls | grep -q "whatsapp_statics"; then
        log "Backing up WhatsApp statics volume..."
        docker run --rm -v hm-cukupya_whatsapp_statics:/data -v "$(pwd)/$BACKUP_DIR":/backup alpine tar czf /backup/whatsapp_statics.tar.gz -C /data . || true
    fi
    
    success "Backup created in $BACKUP_DIR"
}

# Stop and remove existing containers
stop_existing() {
    step "Stopping existing WhatsApp containers..."
    
    # Stop the service gracefully
    if docker-compose ps | grep -q "$SERVICE_NAME"; then
        log "Stopping WhatsApp service..."
        docker-compose stop "$SERVICE_NAME" || true
        sleep 5
    fi
    
    # Remove the container
    if docker ps -a --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        log "Removing existing container..."
        docker rm -f "$CONTAINER_NAME" || true
    fi
    
    # Clean up any orphaned containers
    docker-compose down --remove-orphans || true
    
    success "Existing containers stopped and removed"
}

# Clean up Docker resources
cleanup_docker() {
    step "Cleaning up Docker resources..."
    
    # Remove unused images
    log "Removing unused Docker images..."
    docker image prune -f || true
    
    # Remove unused networks
    log "Removing unused Docker networks..."
    docker network prune -f || true
    
    success "Docker cleanup completed"
}

# Build new image
build_image() {
    step "Building new WhatsApp API image..."
    
    # Build with no cache to ensure fresh build
    log "Building image with no cache..."
    docker-compose build --no-cache "$SERVICE_NAME"
    
    success "New image built successfully"
}

# Start new container
start_container() {
    step "Starting new WhatsApp API container..."
    
    # Start the service
    docker-compose up -d "$SERVICE_NAME"
    
    # Wait for container to be ready
    log "Waiting for container to be ready..."
    sleep 10
    
    # Check if container is running
    if docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
        success "Container started successfully"
    else
        error "Container failed to start"
        return 1
    fi
}

# Health check
health_check() {
    step "Performing health check..."
    
    local max_attempts=30
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        log "Health check attempt $attempt/$max_attempts..."
        
        # Check if container is running
        if ! docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
            error "Container is not running"
            return 1
        fi
        
        # Check if API is responding
        if docker exec "$CONTAINER_NAME" wget --quiet --tries=1 --spider http://localhost:3000/app/devices 2>/dev/null; then
            success "Health check passed - API is responding"
            return 0
        fi
        
        log "API not ready yet, waiting..."
        sleep 10
        ((attempt++))
    done
    
    error "Health check failed after $max_attempts attempts"
    return 1
}

# Test endpoints
test_endpoints() {
    step "Testing WhatsApp API endpoints..."
    
    # Test device endpoint
    log "Testing /app/devices endpoint..."
    if docker exec "$CONTAINER_NAME" wget --quiet --tries=1 --spider http://localhost:3000/app/devices 2>/dev/null; then
        success "Device endpoint is working"
    else
        warning "Device endpoint test failed"
    fi
    
    # Test fresh login endpoint
    log "Testing /app/login-fresh endpoint..."
    if docker exec "$CONTAINER_NAME" wget --quiet --tries=1 --spider http://localhost:3000/app/login-fresh 2>/dev/null; then
        success "Fresh login endpoint is working"
    else
        warning "Fresh login endpoint test failed"
    fi
    
    success "Endpoint testing completed"
}

# Show logs
show_logs() {
    step "Showing recent logs..."
    
    echo -e "${CYAN}=== Recent Container Logs ===${NC}"
    docker logs "$CONTAINER_NAME" --tail 20
    
    echo -e "${CYAN}=== Container Status ===${NC}"
    docker ps --filter name="$CONTAINER_NAME" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
}

# Main deployment function
main() {
    echo -e "${GREEN}"
    echo "============================================================================="
    echo "🚗 HARTONO MOTOR - WhatsApp API VPS Deployment"
    echo "============================================================================="
    echo -e "${NC}"
    
    log "Starting WhatsApp API deployment..."
    log "Log file: $LOG_FILE"
    
    # Run all steps
    check_permissions
    check_prerequisites
    create_backup
    stop_existing
    cleanup_docker
    build_image
    start_container
    health_check
    test_endpoints
    show_logs
    
    echo -e "${GREEN}"
    echo "============================================================================="
    echo "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!"
    echo "============================================================================="
    echo -e "${NC}"
    
    success "WhatsApp API has been deployed successfully"
    info "Container name: $CONTAINER_NAME"
    info "Backup location: $BACKUP_DIR"
    info "Log file: $LOG_FILE"
    
    echo -e "${YELLOW}"
    echo "Next steps:"
    echo "1. Test QR code generation in Filament admin"
    echo "2. Use 'QR Code Baru' button for fresh QR codes"
    echo "3. Monitor logs: docker logs $CONTAINER_NAME -f"
    echo -e "${NC}"
}

# Error handling
trap 'error "Deployment failed at line $LINENO. Check $LOG_FILE for details."' ERR

# Run main function
main "$@"
