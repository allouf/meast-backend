#!/bin/bash
set -e

echo "Waiting for MySQL to be ready..."
while ! nc -z ${DB_HOST} ${DB_PORT:-3306}; do
  echo "MySQL (${DB_HOST}:${DB_PORT:-3306}) not ready, sleeping 3s..."
  sleep 3
done

echo "MySQL is ready! Running Laravel setup..."

# Remove cached provider manifest so Laravel regenerates it (avoids missing ActivitylogServiceProvider if cache was stale)
rm -f /var/www/html/bootstrap/cache/services.php /var/www/html/bootstrap/cache/packages.php

echo "=== Clearing and Caching Configuration ==="
php artisan config:clear || true
php artisan route:clear || true
php artisan cache:clear || true
php artisan view:clear || true
php artisan optimize:clear || true

echo "=== Running Database Migrations ==="
php artisan migrate --seed --force || true

echo "=== Setting up Storage Link ==="
rm -f public/storage || true
php artisan storage:link || true


echo "=== Application Setup Complete ==="
echo "=== Starting Apache Server ===-"

exec "$@"
