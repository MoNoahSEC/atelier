FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.3-fpm-bookworm
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y --no-install-recommends $PHPIZE_DEPS libzip-dev libicu-dev libpng-dev libonig-dev unzip \
    && docker-php-ext-install pdo_mysql bcmath intl mbstring gd zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY deploy/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY deploy/docker/entrypoint.sh /usr/local/bin/atelier-entrypoint
RUN chmod +x /usr/local/bin/atelier-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 9000
ENTRYPOINT ["atelier-entrypoint"]
CMD ["php-fpm"]
