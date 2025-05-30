#!/bin/bash

# =============================================================================
# 🚀 VPS Memory Optimization for WhatsApp API
# =============================================================================
# Optimize VPS memory usage untuk WhatsApp API deployment
# =============================================================================

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================================================="
echo "🚀 VPS MEMORY OPTIMIZATION"
echo "============================================================================="
echo -e "${NC}"

# Fungsi helper
show_step() {
    echo -e "${YELLOW}📋 $1${NC}"
}

show_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

show_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# STEP 1: Check current memory usage
show_step "Checking current memory usage..."

echo "Current memory status:"
free -h
echo ""

echo "Memory details:"
cat /proc/meminfo | grep -E "(MemTotal|MemFree|MemAvailable|Buffers|Cached)"
echo ""

echo "Top memory consuming processes:"
ps aux --sort=-%mem | head -10
echo ""

# STEP 2: Check running services
show_step "Checking running services..."

echo "Active services:"
systemctl list-units --type=service --state=running | grep -E "(apache|nginx|mysql|postgresql|redis|memcached|snapd)"
echo ""

# STEP 3: Stop unnecessary services
show_step "Stopping unnecessary services..."

# Services that can be safely stopped
SERVICES_TO_STOP=(
    "snapd"
    "snapd.socket" 
    "apache2"
    "mysql"
    "postgresql"
    "redis-server"
    "memcached"
)

for service in "${SERVICES_TO_STOP[@]}"; do
    if systemctl is-active --quiet "$service"; then
        echo "Stopping $service..."
        sudo systemctl stop "$service"
        sudo systemctl disable "$service"
        show_success "$service stopped and disabled"
    else
        show_info "$service not running"
    fi
done

echo ""

# STEP 4: Clear caches
show_step "Clearing system caches..."

# Clear page cache, dentries and inodes
sync
echo 3 > /proc/sys/vm/drop_caches

show_success "System caches cleared"
echo ""

# STEP 5: Optimize swap
show_step "Optimizing swap usage..."

# Check if swap exists
if swapon --show | grep -q "/"; then
    show_info "Swap is enabled"
    swapon --show
else
    show_info "No swap detected, creating swap file..."
    
    # Create 1GB swap file
    fallocate -l 1G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    
    # Make permanent
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    
    show_success "1GB swap file created"
fi

# Optimize swappiness (how aggressively to use swap)
echo 'vm.swappiness=10' >> /etc/sysctl.conf
sysctl vm.swappiness=10

show_success "Swap optimized"
echo ""

# STEP 6: Check memory after optimization
show_step "Memory status after optimization..."

echo "Memory after optimization:"
free -h
echo ""

# Calculate available memory
MEMORY_KB=$(grep MemAvailable /proc/meminfo | awk '{print $2}')
MEMORY_MB=$((MEMORY_KB / 1024))

echo "Available memory: ${MEMORY_MB}MB"

if [[ $MEMORY_MB -gt 512 ]]; then
    show_success "Memory optimization successful! ${MEMORY_MB}MB available"
    echo ""
    echo "You can now proceed with WhatsApp API deployment:"
    echo "bash deploy-docker-vps-exact-local.sh"
elif [[ $MEMORY_MB -gt 256 ]]; then
    echo -e "${YELLOW}⚠️  Limited memory: ${MEMORY_MB}MB available${NC}"
    echo ""
    echo "Recommendations:"
    echo "1. Use binary deployment instead of Docker (lighter)"
    echo "2. Consider upgrading VPS plan"
    echo "3. Monitor memory usage closely"
else
    echo -e "${RED}❌ Still insufficient memory: ${MEMORY_MB}MB${NC}"
    echo ""
    echo "Options:"
    echo "1. Upgrade VPS plan (recommended)"
    echo "2. Use external WhatsApp service (Easy Panel)"
    echo "3. Deploy on separate VPS"
fi

echo ""

# STEP 7: Create memory monitoring script
show_step "Creating memory monitoring script..."

cat > /opt/monitor-memory.sh <<'EOF'
#!/bin/bash
echo "=== Memory Usage Monitor ==="
echo "Date: $(date)"
echo ""
echo "Memory:"
free -h
echo ""
echo "Top 5 memory consumers:"
ps aux --sort=-%mem | head -6
echo ""
echo "Disk usage:"
df -h /
echo ""
EOF

chmod +x /opt/monitor-memory.sh

show_success "Memory monitor created: /opt/monitor-memory.sh"
echo ""

echo -e "${BLUE}============================================================================="
echo "🎉 MEMORY OPTIMIZATION COMPLETED"
echo "============================================================================="
echo -e "${NC}"

echo "Final memory status:"
free -h
echo ""

echo "Next steps:"
echo "1. Monitor memory: /opt/monitor-memory.sh"
echo "2. If memory > 512MB: bash deploy-docker-vps-exact-local.sh"
echo "3. If memory < 512MB: Consider VPS upgrade or binary deployment"
