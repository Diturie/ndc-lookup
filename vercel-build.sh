#!/bin/bash

# Exit on error
set -e

echo "🚀 Starting build process..."

# Ensure PHP extensions directory exists
mkdir -p .vercel/php/extensions

# Install PHP dependencies with proper error handling
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Generate application key if not set
echo "Generating application key..."
php artisan key:generate --force

# Clear and cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install Node.js dependencies and build assets
echo "Installing NPM dependencies..."
npm ci
echo "Building assets..."
npm run build

echo "✅ Build process completed!" 