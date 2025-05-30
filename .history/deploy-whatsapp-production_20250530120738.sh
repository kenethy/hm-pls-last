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
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-7}"
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
    
    # Check available disk space (minimum 10GB for production)
    AVAILABLE_SPACE=$(df / | awk 'NR==2 {print $4}')
    REQUIRED_SPACE=10485760  # 10GB in KB
    
    if [[ $AVAILABLE_SPACE -lt $REQUIRED_SPACE ]]; then
        error_exit "Insufficient disk space. Required: 10GB, Available: $(($AVAILABLE_SPACE/1024/1024))GB"
    fi
    
    # Check memory (minimum 2GB for production)
    TOTAL_MEM=$(free -m | awk 'NR==2{print $2}')
    if [[ $TOTAL_MEM -lt 2048 ]]; then
        log_warning "Low memory detected: ${TOTAL_MEM}MB. Recommended: 4GB+ for production"
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
