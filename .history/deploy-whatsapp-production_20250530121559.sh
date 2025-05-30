#!/bin/bash

# =============================================================================
# WhatsApp API Production VPS Deployment Script
# Comprehensive production-ready deployment with Smart Fresh QR implementation
# Security-hardened with proper configuration management
# =============================================================================

set -euo pipefail  # Exit on error, undefined vars, pipe failures

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="whatsapp-api-production"
DOCKER_COMPOSE_VERSION="2.24.0"
REQUIRED_DOCKER_VERSION="24.0.0"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Default configuration (can be overridden by environment variables)
WHATSAPP_PORT="${WHATSAPP_PORT:-3000}"
WHATSAPP_BASIC_AUTH_USERNAME="${WHATSAPP_BASIC_AUTH_USERNAME:-admin}"
WHATSAPP_BASIC_AUTH_PASSWORD="${WHATSAPP_BASIC_AUTH_PASSWORD:-}"
WEBHOOK_SECRET="${WEBHOOK_SECRET:-}"
SSL_ENABLED="${SSL_ENABLED:-false}"
DOMAIN_NAME="${DOMAIN_NAME:-localhost}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-3}"  # Reduced for tiny VPS
ENVIRONMENT="${ENVIRONMENT:-production}"

# Paths
PROJECT_ROOT="/opt/$PROJECT_NAME"
CONFIG_DIR="$PROJECT_ROOT/config"
DATA_DIR="$PROJECT_ROOT/data"
LOGS_DIR="$PROJECT_ROOT/logs"
BACKUPS_DIR="$PROJECT_ROOT/backups"
SSL_DIR="$PROJECT_ROOT/ssl"

# Logging functions
log_info() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')] [INFO]${NC} $1" | tee -a "$LOGS_DIR/deployment.log"
}

log_success() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] [SUCCESS]${NC} $1" | tee -a "$LOGS_DIR/deployment.log"
}

log_warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] [WARNING]${NC} $1" | tee -a "$LOGS_DIR/deployment.log"
}

log_error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] [ERROR]${NC} $1" | tee -a "$LOGS_DIR/deployment.log"
}

log_step() {
    echo -e "${PURPLE}[$(date '+%Y-%m-%d %H:%M:%S')] [STEP]${NC} $1" | tee -a "$LOGS_DIR/deployment.log"
}

# Error handling
error_exit() {
    log_error "$1"
    log_error "Deployment failed. Check logs at $LOGS_DIR/deployment.log"
    exit 1
}

# Trap errors
trap 'error_exit "Script failed at line $LINENO"' ERR

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_warning "Running as root. Consider using a non-root user with sudo privileges."
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# System requirements check
check_system_requirements() {
    log_step "Checking system requirements..."

    # Check OS
    if [[ ! -f /etc/os-release ]]; then
        error_exit "Cannot determine OS version"
    fi

    source /etc/os-release
    log_info "Detected OS: $PRETTY_NAME"

    # Check architecture
    ARCH=$(uname -m)
    if [[ "$ARCH" != "x86_64" && "$ARCH" != "aarch64" ]]; then
        error_exit "Unsupported architecture: $ARCH"
    fi

    # Check available disk space (minimum 1GB - perfect for tiny VPS!)
    AVAILABLE_SPACE=$(df / | awk 'NR==2 {print $4}')
    REQUIRED_SPACE=1048576   # 1GB in KB
    RECOMMENDED_SPACE=2097152 # 2GB in KB

    if [[ $AVAILABLE_SPACE -lt $REQUIRED_SPACE ]]; then
        error_exit "Insufficient disk space. Required: 1GB, Available: $(($AVAILABLE_SPACE/1024/1024))GB"
    elif [[ $AVAILABLE_SPACE -lt $RECOMMENDED_SPACE ]]; then
        log_warning "Tiny VPS detected: $(($AVAILABLE_SPACE/1024/1024))GB. Will work but consider 2GB+ for comfort"
    else
        log_info "Disk space: $(($AVAILABLE_SPACE/1024/1024))GB - Perfect for WhatsApp API!"
    fi

    # Check memory (minimum 256MB, recommended 512MB)
    TOTAL_MEM=$(free -m | awk 'NR==2{print $2}')
    if [[ $TOTAL_MEM -lt 256 ]]; then
        error_exit "Insufficient memory: ${TOTAL_MEM}MB. Minimum required: 256MB"
    elif [[ $TOTAL_MEM -lt 512 ]]; then
        log_warning "Tiny VPS memory: ${TOTAL_MEM}MB. Will work but may be slow during builds"
    else
        log_info "Memory: ${TOTAL_MEM}MB - Good for WhatsApp API!"
    fi

    log_success "System requirements check passed"
}

# Install system dependencies
install_dependencies() {
    log_step "Installing system dependencies..."

    # Update package list
    sudo apt-get update

    # Install essential packages
    sudo apt-get install -y \
        curl \
        wget \
        git \
        unzip \
        htop \
        ufw \
        fail2ban \
        logrotate \
        cron \
        openssl \
        ca-certificates \
        gnupg \
        lsb-release \
        jq \
        tree

    log_success "System dependencies installed"
}

# Install Docker and Docker Compose
install_docker() {
    log_step "Installing Docker and Docker Compose..."

    # Check if Docker is already installed
    if command -v docker &> /dev/null; then
        DOCKER_VERSION=$(docker --version | grep -oP '\d+\.\d+\.\d+' | head -1)
        log_info "Docker already installed: $DOCKER_VERSION"

        # Check if version is sufficient
        if ! printf '%s\n%s\n' "$REQUIRED_DOCKER_VERSION" "$DOCKER_VERSION" | sort -V -C; then
            log_warning "Docker version $DOCKER_VERSION is older than required $REQUIRED_DOCKER_VERSION"
            log_info "Updating Docker..."
        else
            log_success "Docker version is sufficient"
            return 0
        fi
    fi

    # Remove old Docker versions
    sudo apt-get remove -y docker docker-engine docker.io containerd runc || true

    # Add Docker's official GPG key
    sudo mkdir -p /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

    # Set up repository
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

    # Install Docker Engine
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

    # Add current user to docker group
    if ! groups $USER | grep -q docker; then
        sudo usermod -aG docker $USER
        log_warning "Added $USER to docker group. You may need to log out and back in."
    fi

    # Start and enable Docker
    sudo systemctl start docker
    sudo systemctl enable docker

    # Configure Docker daemon for production
    sudo tee /etc/docker/daemon.json > /dev/null <<EOF
{
    "log-driver": "json-file",
    "log-opts": {
        "max-size": "10m",
        "max-file": "3"
    },
    "storage-driver": "overlay2",
    "live-restore": true
}
EOF

    sudo systemctl restart docker

    log_success "Docker installation completed"
}

# Configure firewall
configure_firewall() {
    log_step "Configuring firewall..."

    # Install ufw if not present
    if ! command -v ufw &> /dev/null; then
        sudo apt-get install -y ufw
    fi

    # Reset firewall rules
    sudo ufw --force reset

    # Default policies
    sudo ufw default deny incoming
    sudo ufw default allow outgoing

    # Allow SSH (be careful not to lock yourself out)
    sudo ufw allow ssh
    sudo ufw allow 22/tcp

    # Allow WhatsApp API port
    sudo ufw allow $WHATSAPP_PORT/tcp

    # Allow HTTP/HTTPS if SSL is enabled
    if [[ "$SSL_ENABLED" == "true" ]]; then
        sudo ufw allow 80/tcp
        sudo ufw allow 443/tcp
    fi

    # Rate limiting for SSH
    sudo ufw limit ssh

    # Enable firewall
    sudo ufw --force enable

    log_success "Firewall configured"
}

# Configure fail2ban
configure_fail2ban() {
    log_step "Configuring fail2ban..."

    # Create jail.local configuration
    sudo tee /etc/fail2ban/jail.local > /dev/null <<EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 3600
EOF

    # Restart fail2ban
    sudo systemctl restart fail2ban
    sudo systemctl enable fail2ban

    log_success "Fail2ban configured"
}

# Create project structure
create_project_structure() {
    log_step "Creating project structure..."

    # Create main project directory
    sudo mkdir -p $PROJECT_ROOT
    sudo chown $USER:$USER $PROJECT_ROOT

    # Create subdirectories
    mkdir -p $CONFIG_DIR $DATA_DIR $LOGS_DIR $BACKUPS_DIR $SSL_DIR
    mkdir -p $DATA_DIR/{qrcode,sessions,uploads}
    mkdir -p $LOGS_DIR/{app,nginx,system}

    # Set proper permissions
    chmod 755 $PROJECT_ROOT
    chmod 750 $CONFIG_DIR $DATA_DIR $LOGS_DIR $BACKUPS_DIR
    chmod 755 $DATA_DIR/qrcode  # QR codes need to be web-accessible
    chmod 700 $SSL_DIR  # SSL certificates should be highly restricted

    log_success "Project structure created at $PROJECT_ROOT"
}

# Generate secure credentials
generate_credentials() {
    log_step "Generating secure credentials..."

    # Generate basic auth password if not provided
    if [[ -z "$WHATSAPP_BASIC_AUTH_PASSWORD" ]]; then
        WHATSAPP_BASIC_AUTH_PASSWORD=$(openssl rand -base64 32)
        log_info "Generated basic auth password"
    fi

    # Generate webhook secret if not provided
    if [[ -z "$WEBHOOK_SECRET" ]]; then
        WEBHOOK_SECRET=$(openssl rand -base64 32)
        log_info "Generated webhook secret"
    fi

    # Create environment file
    cat > $CONFIG_DIR/.env <<EOF
# WhatsApp API Configuration
WHATSAPP_PORT=$WHATSAPP_PORT
WHATSAPP_BASIC_AUTH_USERNAME=$WHATSAPP_BASIC_AUTH_USERNAME
WHATSAPP_BASIC_AUTH_PASSWORD=$WHATSAPP_BASIC_AUTH_PASSWORD
WEBHOOK_SECRET=$WEBHOOK_SECRET
ENVIRONMENT=$ENVIRONMENT

# SSL Configuration
SSL_ENABLED=$SSL_ENABLED
DOMAIN_NAME=$DOMAIN_NAME

# Backup Configuration
BACKUP_RETENTION_DAYS=$BACKUP_RETENTION_DAYS

# Generated at: $(date)
EOF

    # Secure the environment file
    chmod 600 $CONFIG_DIR/.env

    log_success "Credentials generated and stored securely"
}

# Create Docker Compose configuration
create_docker_compose() {
    log_step "Creating Docker Compose configuration..."

    cat > $PROJECT_ROOT/docker-compose.production.yml <<EOF
version: '3.8'

services:
  whatsapp-api:
    build:
      context: ./go-whatsapp-web-multidevice-main
      dockerfile: golang.Dockerfile
    container_name: whatsapp-api-production
    restart: unless-stopped
    ports:
      - "${WHATSAPP_PORT}:3000"
    environment:
      - WHATSAPP_BASIC_AUTH_USERNAME=${WHATSAPP_BASIC_AUTH_USERNAME}
      - WHATSAPP_BASIC_AUTH_PASSWORD=${WHATSAPP_BASIC_AUTH_PASSWORD}
      - WEBHOOK_SECRET=${WEBHOOK_SECRET}
      - ENVIRONMENT=${ENVIRONMENT}
    volumes:
      - whatsapp_data:/app/data
      - whatsapp_statics:/app/statics
      - ${LOGS_DIR}/app:/app/logs
    networks:
      - whatsapp_network
    healthcheck:
      test: ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost:3000/app/devices"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"

volumes:
  whatsapp_data:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: ${DATA_DIR}/sessions
  whatsapp_statics:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: ${DATA_DIR}/qrcode

networks:
  whatsapp_network:
    driver: bridge
EOF

    log_success "Docker Compose configuration created"
}

# Setup SSL certificates (if enabled)
setup_ssl() {
    if [[ "$SSL_ENABLED" != "true" ]]; then
        log_info "SSL disabled, skipping SSL setup"
        return 0
    fi

    log_step "Setting up SSL certificates..."

    # Install certbot
    sudo apt-get install -y certbot

    # Generate SSL certificate
    if [[ "$DOMAIN_NAME" != "localhost" ]]; then
        log_info "Generating SSL certificate for $DOMAIN_NAME"
        sudo certbot certonly --standalone -d $DOMAIN_NAME --non-interactive --agree-tos --email admin@$DOMAIN_NAME

        # Copy certificates to project directory
        sudo cp /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem $SSL_DIR/
        sudo cp /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem $SSL_DIR/
        sudo chown $USER:$USER $SSL_DIR/*.pem
        chmod 600 $SSL_DIR/*.pem
    else
        log_warning "Domain is localhost, generating self-signed certificate"
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout $SSL_DIR/privkey.pem \
            -out $SSL_DIR/fullchain.pem \
            -subj "/C=ID/ST=Jakarta/L=Jakarta/O=Hartono Motor/CN=localhost"
        chmod 600 $SSL_DIR/*.pem
    fi

    log_success "SSL certificates configured"
}

# Setup log rotation
setup_log_rotation() {
    log_step "Setting up log rotation..."

    sudo tee /etc/logrotate.d/whatsapp-api > /dev/null <<EOF
$LOGS_DIR/app/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 $USER $USER
    postrotate
        docker kill -s USR1 whatsapp-api-production 2>/dev/null || true
    endscript
}

$LOGS_DIR/deployment.log {
    weekly
    missingok
    rotate 4
    compress
    delaycompress
    notifempty
    create 644 $USER $USER
}
EOF

    log_success "Log rotation configured"
}

# Setup backup system
setup_backup_system() {
    log_step "Setting up backup system..."

    # Create backup script
    cat > $PROJECT_ROOT/backup.sh <<'EOF'
#!/bin/bash

BACKUP_DIR="/opt/whatsapp-api-production/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="whatsapp-backup-$TIMESTAMP"
RETENTION_DAYS=7

# Create backup directory
mkdir -p "$BACKUP_DIR/$BACKUP_NAME"

# Backup WhatsApp data
docker run --rm \
    -v whatsapp_data:/data \
    -v "$BACKUP_DIR/$BACKUP_NAME":/backup \
    alpine tar czf /backup/whatsapp_data.tar.gz -C /data .

# Backup QR codes
docker run --rm \
    -v whatsapp_statics:/data \
    -v "$BACKUP_DIR/$BACKUP_NAME":/backup \
    alpine tar czf /backup/whatsapp_statics.tar.gz -C /data .

# Backup configuration
cp -r /opt/whatsapp-api-production/config "$BACKUP_DIR/$BACKUP_NAME/"

# Remove old backups
find "$BACKUP_DIR" -type d -name "whatsapp-backup-*" -mtime +$RETENTION_DAYS -exec rm -rf {} \;

echo "Backup completed: $BACKUP_NAME"
EOF

    chmod +x $PROJECT_ROOT/backup.sh

    # Setup cron job for daily backups
    (crontab -l 2>/dev/null; echo "0 2 * * * $PROJECT_ROOT/backup.sh >> $LOGS_DIR/backup.log 2>&1") | crontab -

    log_success "Backup system configured"
}

# Copy source code
copy_source_code() {
    log_step "Copying source code..."

    if [[ ! -d "go-whatsapp-web-multidevice-main" ]]; then
        error_exit "go-whatsapp-web-multidevice-main directory not found in current directory"
    fi

    # Copy source code to project directory
    cp -r go-whatsapp-web-multidevice-main $PROJECT_ROOT/

    # Set proper permissions
    chown -R $USER:$USER $PROJECT_ROOT/go-whatsapp-web-multidevice-main

    log_success "Source code copied"
}

# Build and deploy
build_and_deploy() {
    log_step "Building and deploying WhatsApp API..."

    cd $PROJECT_ROOT

    # Load environment variables
    source $CONFIG_DIR/.env

    # Stop existing containers
    docker-compose -f docker-compose.production.yml down || true

    # Build new image
    log_info "Building Docker image..."
    docker-compose -f docker-compose.production.yml build --no-cache

    # Start services
    log_info "Starting services..."
    docker-compose -f docker-compose.production.yml up -d

    log_success "WhatsApp API deployed"
}

# Health check
perform_health_check() {
    log_step "Performing health check..."

    local max_attempts=30
    local attempt=1

    while [[ $attempt -le $max_attempts ]]; do
        log_info "Health check attempt $attempt/$max_attempts..."

        # Check if container is running
        if ! docker ps --format 'table {{.Names}}' | grep -q "whatsapp-api-production"; then
            log_error "Container is not running"
            docker logs whatsapp-api-production --tail 20
            return 1
        fi

        # Check if API is responding
        if curl -s -f http://localhost:$WHATSAPP_PORT/app/devices > /dev/null 2>&1; then
            log_success "Health check passed - API is responding"
            return 0
        fi

        log_info "API not ready yet, waiting..."
        sleep 10
        ((attempt++))
    done

    log_error "Health check failed after $max_attempts attempts"
    docker logs whatsapp-api-production --tail 50
    return 1
}

# Test endpoints
test_endpoints() {
    log_step "Testing WhatsApp API endpoints..."

    local base_url="http://localhost:$WHATSAPP_PORT"

    # Test device endpoint
    log_info "Testing /app/devices endpoint..."
    if curl -s -f "$base_url/app/devices" > /dev/null; then
        log_success "Device endpoint is working"
    else
        log_warning "Device endpoint test failed"
    fi

    # Test fresh login endpoint
    log_info "Testing /app/login-fresh endpoint..."
    if curl -s -f "$base_url/app/login-fresh" > /dev/null; then
        log_success "Fresh login endpoint is working"
    else
        log_warning "Fresh login endpoint test failed"
    fi

    log_success "Endpoint testing completed"
}

# Setup monitoring
setup_monitoring() {
    log_step "Setting up monitoring..."

    # Create monitoring script
    cat > $PROJECT_ROOT/monitor.sh <<'EOF'
#!/bin/bash

CONTAINER_NAME="whatsapp-api-production"
LOG_FILE="/opt/whatsapp-api-production/logs/monitor.log"
ALERT_EMAIL="${ALERT_EMAIL:-admin@localhost}"

# Function to send alert
send_alert() {
    local message="$1"
    echo "[$(date)] ALERT: $message" >> "$LOG_FILE"
    # Uncomment to enable email alerts
    # echo "$message" | mail -s "WhatsApp API Alert" "$ALERT_EMAIL"
}

# Check if container is running
if ! docker ps --format 'table {{.Names}}' | grep -q "$CONTAINER_NAME"; then
    send_alert "Container $CONTAINER_NAME is not running"
    exit 1
fi

# Check if API is responding
if ! curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
    send_alert "WhatsApp API is not responding"
    exit 1
fi

# Check disk space
DISK_USAGE=$(df /opt | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    send_alert "Disk usage is high: ${DISK_USAGE}%"
fi

# Check memory usage
MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ "$MEMORY_USAGE" -gt 90 ]; then
    send_alert "Memory usage is high: ${MEMORY_USAGE}%"
fi

echo "[$(date)] Monitoring check passed" >> "$LOG_FILE"
EOF

    chmod +x $PROJECT_ROOT/monitor.sh

    # Setup cron job for monitoring (every 5 minutes)
    (crontab -l 2>/dev/null; echo "*/5 * * * * $PROJECT_ROOT/monitor.sh") | crontab -

    log_success "Monitoring configured"
}

# Create management scripts
create_management_scripts() {
    log_step "Creating management scripts..."

    # Create start script
    cat > $PROJECT_ROOT/start.sh <<EOF
#!/bin/bash
cd $PROJECT_ROOT
source $CONFIG_DIR/.env
docker-compose -f docker-compose.production.yml up -d
echo "WhatsApp API started"
EOF

    # Create stop script
    cat > $PROJECT_ROOT/stop.sh <<EOF
#!/bin/bash
cd $PROJECT_ROOT
docker-compose -f docker-compose.production.yml down
echo "WhatsApp API stopped"
EOF

    # Create restart script
    cat > $PROJECT_ROOT/restart.sh <<EOF
#!/bin/bash
cd $PROJECT_ROOT
source $CONFIG_DIR/.env
docker-compose -f docker-compose.production.yml restart
echo "WhatsApp API restarted"
EOF

    # Create logs script
    cat > $PROJECT_ROOT/logs.sh <<EOF
#!/bin/bash
docker logs whatsapp-api-production -f
EOF

    # Create status script
    cat > $PROJECT_ROOT/status.sh <<EOF
#!/bin/bash
echo "=== Container Status ==="
docker ps --filter name=whatsapp-api-production --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
echo "=== Health Check ==="
curl -s http://localhost:$WHATSAPP_PORT/app/devices | jq . || echo "API not responding"
echo ""
echo "=== Resource Usage ==="
docker stats whatsapp-api-production --no-stream
EOF

    # Make scripts executable
    chmod +x $PROJECT_ROOT/{start,stop,restart,logs,status}.sh

    log_success "Management scripts created"
}

# Display deployment summary
display_summary() {
    log_step "Deployment Summary"

    echo -e "${GREEN}"
    echo "============================================================================="
    echo "🎉 WhatsApp API Production Deployment Completed Successfully!"
    echo "============================================================================="
    echo -e "${NC}"

    echo -e "${CYAN}📋 Deployment Information:${NC}"
    echo "  • Project Directory: $PROJECT_ROOT"
    echo "  • Container Name: whatsapp-api-production"
    echo "  • API Port: $WHATSAPP_PORT"
    echo "  • Environment: $ENVIRONMENT"
    echo "  • SSL Enabled: $SSL_ENABLED"
    echo "  • Domain: $DOMAIN_NAME"
    echo ""

    echo -e "${CYAN}🔐 Security Information:${NC}"
    echo "  • Basic Auth Username: $WHATSAPP_BASIC_AUTH_USERNAME"
    echo "  • Basic Auth Password: [STORED IN $CONFIG_DIR/.env]"
    echo "  • Webhook Secret: [STORED IN $CONFIG_DIR/.env]"
    echo "  • Firewall: Configured with UFW"
    echo "  • Fail2ban: Enabled"
    echo ""

    echo -e "${CYAN}📁 Important Paths:${NC}"
    echo "  • Configuration: $CONFIG_DIR"
    echo "  • Data Directory: $DATA_DIR"
    echo "  • Logs Directory: $LOGS_DIR"
    echo "  • Backups Directory: $BACKUPS_DIR"
    echo "  • SSL Certificates: $SSL_DIR"
    echo ""

    echo -e "${CYAN}🛠️ Management Commands:${NC}"
    echo "  • Start API: $PROJECT_ROOT/start.sh"
    echo "  • Stop API: $PROJECT_ROOT/stop.sh"
    echo "  • Restart API: $PROJECT_ROOT/restart.sh"
    echo "  • View Logs: $PROJECT_ROOT/logs.sh"
    echo "  • Check Status: $PROJECT_ROOT/status.sh"
    echo "  • Manual Backup: $PROJECT_ROOT/backup.sh"
    echo ""

    echo -e "${CYAN}🔗 API Endpoints:${NC}"
    echo "  • Device Status: http://localhost:$WHATSAPP_PORT/app/devices"
    echo "  • Fresh QR Login: http://localhost:$WHATSAPP_PORT/app/login-fresh"
    echo "  • Regular QR Login: http://localhost:$WHATSAPP_PORT/app/login"
    echo ""

    echo -e "${CYAN}📊 Monitoring:${NC}"
    echo "  • Automated monitoring every 5 minutes"
    echo "  • Daily backups at 2:00 AM"
    echo "  • Log rotation configured"
    echo "  • Health checks enabled"
    echo ""

    echo -e "${YELLOW}⚠️ Important Notes:${NC}"
    echo "  • Store the credentials from $CONFIG_DIR/.env securely"
    echo "  • Monitor logs regularly: $LOGS_DIR/"
    echo "  • Backups are stored in: $BACKUPS_DIR/"
    echo "  • SSL certificates (if enabled) are in: $SSL_DIR/"
    echo ""

    echo -e "${GREEN}✅ Next Steps:${NC}"
    echo "  1. Test QR code generation: curl http://localhost:$WHATSAPP_PORT/app/login-fresh"
    echo "  2. Scan QR code with WhatsApp mobile app"
    echo "  3. Verify device linking works properly"
    echo "  4. Set up external monitoring if needed"
    echo "  5. Configure your application to use this API"
    echo ""
}

# Main deployment function
main() {
    echo -e "${GREEN}"
    echo "============================================================================="
    echo "🚗 WhatsApp API Production VPS Deployment"
    echo "============================================================================="
    echo -e "${NC}"

    # Create logs directory early
    mkdir -p $LOGS_DIR

    log_info "Starting WhatsApp API production deployment..."
    log_info "Timestamp: $TIMESTAMP"

    # Run all deployment steps
    check_root
    check_system_requirements
    install_dependencies
    install_docker
    configure_firewall
    configure_fail2ban
    create_project_structure
    generate_credentials
    create_docker_compose
    setup_ssl
    setup_log_rotation
    setup_backup_system
    copy_source_code
    build_and_deploy
    perform_health_check
    test_endpoints
    setup_monitoring
    create_management_scripts
    display_summary

    log_success "WhatsApp API production deployment completed successfully!"
}

# Run main function
main "$@"
