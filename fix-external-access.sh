#!/bin/bash

# =============================================================================
# 🔧 Fix External Access to WhatsApp API Port 3000
# =============================================================================
# Mengatasi masalah port 3000 tidak bisa diakses dari luar VPS
# =============================================================================

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

echo -e "${PURPLE}"
echo "============================================================================="
echo "🔧 FIX EXTERNAL ACCESS TO WHATSAPP API PORT 3000"
echo "============================================================================="
echo "Mengatasi masalah port 3000 tidak bisa diakses dari internet"
echo "============================================================================="
echo -e "${NC}"

# Fungsi helper
show_step() {
    echo -e "${YELLOW}📋 STEP $1: $2${NC}"
}

show_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

show_error() {
    echo -e "${RED}❌ $1${NC}"
}

show_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

show_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# STEP 1: Diagnose current situation
show_step "1" "Diagnosing current network situation..."

echo "=== Container Status ==="
docker ps | grep whatsapp || echo "No WhatsApp container found"
echo ""

echo "=== Port Binding ==="
docker port whatsapp-api-vps 2>/dev/null || echo "Container not found"
echo ""

echo "=== Network Listening ==="
sudo netstat -tlnp | grep :3000 || echo "Port 3000 not listening"
echo ""

echo "=== UFW Firewall Status ==="
sudo ufw status verbose
echo ""

echo "=== IPTables Rules ==="
sudo iptables -L INPUT -n | grep -E "(3000|ACCEPT|DROP)" || echo "No specific rules for port 3000"
echo ""

# STEP 2: Test internal connectivity
show_step "2" "Testing internal connectivity..."

if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
    show_success "Internal access (localhost:3000) working"
    INTERNAL_OK=true
else
    show_error "Internal access failed"
    INTERNAL_OK=false
fi

# STEP 3: Fix UFW firewall
show_step "3" "Fixing UFW firewall rules..."

# Check if UFW is active
if sudo ufw status | grep -q "Status: active"; then
    show_info "UFW is active, adding rules..."
    
    # Add rule for port 3000
    sudo ufw allow 3000/tcp
    show_success "UFW rule added for port 3000"
    
    # Reload UFW
    sudo ufw reload
    show_success "UFW reloaded"
    
    # Show updated status
    echo "Updated UFW status:"
    sudo ufw status | grep -E "(3000|Status)"
    
else
    show_warning "UFW is not active"
    
    # Ask if user wants to enable UFW
    read -p "Enable UFW firewall? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo ufw --force enable
        sudo ufw allow ssh
        sudo ufw allow 80/tcp
        sudo ufw allow 443/tcp
        sudo ufw allow 3000/tcp
        show_success "UFW enabled with necessary rules"
    fi
fi

echo ""

# STEP 4: Check iptables
show_step "4" "Checking iptables rules..."

# Check if there are blocking rules
BLOCKING_RULES=$(sudo iptables -L INPUT -n | grep -E "DROP.*3000|REJECT.*3000" | wc -l)

if [[ $BLOCKING_RULES -gt 0 ]]; then
    show_warning "Found blocking iptables rules for port 3000"
    echo "Blocking rules:"
    sudo iptables -L INPUT -n | grep -E "DROP.*3000|REJECT.*3000"
    
    # Ask if user wants to add allow rule
    read -p "Add iptables rule to allow port 3000? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo iptables -I INPUT -p tcp --dport 3000 -j ACCEPT
        show_success "iptables rule added"
        
        # Make persistent
        if command -v iptables-persistent &> /dev/null; then
            sudo iptables-save > /etc/iptables/rules.v4
            show_success "iptables rules saved"
        else
            show_warning "iptables-persistent not installed, rules may not persist after reboot"
        fi
    fi
else
    show_success "No blocking iptables rules found"
fi

echo ""

# STEP 5: Check Docker configuration
show_step "5" "Checking Docker configuration..."

# Check if container is running
if docker ps | grep -q whatsapp-api-vps; then
    show_success "WhatsApp container is running"
    
    # Check port binding
    PORT_BINDING=$(docker port whatsapp-api-vps 3000 2>/dev/null)
    if [[ "$PORT_BINDING" == "0.0.0.0:3000" ]]; then
        show_success "Port binding correct: $PORT_BINDING"
    else
        show_warning "Port binding issue: $PORT_BINDING"
        
        # Restart container with correct binding
        show_info "Restarting container with correct port binding..."
        cd /opt/whatsapp-docker
        docker-compose down
        docker-compose up -d
        sleep 10
        
        # Check again
        NEW_BINDING=$(docker port whatsapp-api-vps 3000 2>/dev/null)
        if [[ "$NEW_BINDING" == "0.0.0.0:3000" ]]; then
            show_success "Port binding fixed: $NEW_BINDING"
        else
            show_error "Port binding still incorrect: $NEW_BINDING"
        fi
    fi
else
    show_error "WhatsApp container not running"
    
    # Try to start it
    show_info "Attempting to start container..."
    cd /opt/whatsapp-docker
    docker-compose up -d
    sleep 10
    
    if docker ps | grep -q whatsapp-api-vps; then
        show_success "Container started"
    else
        show_error "Failed to start container"
        echo "Container logs:"
        docker-compose logs --tail=10
    fi
fi

echo ""

# STEP 6: Check cloud provider firewall
show_step "6" "Checking cloud provider settings..."

show_info "Cloud Provider Checklist:"
echo "1. ✓ Check Vultr firewall settings in dashboard"
echo "2. ✓ Ensure port 3000 is allowed in Vultr security groups"
echo "3. ✓ Check if there's a separate cloud firewall blocking the port"
echo "4. ✓ Verify VPS network configuration"
echo ""

show_warning "If external access still fails after this script:"
echo "1. Login to Vultr dashboard"
echo "2. Go to your VPS settings"
echo "3. Check 'Firewall' or 'Security Groups' section"
echo "4. Add rule: Allow TCP port 3000 from 0.0.0.0/0"
echo ""

# STEP 7: Test external connectivity
show_step "7" "Testing external connectivity..."

# Test from VPS itself (simulating external)
show_info "Testing external IP access from VPS..."

if curl -s -f http://45.32.116.20:3000/app/devices > /dev/null 2>&1; then
    show_success "External IP access working from VPS"
    EXTERNAL_VPS_OK=true
else
    show_warning "External IP access failed from VPS"
    EXTERNAL_VPS_OK=false
fi

# Test with authentication
if curl -s -f -u admin:hartonomotor123 http://45.32.116.20:3000/app/devices > /dev/null 2>&1; then
    show_success "External IP access with auth working from VPS"
    EXTERNAL_AUTH_OK=true
else
    show_warning "External IP access with auth failed from VPS"
    EXTERNAL_AUTH_OK=false
fi

echo ""

# STEP 8: Alternative solutions
show_step "8" "Alternative solutions if external access still fails..."

show_info "Option 1: Use Nginx reverse proxy (recommended)"
cat > /tmp/nginx-whatsapp.conf <<'EOF'
server {
    listen 80;
    server_name whatsapp.hartonomotor.xyz;
    
    location / {
        proxy_pass http://localhost:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

echo "Nginx config created at /tmp/nginx-whatsapp.conf"
echo "To use: sudo cp /tmp/nginx-whatsapp.conf /etc/nginx/sites-available/"
echo "        sudo ln -s /etc/nginx/sites-available/nginx-whatsapp.conf /etc/nginx/sites-enabled/"
echo "        sudo nginx -t && sudo systemctl reload nginx"
echo ""

show_info "Option 2: Change to different port (8080)"
echo "Edit /opt/whatsapp-docker/docker-compose.yml"
echo "Change ports from '3000:3000' to '8080:3000'"
echo "Then: docker-compose down && docker-compose up -d"
echo ""

show_info "Option 3: Use SSH tunnel for testing"
echo "From your local machine:"
echo "ssh -L 3000:localhost:3000 root@45.32.116.20"
echo "Then access: http://localhost:3000"
echo ""

# FINAL RESULTS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🎉 EXTERNAL ACCESS FIX COMPLETED"
echo "============================================================================="
echo -e "${NC}"

echo "Status Summary:"
echo "  • Internal Access: $([[ "$INTERNAL_OK" == true ]] && echo "✅ Working" || echo "❌ Failed")"
echo "  • External VPS Access: $([[ "$EXTERNAL_VPS_OK" == true ]] && echo "✅ Working" || echo "❌ Failed")"
echo "  • External Auth Access: $([[ "$EXTERNAL_AUTH_OK" == true ]] && echo "✅ Working" || echo "❌ Failed")"
echo ""

if [[ "$EXTERNAL_AUTH_OK" == true ]]; then
    echo -e "${GREEN}🎉 SUCCESS! External access should now work${NC}"
    echo ""
    echo "Test from your browser:"
    echo "  • URL: http://45.32.116.20:3000"
    echo "  • Username: admin"
    echo "  • Password: hartonomotor123"
else
    echo -e "${YELLOW}⚠️  External access may still be blocked${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Check Vultr dashboard firewall settings"
    echo "2. Try alternative solutions above"
    echo "3. Contact Vultr support if needed"
fi

echo ""
echo "Current container status:"
docker ps | grep whatsapp || echo "No WhatsApp container running"
