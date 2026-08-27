# Production Dockerfile for Laravel with Pre-built Assets
FROM php:8.3-fpm-alpine

# Install system dependencies & build requirements with parallel compilation
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    sqlite-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_sqlite mbstring gd zip bcmath intl opcache pcntl posix

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Composer environment
ARG GITHUB_TOKEN
ENV GITHUB_TOKEN=$GITHUB_TOKEN \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_PROCESS_TIMEOUT=600 \
    COMPOSER_MEMORY_LIMIT=-1

WORKDIR /var/www/html

# Copy composer files first for optimal Docker layer caching
COPY composer.json composer.lock ./

# Install composer dependencies with parallel dist download
RUN if [ -n "$GITHUB_TOKEN" ]; then composer config -g github-oauth.github.com "$GITHUB_TOKEN"; fi \
    && composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --prefer-dist --no-scripts --no-progress

# Copy remaining application code
COPY . .

# Generate optimized classmap & autoloader
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# Set directory permissions
RUN mkdir -p /var/www/html/database \
    && touch /var/www/html/database/database.sqlite \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Copy Nginx configuration
COPY .docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy entrypoint script
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

