#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [[ "${RUN_MIGRATIONS:-false}" == "true" ]]; then
  php artisan migrate --force
fi

if [[ "${RUN_OPTIMIZE:-true}" == "true" ]]; then
  php artisan optimize:clear
  php artisan optimize
fi

exec "$@"
