#!/bin/bash

# Fix Laravel storage permissions for Docker VPS environment
# This script resolves the "Permission denied" error for storage/framework/views

set -e

echo "🔧 Fixing Laravel Storage Permissions for Docker VPS"
echo "===================================================="
echo ""

# Configuration - Update these for your Docker setup
CONTAINER_NAME="hartono-app"  # Update with your actual container name
WEB_USER="www-data"           # Web server user in Docker container

# Function to check if we're running in Docker or local environment
check_environment() {
    if command -v docker &> /dev/null && docker ps | grep -q "$CONTAINER_NAME"; then
        echo "✅ Docker environment detected"
        echo "Container: $CONTAINER_NAME"
        DOCKER_MODE=true
    else
        echo "ℹ️  Local environment detected"
        DOCKER_MODE=false
    fi
}

# Function to fix permissions in Docker container
fix_docker_permissions() {
    echo "🐳 Fixing permissions in Docker container..."
    
    # Fix ownership and permissions for storage directory
    echo "Setting ownership to $WEB_USER:$WEB_USER..."
    docker exec $CONTAINER_NAME chown -R $WEB_USER:$WEB_USER /var/www/html/storage
    
    # Set proper directory permissions (755)
    echo "Setting directory permissions to 755..."
    docker exec $CONTAINER_NAME find /var/www/html/storage -type d -exec chmod 755 {} \;
    
    # Set proper file permissions (644)
    echo "Setting file permissions to 644..."
    docker exec $CONTAINER_NAME find /var/www/html/storage -type f -exec chmod 644 {} \;
    
    # Fix bootstrap/cache directory as well
    echo "Fixing bootstrap/cache permissions..."
    docker exec $CONTAINER_NAME chown -R $WEB_USER:$WEB_USER /var/www/html/bootstrap/cache
    docker exec $CONTAINER_NAME chmod -R 755 /var/www/html/bootstrap/cache
    
    # Create views directory if it doesn't exist
    echo "Ensuring views directory exists..."
    docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/framework/views
    docker exec $CONTAINER_NAME chown $WEB_USER:$WEB_USER /var/www/html/storage/framework/views
    docker exec $CONTAINER_NAME chmod 755 /var/www/html/storage/framework/views
}

# Function to fix permissions locally
fix_local_permissions() {
    echo "💻 Fixing permissions locally..."
    
    # Check if we have sudo access
    if command -v sudo &> /dev/null; then
        echo "Using sudo to fix permissions..."
        
        # Fix ownership (you may need to adjust the user)
        sudo chown -R www-data:www-data storage/ bootstrap/cache/ 2>/dev/null || {
            echo "⚠️  Could not change ownership to www-data, trying current user..."
            sudo chown -R $(whoami):$(whoami) storage/ bootstrap/cache/
        }
        
        # Set permissions
        sudo chmod -R 755 storage/
        sudo chmod -R 755 bootstrap/cache/
        
    else
        echo "No sudo available, setting permissions for current user..."
        chmod -R 755 storage/
        chmod -R 755 bootstrap/cache/
    fi
    
    # Create views directory if it doesn't exist
    mkdir -p storage/framework/views
    chmod 755 storage/framework/views
}

# Function to verify permissions
verify_permissions() {
    echo ""
    echo "🔍 Verifying permissions..."
    
    if [ "$DOCKER_MODE" = true ]; then
        echo "Checking Docker container permissions..."
        docker exec $CONTAINER_NAME ls -la /var/www/html/storage/framework/
        echo ""
        echo "Views directory:"
        docker exec $CONTAINER_NAME ls -la /var/www/html/storage/framework/views/ || echo "Views directory is empty (this is normal)"
    else
        echo "Checking local permissions..."
        ls -la storage/framework/
        echo ""
        echo "Views directory:"
        ls -la storage/framework/views/ || echo "Views directory is empty (this is normal)"
    fi
}

# Function to test write permissions
test_write_permissions() {
    echo ""
    echo "🧪 Testing write permissions..."
    
    if [ "$DOCKER_MODE" = true ]; then
        # Test write permission in Docker container
        docker exec $CONTAINER_NAME touch /var/www/html/storage/framework/views/test_write.tmp
        if docker exec $CONTAINER_NAME test -f /var/www/html/storage/framework/views/test_write.tmp; then
            echo "✅ Write test successful in Docker container"
            docker exec $CONTAINER_NAME rm /var/www/html/storage/framework/views/test_write.tmp
        else
            echo "❌ Write test failed in Docker container"
            return 1
        fi
    else
        # Test write permission locally
        touch storage/framework/views/test_write.tmp
        if [ -f "storage/framework/views/test_write.tmp" ]; then
            echo "✅ Write test successful locally"
            rm storage/framework/views/test_write.tmp
        else
            echo "❌ Write test failed locally"
            return 1
        fi
    fi
}

# Function to clear Laravel caches
clear_laravel_caches() {
    echo ""
    echo "🧹 Clearing Laravel caches..."
    
    if [ "$DOCKER_MODE" = true ]; then
        docker exec $CONTAINER_NAME php artisan view:clear
        docker exec $CONTAINER_NAME php artisan config:clear
        docker exec $CONTAINER_NAME php artisan route:clear
        docker exec $CONTAINER_NAME php artisan cache:clear
    else
        php artisan view:clear
        php artisan config:clear
        php artisan route:clear
        php artisan cache:clear
    fi
    
    echo "✅ Caches cleared successfully"
}

# Main execution
main() {
    echo "Starting permission fix process..."
    echo ""
    
    # Check environment
    check_environment
    echo ""
    
    # Fix permissions based on environment
    if [ "$DOCKER_MODE" = true ]; then
        fix_docker_permissions
    else
        fix_local_permissions
    fi
    
    # Verify the fix
    verify_permissions
    
    # Test write permissions
    test_write_permissions
    
    # Clear Laravel caches
    clear_laravel_caches
    
    echo ""
    echo "🎉 Permission fix completed successfully!"
    echo ""
    echo "The following issues should now be resolved:"
    echo "✅ file_put_contents permission denied errors"
    echo "✅ Blade view compilation errors"
    echo "✅ Storage directory write access"
    echo ""
    echo "You can now test your website:"
    echo "1. Visit hartonomotor.xyz"
    echo "2. Navigate to different pages"
    echo "3. Check that no permission errors occur"
    echo ""
    echo "If you're using Docker, these permissions should persist"
    echo "across container restarts."
}

# Run main function
main "$@"
