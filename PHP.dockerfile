FROM composer:latest as composer_stage

RUN rm -rf /composer && mkdir -p /composer
WORKDIR /composer
COPY /app/composer.* .
RUN composer install --ignore-platform-reqs --prefer-dist --no-scripts --no-progress --no-interaction --no-dev --no-autoloader
RUN composer dump-autoload --optimize --apcu --no-dev

FROM php:8.2-fpm-alpine
RUN apk add autoconf gcc make g++ zlib-dev linux-headers libzip-dev zip

RUN docker-php-ext-install pdo pdo_mysql zip

RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY --from=composer_stage /composer /composer
