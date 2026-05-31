#!/bin/sh
set -eu

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force
php artisan db:seed --class=ReferenceDataSeeder --force

if [ "${PRODUCTION_DEMO_SEED_ENABLED:-false}" = "true" ]; then
    php artisan db:seed --class=ProductionDemoSeeder --force
fi

PORT_VALUE="${PORT:-10000}"

sed -ri "s/^Listen .*/Listen ${PORT_VALUE}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT_VALUE}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
