#!/bin/bash

echo "=========================================="
echo "DOCKER & DATABASE DIAGNOSIS SCRIPT"
echo "=========================================="
echo ""

echo "1. CURRENT DIRECTORY & FILES"
echo "----------------------------"
echo "Current directory: $(pwd)"
echo ""
echo "Files in current directory:"
ls -la
echo ""

echo "2. DOCKER COMPOSE STATUS"
echo "------------------------"
echo "Docker Compose containers in current directory:"
if [ -f "docker-compose.yml" ]; then
    docker-compose ps
else
    echo "No docker-compose.yml found in current directory"
fi
echo ""

echo "3. ALL DOCKER CONTAINERS"
echo "------------------------"
echo "All Docker containers (running and stopped):"
docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}\t{{.Image}}"
echo ""

echo "4. PROCESSES USING WEB PORTS"
echo "-----------------------------"
echo "Processes using port 80 (HTTP):"
sudo lsof -i :80 2>/dev/null || echo "No processes found on port 80"
echo ""
echo "Processes using port 443 (HTTPS):"
sudo lsof -i :443 2>/dev/null || echo "No processes found on port 443"
echo ""
echo "Processes using port 3306 (MySQL):"
sudo lsof -i :3306 2>/dev/null || echo "No processes found on port 3306"
echo ""

echo "5. HOST PROCESSES"
echo "-----------------"
echo "Nginx processes on host:"
ps aux | grep nginx | grep -v grep || echo "No nginx processes found"
echo ""
echo "PHP processes on host:"
ps aux | grep php | grep -v grep || echo "No PHP processes found"
echo ""
echo "MySQL processes on host:"
ps aux | grep mysql | grep -v grep || echo "No MySQL processes found"
echo ""

echo "6. DOCKER NETWORKS"
echo "------------------"
echo "Docker networks:"
docker network ls
echo ""

echo "7. ENVIRONMENT CONFIGURATION"
echo "-----------------------------"
if [ -f ".env" ]; then
    echo "Database configuration from .env:"
    grep "DB_" .env
else
    echo "No .env file found in current directory"
fi
echo ""

echo "8. OTHER DOCKER-COMPOSE LOCATIONS"
echo "----------------------------------"
echo "Checking /var/www/whatsapp-api/ directory:"
if [ -d "/var/www/whatsapp-api/go-whatsapp-web-multidevice-main" ]; then
    cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main/
    echo "Directory: $(pwd)"
    echo "Files:"
    ls -la | head -10
    echo ""
    if [ -f "docker-compose.yml" ]; then
        echo "Docker Compose status:"
        docker-compose ps
    fi
    cd - > /dev/null
else
    echo "Directory not found"
fi
echo ""

echo "9. SYSTEM RESOURCES"
echo "-------------------"
echo "Memory usage:"
free -h
echo ""
echo "Disk usage:"
df -h | head -5
echo ""

echo "10. DOCKER LOGS (if containers exist)"
echo "-------------------------------------"
echo "Checking for hartono-db logs:"
docker logs hartono-db --tail 20 2>/dev/null || echo "hartono-db container not found"
echo ""
echo "Checking for hartono-app logs:"
docker logs hartono-app --tail 20 2>/dev/null || echo "hartono-app container not found"
echo ""

echo "11. NETWORK CONNECTIVITY TEST"
echo "------------------------------"
echo "Testing database connectivity:"
if docker ps | grep -q "hartono-app"; then
    echo "Testing from hartono-app container:"
    docker exec hartono-app ping -c 2 db 2>/dev/null || echo "Cannot ping db from hartono-app"
    docker exec hartono-app nslookup db 2>/dev/null || echo "Cannot resolve db hostname from hartono-app"
else
    echo "hartono-app container not running"
fi
echo ""

echo "12. SUMMARY & RECOMMENDATIONS"
echo "------------------------------"
echo "Based on the diagnosis above:"
echo ""

# Check if main containers are running
if ! docker ps | grep -q "hartono-db"; then
    echo "❌ ISSUE: hartono-db container is NOT running"
fi

if ! docker ps | grep -q "hartono-app"; then
    echo "❌ ISSUE: hartono-app container is NOT running"
fi

if ! docker ps | grep -q "hartono-webserver"; then
    echo "❌ ISSUE: hartono-webserver container is NOT running"
fi

# Check if website is served by host processes
if ps aux | grep -q "[n]ginx" || ps aux | grep -q "[p]hp"; then
    echo "ℹ️  INFO: Website might be running on host (not Docker)"
fi

echo ""
echo "SUGGESTED ACTIONS:"
echo "1. If main containers are not running: docker-compose up -d"
echo "2. If running on host: Check .env DB_HOST should be 'localhost' not 'db'"
echo "3. If mixed setup: Decide between Docker or host deployment"
echo ""
echo "=========================================="
echo "DIAGNOSIS COMPLETE"
echo "=========================================="
