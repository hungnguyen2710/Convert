FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip libpng-dev libonig-dev \
    libxml2-dev libxslt1-dev \
    python3 python3-pip \
    fonts-dejavu \
    default-jre \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install pdf2docx
RUN pip3 install pdf2docx

# PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    exif \
    pcntl

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN chown -R www-data:www-data /var/www

USER www-data