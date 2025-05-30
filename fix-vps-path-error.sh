#!/bin/bash

# Fix Laravel path error and storage permissions for Docker VPS environment
# This script resolves the "/hm-new/storage/" path error and permission issues

set -e

echo "🔧 Fixing Laravel Path Error and Storage Permissions for Docker VPS"
echo "=================================================================="
echo ""

# Auto-detect container name
echo "🔍 Auto-detecting Laravel container..."
CONTAINER_NAME=$(docker ps --format "table {{.Names}}" | grep -E "(app|laravel|php|hartono)" | head -1)

if [ -z "$CONTAINER_NAME" ]; then
    echo "❌ Cannot find Laravel container automatically."
    echo "📋 Available containers:"
    docker ps --format "table {{.Names}}\t{{.Image}}"
    echo ""
    read -p "Please enter your container name: " CONTAINER_NAME
fi

echo "✅ Using container: $CONTAINER_NAME"
echo ""

# Step 1: Check current path and diagnose the issue
echo "🔍 Step 1: Diagnosing path issue..."
docker exec $CONTAINER_NAME bash -c "
    echo '📍 Current working directory:'
    pwd
    echo ''
    echo '📁 Checking Laravel root directory:'
    ls -la /var/www/html/ | head -10
    echo ''
    echo '🔍 Checking if /hm-new exists (this should NOT exist):'
    if [ -d '/hm-new' ]; then
        echo '❌ Found /hm-new directory - this is the problem!'
        ls -la /hm-new/
    else
        echo '✅ /hm-new does not exist - good!'
    fi
    echo ''
"

# Step 2: Fix storage directories and permissions
echo "🛠️ Step 2: Creating storage directories and fixing permissions..."
docker exec $CONTAINER_NAME bash -c "
    echo '📁 Creating all required storage directories...'
    mkdir -p /var/www/html/storage/app/public
    mkdir -p /var/www/html/storage/framework/cache
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/testing
    mkdir -p /var/www/html/storage/framework/views
    mkdir -p /var/www/html/storage/logs
    mkdir -p /var/www/html/bootstrap/cache
    
    echo '🔐 Setting proper permissions...'
    chmod -R 777 /var/www/html/storage
    chmod -R 777 /var/www/html/bootstrap/cache
    
    echo '✅ Storage directories created and permissions set!'
"

# Step 3: Check and fix environment configuration
echo "🔧 Step 3: Checking environment configuration..."
docker exec $CONTAINER_NAME bash -c "
    echo '📋 Current environment variables:'
    echo 'APP_URL:' \$(grep '^APP_URL=' /var/www/html/.env 2>/dev/null || echo 'Not found')
    echo 'APP_ENV:' \$(grep '^APP_ENV=' /var/www/html/.env 2>/dev/null || echo 'Not found')
    echo ''
    
    echo '📁 Checking if .env file exists:'
    if [ -f '/var/www/html/.env' ]; then
        echo '✅ .env file exists'
    else
        echo '❌ .env file missing!'
        if [ -f '/var/www/html/.env.docker' ]; then
            echo '📋 Copying .env.docker to .env...'
            cp /var/www/html/.env.docker /var/www/html/.env
            echo '✅ .env file created from .env.docker'
        fi
    fi
"

# Step 4: Clear all Laravel caches
echo "🧹 Step 4: Clearing Laravel caches..."
docker exec $CONTAINER_NAME bash -c "
    echo '🗑️ Clearing all Laravel caches...'
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear
    
    echo '✅ All caches cleared!'
"

# Step 5: Test write permissions
echo "🧪 Step 5: Testing write permissions..."
docker exec $CONTAINER_NAME bash -c "
    echo '📝 Testing write to storage/framework/views...'
    TEST_FILE='/var/www/html/storage/framework/views/test_write.php'
    
    if echo '<?php echo \"Test successful\"; ?>' > \$TEST_FILE; then
        echo '✅ Write test successful!'
        rm \$TEST_FILE
    else
        echo '❌ Write test failed!'
        exit 1
    fi
"

# Step 6: Final verification
echo "✅ Step 6: Final verification..."
docker exec $CONTAINER_NAME bash -c "
    echo '📊 Final status check:'
    echo '- Storage directory exists:' \$([ -d '/var/www/html/storage/framework/views' ] && echo 'YES' || echo 'NO')
    echo '- Storage writable:' \$([ -w '/var/www/html/storage/framework/views' ] && echo 'YES' || echo 'NO')
    echo '- Bootstrap cache writable:' \$([ -w '/var/www/html/bootstrap/cache' ] && echo 'YES' || echo 'NO')
    echo ''
    echo '🎯 Laravel should now work properly!'
"

echo ""
echo "🎉 Fix completed! Please test your Laravel application now."
echo "📝 If you still get errors, please share the new error message."
echo ""
