FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip libpng-dev libonig-dev \
    libxml2-dev libxslt1-dev \
    python3 python3-pip python3-dev \
    build-essential \
    libgl1 \
    fonts-dejavu \
    default-jre \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Upgrade pip (QUAN TRỌNG)
RUN pip3 install --upgrade pip

# Install pdf2docx + dependencies
RUN pip3 install numpy
RUN pip3 install opencv-python-headless
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