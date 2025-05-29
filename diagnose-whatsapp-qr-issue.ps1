# WhatsApp QR Code Issue Diagnosis Script
# For VPS Environment - PowerShell Version

Write-Host "🔍 WhatsApp QR Code Issue Diagnosis" -ForegroundColor Blue
Write-Host "==================================================" -ForegroundColor Blue

# Step 1: Check if we can connect to VPS
Write-Host "`n📡 Step 1: Testing VPS connection..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "https://hartonomotor.xyz" -Method Head -TimeoutSec 10
    Write-Host "✅ VPS is accessible (Status: $($response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "❌ Cannot connect to VPS: $($_.Exception.Message)" -ForegroundColor Red
}

# Step 2: Test WhatsApp API endpoint
Write-Host "`n🔗 Step 2: Testing WhatsApp API endpoints..." -ForegroundColor Yellow

Write-Host "Testing WhatsApp API login endpoint:"
try {
    $apiResponse = Invoke-WebRequest -Uri "https://hartonomotor.xyz/whatsapp-api/app/login" -TimeoutSec 15
    Write-Host "✅ API endpoint accessible (Status: $($apiResponse.StatusCode))" -ForegroundColor Green
    
    # Try to parse JSON response
    try {
        $jsonData = $apiResponse.Content | ConvertFrom-Json
        Write-Host "API Response:" -ForegroundColor Cyan
        Write-Host $apiResponse.Content -ForegroundColor White
        
        if ($jsonData.results.qr_link) {
            Write-Host "`nQR Link found: $($jsonData.results.qr_link)" -ForegroundColor Green
            
            # Test QR image accessibility
            Write-Host "`n🖼️ Testing QR image accessibility..." -ForegroundColor Yellow
            try {
                $qrResponse = Invoke-WebRequest -Uri $jsonData.results.qr_link -Method Head -TimeoutSec 10
                Write-Host "✅ QR image accessible (Status: $($qrResponse.StatusCode))" -ForegroundColor Green
            } catch {
                Write-Host "❌ QR image not accessible: $($_.Exception.Message)" -ForegroundColor Red
                
                # Try alternative URLs
                $originalUrl = $jsonData.results.qr_link
                $alternativeUrls = @(
                    $originalUrl -replace "http://localhost:3000/", "https://hartonomotor.xyz/"
                    $originalUrl -replace "http://whatsapp-api:3000/", "https://hartonomotor.xyz/"
                    "https://hartonomotor.xyz/statics/qrcode/test.png"
                )
                
                foreach ($altUrl in $alternativeUrls) {
                    Write-Host "Testing alternative URL: $altUrl" -ForegroundColor Cyan
                    try {
                        $altResponse = Invoke-WebRequest -Uri $altUrl -Method Head -TimeoutSec 5
                        Write-Host "✅ Alternative URL works (Status: $($altResponse.StatusCode))" -ForegroundColor Green
                        break
                    } catch {
                        Write-Host "❌ Alternative URL failed: $($_.Exception.Message)" -ForegroundColor Red
                    }
                }
            }
        }
    } catch {
        Write-Host "⚠️ Cannot parse API response as JSON" -ForegroundColor Yellow
        Write-Host "Raw response: $($apiResponse.Content)" -ForegroundColor White
    }
} catch {
    Write-Host "❌ API endpoint not accessible: $($_.Exception.Message)" -ForegroundColor Red
}

# Step 3: Test static files directory
Write-Host "`n📁 Step 3: Testing static files access..." -ForegroundColor Yellow
$staticUrls = @(
    "https://hartonomotor.xyz/statics/",
    "https://hartonomotor.xyz/statics/qrcode/",
    "https://hartonomotor.xyz/whatsapp-api/statics/",
    "https://hartonomotor.xyz/whatsapp-api/statics/qrcode/"
)

foreach ($url in $staticUrls) {
    Write-Host "Testing: $url" -ForegroundColor Cyan
    try {
        $staticResponse = Invoke-WebRequest -Uri $url -Method Head -TimeoutSec 10
        Write-Host "✅ Accessible (Status: $($staticResponse.StatusCode))" -ForegroundColor Green
    } catch {
        Write-Host "❌ Not accessible: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Step 4: Check local Docker Compose files
Write-Host "`n📄 Step 4: Checking local Docker Compose configuration..." -ForegroundColor Yellow

if (Test-Path "docker-compose.yml") {
    Write-Host "✅ Main docker-compose.yml found" -ForegroundColor Green
    $dockerComposeContent = Get-Content "docker-compose.yml" -Raw
    if ($dockerComposeContent -match "whatsapp") {
        Write-Host "✅ WhatsApp service found in main docker-compose.yml" -ForegroundColor Green
        # Extract WhatsApp service configuration
        $lines = Get-Content "docker-compose.yml"
        $inWhatsappService = $false
        $whatsappConfig = @()
        foreach ($line in $lines) {
            if ($line -match "whatsapp-api:") {
                $inWhatsappService = $true
            }
            if ($inWhatsappService) {
                $whatsappConfig += $line
                if ($line -match "^\s*[a-zA-Z].*:" -and $line -notmatch "whatsapp") {
                    break
                }
            }
        }
        Write-Host "WhatsApp service configuration:" -ForegroundColor Cyan
        $whatsappConfig | ForEach-Object { Write-Host $_ -ForegroundColor White }
    } else {
        Write-Host "⚠️ No WhatsApp service found in main docker-compose.yml" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ Main docker-compose.yml not found" -ForegroundColor Red
}

if (Test-Path "go-whatsapp-web-multidevice-main/docker-compose.yml") {
    Write-Host "`n✅ WhatsApp docker-compose.yml found" -ForegroundColor Green
    Write-Host "WhatsApp docker-compose.yml content:" -ForegroundColor Cyan
    Get-Content "go-whatsapp-web-multidevice-main/docker-compose.yml" | ForEach-Object { Write-Host $_ -ForegroundColor White }
} else {
    Write-Host "`n❌ WhatsApp docker-compose.yml not found" -ForegroundColor Red
}

# Step 5: Check Nginx configuration
Write-Host "`n🌐 Step 5: Checking Nginx configuration..." -ForegroundColor Yellow
if (Test-Path "docker/nginx/conf.d/app.conf") {
    Write-Host "✅ Nginx configuration found" -ForegroundColor Green
    $nginxContent = Get-Content "docker/nginx/conf.d/app.conf" -Raw
    if ($nginxContent -match "/statics/") {
        Write-Host "✅ Static files configuration found in Nginx" -ForegroundColor Green
        # Extract static files configuration
        $lines = Get-Content "docker/nginx/conf.d/app.conf"
        $inStaticConfig = $false
        $staticConfig = @()
        foreach ($line in $lines) {
            if ($line -match "location /statics/") {
                $inStaticConfig = $true
            }
            if ($inStaticConfig) {
                $staticConfig += $line
                if ($line -match "^\s*}" -and $staticConfig.Count -gt 1) {
                    break
                }
            }
        }
        Write-Host "Static files configuration:" -ForegroundColor Cyan
        $staticConfig | ForEach-Object { Write-Host $_ -ForegroundColor White }
    } else {
        Write-Host "❌ No static files configuration found in Nginx" -ForegroundColor Red
    }
} else {
    Write-Host "❌ Nginx configuration not found" -ForegroundColor Red
}

# Step 6: Summary and recommendations
Write-Host "`n📋 Step 6: Summary and Recommendations" -ForegroundColor Blue
Write-Host "================================================" -ForegroundColor Blue

Write-Host "`n🔍 Based on the diagnosis above, the likely issues are:" -ForegroundColor Yellow
Write-Host "1. Volume mounting mismatch between Docker containers" -ForegroundColor White
Write-Host "2. Nginx reverse proxy not properly configured for /statics/ path" -ForegroundColor White
Write-Host "3. QR code files not being generated in the correct directory" -ForegroundColor White
Write-Host "4. Static file serving permissions or path issues" -ForegroundColor White

Write-Host "`n💡 Recommended next steps:" -ForegroundColor Green
Write-Host "1. Check VPS Docker container status with: docker ps" -ForegroundColor White
Write-Host "2. Verify volume mounts with: docker inspect <whatsapp-container>" -ForegroundColor White
Write-Host "3. Check static files directory on VPS host" -ForegroundColor White
Write-Host "4. Review and fix Nginx configuration for static files" -ForegroundColor White
Write-Host "5. Restart containers with proper volume configuration" -ForegroundColor White

Write-Host "`n✅ Diagnosis complete!" -ForegroundColor Green
