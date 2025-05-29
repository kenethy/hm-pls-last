#!/bin/bash

# WhatsApp Integration Deployment Script
# This script deploys the WhatsApp API integration to your VPS

echo "🚀 Starting WhatsApp Integration Deployment..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose is not installed. Please install docker-compose and try again."
    exit 1
fi

echo "✅ Docker environment check passed"

# Stop existing containers
echo "🛑 Stopping existing containers..."
docker-compose down

# Build and start WhatsApp API container
echo "🔨 Building WhatsApp API container..."
docker-compose build whatsapp-api

# Start all services
echo "🚀 Starting all services..."
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 30

# Check if WhatsApp API is running
echo "🔍 Checking WhatsApp API status..."
if curl -f http://localhost:3000/app/devices > /dev/null 2>&1; then
    echo "✅ WhatsApp API is running successfully!"
else
    echo "⚠️  WhatsApp API might not be ready yet. Please check logs with: docker-compose logs whatsapp-api"
fi

# Run Laravel migrations and seeders
echo "📊 Running database migrations and seeders..."
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --class=WhatsAppConfigSeeder --force

# Test network connectivity between containers
echo "🔗 Testing container network connectivity..."
echo "Testing from Laravel container to WhatsApp API..."
if docker-compose exec app curl -f http://whatsapp-api:3000/app/devices > /dev/null 2>&1; then
    echo "✅ Internal network connectivity: SUCCESS"
elif docker-compose exec app curl -f http://hartono-whatsapp-api:3000/app/devices > /dev/null 2>&1; then
    echo "✅ Internal network connectivity: SUCCESS (using full container name)"
elif docker-compose exec app curl -f http://localhost:3000/app/devices > /dev/null 2>&1; then
    echo "✅ Internal network connectivity: SUCCESS (using localhost)"
else
    echo "⚠️  Internal network connectivity: FAILED"
    echo "   This may cause connection issues in Filament admin"
    echo "   Check network configuration with: docker network inspect hartono-network"
fi

echo "🎉 WhatsApp Integration deployment completed!"
echo ""
echo "📋 COMPLETE SETUP GUIDE:"
echo ""
echo "🔐 STEP 1: Authentication Setup"
echo "1. Access Filament admin: http://your-domain/admin"
echo "2. Go to: WhatsApp Integration > Konfigurasi WhatsApp"
echo "3. Verify API URL is set to: http://whatsapp-api:3000"
echo "4. Click 'Test Koneksi' - should show 'Koneksi Berhasil'"
echo "5. Click 'Autentikasi WhatsApp' to open web interface"
echo "6. Scan QR code with your WhatsApp mobile app"
echo "7. Wait for 'Status WhatsApp' to show 'Authenticated'"
echo ""
echo "📱 STEP 2: Test Message Sending"
echo "1. In WhatsApp Configuration, click 'Test Pesan'"
echo "2. Enter your phone number (08123456789 or 628123456789)"
echo "3. Send test message and verify receipt on WhatsApp"
echo "4. Check WhatsApp Integration > Pesan WhatsApp for logs"
echo ""
echo "🔄 STEP 3: Configure Follow-up Templates"
echo "1. Go to: WhatsApp Integration > Template Follow-up"
echo "2. Create template with:"
echo "   - Trigger Event: Selesai Servis"
echo "   - WhatsApp Enabled: Yes"
echo "   - Auto Send on Completion: Yes"
echo "3. Use variables like {customer_name}, {service_type}, etc."
echo ""
echo "✅ STEP 4: Test Service Completion Flow"
echo "1. Create test service with valid customer phone"
echo "2. Complete the service (change status to 'Completed')"
echo "3. Verify automatic WhatsApp follow-up is sent"
echo ""
echo "📖 For detailed testing guide, see: WHATSAPP_TESTING_GUIDE.md"
echo ""
echo "🔧 Useful Commands:"
echo "- View WhatsApp API logs: docker-compose logs -f whatsapp-api"
echo "- View Laravel logs: docker-compose logs -f app"
echo "- Test connectivity: docker-compose exec app curl http://whatsapp-api:3000/app/devices"
echo "- Restart services: docker-compose restart whatsapp-api app"
echo "- Check status: docker-compose ps"
echo ""
echo "🚨 If issues occur, check WHATSAPP_TROUBLESHOOTING.md"
