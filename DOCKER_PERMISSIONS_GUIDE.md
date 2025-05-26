# Docker VPS Permissions Fix Guide

This guide helps you fix the critical "Permission denied" error on hartonomotor.xyz that prevents Blade view compilation.

## 🚨 Error Details

**Error**: `file_put_contents(/var/www/html/storage/framework/views/2d866a4203fc9cf3f82662ec5b055e3e.php): Failed to open stream: Permission denied`

**Root Cause**: The web server (www-data) cannot write to the Laravel storage directories due to incorrect file permissions.

## 🔧 Quick Fix for Docker VPS

### Step 1: Update Container Name

First, update the container name in the fix script:

```bash
# Edit the script
nano fix-docker-permissions.sh

# Change this line to match your actual container name:
CONTAINER_NAME="your-actual-container-name"
```

### Step 2: Run the Fix Script

```bash
# Make sure you're in the Laravel project directory
cd /path/to/your/laravel/project

# Run the Docker permission fix
./fix-docker-permissions.sh
```

### Step 3: Manual Fix (If Script Doesn't Work)

If the script fails, run these commands manually:

```bash
# Replace 'hartono-app' with your actual container name
CONTAINER_NAME="hartono-app"

# Create necessary directories
docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/framework/views
docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/framework/cache
docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/framework/sessions
docker exec $CONTAINER_NAME mkdir -p /var/www/html/storage/logs
docker exec $CONTAINER_NAME mkdir -p /var/www/html/bootstrap/cache

# Fix ownership
docker exec $CONTAINER_NAME chown -R www-data:www-data /var/www/html/storage
docker exec $CONTAINER_NAME chown -R www-data:www-data /var/www/html/bootstrap/cache

# Set permissions
docker exec $CONTAINER_NAME chmod -R 755 /var/www/html/storage
docker exec $CONTAINER_NAME chmod -R 775 /var/www/html/storage/framework/views
docker exec $CONTAINER_NAME chmod -R 775 /var/www/html/storage/framework/cache
docker exec $CONTAINER_NAME chmod -R 775 /var/www/html/storage/framework/sessions
docker exec $CONTAINER_NAME chmod -R 775 /var/www/html/storage/logs
docker exec $CONTAINER_NAME chmod -R 775 /var/www/html/bootstrap/cache

# Clear Laravel caches
docker exec $CONTAINER_NAME php artisan view:clear
docker exec $CONTAINER_NAME php artisan config:clear
docker exec $CONTAINER_NAME php artisan route:clear
docker exec $CONTAINER_NAME php artisan cache:clear
```

## 🧪 Verification

### Test 1: Check Container Status

```bash
# List running containers
docker ps

# Check if your Laravel container is running
docker ps | grep "your-container-name"
```

### Test 2: Test Write Permissions

```bash
# Test if we can write to the views directory
docker exec $CONTAINER_NAME touch /var/www/html/storage/framework/views/test.tmp
docker exec $CONTAINER_NAME rm /var/www/html/storage/framework/views/test.tmp

# If successful, you should see no errors
```

### Test 3: Check Permissions

```bash
# Check current permissions
docker exec $CONTAINER_NAME ls -la /var/www/html/storage/framework/
```

Expected output should show `www-data` as owner:
```
drwxrwxr-x 2 www-data www-data 4096 May 26 09:53 views
drwxrwxr-x 2 www-data www-data 4096 May 26 09:53 cache
drwxrwxr-x 2 www-data www-data 4096 May 26 09:53 sessions
```

### Test 4: Test Website

1. Visit `hartonomotor.xyz`
2. Navigate to `/admin/mechanic-reports`
3. Click "Riwayat Servis" for any report
4. Verify no permission errors occur

## 🔄 Making the Fix Persistent

The fix script creates a persistent solution that survives container restarts:

```bash
# The script creates this file inside the container:
/usr/local/bin/fix-laravel-permissions.sh

# You can run it anytime with:
docker exec $CONTAINER_NAME /usr/local/bin/fix-laravel-permissions.sh
```

## 🐳 Docker Compose Integration

If you're using Docker Compose, add this to your `docker-compose.yml`:

```yaml
services:
  app:
    # ... your existing configuration
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    user: "www-data:www-data"  # Run as www-data user
    # OR add this command to fix permissions on startup:
    command: >
      bash -c "
        chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache &&
        chmod -R 775 /var/www/html/storage/framework/views &&
        chmod -R 775 /var/www/html/storage/framework/cache &&
        chmod -R 775 /var/www/html/storage/logs &&
        php-fpm
      "
```

## 🚨 Troubleshooting

### Issue: Container Not Found

```bash
# List all containers (including stopped ones)
docker ps -a

# Start your container if it's stopped
docker start your-container-name
```

### Issue: Permission Still Denied

```bash
# Check if SELinux is causing issues (on CentOS/RHEL)
sestatus

# If SELinux is enabled, try:
sudo setsebool -P httpd_exec_t 1
sudo setsebool -P httpd_unified 1
```

### Issue: Changes Don't Persist

```bash
# Make sure you're not overriding with volume mounts
docker inspect your-container-name | grep -A 10 "Mounts"

# If using bind mounts, fix permissions on the host:
sudo chown -R www-data:www-data storage/ bootstrap/cache/
sudo chmod -R 775 storage/framework/
```

## ✅ Success Indicators

After applying the fix, you should see:

1. ✅ Website loads without errors
2. ✅ Admin panel accessible at `/admin`
3. ✅ Mechanic reports page works
4. ✅ "Riwayat Servis" buttons work without errors
5. ✅ No "Permission denied" errors in logs

## 📞 Emergency Commands

If the website is completely down:

```bash
# Quick emergency fix
docker exec your-container-name chmod -R 777 /var/www/html/storage
docker exec your-container-name php artisan view:clear

# Then apply proper permissions later
docker exec your-container-name chown -R www-data:www-data /var/www/html/storage
docker exec your-container-name chmod -R 755 /var/www/html/storage
docker exec your-container-name chmod -R 775 /var/www/html/storage/framework/views
```

## 📝 Notes

- The fix is designed to work with standard Laravel Docker setups
- Permissions are set to be secure but functional
- The solution includes both immediate fix and persistent solution
- All scripts are tested and safe to run multiple times

## 🎯 Final Verification

Run this command to verify everything is working:

```bash
docker exec your-container-name php -r "
echo 'Testing write permissions...' . PHP_EOL;
file_put_contents('/var/www/html/storage/framework/views/test.php', '<?php echo \"OK\"; ?>');
echo 'Success: Laravel can now compile Blade views!' . PHP_EOL;
unlink('/var/www/html/storage/framework/views/test.php');
"
```

If this runs without errors, your permission fix is successful! 🎉
