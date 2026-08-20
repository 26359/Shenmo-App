#!/bin/bash
set -euo pipefail

# ============================================================
# Script: 02_setup_database.sh
# Purpose: Create database, user, and migrate schema
# ============================================================

echo "=== Starting Database Setup ==="

# Load config (source from environment or prompt)
DB_NAME="${DB_NAME:-shenmo_app}"
DB_USER="${DB_USER:-shenmo_user}"
DB_PASS="${DB_PASS:-YOUR_SECURE_DB_PASSWORD}"
DB_HOST="${DB_HOST:-localhost}"

if [ "$DB_PASS" = "YOUR_SECURE_DB_PASSWORD" ]; then
    read -sp "Enter MySQL root password: " MYSQL_ROOT_PASS
    echo
    read -sp "Enter new database password for $DB_USER: " DB_PASS
    echo
else
    MYSQL_ROOT_PASS="shenmo_secure_pass"
fi

# Create database and user
echo "[1/4] Creating database and user..."
mysql -u root -p"$MYSQL_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "Database '$DB_NAME' and user '$DB_USER' created."

# Migrate schema
echo "[2/4] Migrating schema..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/lms_schema.sql
echo "lms_schema.sql migrated."

mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/payment_course_schema.sql
echo "payment_course_schema.sql migrated."

# Run any additional migrations
echo "[3/4] Running application migrations..."
php migrate.php 2>/dev/null || echo "No additional migrations or migrate.php not found."

# Verify tables
echo "[4/4] Verifying tables..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" | wc -l

echo "=== Database Setup Complete ==="
echo "Database: $DB_NAME"
echo "User: $DB_USER"
echo "Host: $DB_HOST"
