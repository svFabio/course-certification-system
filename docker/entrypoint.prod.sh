#!/bin/sh
set -e

echo "[entrypoint.prod] Starting production entrypoint"

# ─── Safety guards ────────────────────────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint.prod] FATAL: APP_KEY is not set. Aborting." >&2
    exit 1
fi

if [ -z "$DB_PASSWORD" ]; then
    echo "[entrypoint.prod] FATAL: DB_PASSWORD is not set. Aborting." >&2
    exit 1
fi

# ─── Storage symlink ──────────────────────────────────────────────────────────
if [ ! -L public/storage ]; then
    echo "[entrypoint.prod] Creating storage symlink"
    php artisan storage:link --no-interaction
fi

# ─── Run pending migrations ───────────────────────────────────────────────────
echo "[entrypoint.prod] Running migrations"
php artisan migrate --force --no-interaction

# ─── Laravel production optimizations ────────────────────────────────────────
echo "[entrypoint.prod] Caching config, routes, views and events"
php artisan optimize --no-interaction
php artisan icons:cache --no-interaction
php artisan filament:cache-components --no-interaction

# ─── Fix permissions (FPM runs as www-data) ───────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "[entrypoint.prod] Ready. Handing off to: $@"
exec "$@"
