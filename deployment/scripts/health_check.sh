#!/bin/bash
set -euo pipefail

# ============================================================
# Script: health_check.sh
# Purpose: Comprehensive health check of production server
# ============================================================

echo "=== Shenmo LMS - Production Health Check ==="
echo "Date: $(date)"
echo ""

ERRORS=0
WARNINGS=0

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

check() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}[OK]${NC} $2"
    else
        echo -e "${RED}[FAIL]${NC} $2"
        ((ERRORS++))
    fi
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    ((WARNINGS++))
}

info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

# 1. Check services
echo "--- Services ---"
systemctl is-active --quiet nginx && check 0 "Nginx is running" || check 1 "Nginx is running"
systemctl is-active --quiet mysql && check 0 "MySQL is running" || check 1 "MySQL is running"
systemctl is-active --quiet php8.2-fpm && check 0 "PHP-FPM is running" || check 1 "PHP-FPM is running"

# 2. Check disk space
echo ""
echo "--- Disk Space ---"
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 90 ]; then
    warn "Disk usage is at ${DISK_USAGE}%"
elif [ "$DISK_USAGE" -gt 80 ]; then
    warn "Disk usage is at ${DISK_USAGE}% (consider cleanup)"
else
    check 0 "Disk usage: ${DISK_USAGE}%"
fi

# 3. Check memory
echo ""
echo "--- Memory ---"
MEM_USED=$(free -m | awk 'NR==2{print $3}')
MEM_TOTAL=$(free -m | awk 'NR==2{print $2}')
MEM_PERCENT=$((MEM_USED * 100 / MEM_TOTAL))
if [ "$MEM_PERCENT" -gt 90 ]; then
    warn "Memory usage is at ${MEM_PERCENT}%"
else
    check 0 "Memory usage: ${MEM_USED}MB / ${MEM_TOTAL}MB (${MEM_PERCENT}%)"
fi

# 4. Check database connectivity
echo ""
echo "--- Database ---"
if mysql -u shenmo_user -p"${DB_PASS:-YOUR_SECURE_DB_PASSWORD}" -e "SELECT 1" shenmo_app >/dev/null 2>&1; then
    check 0 "Database connection successful"
    
    # Check table count
    TABLE_COUNT=$(mysql -u shenmo_user -p"${DB_PASS:-YOUR_SECURE_DB_PASSWORD}" shenmo_app -e "SHOW TABLES;" | wc -l)
    info "Tables in database: $TABLE_COUNT"
    
    # Check for slow queries
    SLOW_QUERIES=$(mysql -u shenmo_user -p"${DB_PASS:-YOUR_SECURE_DB_PASSWORD}" shenmo_app -e "SHOW STATUS LIKE 'Slow_queries';" | tail -1 | awk '{print $2}')
    if [ "$SLOW_QUERIES" -gt 100 ]; then
        warn "Slow queries: $SLOW_QUERIES (check slow query log)"
    else
        check 0 "Slow queries: $SLOW_QUERIES"
    fi
else
    check 1 "Database connection"
fi

# 5. Check application files
echo ""
echo "--- Application ---"
APP_ROOT="/var/www/shenmo_app1"
if [ -d "$APP_ROOT" ]; then
    check 0 "Application directory exists"
    
    # Check key files
    for file in index.php login.php config/database.php config/app.php; do
        if [ -f "$APP_ROOT/$file" ]; then
            check 0 "File exists: $file"
        else
            check 1 "File exists: $file"
        fi
    done
    
    # Check uploads directory
    if [ -d "$APP_ROOT/uploads" ]; then
        check 0 "Uploads directory exists"
        UPLOADS_PERMS=$(stat -c "%a" "$APP_ROOT/uploads")
        if [ "$UPLOADS_PERMS" = "750" ] || [ "$UPLOADS_PERMS" = "755" ]; then
            check 0 "Uploads permissions: $UPLOADS_PERMS"
        else
            warn "Uploads permissions: $UPLOADS_PERMS (should be 750 or 755)"
        fi
    else
        check 1 "Uploads directory exists"
    fi
else
    check 1 "Application directory exists"
fi

# 6. Check Nginx configuration
echo ""
echo "--- Nginx ---"
if nginx -t >/dev/null 2>&1; then
    check 0 "Nginx configuration valid"
else
    check 1 "Nginx configuration valid"
    nginx -t
fi

# Check if site is enabled
if [ -L "/etc/nginx/sites-enabled/shenmo" ]; then
    check 0 "Nginx site enabled"
else
    warn "Nginx site 'shenmo' not enabled"
fi

# 7. Check SSL certificate
echo ""
echo "--- SSL Certificate ---"
DOMAIN=$(grep -oP 'server_name \K[^;]+' /etc/nginx/sites-available/shenmo 2>/dev/null | head -1 | awk '{print $1}')
if [ -n "$DOMAIN" ] && [ "$DOMAIN" != "your-domain.com" ]; then
    if [ -d "/etc/letsencrypt/live/$DOMAIN" ]; then
        check 0 "SSL certificate exists for $DOMAIN"
        
        # Check expiry
        EXPIRY=$(openssl x509 -in "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" -noout -enddate 2>/dev/null | cut -d= -f2)
        if [ -n "$EXPIRY" ]; then
            EXPIRY_EPOCH=$(date -d "$EXPIRY" +%s)
            NOW_EPOCH=$(date +%s)
            DAYS_LEFT=$(( (EXPIRY_EPOCH - NOW_EPOCH) / 86400 ))
            
            if [ "$DAYS_LEFT" -lt 7 ]; then
                warn "SSL certificate expires in $DAYS_LEFT days"
            elif [ "$DAYS_LEFT" -lt 30 ]; then
                warn "SSL certificate expires in $DAYS_LEFT days"
            else
                check 0 "SSL certificate valid for $DAYS_LEFT more days"
            fi
        fi
    else
        warn "SSL certificate not found for $DOMAIN"
    fi
else
    warn "Could not determine domain from Nginx config"
fi

# 8. Check firewall
echo ""
echo "--- Firewall ---"
if command -v ufw &> /dev/null; then
    UFW_STATUS=$(ufw status | head -1)
    if echo "$UFW_STATUS" | grep -q "active"; then
        check 0 "Firewall is active"
    else
        warn "Firewall is not active"
    fi
else
    warn "UFW not installed"
fi

# 9. Check fail2ban
echo ""
echo "--- Fail2Ban ---"
if systemctl is-active --quiet fail2ban; then
    check 0 "Fail2Ban is running"
    JAILS=$(fail2ban-client status 2>/dev/null | grep "Jail list" | cut -d: -f2)
    info "Active jails: $JAILS"
else
    warn "Fail2Ban is not running"
fi

# 10. Summary
echo ""
echo "=== Health Check Summary ==="
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}All checks passed!${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}Passed with $WARNINGS warnings${NC}"
    exit 0
else
    echo -e "${RED}Failed with $ERRORS errors and $WARNINGS warnings${NC}"
    exit 1
fi
