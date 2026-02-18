#!/bin/bash

# SmartAds Deployment Script
# Kør dette script efter git pull på live server

echo "🚀 Starter SmartAds deployment..."

# Stop ved fejl
set -e

# Gå til backend mappen
cd backend

echo "📦 Installerer dependencies..."
composer install --no-dev --optimize-autoloader

echo "🗄️ Kører migrations..."
php artisan migrate --force

echo "🗑️ Rydder cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

echo "🔧 Optimerer cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "📦 Bygger frontend assets..."
cd ../frontend
npm ci
npm run build

echo "🔄 Genstarter queue worker..."
cd ../backend
php artisan queue:restart

echo "🔍 Tjekker status..."
php artisan about
php artisan queue:failed

echo "✅ Deployment færdig!"
echo "📝 Husk at tjekke at webserveren peger på den nye frontend build"
