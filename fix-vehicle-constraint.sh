#!/bin/bash
set -e

echo "Fixing vehicle license plate constraint..."

# Check if docker-compose is running
if ! docker-compose ps | grep -q "app.*Up"; then
    echo "Error: Docker containers are not running. Please start them with 'docker-compose up -d' first."
    exit 1
fi

# Run the migration
docker-compose exec app php artisan migrate --path=database/migrations/2025_05_12_165657_update_vehicles_table_license_plate_constraint.php

# Clear cache
echo "Clearing cache..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan filament:clear-cache

echo "Done! Vehicle license plate constraint has been fixed."
