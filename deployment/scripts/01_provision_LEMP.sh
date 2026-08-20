#!/bin/bash
set -euo pipefail

# ============================================================
# Script: 01_provision_LEMP.sh
# Purpose: Install LEMP stack on Ubuntu 22.04 LTS
# ============================================================

echo "=== Starting LEMP Stack Provisioning ==="

# Update system
echo "[1/8] Updating system packages..."
apt update && apt upgrade -y

# Install base tools
echo "[2/8] Installing base tools..."
apt install -y git curl unzip ufw htop nano software-properties-common

# Add PHP PPA
echo "[3/8] Adding PHP PPA..."
add-apt-repository ppa:ondrej/php -y
apt update

# Install Nginx, MySQL, PHP 8.2 and extensions
echo "[4/8] Installing Nginx, MySQL, PHP 8.2..."
apt install -y \
    nginx \
    mysql-server \
    php8.2 \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-gd \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl

# Secure MySQL
echo "[5/8] Securing MySQL installation..."
mysql_secure_installation <<EOF

y
shenmo_secure_pass
shenmo_secure_pass
y
y
y
y
EOF

# Configure PHP-FPM
echo "[6/8] Configuring PHP-FPM..."
PHP_INI="/etc/php/8.2/fpm/php.ini"
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 50M/' "$PHP_INI"
sed -i 's/^post_max_size = .*/post_max_size = 50M/' "$PHP_INI"
sed -i 's/^max_execution_time = .*/max_execution_time = 120/' "$PHP_INI"
sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
sed -i 's/^session.cookie_httponly = .*/session.cookie_httponly = 1/' "$PHP_INI"
sed -i 's/^session.cookie_secure = .*/session.cookie_secure = 0/' "$PHP_INI"
sed -i 's/^expose_php = .*/expose_php = Off/' "$PHP_INI"

# Create session directory with proper permissions
mkdir -p /var/lib/php/sessions
chown www-data:www-data /var/lib/php/sessions

# Configure firewall
echo "[7/8] Configuring firewall..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# Start and enable services
echo "[8/8] Starting services..."
systemctl enable nginx
systemctl start nginx
systemctl enable mysql
systemctl start mysql
systemctl enable php8.2-fpm
systemctl start php8.2-fpm

echo "=== LEMP Stack Provisioning Complete ==="
echo "PHP version: $(php -v | head -1)"
echo "MySQL version: $(mysql --version)"
echo "Nginx version: $(nginx -v 2>&1)"
