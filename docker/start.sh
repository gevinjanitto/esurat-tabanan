#!/bin/sh
set -e
mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --force
php artisan schedule:work > storage/logs/scheduler.log 2>&1 &
exec frankenphp php-server --root public --listen ":${PORT:-8080}"
