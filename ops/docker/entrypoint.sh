#!/bin/sh
set -e

# Render (and most PaaS) inject the listening port through $PORT.
PORT="${PORT:-8080}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "${APP_KEY}" ]; then
    echo "APP_KEY is not set. Generate one locally with: php artisan key:generate --show" >&2
    exit 1
fi

# Cache config from the real environment variables (no .env file in the image).
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations are opt-in: the Supabase schema is normally migrated with the
# migration role from a workstation (see ops/README.md).
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
