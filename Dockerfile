# Step 1: Node stage for compiling assets
FROM node:22-alpine AS asset-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources/ ./resources/
COPY vite.config.js tailwind.config.js* ./
# Compiles assets into public/build
RUN npm run build 

# --- Build Stage ---
FROM php:8.5-fpm-alpine AS builder

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite-dev \
    oniguruma-dev

# Install PHP extensions needed for Laravel
RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring xml bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Run composer installation for production optimization
RUN composer install --no-interaction --optimize-autoloader --no-dev --prefer-dist

# --- Production Stage ---
FROM php:8.5-fpm-alpine

RUN apk add --no-cache libpng libxml2 icu-dev \
    icu-libs \
    icu-data-full \
    g++

# intl
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# Install the extension via a single command
RUN install-php-extensions intl

RUN docker-php-ext-install pdo_mysql bcmath

WORKDIR /var/www

# Copy codebase from builder stage
COPY --from=builder /app /var/www
# Copy compiled assets from Step 1
#COPY --from=asset-builder /app/public/build/ ./public/build/
COPY --from=asset-builder /app/public/build/ /var/www/html/public/build/

# Set appropriate directory ownership permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database \
    && chmod -R 775 /var/www/database

# Cache configuration, routes, and views for lightning-fast performance
RUN php artisan route:cache && \
    php artisan view:cache && \
    php artisan config:cache

USER www-data

EXPOSE 9000

# Default execution layer targets the web server process
CMD ["php-fpm"]
