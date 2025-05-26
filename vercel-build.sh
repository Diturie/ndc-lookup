#!/bin/bash

# Exit on error
set -e

echo "🚀 Starting build process..."

# Ensure PHP extensions directory exists
mkdir -p .vercel/php/extensions

# Install PHP dependencies with proper error handling
echo "Installing PHP dependencies..."
PHP_INI_SCAN_DIR=api composer install --no-dev --optimize-autoloader --no-interaction

# Generate application key if not set
echo "Generating application key..."
PHP_INI_SCAN_DIR=api php artisan key:generate --force

# Clear and cache configuration
echo "Caching configuration..."
PHP_INI_SCAN_DIR=api php artisan config:clear
PHP_INI_SCAN_DIR=api php artisan config:cache

# Clear and cache routes
echo "Caching routes..."
PHP_INI_SCAN_DIR=api php artisan route:clear
PHP_INI_SCAN_DIR=api php artisan route:cache

# Clear and cache views
echo "Caching views..."
PHP_INI_SCAN_DIR=api php artisan view:clear
PHP_INI_SCAN_DIR=api php artisan view:cache

# Install Node.js dependencies and build assets
echo "Installing Node.js dependencies..."
npm ci
echo "Building assets..."
npm run build

echo "✅ Build process completed!" 