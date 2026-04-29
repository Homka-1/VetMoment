#!/bin/bash

# Fix permissions for Laravel
chmod -R 777 storage bootstrap/cache

# Run migrations
php artisan migrate --force

# Start Apache
apache2-foreground
