#!/bin/bash
set -euo pipefail

# ============================================================
# Script: quick_deploy.sh
# Purpose: Quick deployment for updates (pull code + reload)
# ============================================================

APP_ROOT="/var/www/shenmo_app1"

echo "=== Quick Deploy: Updating Application ==="

# Check if git repository
if [ -d "$APP_ROOT/.git" ]; then
    echo "Pulling latest code..."
    cd "$APP_ROOT"
    git pull origin main || git pull origin master
else
    echo "WARNING: Not a git repository. Skipping git pull."
    echo "Manually upload updated files to $APP_ROOT"
fi

# Update permissions
echo "Updating permissions..."
chown -R www-data:www-data "$APP_ROOT"

# Reload services
echo "Reloading services..."
systemctl reload php8.2-fpm
systemctl reload nginx

# Run database migrations if migrate.php exists
if [ -f "$APP_ROOT/migrate.php" ]; then
    echo "Running database migrations..."
    php "$APP_ROOT/migrate.php" || echo "Migration failed or not needed"
fi

echo "=== Quick Deploy Complete ==="
echo "Application updated and services reloaded."
