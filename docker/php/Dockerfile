FROM php:8.3.21-fpm-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libcurl4-openssl-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zlib1g-dev \
    libicu-dev \
    libpng-dev \
    && rm -rf /var/lib/apt/lists/*

# Install and configure PHP extensions required by Laravel 13 & Livewire
RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
    curl \
    mbstring \
    xml \
    dom \
    zip \
    pdo_mysql \
    intl \
    bcmath \
    pcntl \
    opcache

# Copy pinned Composer binary directly from official image
COPY --from=composer:2.8.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Pre-create storage structure and set initial permissions
RUN mkdir -p /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint script
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
