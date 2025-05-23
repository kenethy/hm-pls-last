# File Upload Error Solution: "Request Entity Too Large"

## Problem
When creating new promotions in the Filament admin panel, users encountered the error: "Upload failed: Upload failed: Request Entity Too Large" when attempting to upload promotional images.

## Root Cause Analysis
The "Request Entity Too Large" error typically occurs at the web server level (Nginx/Apache) rather than at the PHP or application level. The investigation revealed:

1. **PHP Settings**: Were adequate (40MB upload_max_filesize, 40MB post_max_size)
2. **Livewire Config**: Was set to 50MB max
3. **Application Config**: Was limited to 5MB
4. **Web Server**: Likely had default limits causing the issue

## Solution Implemented

### 1. Updated PromoResource.php
- **Replaced custom PromoImageUpload component** with standard Filament FileUpload
- **Increased file size limit** from 5MB to 10MB
- **Added comprehensive file type validation**
- **Improved user experience** with progress indicators and helper text

```php
Forms\Components\FileUpload::make('image_path')
    ->label('Gambar Promo')
    ->image()
    ->directory('promos')
    ->disk('public')
    ->visibility('public')
    ->maxSize(10240) // 10MB
    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
    ->required()
    ->helperText('Maksimal 10MB. Format yang didukung: JPEG, PNG, WebP')
```

### 2. Enhanced FilamentServiceProvider.php
- **Increased global file upload limits** from 5MB to 10MB
- **Standardized file upload configuration** across all Filament components
- **Added consistent UI settings** for better user experience

### 3. Added PHP Upload Configuration to .htaccess
- **Set upload_max_filesize to 50M**
- **Set post_max_size to 50M**
- **Increased execution time limits** to 300 seconds
- **Enhanced memory limits** to 512M

```apache
<IfModule mod_php.c>
    php_value upload_max_filesize 50M
    php_value post_max_size 50M
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit 512M
</IfModule>
```

### 4. Created IncreaseUploadLimits Middleware
- **Dynamically sets PHP limits** for file upload requests
- **Ensures consistent limits** across the application
- **Handles edge cases** where .htaccess might not be effective

### 5. Docker/Nginx Configuration
- **Created nginx configuration file** for Docker environments
- **Set client_max_body_size to 50M**
- **Configured appropriate timeouts** for large file uploads
- **Optimized buffer sizes** for better performance

## Files Modified

1. **app/Filament/Resources/PromoResource.php**
   - Replaced custom upload component with standard Filament FileUpload
   - Increased file size limits and improved validation

2. **app/Providers/FilamentServiceProvider.php**
   - Updated global Filament file upload configuration
   - Increased default limits from 5MB to 10MB

3. **public/.htaccess**
   - Added PHP upload configuration directives
   - Set comprehensive upload limits and timeouts

4. **app/Http/Middleware/IncreaseUploadLimits.php** (New)
   - Custom middleware to dynamically set PHP upload limits
   - Ensures consistent configuration across requests

5. **app/Http/Kernel.php**
   - Registered IncreaseUploadLimits middleware in web group
   - Applied to all web requests for consistency

6. **docker/nginx/upload-limits.conf** (New)
   - Nginx configuration for Docker environments
   - Comprehensive upload limits and timeout settings

## Testing Instructions

### 1. Clear Application Cache
```bash
php artisan optimize:clear
```

### 2. Test Promotion Image Upload
1. Navigate to Admin Panel → Promotions → Create
2. Try uploading images of various sizes (1MB, 5MB, 8MB)
3. Verify successful uploads without "Request Entity Too Large" error

### 3. Verify File Size Limits
- **Small files (< 1MB)**: Should upload instantly
- **Medium files (1-5MB)**: Should upload with progress indicator
- **Large files (5-10MB)**: Should upload successfully with longer progress
- **Oversized files (> 10MB)**: Should show validation error (not server error)

### 4. Docker Environment Setup
If using Docker with Nginx, include the upload-limits.conf:

```nginx
# In your main nginx.conf or site configuration
include /path/to/docker/nginx/upload-limits.conf;
```

## Expected Results

### ✅ Success Indicators
- Promotion images upload successfully without server errors
- Progress indicators show during upload
- File size validation works correctly (rejects > 10MB files)
- Upload process completes within reasonable time
- No "Request Entity Too Large" errors

### ⚠️ If Issues Persist

1. **Check Web Server Logs**
   ```bash
   # For Apache
   tail -f /var/log/apache2/error.log
   
   # For Nginx
   tail -f /var/log/nginx/error.log
   ```

2. **Verify PHP Configuration**
   ```bash
   php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
   ```

3. **Test Direct PHP Upload**
   Create a simple test script to isolate the issue

4. **Docker Container Limits**
   Check if Docker container has resource limits that might affect uploads

## Maintenance Notes

- **Monitor upload performance** regularly
- **Adjust limits** based on actual promotional image requirements
- **Keep Docker configuration** in sync with application settings
- **Update documentation** when limits are changed

## Security Considerations

- File type validation prevents malicious uploads
- Size limits prevent resource exhaustion
- Proper disk storage configuration maintains security
- Regular cleanup of temporary upload files recommended
