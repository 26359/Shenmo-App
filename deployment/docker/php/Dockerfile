FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    mysql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    gmp-dev \
    bash \
    curl \
    git \
    unzip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        xml \
        dom \
        gmp

# Configure PHP
COPY deployment/docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure Nginx
COPY deployment/docker/nginx/conf.d/default.conf /etc/nginx/http.d/default.conf

# Create required directories
RUN mkdir -p /var/www/shenmo_app1 \
    && mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/www/shenmo_app1 /var/lib/php/sessions

# Copy application
COPY . /var/www/shenmo_app1

# Set permissions
RUN chown -R www-data:www-data /var/www/shenmo_app1 \
    && chmod -R 755 /var/www/shenmo_app1 \
    && chmod -R 750 /var/www/shenmo_app1/config \
    && chmod 750 /var/www/shenmo_app1/uploads

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

# Expose ports
EXPOSE 80

# Start services
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
