#!/bin/bash

# Exit on error
set -e

echo "🚀 Starting build process..."

# Install PHP dependencies with proper error handling
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Generate application key if not set
echo "Generating application key..."
php artisan key:generate --force

# Clear and cache configuration
echo "Caching configuration..."
php artisan config:clear
php artisan config:cache

# Clear and cache routes
echo "Caching routes..."
php artisan route:clear
php artisan route:cache

# Clear and cache views
echo "Caching views..."
php artisan view:clear
php artisan view:cache

# Install Node.js dependencies and build assets
echo "Installing Node.js dependencies..."
npm ci
echo "Building assets..."
npm run build

echo "✅ Build process completed!" 