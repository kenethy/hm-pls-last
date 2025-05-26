#!/bin/bash

# Docker VPS specific permission fix for hartonomotor.xyz
# This script fixes the "Permission denied" error in storage/framework/views

set -e

echo "🐳 Fixing Laravel Storage Permissions in Docker VPS"
echo "==================================================="
echo ""

# Configuration - Update these for your Docker setup
CONTAINER_NAME="hartono-app"  # Update with your actual container name
WEB_USER="www-data"

# Function to check if container exists and is running
check_container() {
    if ! docker ps | grep -q "$CONTAINER_NAME"; then
        echo "❌ Error: Container '$CONTAINER_NAME' is not running"
        echo "Please start your Docker containers first"
        echo ""
        echo "Available containers:"
        docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
        exit 1
    fi
    echo "✅ Container '$CONTAINER_NAME' is running"
}

# Function to fix storage permissions
fix_storage_permissions() {
    echo ""
    echo "🔧 Fixing storage directory permissions..."
    
    # Create storage directories if they don't exist
    echo "Creating storage directories..."
    docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/framework/views
    docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/framework/cache
    docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/framework/sessions
    docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/logs
    docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/app/public
    
    # Fix ownership for entire storage directory
    echo "Setting ownership to $WEB_USER:$WEB_USER..."
    docker exec $CONTAINER_NAME chown -R $WEB_USER:$WEB_USER /var/www/html/storage
    
    # Set directory permissions (755 = rwxr-xr-x)
    echo "Setting directory permissions..."
    docker exec $CONTAINER_NAME find /var/www/html/storage -type d -exec chmod 755 {} \;
    
    # Set file permissions (644 = rw-r--r--)
    echo "Setting file permissions..."
    docker exec $CONTAINER_NAME find /var/www/html/storage -type f -exec chmod 644 {} \;
    
    # Special permissions for specific directories
    echo "Setting special permissions for writable directories..."
    docker exec $CONTAINER_NAME chmod 775 /var/www/html/storage/framework/views
    docker exec $CONTAINER_NAME chmod 775 /var/www/html/storage/framework/cache
    docker exec $CONTAINER_NAME chmod 775 /var/www/html/storage/framework/sessions
    docker exec $CONTAINER_NAME chmod 775 /var/www/html/storage/logs
}

# Function to fix bootstrap cache permissions
fix_bootstrap_permissions() {
    echo ""
    echo "🔧 Fixing bootstrap/cache permissions..."
    
    # Create bootstrap cache directory if it doesn't exist
    docker exec $CONTAINER_NAME mkdir -p /var/www/html/bootstrap/cache
    
    # Fix ownership and permissions
    docker exec $CONTAINER_NAME chown -R $WEB_USER:$WEB_USER /var/www/html/bootstrap/cache
    docker exec $CONTAINER_NAME chmod -R 775 /var/www/html/bootstrap/cache
}

# Function to test write permissions
test_write_permissions() {
    echo ""
    echo "🧪 Testing write permissions..."
    
    # Test views directory
    echo "Testing storage/framework/views..."
    if docker exec $CONTAINER_NAME touch /var/www/html/storage/framework/views/test_write.tmp; then
        echo "✅ Views directory is writable"
        docker exec $CONTAINER_NAME rm /var/www/html/storage/framework/views/test_write.tmp
    else
        echo "❌ Views directory is not writable"
        return 1
    fi
    
    # Test cache directory
    echo "Testing storage/framework/cache..."
    if docker exec $CONTAINER_NAME touch /var/www/html/storage/framework/cache/test_write.tmp; then
        echo "✅ Cache directory is writable"
        docker exec $CONTAINER_NAME rm /var/www/html/storage/framework/cache/test_write.tmp
    else
        echo "❌ Cache directory is not writable"
        return 1
    fi
    
    # Test logs directory
    echo "Testing storage/logs..."
    if docker exec $CONTAINER_NAME touch /var/www/html/storage/logs/test_write.tmp; then
        echo "✅ Logs directory is writable"
        docker exec $CONTAINER_NAME rm /var/www/html/storage/logs/test_write.tmp
    else
        echo "❌ Logs directory is not writable"
        return 1
    fi
}

# Function to clear Laravel caches
clear_laravel_caches() {
    echo ""
    echo "🧹 Clearing Laravel caches..."
    
    # Clear all caches
    docker exec $CONTAINER_NAME php artisan view:clear
    docker exec $CONTAINER_NAME php artisan config:clear
    docker exec $CONTAINER_NAME php artisan route:clear
    docker exec $CONTAINER_NAME php artisan cache:clear
    
    echo "✅ All caches cleared successfully"
}

# Function to verify current permissions
verify_permissions() {
    echo ""
    echo "🔍 Current permissions status:"
    echo "------------------------------"
    
    echo "Storage directory:"
    docker exec $CONTAINER_NAME ls -la /var/www/html/storage/
    
    echo ""
    echo "Framework directory:"
    docker exec $CONTAINER_NAME ls -la /var/www/html/storage/framework/
    
    echo ""
    echo "Views directory:"
    docker exec $CONTAINER_NAME ls -la /var/www/html/storage/framework/views/ || echo "(Views directory is empty - this is normal)"
    
    echo ""
    echo "Bootstrap cache:"
    docker exec $CONTAINER_NAME ls -la /var/www/html/bootstrap/cache/ || echo "(Bootstrap cache is empty - this is normal)"
}

# Function to create a persistent fix
create_persistent_fix() {
    echo ""
    echo "📝 Creating persistent permission fix..."
    
    # Create a script inside the container that can be run on startup
    docker exec $CONTAINER_NAME bash -c 'cat > /usr/local/bin/fix-laravel-permissions.sh << EOF
#!/bin/bash
# Laravel permission fix script
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
find /var/www/html/storage -type d -exec chmod 755 {} \;
find /var/www/html/storage -type f -exec chmod 644 {} \;
chmod -R 775 /var/www/html/storage/framework/views
chmod -R 775 /var/www/html/storage/framework/cache
chmod -R 775 /var/www/html/storage/framework/sessions
chmod -R 775 /var/www/html/storage/logs
chmod -R 775 /var/www/html/bootstrap/cache
echo "Laravel permissions fixed successfully"
EOF'
    
    docker exec $CONTAINER_NAME chmod +x /usr/local/bin/fix-laravel-permissions.sh
    
    echo "✅ Persistent fix script created at /usr/local/bin/fix-laravel-permissions.sh"
    echo "You can run this script anytime with:"
    echo "docker exec $CONTAINER_NAME /usr/local/bin/fix-laravel-permissions.sh"
}

# Main execution
main() {
    echo "Starting Docker VPS permission fix..."
    echo ""
    
    # Check if container is running
    check_container
    
    # Show current permissions
    verify_permissions
    
    # Fix storage permissions
    fix_storage_permissions
    
    # Fix bootstrap permissions
    fix_bootstrap_permissions
    
    # Test write permissions
    test_write_permissions
    
    # Clear Laravel caches
    clear_laravel_caches
    
    # Create persistent fix
    create_persistent_fix
    
    # Show final permissions
    verify_permissions
    
    echo ""
    echo "🎉 Docker VPS permission fix completed successfully!"
    echo ""
    echo "Fixed issues:"
    echo "✅ file_put_contents permission denied errors"
    echo "✅ Blade view compilation errors"
    echo "✅ Storage directory write access"
    echo "✅ Bootstrap cache write access"
    echo ""
    echo "Your website should now work properly:"
    echo "1. Visit hartonomotor.xyz"
    echo "2. Navigate to /admin/mechanic-reports"
    echo "3. Test the 'Riwayat Servis' functionality"
    echo ""
    echo "If permissions get reset, run:"
    echo "docker exec $CONTAINER_NAME /usr/local/bin/fix-laravel-permissions.sh"
}

# Run main function
main "$@"
