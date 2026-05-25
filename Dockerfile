FROM php:8.2-apache

ENV APP_ENV=production

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        git \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" mysqli curl gd mbstring exif zip \
    && rm -rf /var/lib/apt/lists/*

RUN a2dismod mpm_event mpm_worker || true

RUN a2enmod mpm_prefork headers rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && printf '%s\n' \
        '<Directory "/var/www/html/storage">' \
        '    Require all denied' \
        '</Directory>' \
        '<Directory "/var/www/html/includes">' \
        '    Require all denied' \
        '</Directory>' \
        '<Directory "/var/www/html/plantillas">' \
        '    Require all denied' \
        '</Directory>' \
        '<Directory "/var/www/html/sql">' \
        '    Require all denied' \
        '</Directory>' \
        '<Directory "/var/www/html/vendor">' \
        '    Require all denied' \
        '</Directory>' \
        '<Directory "/var/www/html/deploy">' \
        '    Require all denied' \
        '</Directory>' \
        > /etc/apache2/conf-available/jjh-interno.conf \
    && a2enconf jjh-interno

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY . .

RUN mkdir -p storage/facturas storage/presupuestos storage/tmp storage/logs \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage \
    && chmod +x deploy/docker-entrypoint-jjh.sh

EXPOSE 80

ENTRYPOINT ["/var/www/html/deploy/docker-entrypoint-jjh.sh"]
CMD ["apache2-foreground"]
