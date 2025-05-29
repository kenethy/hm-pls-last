#!/bin/bash

# Find Laravel Project Structure Script
# This script will comprehensively search for Laravel projects on your VPS

echo "🔍 Comprehensive Laravel Project Search"
echo "======================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${BLUE}📋 Starting comprehensive search...${NC}"
echo -e "  VPS Host: $(hostname)"
echo -e "  Current Directory: $(pwd)"
echo -e "  Current User: $(whoami)"
echo ""

# Function to search in specific directories
search_directories() {
    local search_paths=("/var/www" "/home" "/opt" "/root" "/usr/share" "/srv" "/data" "/app" "/hm" "/laravel" "/www")
    
    echo -e "${PURPLE}1. SEARCHING COMMON DIRECTORIES${NC}"
    echo "==============================="
    
    for path in "${search_paths[@]}"; do
        if [ -d "$path" ]; then
            echo -e "${YELLOW}🔍 Searching in: $path${NC}"
            find "$path" -name "artisan" -type f 2>/dev/null | while read artisan_path; do
                laravel_dir=$(dirname "$artisan_path")
                echo -e "${GREEN}📍 Laravel found: $laravel_dir${NC}"
                
                # Check Laravel version
                if [ -f "$laravel_dir/composer.json" ]; then
                    version=$(grep -o '"laravel/framework": "[^"]*"' "$laravel_dir/composer.json" 2>/dev/null || echo "Version unknown")
                    echo -e "   Version: $version"
                    
                    # Check if it's Hartono Motor project
                    if grep -q "hartonomotor\|Hartono" "$laravel_dir/composer.json" 2>/dev/null; then
                        echo -e "   ${GREEN}🎯 This is the Hartono Motor project!${NC}"
                    fi
                fi
                
                # Check directory size
                size=$(du -sh "$laravel_dir" 2>/dev/null | cut -f1)
                echo -e "   Size: $size"
                echo ""
            done
        else
            echo -e "${RED}❌ Directory $path does not exist${NC}"
        fi
    done
}

# Function to search for composer.json files
search_composer_files() {
    echo -e "${PURPLE}2. SEARCHING FOR COMPOSER.JSON FILES${NC}"
    echo "==================================="
    
    echo -e "${YELLOW}🔍 Looking for composer.json files with Laravel...${NC}"
    find / -name "composer.json" -type f 2>/dev/null | while read composer_path; do
        if grep -q "laravel/framework" "$composer_path" 2>/dev/null; then
            project_dir=$(dirname "$composer_path")
            echo -e "${GREEN}📍 Laravel project: $project_dir${NC}"
            
            # Check if artisan exists
            if [ -f "$project_dir/artisan" ]; then
                echo -e "   ${GREEN}✅ artisan file exists${NC}"
            else
                echo -e "   ${RED}❌ artisan file missing${NC}"
            fi
            
            # Check project name
            project_name=$(grep -o '"name": "[^"]*"' "$composer_path" 2>/dev/null | cut -d'"' -f4)
            echo -e "   Project name: $project_name"
            echo ""
        fi
    done
}

# Function to search for specific Laravel files
search_laravel_files() {
    echo -e "${PURPLE}3. SEARCHING FOR LARAVEL SPECIFIC FILES${NC}"
    echo "======================================"
    
    # Search for bootstrap/app.php
    echo -e "${YELLOW}🔍 Looking for bootstrap/app.php files...${NC}"
    find / -path "*/bootstrap/app.php" -type f 2>/dev/null | while read app_path; do
        laravel_dir=$(dirname "$(dirname "$app_path")")
        echo -e "${GREEN}📍 Laravel bootstrap: $laravel_dir${NC}"
        
        # Verify it's Laravel
        if [ -f "$laravel_dir/artisan" ]; then
            echo -e "   ${GREEN}✅ Confirmed Laravel project${NC}"
        else
            echo -e "   ${YELLOW}⚠️ Missing artisan file${NC}"
        fi
        echo ""
    done
    
    # Search for .env files in Laravel projects
    echo -e "${YELLOW}🔍 Looking for .env files in potential Laravel projects...${NC}"
    find / -name ".env" -type f 2>/dev/null | while read env_path; do
        env_dir=$(dirname "$env_path")
        if [ -f "$env_dir/artisan" ]; then
            echo -e "${GREEN}📍 Laravel with .env: $env_dir${NC}"
            
            # Check APP_NAME
            if grep -q "APP_NAME=" "$env_path"; then
                app_name=$(grep "APP_NAME=" "$env_path" | cut -d'=' -f2 | tr -d '"')
                echo -e "   App name: $app_name"
            fi
            echo ""
        fi
    done
}

# Function to search for Docker-related Laravel
search_docker_laravel() {
    echo -e "${PURPLE}4. SEARCHING FOR DOCKER-RELATED LARAVEL${NC}"
    echo "====================================="
    
    # Search for docker-compose.yml files
    echo -e "${YELLOW}🔍 Looking for docker-compose.yml files...${NC}"
    find / -name "docker-compose.yml" -type f 2>/dev/null | while read compose_path; do
        compose_dir=$(dirname "$compose_path")
        echo -e "${BLUE}📍 Docker compose: $compose_dir${NC}"
        
        # Check if it mentions Laravel
        if grep -q "laravel\|php.*fpm" "$compose_path" 2>/dev/null; then
            echo -e "   ${GREEN}✅ Contains Laravel/PHP references${NC}"
            
            # Look for volume mappings
            volumes=$(grep -A 5 -B 5 "volumes:" "$compose_path" 2>/dev/null | grep -E "^\s*-\s*" | head -3)
            if [ -n "$volumes" ]; then
                echo -e "   Volume mappings:"
                echo "$volumes" | while read volume; do
                    echo -e "     $volume"
                done
            fi
        fi
        echo ""
    done
    
    # Search for Dockerfile
    echo -e "${YELLOW}🔍 Looking for Dockerfile files...${NC}"
    find / -name "Dockerfile" -type f 2>/dev/null | while read dockerfile_path; do
        dockerfile_dir=$(dirname "$dockerfile_path")
        if grep -q "laravel\|php.*fpm\|artisan" "$dockerfile_path" 2>/dev/null; then
            echo -e "${GREEN}📍 Laravel Dockerfile: $dockerfile_dir${NC}"
            echo ""
        fi
    done
}

# Function to check current directory structure
check_current_structure() {
    echo -e "${PURPLE}5. CURRENT DIRECTORY STRUCTURE${NC}"
    echo "============================="
    
    echo -e "${YELLOW}🔍 Current directory contents:${NC}"
    ls -la
    echo ""
    
    echo -e "${YELLOW}🔍 Directory tree (2 levels):${NC}"
    if command -v tree >/dev/null 2>&1; then
        tree -L 2 -a
    else
        find . -maxdepth 2 -type d | head -20
    fi
    echo ""
}

# Function to search for web server document roots
search_web_roots() {
    echo -e "${PURPLE}6. WEB SERVER DOCUMENT ROOTS${NC}"
    echo "============================"
    
    # Check Nginx configurations
    if [ -d "/etc/nginx" ]; then
        echo -e "${YELLOW}🔍 Checking Nginx configurations...${NC}"
        find /etc/nginx -name "*.conf" -o -name "*hartonomotor*" 2>/dev/null | while read config_path; do
            if grep -q "root\s" "$config_path" 2>/dev/null; then
                echo -e "${BLUE}📍 Nginx config: $config_path${NC}"
                grep "root\s" "$config_path" | while read root_line; do
                    echo -e "   $root_line"
                done
                echo ""
            fi
        done
    fi
    
    # Check Apache configurations
    if [ -d "/etc/apache2" ]; then
        echo -e "${YELLOW}🔍 Checking Apache configurations...${NC}"
        find /etc/apache2 -name "*.conf" 2>/dev/null | while read config_path; do
            if grep -q "DocumentRoot\s" "$config_path" 2>/dev/null; then
                echo -e "${BLUE}📍 Apache config: $config_path${NC}"
                grep "DocumentRoot\s" "$config_path" | while read root_line; do
                    echo -e "   $root_line"
                done
                echo ""
            fi
        done
    fi
}

# Function to provide recommendations
provide_recommendations() {
    echo -e "${PURPLE}7. RECOMMENDATIONS${NC}"
    echo "================="
    
    echo -e "${BLUE}📋 Based on the search results above:${NC}"
    echo ""
    
    echo -e "${YELLOW}1. Look for Laravel projects in the output above${NC}"
    echo -e "${YELLOW}2. Note the exact path of your Hartono Motor project${NC}"
    echo -e "${YELLOW}3. Use that path in the fix script${NC}"
    echo ""
    
    echo -e "${BLUE}🔧 To fix your Laravel project:${NC}"
    echo -e "1. Identify the correct Laravel directory from above"
    echo -e "2. Edit the fix script to use the correct path:"
    echo -e "   ${YELLOW}nano fix-hm-new-directory.sh${NC}"
    echo -e "   ${YELLOW}# Change LARAVEL_DIR=\"/hm/new\" to the correct path${NC}"
    echo -e "3. Run the modified script"
    echo ""
    
    echo -e "${BLUE}📱 Common Laravel directory patterns:${NC}"
    echo -e "   /var/www/html/laravel"
    echo -e "   /var/www/hartonomotor.xyz"
    echo -e "   /home/user/laravel"
    echo -e "   /opt/laravel"
    echo -e "   /app/laravel"
    echo -e "   /srv/www/laravel"
    echo ""
}

# Main execution
echo -e "${BLUE}Starting comprehensive Laravel search...${NC}"
echo ""

search_directories
search_composer_files
search_laravel_files
search_docker_laravel
check_current_structure
search_web_roots
provide_recommendations

echo -e "${GREEN}🎉 Search completed!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Review the Laravel projects found above"
echo -e "2. Identify your Hartono Motor project"
echo -e "3. Note the exact directory path"
echo -e "4. Use that path to fix your Laravel application"
echo ""
echo -e "${YELLOW}💡 If you found your Laravel project, run:${NC}"
echo -e "   ${YELLOW}cd /path/to/your/laravel/project${NC}"
echo -e "   ${YELLOW}# Then modify and run the fix script${NC}"
