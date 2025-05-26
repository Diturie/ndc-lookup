#!/bin/bash

echo "🚀 Starting build process..."

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Generate application key if not set
php artisan key:generate --force

# Clear and cache configuration
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Install Node.js dependencies and build assets
npm install
npm run build

echo "✅ Build process completed!" 