#!/bin/bash
set -euo pipefail

# ============================================================
# Script: 07_security_hardening.sh
# Purpose: Apply security hardening for production
# ============================================================

echo "=== Applying Security Hardening ==="

APP_ROOT="/var/www/shenmo_app1"

# Disable PHP info and exposure
echo "[1/7] Hardening PHP configuration..."
PHP_INI="/etc/php/8.2/fpm/php.ini"
sed -i 's/^expose_php = .*/expose_php = Off/' "$PHP_INI"
sed -i 's/^allow_url_fopen = .*/allow_url_fopen = Off/' "$PHP_INI"
sed -i 's/^display_errors = .*/display_errors = Off/' "$PHP_INI"
sed -i 's/^log_errors = .*/log_errors = On/' "$PHP_INI"
sed -i 's|^error_log = .*|error_log = /var/log/php8.2-fpm.log|' "$PHP_INI"

# Create PHP error log
touch /var/log/php8.2-fpm.log
chown www-data:www-data /var/log/php8.2-fpm.log

# Secure file permissions
echo "[2/7] Setting secure file permissions..."
chown -R www-data:www-data "$APP_ROOT"
find "$APP_ROOT" -type f -exec chmod 644 {} \;
find "$APP_ROOT" -type d -exec chmod 755 {} \;

# Special permissions for uploads (writable but not executable)
if [ -d "$APP_ROOT/uploads" ]; then
    chmod 755 "$APP_ROOT/uploads"
    find "$APP_ROOT/uploads" -type f -exec chmod 644 {} \;
    find "$APP_ROOT/uploads" -type d -exec chmod 755 {} \;
    # Remove execute permission from uploads directory
    chmod 750 "$APP_ROOT/uploads"
fi

# Secure config directory
if [ -d "$APP_ROOT/config" ]; then
    chmod 750 "$APP_ROOT/config"
    find "$APP_ROOT/config" -type f -exec chmod 640 {} \;
fi

# Prevent PHP execution in uploads
echo "[3/7] Preventing PHP execution in uploads..."
cat > "$APP_ROOT/uploads/.htaccess" <<'HTACCESS'
<FilesMatch "\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
HTACCESS

# Setup fail2ban for SSH and web attacks
echo "[4/7] Installing Fail2Ban..."
apt install -y fail2ban

cat > /etc/fail2ban/jail.local <<'EOF'
[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 5
bantime = 3600

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/shenmo_error.log
maxretry = 5

[nginx-badbots]
enabled = true
port = http,https
filter = nginx-badbots
logpath = /var/log/nginx/shenmo_access.log
maxretry = 2
bantime = 86400

[nginx-noscript]
enabled = true
port = http,https
filter = nginx-noscript
logpath = /var/log/nginx/shenmo_access.log
maxretry = 2
EOF

systemctl enable fail2ban
systemctl start fail2ban

# Enable automatic security updates
echo "[5/7] Enabling automatic security updates..."
apt install -y unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades

# Disable unnecessary PHP modules
echo "[6/7] Disabling unnecessary PHP modules..."
phpdismod -v 8.2 -s ALL xdebug pcov || true

# Restart services to apply changes
echo "[7/7] Restarting services..."
systemctl restart php8.2-fpm
systemctl restart nginx

echo "=== Security Hardening Complete ==="
echo "Fail2Ban: Active"
echo "Automatic updates: Enabled"
echo "PHP exposure: Disabled"
