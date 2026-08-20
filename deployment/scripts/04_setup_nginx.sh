#!/bin/bash
set -euo pipefail

# ============================================================
# Script: 04_setup_nginx.sh
# Purpose: Configure Nginx virtual host for shenmo_app1
# ============================================================

echo "=== Setting up Nginx Configuration ==="

APP_NAME="shenmo"
APP_ROOT="/var/www/shenmo_app1"
DOMAIN="${DOMAIN:-your-domain.com}"

if [ "$DOMAIN" = "your-domain.com" ]; then
    read -p "Enter your domain name (e.g., abacusacademy.rw): " DOMAIN
fi

# Create Nginx site configuration
echo "[1/3] Creating Nginx site configuration..."
cat > /etc/nginx/sites-available/$APP_NAME <<'NGINX_CONF'
server {
    listen 80;
    server_name DOMAIN_PLACEHOLDER www.DOMAIN_PLACEHOLDER;
    root APP_ROOT_PLACEHOLDER;
    index index.php;

    client_max_body_size 50M;
    client_body_timeout 300s;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_index index.php;
        
        # Timeouts
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 120s;
        fastcgi_read_timeout 120s;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to sensitive files
    location ~* \.(env|log|sql|md|gitignore|gitattributes|htaccess)$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        access_log off;
    }

    # Uploads directory - serve files but prevent script execution
    location ~* ^/uploads/.*\.php$ {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json application/xml image/svg+xml;
    gzip_comp_level 6;
}
NGINX_CONF

# Replace placeholders
sed -i "s|DOMAIN_PLACEHOLDER|$DOMAIN|g" /etc/nginx/sites-available/$APP_NAME
sed -i "s|APP_ROOT_PLACEHOLDER|$APP_ROOT|g" /etc/nginx/sites-available/$APP_NAME

# Add www redirect if needed
# Note: The main config handles both domain and www.domain

# Enable site
echo "[2/3] Enabling site..."
ln -sf /etc/nginx/sites-available/$APP_NAME /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload
echo "[3/3] Testing and reloading Nginx..."
nginx -t && systemctl reload nginx

echo "=== Nginx Configuration Complete ==="
echo "Domain: $DOMAIN"
echo "Root: $APP_ROOT"
echo "Test URL: http://$DOMAIN"
