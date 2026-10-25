#!/bin/sh
set -e

echo "[entrypoint.prod] Starting production entrypoint"

# ─── Safety guards ────────────────────────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint.prod] FATAL: APP_KEY is not set. Aborting." >&2
    exit 1
fi

if [ -z "$DB_PASSWORD" ] && [ -z "$DATABASE_URL" ]; then
    echo "[entrypoint.prod] FATAL: Neither DB_PASSWORD nor DATABASE_URL is set. Aborting." >&2
    exit 1
fi

# ─── Dynamic Port Binding (Render injects $PORT, default 80) ──────────────────
if [ -f /etc/apache2/ports.conf ]; then
    PORT_TO_USE="${PORT:-80}"
    echo "[entrypoint.prod] Configuring Apache port to ${PORT_TO_USE}"
    sed -i "s/Listen [0-9]*/Listen ${PORT_TO_USE}/g" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT_TO_USE}>/g" /etc/apache2/sites-available/*.conf
fi

# ─── Storage symlink ──────────────────────────────────────────────────────────
if [ ! -L public/storage ]; then
    echo "[entrypoint.prod] Creating storage symlink"
    php artisan storage:link --no-interaction || true
fi

# ─── Run pending migrations ───────────────────────────────────────────────────
# Run migrations only when booting the web server (avoid race conditions in workers)
if [ "$1" = "apache2-foreground" ] || [ -z "$1" ]; then
    echo "[entrypoint.prod] Running migrations"
    php artisan migrate --force --no-interaction
fi

# ─── Laravel production optimizations ────────────────────────────────────────
echo "[entrypoint.prod] Caching config, routes, views and events"
php artisan optimize --no-interaction
php artisan icons:cache --no-interaction
php artisan filament:cache-components --no-interaction

# ─── Fix permissions ──────────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# ─── In-container queue worker & background seeder for Free Tier ──────────────
if [ "$1" = "apache2-foreground" ] || [ -z "$1" ]; then
    echo "[entrypoint.prod] Starting background queue worker on free tier"
    php artisan queue:work --tries=3 --sleep=3 --max-time=3600 > /dev/null 2>&1 &

    echo "[entrypoint.prod] Starting background database seeder"
    php artisan db:seed --force --no-interaction > /tmp/seeder.log 2>&1 &
fi

echo "[entrypoint.prod] Ready. Handing off to: $@"
exec "$@"
