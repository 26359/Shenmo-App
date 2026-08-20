#!/bin/bash
set -euo pipefail

# ============================================================
# Script: 06_setup_monitoring.sh
# Purpose: Install Netdata, configure monitoring, setup alerts
# ============================================================

echo "=== Setting up Monitoring & Observability ==="

# Install Netdata
echo "[1/5] Installing Netdata..."
if ! command -v netdata &> /dev/null; then
    curl -Ss https://my-netdata.io/kickstart.sh | bash -s -- --stable-channel --no-updates
else
    echo "Netdata already installed"
fi

# Configure Netdata
echo "[2/5] Configuring Netdata..."
mkdir -p /etc/netdata

# Create health alarm configuration for MySQL
cat > /etc/netdata/health.d/mysql.conf <<'EOF'
alarm: mysql_connections
    on: mysql.net.connections
    lookup: average -10m unaligned of mysql.net.connections
    units: connections
    every: 1m
    warn: $this > 80
    crit: $this > 150

alarm: mysql_queries
    on: mysql.net.queries
    lookup: average -10m unaligned of mysql.net.queries
    units: queries/s
    every: 1m
    warn: $this > 1000
    crit: $this > 5000
EOF

# Create health alarm for PHP-FPM
cat > /etc/netdata/health.d/phpfpm.conf <<'EOF'
alarm: phpfpm_active_processes
    on: php-fpm.localhost.active_processes
    lookup: average -10m unaligned of php-fpm.localhost.active_processes
    units: processes
    every: 1m
    warn: $this > 20
    crit: $this > 40

alarm: phpfpm_slow_requests
    on: php-fpm.localhost.slow_requests
    lookup: average -10m unaligned of php-fpm.localhost.slow_requests
    units: requests/s
    every: 1m
    warn: $this > 5
    crit: $this > 20
EOF

# Create health alarm for Nginx
cat > /etc/netdata/health.d/nginx.conf <<'EOF'
alarm: nginx_requests
    on: nginx.localhost.requests
    lookup: average -10m unaligned of nginx.localhost.requests
    units: requests/s
    every: 1m
    warn: $this > 100
    crit: $this > 500

alarm: nginx_error_rate
    on: nginx.localhost.4xx_requests
    lookup: average -10m unaligned of nginx.localhost.4xx_requests
    units: errors/s
    every: 1m
    warn: $this > 10
    crit: $this > 50
EOF

systemctl restart netdata

# Create custom application metrics endpoint
echo "[3/5] Creating application metrics endpoint..."
cat > /var/www/shenmo_app1/api/metrics.php <<'PHPEOF'
<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

try {
    require_once __DIR__ . '/../config/database.php';
    $config = require __DIR__ . '/../config/database.php';
    
    $conn = new mysqli(
        $config['host'],
        $config['user'],
        $config['pass'],
        $config['dbname']
    );
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    $metrics = [];
    
    // User metrics
    $metrics['users_total'] = (int)$conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
    $metrics['users_active_today'] = (int)$conn->query("SELECT COUNT(*) FROM students WHERE DATE(updated_at) = CURDATE() OR DATE(created_at) = CURDATE()")->fetch_row()[0];
    
    // Payment metrics
    $metrics['payments_today'] = (int)$conn->query("SELECT COUNT(*) FROM payments WHERE DATE(payment_date) = CURDATE()")->fetch_row()[0];
    $metrics['payments_pending'] = (int)$conn->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetch_row()[0];
    $metrics['payments_completed_today'] = (int)$conn->query("SELECT COUNT(*) FROM payments WHERE status = 'completed' AND DATE(payment_date) = CURDATE()")->fetch_row()[0];
    $metrics['revenue_today'] = (float)$conn->query("SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE status = 'completed' AND DATE(payment_date) = CURDATE()")->fetch_row()[0];
    
    // Course metrics
    $metrics['courses_active'] = (int)$conn->query("SELECT COUNT(*) FROM courses WHERE is_active = 1")->fetch_row()[0];
    $metrics['enrollments_active'] = (int)$conn->query("SELECT COUNT(*) FROM enrollments WHERE enrollment_status = 'active'")->fetch_row()[0];
    
    // Health status
    $metrics['database_status'] = 'connected';
    $metrics['timestamp'] = date('c');
    
    $conn->close();
    
    echo json_encode($metrics, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'database_status' => 'error',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
PHPEOF

chown www-data:www-data /var/www/shenmo_app1/api/metrics.php
chmod 644 /var/www/shenmo_app1/api/metrics.php

# Create monitoring health check
echo "[4/5] Creating health check endpoint..."
cat > /var/www/shenmo_app1/health.php <<'PHPEOF'
<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Check database
try {
    require_once __DIR__ . '/config/database.php';
    $config = require __DIR__ . '/config/database.php';
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);
    
    if ($conn->connect_error) {
        throw new Exception('Connection failed');
    }
    $conn->query("SELECT 1");
    $health['checks']['database'] = ['status' => 'healthy', 'message' => 'Connected'];
    $conn->close();
} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Check disk space
$diskFree = disk_free_space('/');
$diskTotal = disk_total_space('/');
$diskPercent = round(($diskTotal - $diskFree) / $diskTotal * 100, 1);
$health['checks']['disk'] = [
    'status' => $diskPercent > 90 ? 'warning' : 'healthy',
    'free_gb' => round($diskFree / 1073741824, 2),
    'total_gb' => round($diskTotal / 1073741824, 2),
    'used_percent' => $diskPercent
];

// Check PHP
$health['checks']['php'] = [
    'status' => 'healthy',
    'version' => phpversion()
];

http_response_code($health['status'] === 'healthy' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
PHPEOF

chown www-data:www-data /var/www/shenmo_app1/health.php
chmod 644 /var/www/shenmo_app1/health.php

# Setup log rotation for application
echo "[5/5] Setting up log rotation..."
cat > /etc/logrotate.d/shenmo_app1 <<'EOF'
/var/log/nginx/shenmo_access.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data adm
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 `cat /var/run/nginx.pid`
    endscript
}

/var/log/nginx/shenmo_error.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data adm
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 `cat /var/run/nginx.pid`
    endscript
}
EOF

echo "=== Monitoring Setup Complete ==="
echo "Netdata dashboard: http://YOUR_SERVER_IP:19999"
echo "Application metrics: https://$DOMAIN/api/metrics.php"
echo "Health check: https://$DOMAIN/health.php"
