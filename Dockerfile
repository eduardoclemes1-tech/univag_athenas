FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && mkdir -p /var/athenas_uploads \
    && chown www-data:www-data /var/athenas_uploads

COPY php.ini /usr/local/etc/php/conf.d/athenas.ini

WORKDIR /var/www/html
