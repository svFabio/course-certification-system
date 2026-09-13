#!/bin/sh
set -e

# Copy .env from example if missing
if [ ! -f /var/www/html/.env ]; then
    echo "[entrypoint] .env not found — copying from .env.example"
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Install Composer dependencies if vendor is missing
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ missing — running composer install"
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "[entrypoint] APP_KEY missing — generating"
    php artisan key:generate --no-interaction
fi

# Fix storage permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run migrations
echo "[entrypoint] Running migrations"
php artisan migrate --force --no-interaction

# Publish filament assets if missing
if [ ! -d /var/www/html/public/css/filament ]; then
    echo "[entrypoint] Publishing Filament assets"
    php artisan filament:assets --no-interaction
fi

# Clear config cache so env changes are picked up
php artisan config:clear

exec "$@"
