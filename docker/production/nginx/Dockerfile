# Stage 1: Build assets
#
# Unlike a plain Node image, this stage is based on PHP because the
# frontend build runs `php artisan wayfinder:generate` (via
# @laravel/vite-plugin-wayfinder) and therefore requires PHP, Composer
# and the installed Composer dependencies.
FROM php:8.5-cli AS builder

# Install system dependencies, the PHP extensions Laravel needs to boot
# Artisan, and Node.js / npm to build the frontend assets
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    unzip \
    git \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    nodejs \
    npm \
    && docker-php-ext-install -j$(nproc) \
    intl \
    zip \
    bcmath \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www

# Copy Laravel application code
COPY . /var/www

# Install PHP dependencies (required so `php artisan wayfinder:generate` can run)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist --ignore-platform-req=ext-gd

# Install Node.js dependencies and build assets
RUN npm install && npm run build

# Stage 2: Nginx production image
FROM nginx:alpine

# Copy custom Nginx configuration
# -----------------------------------------------------------
# Replace the default Nginx configuration with our custom one
# that is optimized for serving a Laravel application.
# -----------------------------------------------------------
COPY ./docker/production/nginx/nginx.conf /etc/nginx/nginx.conf

# Copy Laravel's public assets from the builder stage
# -----------------------------------------------------------
# We only need the 'public' directory from our Laravel app.
# -----------------------------------------------------------
COPY --from=builder /var/www/public /var/www/public

# Set the working directory to the public folder
WORKDIR /var/www/public

# Expose port 80 and start Nginx
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
