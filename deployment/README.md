# Shenmo LMS - Production Deployment Guide

Complete guide to deploy the Shenmo Learning Management System to a production cloud server.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Manual Deployment Steps](#manual-deployment-steps)
4. [Configuration](#configuration)
5. [Verification](#verification)
6. [Monitoring](#monitoring)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### What You'll Need
- **DigitalOcean account** (or AWS/Azure/GCP)
- **Domain name** (e.g., `abacusacademy.rw`) pointed to your server
- **SSH access** to your server
- **Local copy** of this repository

### Recommended Server Specs
- **Provider**: DigitalOcean Droplet
- **Size**: 2GB RAM / 2 vCPU / 50GB SSD
- **OS**: Ubuntu 22.04 LTS
- **Monthly Cost**: ~$24-30/month

---

## Quick Start

### Option A: Automated Deployment (Recommended)

```bash
# 1. SSH into your server
ssh root@YOUR_SERVER_IP

# 2. Clone or upload this repository
git clone YOUR_REPO_URL /root/shenmo_deploy
cd /root/shenmo_deploy

# 3. Run the master deployment script
chmod +x deployment/scripts/*.sh
bash deployment/scripts/master_deploy.sh
```

### Option B: Manual Step-by-Step

Follow the manual steps below if you prefer more control.

---

## Manual Deployment Steps

### Step 1: Provision Server

1. Create a new Droplet on DigitalOcean:
   - Image: Ubuntu 22.04 LTS
   - Size: 2GB RAM / 2 vCPU
   - Region: Closest to your users (e.g., Frankfurt for Rwanda)
   - Authentication: SSH key (recommended) or password

2. Note your server's public IP address

3. SSH into the server:
   ```bash
   ssh root@YOUR_SERVER_IP
   ```

### Step 2: Provision LEMP Stack

```bash
# Update system
apt update && apt upgrade -y

# Install base tools
apt install -y git curl unzip ufw htop software-properties-common

# Add PHP PPA
add-apt-repository ppa:ondrej/php -y
apt update

# Install LEMP stack
apt install -y nginx mysql-server php8.2 php8.2-fpm \
  php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl \
  php8.2-gd php8.2-zip php8.2-bcmath php8.2-intl

# Secure MySQL (follow prompts)
mysql_secure_installation

# Configure firewall
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# Start services
systemctl enable nginx mysql php8.2-fpm
systemctl start nginx mysql php8.2-fpm
```

### Step 3: Create Database

```bash
# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE shenmo_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shenmo_user'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';
GRANT ALL PRIVILEGES ON shenmo_app.* TO 'shenmo_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 4: Deploy Application Files

```bash
# Create application directory
mkdir -p /var/www/shenmo_app1

# Upload files (choose one method):
# Method A: Git clone
git clone YOUR_REPO_URL /var/www/shenmo_app1

# Method B: SCP from local machine
# On your LOCAL machine:
# scp -r /path/to/shenmo_app1/* root@YOUR_SERVER_IP:/var/www/shenmo_app1/

# Set permissions
chown -R www-data:www-data /var/www/shenmo_app1
chmod -R 755 /var/www/shenmo_app1
chmod -R 750 /var/www/shenmo_app1/config
chmod 750 /var/www/shenmo_app1/uploads
```

### Step 5: Update Configuration

**CRITICAL**: Update these files before proceeding:

#### config/app.php
```php
<?php
return [
    'app_url' => 'https://your-domain.com',  // CHANGE THIS
    'app_name' => 'Abacus Academy',
];
```

#### config/mail.php
```php
<?php
return [
    'from_email' => 'noreply@your-domain.com',
    'from_name' => 'Abacus Academy',
    'use_smtp' => true,
    'smtp_host' => 'smtp.sendgrid.net',  // Or your SMTP provider
    'smtp_port' => 587,
    'smtp_username' => 'apikey',
    'smtp_password' => 'YOUR_SENDGRID_API_KEY',
    'smtp_encryption' => 'tls',
];
```

#### config/database.php
```php
<?php
return [
    'host' => 'localhost',
    'dbname' => 'shenmo_app',
    'user' => 'shenmo_user',
    'pass' => 'YourSecurePassword123!',  // CHANGE THIS
    'charset' => 'utf8mb4',
];
```

### Step 6: Update Database Credentials in PHP Files

The application has inline database credentials in 36+ PHP files. Run this script to centralize them:

```bash
cd /var/www/shenmo_app1

# First, ensure config/database.php has correct credentials
# Then run:
php deployment/scripts/03_update_credentials.php

# This will update all PHP files to use config/database.php
```

**Alternative**: Manually edit each PHP file's database connection section:
```php
// Replace these lines:
$host = "localhost";
$dbname = "shenmo_app";
$user = "root";
$pass = "";

// With:
require_once __DIR__ . '/config/database.php';
$config = require __DIR__ . '/config/database.php';

// And replace:
$conn = new mysqli($host, $user, $pass, $dbname);

// With:
$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);
```

### Step 7: Migrate Database Schema

```bash
cd /var/www/shenmo_app1

# Run schema migrations
mysql -u shenmo_user -p shenmo_app < database/lms_schema.sql
mysql -u shenmo_user -p shenmo_app < database/payment_course_schema.sql

# Verify tables created
mysql -u shenmo_user -p shenmo_app -e "SHOW TABLES;"
```

### Step 8: Configure Nginx

```bash
# Copy Nginx configuration
cp deployment/nginx/shenmo /etc/nginx/sites-available/shenmo

# Edit the configuration with your domain
nano /etc/nginx/sites-available/shenmo

# Update these lines:
# server_name your-domain.com www.your-domain.com;
# root /var/www/shenmo_app1;

# Enable site
ln -s /etc/nginx/sites-available/shenmo /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload
nginx -t && systemctl reload nginx
```

### Step 9: Configure SSL with Let's Encrypt

```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Obtain certificate
certbot --nginx -d your-domain.com -d www.your-domain.com \
    --email admin@your-domain.com \
    --agree-tos \
    --no-eff-email \
    --redirect

# Verify auto-renewal
certbot renew --dry-run
```

### Step 10: Configure PHP

```bash
# Edit PHP configuration
nano /etc/php/8.2/fpm/php.ini
```

Update these settings:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 120
memory_limit = 256M
session.cookie_httponly = 1
session.cookie_secure = 1
expose_php = Off
display_errors = Off
log_errors = On
```

Restart PHP-FPM:
```bash
systemctl restart php8.2-fpm
```

### Step 11: Install Monitoring

```bash
# Install Netdata
bash <(curl -Ss https://my-netdata.io/kickstart.sh)

# Verify Netdata is running
curl http://localhost:19999/api/v1/info
```

Access Netdata dashboard at: `http://YOUR_SERVER_IP:19999`

### Step 12: Security Hardening

```bash
# Install Fail2Ban
apt install -y fail2ban

# Enable automatic security updates
apt install -y unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades

# Secure uploads directory
chmod 750 /var/www/shenmo_app1/uploads

# Restart services
systemctl restart php8.2-fpm nginx
```

---

## Configuration

### Environment Variables (Recommended)

Instead of hardcoding credentials, use environment variables:

```bash
# /etc/environment
DB_HOST=localhost
DB_NAME=shenmo_app
DB_USER=shenmo_user
DB_PASS=YourSecurePassword123!
APP_URL=https://your-domain.com
SMTP_HOST=smtp.sendgrid.net
SMTP_USER=apikey
SMTP_PASS=YOUR_API_KEY
```

Update `config/database.php` to read from environment:
```php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'shenmo_app';
$db_user = getenv('DB_USER') ?: 'shenmo_user';
$db_pass = getenv('DB_PASS') ?: 'YOUR_SECURE_DB_PASSWORD';
```

### SMTP Configuration

Choose one of these SMTP providers:

#### SendGrid (Recommended)
```php
'smtp_host' => 'smtp.sendgrid.net',
'smtp_port' => 587,
'smtp_username' => 'apikey',
'smtp_password' => 'SG.YOUR_API_KEY',
'smtp_encryption' => 'tls',
```

#### Mailgun
```php
'smtp_host' => 'smtp.mailgun.org',
'smtp_port' => 587,
'smtp_username' => 'postmaster@your-domain.com',
'smtp_password' => 'YOUR_MAILGUN_PASSWORD',
'smtp_encryption' => 'tls',
```

#### Gmail (for testing only)
```php
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@gmail.com',
'smtp_password' => 'your-app-password',
'smtp_encryption' => 'tls',
```

---

## Verification

### Verify Installation

```bash
# Check services are running
systemctl status nginx mysql php8.2-fpm

# Test PHP
php -v

# Test MySQL connection
mysql -u shenmo_user -p -e "SELECT 1"

# Test Nginx configuration
nginx -t

# Check file permissions
ls -la /var/www/shenmo_app1/
```

### Test Application

1. Navigate to `https://your-domain.com`
2. You should see the login page
3. Try registering a new account
4. Verify email delivery
5. Login and test core features

### Test SSL

```bash
# Check SSL certificate
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Test SSL Labs (online tool)
# Visit: https://www.ssllabs.com/ssltest/
```

---

## Monitoring

### Netdata Dashboard
- URL: `http://YOUR_SERVER_IP:19999`
- Monitor: CPU, RAM, Disk, Network, Nginx, PHP-FPM, MySQL

### Application Health
- URL: `https://your-domain.com/health.php`
- Returns JSON with health status

### Application Metrics
- URL: `https://your-domain.com/api/metrics.php`
- Returns business metrics (users, payments, etc.)

### UptimeRobot Setup
See `deployment/monitoring/uptimerobot_setup.md` for detailed instructions.

### Log Monitoring
```bash
# Real-time error monitoring
tail -f /var/log/nginx/shenmo_error.log | grep --line-buffered PHP

# Access log analysis
tail -f /var/log/nginx/shenmo_access.log | awk '{print $1}' | sort | uniq -c | sort -rn | head -10
```

---

## Troubleshooting

### 502 Bad Gateway
```bash
# Check PHP-FPM is running
systemctl status php8.2-fpm

# Check PHP-FPM socket
ls -la /var/run/php/php8.2-fpm.sock

# Check Nginx error log
tail -f /var/log/nginx/shenmo_error.log
```

### Database Connection Failed
```bash
# Verify MySQL is running
systemctl status mysql

# Check credentials
mysql -u shenmo_user -p -e "SELECT 1"

# Check database exists
mysql -u shenmo_user -p -e "SHOW DATABASES;"
```

### Permission Denied
```bash
# Fix file permissions
chown -R www-data:www-data /var/www/shenmo_app1
chmod -R 755 /var/www/shenmo_app1
chmod -R 750 /var/www/shenmo_app1/config
chmod 750 /var/www/shenmo_app1/uploads
```

### SSL Certificate Issues
```bash
# Check certificate status
certbot certificates

# Renew manually if needed
certbot renew

# Check Nginx SSL config
nginx -T | grep -A 10 "listen 443"
```

### Slow Performance
```bash
# Check Netdata for resource usage
# Look for high CPU, RAM, or I/O wait

# Enable MySQL slow query log
mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON';"

# Check for slow queries
tail -f /var/log/mysql/slow.log
```

### Email Not Sending
```bash
# Test SMTP from command line
php -r "
require '/var/www/shenmo_app1/config/mail.php';
\$config = require '/var/www/shenmo_app1/config/mail.php';
print_r(\$config);
"

# Check mail log
tail -f /var/log/mail.log
```

---

## Maintenance

### Regular Tasks

**Daily**:
- Check UptimeRobot for downtime alerts
- Review error logs

**Weekly**:
- Review Netdata metrics for trends
- Check disk space: `df -h`
- Review slow query log

**Monthly**:
- Update system packages: `apt update && apt upgrade -y`
- Review and rotate logs
- Test backup restoration

### Backup Script

```bash
#!/bin/bash
# /etc/cron.daily/shenmo-backup

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/shenmo"
mkdir -p "$BACKUP_DIR"

# Database backup
mysqldump -u shenmo_user -p'YourSecurePassword123!' shenmo_app > "$BACKUP_DIR/db_$DATE.sql"

# File backup
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /var/www/shenmo_app1/uploads

# Keep only last 30 days
find "$BACKUP_DIR" -name "*.sql" -mtime +30 -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete
```

---

## Support

For issues or questions:
1. Check `deployment/TESTING_CHECKLIST.md` for validation steps
2. Review Netdata metrics for performance issues
3. Check application logs in `/var/log/nginx/`

---

**Last Updated**: 2026-08-01  
**Version**: 1.0  
**Status**: Production Ready
