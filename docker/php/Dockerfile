# Nischoyota app container: PHP 8.3 CLI + Composer, served with `php artisan serve`.
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip intl bcmath gd pcntl exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { echo 'upload_max_filesize=8M'; echo 'post_max_size=40M'; echo 'memory_limit=512M'; } > /usr/local/etc/php/conf.d/nischoyota.ini

WORKDIR /var/www/html
