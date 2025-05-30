#!/bin/bash

echo "🚀 Hartono Motor VPS Optimization Script"
echo "========================================"

# 1. System Resource Check
echo "📊 Current System Resources:"
echo "Memory Usage:"
free -h
echo ""
echo "Disk Usage:"
df -h
echo ""
echo "CPU Usage:"
top -bn1 | grep "Cpu(s)"
echo ""

# 2. Docker Container Optimization
echo "🐳 Docker Container Optimization:"
echo "Current container resource usage:"
docker stats --no-stream

# 3. Clean Docker System
echo "🧹 Cleaning Docker System:"
docker system prune -f
docker volume prune -f
docker image prune -f

# 4. Optimize MySQL
echo "🗄️ MySQL Optimization:"
docker exec -it hartono-db mysql -u root -p${DB_ROOT_PASSWORD} -e "
SET GLOBAL innodb_buffer_pool_size = 128M;
SET GLOBAL query_cache_size = 32M;
SET GLOBAL max_connections = 50;
FLUSH PRIVILEGES;
"

# 5. Clear Laravel Caches
echo "🔄 Clearing Laravel Caches:"
docker exec -it hartono-app php artisan cache:clear
docker exec -it hartono-app php artisan config:clear
docker exec -it hartono-app php artisan route:clear
docker exec -it hartono-app php artisan view:clear

# 6. Optimize Laravel
echo "⚡ Laravel Optimization:"
docker exec -it hartono-app php artisan config:cache
docker exec -it hartono-app php artisan route:cache
docker exec -it hartono-app php artisan view:cache

# 7. System Memory Optimization
echo "💾 System Memory Optimization:"
# Clear system cache
sync && echo 3 > /proc/sys/vm/drop_caches

# Set swappiness (reduce swap usage)
echo 10 > /proc/sys/vm/swappiness

# 8. Nginx Optimization
echo "🌐 Nginx Optimization:"
docker exec -it hartono-webserver nginx -s reload

# 9. WhatsApp Container Optimization
echo "📱 WhatsApp Container Optimization:"
# Restart WhatsApp container to free memory
docker restart hartono-whatsapp-api

# 10. Final Resource Check
echo "📈 Final Resource Check:"
echo "Memory Usage After Optimization:"
free -h
echo ""
echo "Docker Container Usage After Optimization:"
docker stats --no-stream

echo "✅ VPS Optimization Complete!"
echo "🎯 Recommendations:"
echo "- Monitor memory usage regularly"
echo "- Run this script weekly"
echo "- Consider upgrading VPS if consistently high usage"
echo "- Use WhatsApp Chat Manager only when needed"
