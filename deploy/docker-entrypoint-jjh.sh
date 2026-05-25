#!/bin/sh
set -e

mkdir -p /var/www/html/storage/facturas/tmp /var/www/html/storage/presupuestos /var/www/html/storage/tmp
chown -R www-data:www-data /var/www/html/storage
chmod -R u+rwX,g+rwX /var/www/html/storage

exec docker-php-entrypoint "$@"
