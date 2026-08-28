#!/bin/sh
set -e

# Ensure SQLite database exists with proper permissions
mkdir -p /var/www/html/database
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi
chown -R www-data:www-data /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache

# Discover packages & clear stale cached files on startup
php artisan package:discover --ansi || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

# Run database migrations, seeding, and storage link
php artisan migrate --force || true
php artisan db:seed --force || true
php artisan storage:link || true

# Cache fresh config, routes, views, and events
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan event:cache || true

# Start php-fpm in background and nginx in foreground
php-fpm -D
exec nginx -g "daemon off;"

