#!/bin/bash
set -e

echo "🚀 Setting up Spare Parts Management System..."

# Find the correct container name
CONTAINER_NAME=$(docker ps | grep -E 'app|laravel|php' | awk '{print $NF}' | head -1)

if [ -z "$CONTAINER_NAME" ]; then
    echo "❌ Error: Cannot find PHP/Laravel container. Please check running containers with 'docker ps'."
    exit 1
fi

echo "📦 Using container: $CONTAINER_NAME"

# Run migrations
echo "🔄 Running migrations..."
docker exec $CONTAINER_NAME php artisan migrate --force

# Run seeders
echo "🌱 Running seeders..."
docker exec $CONTAINER_NAME php artisan db:seed --class=SparePartCategorySeeder --force
docker exec $CONTAINER_NAME php artisan db:seed --class=SparePartSeeder --force

echo "⚙️ Setting up pricing notification settings..."
# The settings are automatically created by the migration

# Clear cache
echo "🧹 Clearing cache..."
docker exec $CONTAINER_NAME php artisan cache:clear
docker exec $CONTAINER_NAME php artisan config:clear
docker exec $CONTAINER_NAME php artisan route:clear
docker exec $CONTAINER_NAME php artisan view:clear

# Create storage link if not exists
echo "🔗 Creating storage link..."
docker exec $CONTAINER_NAME php artisan storage:link

echo "✅ Spare Parts Management System setup complete!"
echo ""
echo "📋 What's been created:"
echo "   • Spare Part Categories table and model"
echo "   • Spare Parts table and model with marketplace links"
echo "   • Spare Part Settings table for pricing notifications"
echo "   • Filament admin resources for all components"
echo "   • Sample categories and products with marketplace links"
echo "   • Updated controller with database integration"
echo "   • New routes for category and product pages"
echo "   • Pricing notification system (banner/modal/sticky)"
echo "   • Marketplace integration (Shopee, Tokopedia, Lazada, Bukalapak)"
echo ""
echo "🎯 Next steps:"
echo "   1. Login to admin panel: /admin"
echo "   2. Navigate to 'Manajemen Sparepart' section"
echo "   3. Add more categories and products"
echo "   4. Upload product images"
echo "   5. Update the spare parts page template to use database data"
echo ""
echo "🔧 Admin Features Available:"
echo "   • Full CRUD operations for categories and products"
echo "   • Image upload with editor"
echo "   • Stock management"
echo "   • SEO fields"
echo "   • Product specifications and compatibility"
echo "   • Featured and best seller flags"
echo "   • Advanced filtering and search"
echo "   • Marketplace links management (Shopee, Tokopedia, Lazada, Bukalapak)"
echo "   • Pricing notification settings (banner/modal/sticky)"
echo "   • Customizable notification messages and WhatsApp integration"
echo ""
