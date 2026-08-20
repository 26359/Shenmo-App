FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx \
    mysql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    gmp-dev \
    bash \
    curl \
    freetype-dev

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

# Copy app
COPY . /var/www/html

# Nginx config
RUN echo 'server { \
    listen $PORT default_server; \
    root /var/www/html; \
    index login.php index.php; \
    client_max_body_size 50M; \
    location / { try_files $uri $uri/ /login.php?$query_string; } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}' > /etc/nginx/http.d/default.conf

# Startup script
RUN echo '#!/bin/sh' > /start.sh && \
    echo 'sed -i "s/\$PORT/$PORT/g" /etc/nginx/http.d/default.conf' >> /start.sh && \
    echo 'php-fpm -D' >> /start.sh && \
    echo 'nginx -g "daemon off;"' >> /start.sh && \
    chmod +x /start.sh

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/uploads/homework \
        /var/www/html/uploads/certificates \
        /var/www/html/uploads/submissions \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 8080

CMD ["sh", "/start.sh"]
