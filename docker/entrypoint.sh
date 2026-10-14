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

# Ensure demo users exist (tests or a fresh volume can leave the DB empty)
if ! php artisan tinker --execute="echo App\\Models\\User::query()->exists() ? '1' : '0';" 2>/dev/null | grep -q '1'; then
    echo "[entrypoint] No users found — running db:seed"
    php artisan db:seed --force --no-interaction
fi

# Fix storage permissions AFTER artisan (CLI runs as root and may recreate logs)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
touch /var/www/html/storage/logs/laravel.log
chown www-data:www-data /var/www/html/storage/logs/laravel.log

exec "$@"
