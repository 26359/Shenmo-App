#!/bin/bash
set -euo pipefail

# ============================================================
# Script: 05_setup_ssl.sh
# Purpose: Configure Let's Encrypt SSL certificate
# ============================================================

echo "=== Setting up SSL with Let's Encrypt ==="

DOMAIN="${DOMAIN:-your-domain.com}"
EMAIL="${EMAIL:-admin@your-domain.com}"

if [ "$DOMAIN" = "your-domain.com" ]; then
    read -p "Enter your domain name: " DOMAIN
fi

if [ "$EMAIL" = "admin@your-domain.com" ]; then
    read -p "Enter your email for SSL notifications: " EMAIL
fi

# Install Certbot
echo "[1/3] Installing Certbot..."
apt install -y certbot python3-certbot-nginx

# Obtain certificate
echo "[2/3] Obtaining SSL certificate for $DOMAIN..."
certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" \
    --email "$EMAIL" \
    --agree-tos \
    --no-eff-email \
    --redirect \
    --hsts

# Setup auto-renewal
echo "[3/3] Verifying auto-renewal..."
certbot renew --dry-run

echo "=== SSL Setup Complete ==="
echo "HTTPS URL: https://$DOMAIN"
echo "Certificate will auto-renew via systemd timer"
