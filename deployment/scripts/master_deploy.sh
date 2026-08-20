#!/bin/bash
set -euo pipefail

# ============================================================
# Script: master_deploy.sh
# Purpose: Orchestrate full deployment of Shenmo LMS
# ============================================================

echo "=========================================="
echo "   Shenmo LMS - Master Deployment Script"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="shenmo_app1"
APP_ROOT="/var/www/$APP_NAME"
DOMAIN="${DOMAIN:-}"
EMAIL="${EMAIL:-}"
DB_NAME="${DB_NAME:-shenmo_app}"
DB_USER="${DB_USER:-shenmo_user}"
DB_PASS="${DB_PASS:-}"

# Prompt for missing configuration
if [ -z "$DOMAIN" ]; then
    read -p "Enter your domain name (e.g., abacusacademy.rw): " DOMAIN
fi

if [ -z "$EMAIL" ]; then
    read -p "Enter your email for SSL notifications: " EMAIL
fi

if [ -z "$DB_PASS" ]; then
    read -sp "Enter database password for $DB_USER: " DB_PASS
    echo
fi

export DOMAIN EMAIL DB_NAME DB_USER DB_PASS

echo ""
echo "Configuration Summary:"
echo "  Domain: $DOMAIN"
echo "  Email: $EMAIL"
echo "  Database: $DB_NAME"
echo "  DB User: $DB_USER"
echo "  App Root: $APP_ROOT"
echo ""

read -p "Continue with deployment? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 0
fi

# Phase 1: Provision LEMP Stack
echo ""
echo -e "${YELLOW}Phase 1/6: Provisioning LEMP Stack...${NC}"
bash deployment/scripts/01_provision_LEMP.sh

# Phase 2: Deploy Application
echo ""
echo -e "${YELLOW}Phase 2/6: Deploying Application...${NC}"
echo "Creating application directory..."
mkdir -p "$APP_ROOT"
mkdir -p "$APP_ROOT/uploads"
mkdir -p "$APP_ROOT/config"
mkdir -p "$APP_ROOT/api"
mkdir -p "$APP_ROOT/database"

echo "Copying application files..."
# Note: In production, use git clone or rsync from your repo
# For now, we assume files are already in place or use git
if [ -d ".git" ]; then
    echo "Git repository detected. Use git to deploy."
    git remote -v || true
else
    echo "Copying local files to $APP_ROOT..."
    cp -r *.php "$APP_ROOT/" 2>/dev/null || true
    cp -r config "$APP_ROOT/" 2>/dev/null || true
    cp -r api "$APP_ROOT/" 2>/dev/null || true
    cp -r database "$APP_ROOT/" 2>/dev/null || true
    cp -r uploads "$APP_ROOT/" 2>/dev/null || true
    cp -r icons "$APP_ROOT/" 2>/dev/null || true
    cp -r includes "$APP_ROOT/" 2>/dev/null || true
    cp -r css "$APP_ROOT/" 2>/dev/null || true
    cp -r js "$APP_ROOT/" 2>/dev/null || true
fi

# Update config files
echo "Updating configuration files..."
cp deployment/production_configs/app.php "$APP_ROOT/config/app.php"
cp deployment/production_configs/mail.php "$APP_ROOT/config/mail.php"
cp deployment/production_configs/database.php "$APP_ROOT/config/database.php"

# Update app.php with domain
sed -i "s|https://your-domain.com|https://$DOMAIN|g" "$APP_ROOT/config/app.php"

# Set ownership
chown -R www-data:www-data "$APP_ROOT"

echo -e "${GREEN}Application deployed.${NC}"

# Phase 3: Database Setup
echo ""
echo -e "${YELLOW}Phase 3/6: Setting up Database...${NC}"
export DB_PASS
bash deployment/scripts/02_setup_database.sh

# Phase 4: Nginx Configuration
echo ""
echo -e "${YELLOW}Phase 4/6: Configuring Nginx...${NC}"
export DOMAIN
bash deployment/scripts/04_setup_nginx.sh

# Phase 5: SSL Certificate
echo ""
echo -e "${YELLOW}Phase 5/6: Setting up SSL...${NC}"
export DOMAIN EMAIL
bash deployment/scripts/05_setup_ssl.sh

# Phase 6: Monitoring & Security
echo ""
echo -e "${YELLOW}Phase 6/6: Installing Monitoring & Security...${NC}"
bash deployment/scripts/06_setup_monitoring.sh
bash deployment/scripts/07_security_hardening.sh

# Final steps
echo ""
echo -e "${GREEN}=========================================="
echo "   Deployment Complete!"
echo "==========================================${NC}"
echo ""
echo "Next Steps:"
echo "1. Verify application at: https://$DOMAIN"
echo "2. Check Netdata at: http://$(curl -s ifconfig.me):19999"
echo "3. Test registration and login flows"
echo "4. Configure SMTP in config/mail.php"
echo "5. Set up UptimeRobot monitoring (see deployment/README.md)"
echo ""
echo "Important Files:"
echo "  - App config: $APP_ROOT/config/"
echo "  - Nginx config: /etc/nginx/sites-available/shenmo"
echo "  - SSL certs: /etc/letsencrypt/live/$DOMAIN/"
echo "  - Logs: /var/log/nginx/"
echo ""
echo "To update credentials later:"
echo "  1. Edit $APP_ROOT/config/database.php"
echo "  2. Run: php $APP_ROOT/deployment/scripts/03_update_credentials.php"
