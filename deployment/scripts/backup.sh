#!/bin/bash
set -euo pipefail

# ============================================================
# Script: backup.sh
# Purpose: Automated backup of database and files
# ============================================================

BACKUP_DIR="/backups/shenmo"
APP_ROOT="/var/www/shenmo_app1"
DATE=$(date +%Y%m%d_%H%M%S)

# Load database credentials
DB_USER="shenmo_user"
DB_NAME="shenmo_app"
DB_PASS="${DB_PASS:-YOUR_SECURE_DB_PASSWORD}"

if [ "$DB_PASS" = "YOUR_SECURE_DB_PASSWORD" ]; then
    read -sp "Enter database password for $DB_USER: " DB_PASS
    echo
fi

mkdir -p "$BACKUP_DIR"

echo "=== Starting Backup ==="

# Database backup
echo "[1/3] Backing up database..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/db_$DATE.sql"
gzip "$BACKUP_DIR/db_$DATE.sql"
echo "Database backup complete: db_$DATE.sql.gz"

# Files backup
echo "[2/3] Backing up application files..."
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" \
    -C /var/www shenmo_app1/uploads \
    -C /var/www shenmo_app1/config \
    -C /var/www shenmo_app1/icons 2>/dev/null || true
echo "Files backup complete: files_$DATE.tar.gz"

# Uploads backup (separate for quick access)
echo "[3/3] Backing up uploads..."
if [ -d "$APP_ROOT/uploads" ]; then
    tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C "$APP_ROOT" uploads
    echo "Uploads backup complete: uploads_$DATE.tar.gz"
fi

# Cleanup old backups (keep 30 days)
echo "Cleaning up old backups..."
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +30 -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete

# Summary
echo ""
echo "=== Backup Summary ==="
echo "Location: $BACKUP_DIR"
ls -lh "$BACKUP_DIR"
echo ""
echo "To restore:"
echo "  Database: gunzip < $BACKUP_DIR/db_$DATE.sql.gz | mysql -u $DB_USER -p $DB_NAME"
echo "  Files: tar -xzf $BACKUP_DIR/files_$DATE.tar.gz -C /var/www/"
