#!/bin/bash

# VPS Structure Diagnostic Script
# This script will help you find where your Laravel project and files are located

echo "🔍 VPS Structure Diagnostic for hartonomotor.xyz"
echo "================================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${BLUE}📋 Starting VPS structure analysis...${NC}"
echo ""

# Function to check if directory exists and show contents
check_directory() {
    local dir=$1
    local description=$2
    
    echo -e "${YELLOW}🔍 Checking: ${description}${NC}"
    echo -e "   Path: ${dir}"
    
    if [ -d "$dir" ]; then
        echo -e "   ${GREEN}✅ Directory exists${NC}"
        echo -e "   Contents:"
        ls -la "$dir" 2>/dev/null | head -10 | while read line; do
            echo -e "     $line"
        done
        
        # Check if it's a Laravel project
        if [ -f "$dir/artisan" ]; then
            echo -e "   ${GREEN}🎯 This is a Laravel project!${NC}"
        fi
        
        # Check for go-whatsapp folder
        if [ -d "$dir/go-whatsapp-web-multidevice-main" ]; then
            echo -e "   ${GREEN}📱 WhatsApp API folder found here!${NC}"
        fi
    else
        echo -e "   ${RED}❌ Directory does not exist${NC}"
    fi
    echo ""
}

# 1. Check common web directories
echo -e "${PURPLE}1. CHECKING COMMON WEB DIRECTORIES${NC}"
echo "=================================="

check_directory "/var/www" "Main web directory"
check_directory "/var/www/html" "Default Apache/Nginx web root"
check_directory "/var/www/hartonomotor.xyz" "Expected Laravel location"
check_directory "/home/www" "Alternative web directory"
check_directory "/opt/www" "Optional web directory"

# 2. Search for Laravel projects
echo -e "${PURPLE}2. SEARCHING FOR LARAVEL PROJECTS${NC}"
echo "================================="

echo -e "${YELLOW}🔍 Searching for 'artisan' files (Laravel indicator)...${NC}"
find /var/www /home /opt -name "artisan" -type f 2>/dev/null | while read artisan_path; do
    laravel_dir=$(dirname "$artisan_path")
    echo -e "${GREEN}📍 Laravel project found: ${laravel_dir}${NC}"
    
    # Check Laravel version
    if [ -f "$laravel_dir/composer.json" ]; then
        version=$(grep -o '"laravel/framework": "[^"]*"' "$laravel_dir/composer.json" 2>/dev/null || echo "Version not found")
        echo -e "   Laravel version: $version"
    fi
    
    # Check if it's hartonomotor project
    if grep -q "hartonomotor\|Hartono" "$laravel_dir/composer.json" 2>/dev/null; then
        echo -e "   ${GREEN}🎯 This looks like the Hartono Motor project!${NC}"
    fi
    echo ""
done

# 3. Search for WhatsApp API files
echo -e "${PURPLE}3. SEARCHING FOR WHATSAPP API FILES${NC}"
echo "=================================="

echo -e "${YELLOW}🔍 Searching for 'go-whatsapp-web-multidevice-main' folder...${NC}"
find /var/www /home /opt -name "go-whatsapp-web-multidevice-main" -type d 2>/dev/null | while read whatsapp_path; do
    echo -e "${GREEN}📱 WhatsApp API found: ${whatsapp_path}${NC}"
    
    # Check contents
    if [ -f "$whatsapp_path/docker-compose.yml" ]; then
        echo -e "   ${GREEN}✅ Docker Compose file found${NC}"
    fi
    if [ -f "$whatsapp_path/src/main.go" ]; then
        echo -e "   ${GREEN}✅ Go source files found${NC}"
    fi
    echo ""
done

# 4. Check current user and permissions
echo -e "${PURPLE}4. USER AND PERMISSIONS INFO${NC}"
echo "============================="

echo -e "${YELLOW}Current user:${NC} $(whoami)"
echo -e "${YELLOW}User groups:${NC} $(groups)"
echo -e "${YELLOW}Current directory:${NC} $(pwd)"
echo ""

# 5. Check web server configuration
echo -e "${PURPLE}5. WEB SERVER CONFIGURATION${NC}"
echo "============================"

# Check Nginx
if command -v nginx >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Nginx is installed${NC}"
    
    # Find Nginx sites
    if [ -d "/etc/nginx/sites-available" ]; then
        echo -e "${YELLOW}Nginx sites available:${NC}"
        ls -la /etc/nginx/sites-available/ | grep hartonomotor
    fi
    
    # Check for hartonomotor config
    if [ -f "/etc/nginx/sites-available/hartonomotor.xyz" ]; then
        echo -e "${GREEN}📍 Hartonomotor Nginx config found${NC}"
        echo -e "${YELLOW}Document root from config:${NC}"
        grep -i "root" /etc/nginx/sites-available/hartonomotor.xyz | head -1
    fi
else
    echo -e "${RED}❌ Nginx not found${NC}"
fi

# Check Apache
if command -v apache2 >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Apache is installed${NC}"
else
    echo -e "${YELLOW}⚠️ Apache not found${NC}"
fi
echo ""

# 6. Check Git repositories
echo -e "${PURPLE}6. GIT REPOSITORIES${NC}"
echo "=================="

echo -e "${YELLOW}🔍 Searching for Git repositories...${NC}"
find /var/www /home /opt -name ".git" -type d 2>/dev/null | while read git_path; do
    repo_dir=$(dirname "$git_path")
    echo -e "${GREEN}📍 Git repository: ${repo_dir}${NC}"
    
    # Check remote origin
    cd "$repo_dir"
    remote=$(git remote get-url origin 2>/dev/null || echo "No remote found")
    echo -e "   Remote: $remote"
    
    # Check if it's the hartonomotor repo
    if echo "$remote" | grep -q "hm-pls-last\|hartonomotor"; then
        echo -e "   ${GREEN}🎯 This is the Hartono Motor repository!${NC}"
    fi
    echo ""
done

# 7. Summary and recommendations
echo -e "${PURPLE}7. SUMMARY AND RECOMMENDATIONS${NC}"
echo "=============================="

echo -e "${BLUE}📋 What we found:${NC}"

# Find the most likely Laravel directory
LARAVEL_DIRS=$(find /var/www /home /opt -name "artisan" -type f 2>/dev/null | xargs dirname)
if [ -n "$LARAVEL_DIRS" ]; then
    echo -e "${GREEN}✅ Laravel project(s) found at:${NC}"
    echo "$LARAVEL_DIRS" | while read dir; do
        echo -e "   📁 $dir"
    done
else
    echo -e "${RED}❌ No Laravel projects found${NC}"
fi

# Find WhatsApp API directories
WHATSAPP_DIRS=$(find /var/www /home /opt -name "go-whatsapp-web-multidevice-main" -type d 2>/dev/null)
if [ -n "$WHATSAPP_DIRS" ]; then
    echo -e "${GREEN}✅ WhatsApp API found at:${NC}"
    echo "$WHATSAPP_DIRS" | while read dir; do
        echo -e "   📱 $dir"
    done
else
    echo -e "${RED}❌ WhatsApp API folder not found${NC}"
fi

echo ""
echo -e "${BLUE}🔧 Next Steps:${NC}"
echo -e "1. Note the Laravel project location from above"
echo -e "2. If WhatsApp API folder is missing, we'll help you download it"
echo -e "3. Run the fix script with the correct paths"
echo ""
echo -e "${GREEN}✅ Diagnostic completed!${NC}"
echo -e "${YELLOW}💡 Save this output and run the fix script next.${NC}"
