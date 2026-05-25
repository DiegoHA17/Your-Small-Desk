#!/bin/sh
set -e

mkdir -p /var/www/html/storage/facturas /var/www/html/storage/presupuestos /var/www/html/storage/tmp /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Garantiza un unico MPM tambien en el arranque del contenedor.
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
a2enmod mpm_prefork >/dev/null 2>&1
apache2ctl configtest

exec docker-php-entrypoint "$@"
