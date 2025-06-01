FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    zip \
    libonig-dev \
    libpq-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
