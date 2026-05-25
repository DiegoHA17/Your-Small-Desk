#!/bin/sh
set -e

mkdir -p /var/www/html/storage/facturas /var/www/html/storage/presupuestos /var/www/html/storage/tmp /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

exec docker-php-entrypoint "$@"
