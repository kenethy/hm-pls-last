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

echo "🎉 WhatsApp Integration deployment completed!"
echo ""
echo "📋 Next Steps:"
echo "1. Access your Filament admin panel"
echo "2. Go to WhatsApp Integration > Konfigurasi WhatsApp"
echo "3. Test the connection using the 'Test Koneksi' button"
echo "4. If connection fails, check logs with: docker-compose logs whatsapp-api"
echo ""
echo "🔧 Useful Commands:"
echo "- View WhatsApp API logs: docker-compose logs -f whatsapp-api"
echo "- Restart WhatsApp API: docker-compose restart whatsapp-api"
echo "- Check container status: docker-compose ps"
