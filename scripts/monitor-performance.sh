#!/bin/bash

echo "📊 Hartono Motor Performance Monitor"
echo "===================================="

# Function to get container memory usage
get_container_memory() {
    local container=$1
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}" | grep $container
}

# System Overview
echo "🖥️ System Overview:"
echo "Uptime: $(uptime)"
echo "Load Average: $(cat /proc/loadavg)"
echo ""

# Memory Analysis
echo "💾 Memory Analysis:"
free -h
echo ""

# Disk Usage
echo "💿 Disk Usage:"
df -h | grep -E "(Filesystem|/dev/)"
echo ""

# Docker Container Performance
echo "🐳 Docker Container Performance:"
echo "Container Name          CPU %    Memory Usage    Memory %"
echo "--------------------------------------------------------"
get_container_memory "hartono-app"
get_container_memory "hartono-webserver" 
get_container_memory "hartono-db"
get_container_memory "hartono-whatsapp-api"
get_container_memory "hartono-phpmyadmin"
echo ""

# Network Connections
echo "🌐 Network Connections:"
netstat -tuln | grep -E "(80|443|3001|3306|8080)"
echo ""

# Process Analysis
echo "⚙️ Top Processes by Memory:"
ps aux --sort=-%mem | head -10
echo ""

# WhatsApp Specific Monitoring
echo "📱 WhatsApp Container Details:"
if docker ps | grep -q "hartono-whatsapp-api"; then
    echo "✅ WhatsApp container is running"
    
    # Check WhatsApp API health
    if curl -s http://localhost:3001/health > /dev/null; then
        echo "✅ WhatsApp API is responding"
        
        # Get WhatsApp status
        WHATSAPP_STATUS=$(curl -s http://localhost:3001/health | grep -o '"isReady":[^,]*' | cut -d':' -f2)
        if [ "$WHATSAPP_STATUS" = "true" ]; then
            echo "✅ WhatsApp is ready and connected"
        else
            echo "⚠️ WhatsApp is not ready"
        fi
    else
        echo "❌ WhatsApp API is not responding"
    fi
    
    # WhatsApp container logs (last 5 lines)
    echo "📋 Recent WhatsApp Logs:"
    docker logs --tail 5 hartono-whatsapp-api
else
    echo "❌ WhatsApp container is not running"
fi
echo ""

# Performance Recommendations
echo "🎯 Performance Recommendations:"

# Check memory usage
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.0f", $3/$2 * 100.0)}')
if [ $MEMORY_USAGE -gt 80 ]; then
    echo "⚠️ High memory usage ($MEMORY_USAGE%). Consider:"
    echo "   - Restarting containers: docker-compose restart"
    echo "   - Running optimization script: ./scripts/optimize-vps.sh"
    echo "   - Upgrading VPS memory"
elif [ $MEMORY_USAGE -gt 60 ]; then
    echo "⚠️ Moderate memory usage ($MEMORY_USAGE%). Monitor closely."
else
    echo "✅ Memory usage is healthy ($MEMORY_USAGE%)"
fi

# Check disk usage
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "⚠️ High disk usage ($DISK_USAGE%). Consider:"
    echo "   - Cleaning Docker: docker system prune -f"
    echo "   - Cleaning logs: docker logs --tail 100 [container] > /dev/null"
elif [ $DISK_USAGE -gt 60 ]; then
    echo "⚠️ Moderate disk usage ($DISK_USAGE%). Monitor closely."
else
    echo "✅ Disk usage is healthy ($DISK_USAGE%)"
fi

echo ""
echo "🔄 To optimize performance, run: ./scripts/optimize-vps.sh"
echo "📊 To monitor continuously, run: watch -n 30 ./scripts/monitor-performance.sh"
