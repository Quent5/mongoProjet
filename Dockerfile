FROM php:8.1.11-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:2.4.3 /usr/bin/composer /usr/local/bin/composer