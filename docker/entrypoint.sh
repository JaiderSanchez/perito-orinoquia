#!/usr/bin/env sh
set -eu

APP_PORT="${PORT:-10000}"

case "${APP_KEY:-}" in
    base64:*) ;;
    '')
        echo "APP_KEY is required" >&2
        exit 1
        ;;
    *)
        APP_KEY="base64:$(php -r 'echo base64_encode(hash("sha256", getenv("APP_KEY"), true));')"
        export APP_KEY
        ;;
esac

sed -ri "s/^Listen .*/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${APP_PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force
php artisan migrate --force
php artisan app:ensure-initial-admin
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
