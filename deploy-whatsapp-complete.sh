#!/bin/bash

# Complete WhatsApp Integration Deployment Script for hartonomotor.xyz VPS
# This script runs all deployment steps in sequence
# Run this script on your VPS as root or with sudo privileges

set -e  # Exit on any error

echo "🚀 Complete WhatsApp Integration Deployment for hartonomotor.xyz"
echo "=================================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LARAVEL_DIR="/var/www/hartonomotor.xyz"
DOMAIN="hartonomotor.xyz"

echo -e "${BLUE}📋 Deployment Configuration:${NC}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo -e "  Script Directory: ${SCRIPT_DIR}"
echo ""

# Function to run script with error handling
run_script() {
    local script_name=$1
    local description=$2
    
    echo -e "${PURPLE}🔄 Running: ${description}${NC}"
    echo -e "${YELLOW}Script: ${script_name}${NC}"
    echo ""
    
    if [ -f "${SCRIPT_DIR}/${script_name}" ]; then
        chmod +x "${SCRIPT_DIR}/${script_name}"
        if bash "${SCRIPT_DIR}/${script_name}"; then
            echo -e "${GREEN}✅ ${description} completed successfully${NC}"
        else
            echo -e "${RED}❌ ${description} failed${NC}"
            exit 1
        fi
    else
        echo -e "${RED}❌ Script ${script_name} not found in ${SCRIPT_DIR}${NC}"
        exit 1
    fi
    
    echo ""
    echo -e "${BLUE}Press Enter to continue to next step...${NC}"
    read -r
}

# Welcome message
echo -e "${YELLOW}⚠️  IMPORTANT NOTES:${NC}"
echo -e "1. Make sure you have sudo privileges"
echo -e "2. Ensure all script files are in the same directory"
echo -e "3. This will modify your server configuration"
echo -e "4. Have your WhatsApp phone ready for QR code scanning"
echo ""
echo -e "${BLUE}Press Enter to start deployment...${NC}"
read -r

# Step 1: Deploy WhatsApp API Server
run_script "deploy-whatsapp-api.sh" "WhatsApp API Server Deployment"

# Step 2: Configure Nginx Reverse Proxy
run_script "configure-nginx.sh" "Nginx Reverse Proxy Configuration"

# Step 3: Update Laravel Configuration
run_script "update-laravel-config.sh" "Laravel Configuration Update"

# Step 4: Final verification and instructions
echo -e "${GREEN}🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
echo "=================================================================="
echo ""
echo -e "${BLUE}📋 FINAL STEPS - MANUAL ACTIONS REQUIRED:${NC}"
echo ""
echo -e "${YELLOW}1. Test WhatsApp API Connection:${NC}"
echo -e "   • Login to: ${BLUE}https://${DOMAIN}/admin${NC}"
echo -e "   • Go to: WhatsApp Integration → Konfigurasi WhatsApp"
echo -e "   • Click 'Test Koneksi' button"
echo -e "   • Should show: ${GREEN}'Koneksi Berhasil'${NC}"
echo ""
echo -e "${YELLOW}2. Authenticate WhatsApp:${NC}"
echo -e "   • Open: ${BLUE}https://${DOMAIN}/whatsapp-api/app/login${NC}"
echo -e "   • Scan QR code with your WhatsApp mobile app"
echo -e "   • Wait for 'Connected' status"
echo ""
echo -e "${YELLOW}3. Test Message Sending:${NC}"
echo -e "   • Go to: WhatsApp Integration → Pesan WhatsApp"
echo -e "   • Click 'Kirim Pesan Manual'"
echo -e "   • Send a test message to your phone"
echo ""
echo -e "${YELLOW}4. Test Auto Follow-up:${NC}"
echo -e "   • Create a test service in admin panel"
echo -e "   • Change status to 'completed'"
echo -e "   • Check if WhatsApp message is sent automatically"
echo ""
echo -e "${BLUE}🔧 TROUBLESHOOTING COMMANDS:${NC}"
echo -e "  Check WhatsApp API logs: ${YELLOW}cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose logs -f${NC}"
echo -e "  Check Laravel logs: ${YELLOW}tail -f ${LARAVEL_DIR}/storage/logs/laravel.log${NC}"
echo -e "  Check Nginx logs: ${YELLOW}sudo tail -f /var/log/nginx/hartonomotor.xyz.error.log${NC}"
echo -e "  Restart WhatsApp API: ${YELLOW}cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose restart${NC}"
echo ""
echo -e "${BLUE}📱 WHATSAPP CREDENTIALS:${NC}"
echo -e "  API URL: ${YELLOW}https://${DOMAIN}/whatsapp-api${NC}"
echo -e "  Username: ${YELLOW}admin${NC}"
echo -e "  Password: ${YELLOW}HartonoMotor2025!${NC}"
echo -e "  Webhook URL: ${YELLOW}https://${DOMAIN}/api/whatsapp/webhook${NC}"
echo ""
echo -e "${GREEN}✅ All deployment scripts completed successfully!${NC}"
echo -e "${BLUE}Your WhatsApp integration is now ready for testing.${NC}"
echo ""
echo -e "${PURPLE}🎯 Next: Please complete the manual steps above to fully activate WhatsApp integration.${NC}"
