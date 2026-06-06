# Stage 0: Composer binary
FROM composer:2 AS composer

# Stage 1: Install PHP dependencies
FROM php:8.4-cli-alpine AS php-builder

# Install system and build dependencies for PHP extensions
RUN apk add --no-cache \
    curl zip unzip \
    icu-dev libzip-dev \
    $PHPIZE_DEPS

# Build and enable required PHP extensions
RUN docker-php-ext-install -j"$(nproc)" intl zip

# Add composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# (optional but nice-to-have)
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1

WORKDIR /var/www

# Copy composer files first for layer caching
COPY composer.json composer.lock ./

COPY app/helpers.php app/helpers.php

# Install PHP dependencies
# Skip scripts so @php artisan package:discover doesn't run here (artisan not copied in this stage)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist --no-scripts

# Stage 2: Build assets
FROM node:18-alpine AS asset-builder
WORKDIR /var/www

# Install Node deps (include devDependencies for build)
COPY package*.json ./
RUN npm ci

# Copy source and vendor
# COPY . .
COPY --from=php-builder /var/www/vendor ./vendor

# RUN --mount=type=cache,target=/root/.npm \
#     npm ci --no-audit --no-fund

# Copy only the assets we need to build

COPY vite.config.* tailwind.config.* postcss.config.* ./
COPY resources/ resources/
COPY public/ public/
# Build
RUN npm run build

# Stage 3: Caddy production image
FROM caddy:alpine

# Copy custom Caddy configuration
COPY ./docker/staging/caddy/Caddyfile /etc/caddy/Caddyfile
COPY ./docker/common/caddy/Caddyfile.common /etc/caddy/Caddyfile.common

# Copy Laravel's public assets from the builder stage
# -----------------------------------------------------------
# We only need the 'public' directory from our Laravel app.
# -----------------------------------------------------------
COPY --from=asset-builder /var/www/public /var/www/public

# Create symlink for storage -> public (target will be provided by a runtime volume)
RUN rm -rf /var/www/public/storage \
 && ln -s /var/www/storage/app/public /var/www/public/storage
 
# Set the working directory to the public folder
WORKDIR /var/www/public

# Expose port 80 and start Caddy
EXPOSE 80
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]