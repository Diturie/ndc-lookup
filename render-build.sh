#!/usr/bin/env bash
# exit on error
set -o errexit

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Install Node.js dependencies
npm install

# Build assets
npm run build 