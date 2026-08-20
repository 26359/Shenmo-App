# Monitoring & Observability Setup
## UptimeRobot Configuration

### Step 1: Sign Up
1. Go to [UptimeRobot.com](https://uptimerobot.com)
2. Create a free account (50 monitors, 5-min intervals)

### Step 2: Add Monitors

Create the following monitors:

#### Monitor 1: HTTPS Uptime
- **Monitor Type**: HTTP(s)
- **Friendly Name**: Shenmo LMS - HTTPS
- **URL/IP**: `https://your-domain.com`
- **Monitoring Interval**: 5 minutes
- **Alert Contacts**: Add your email/SMS

#### Monitor 2: HTTP Redirect
- **Monitor Type**: HTTP(s)
- **Friendly Name**: Shenmo LMS - HTTP
- **URL/IP**: `http://your-domain.com`
- **Monitoring Interval**: 5 minutes
- **Expected Status Code**: 301 (redirect to HTTPS)

#### Monitor 3: Health Check API
- **Monitor Type**: HTTP(s)
- **Friendly Name**: Shenmo Health API
- **URL/IP**: `https://your-domain.com/health.php`
- **Monitoring Interval**: 5 minutes
- **Expected Status Code**: 200
- **Alert Contacts**: Add your email/SMS

#### Monitor 4: Login Page
- **Monitor Type**: HTTP(s)
- **Friendly Name**: Shenmo Login Page
- **URL/IP**: `https://your-domain.com/login.php`
- **Monitoring Interval**: 5 minutes

#### Monitor 5: Database Connection
- **Monitor Type**: HTTP(s) - use health.php above, or:
- Create a keyword monitor that checks for `"database_status":"healthy"` in health.php response

### Step 3: Configure Alerts

**Recommended Alert Contacts**:
1. **Email**: Primary notification
2. **SMS**: Critical alerts (if available in your plan)
3. **Slack/Discord**: Team notifications (webhook URL)

**Alert Thresholds**:
- **Down**: Alert after 2 consecutive failures (10 minutes)
- **Degraded**: Alert if response time > 3 seconds

### Step 4: Status Page (Optional)
1. In UptimeRobot, go to **Status Pages**
2. Create a public status page: `https://status.your-domain.com`
3. Add all monitors to the status page
4. Embed the status page badge on your application's footer

### Step 5: Advanced Monitoring

#### Response Time Tracking
UptimeRobot automatically tracks response times. Review trends:
- Normal: < 500ms
- Warning: 500ms - 2s
- Critical: > 2s

#### SSL Certificate Monitoring
UptimeRobot's HTTPS monitors automatically check SSL validity. Set alerts for:
- SSL expiry warning (7 days before)
- SSL expiry critical (3 days before)

### Integration with Netdata

For deeper server monitoring, access Netdata:
```
http://YOUR_SERVER_IP:19999
```

Key dashboards to watch:
1. **Overview**: CPU, RAM, Disk, Network
2. **Nginx**: Requests/sec, response codes, bandwidth
3. **PHP-FPM**: Active processes, slow requests
4. **MySQL**: Queries/sec, connections, slow queries
5. **System**: Load averages, interrupts, context switches

---

## Additional Monitoring Tools (Optional)

### Prometheus + Grafana (For Scaling)

When you have >500 daily users, consider:

```bash
# Install Prometheus
docker run -d -p 9090:9090 prom/prometheus

# Install Grafana
docker run -d -p 3000:3000 grafana/grafana

# Install Node Exporter for server metrics
docker run -d -p 9100:9100 prom/node-exporter

# Install MySQL Exporter
docker run -d -p 9104:9104 prom/mysqld-exporter
```

Import these Grafana dashboards:
- **Node Exporter Full**: ID 1860
- **MySQL Overview**: ID 7362
- **Nginx Monitoring**: ID 11234

### Sentry (Error Tracking)

For production error tracking:

```bash
# Using Docker
docker run -d -p 9000:9000 -e SENTRY_SECRET_KEY=your-key sentry/sentry
```

Or use Sentry Cloud (sentry.io):
1. Create account at sentry.io
2. Create new project: "Shenmo LMS - PHP"
3. Install SDK in your PHP application
4. Add error reporting to your code

### CloudWatch / Cloud Monitoring

If using AWS/DigitalOcean/Azure:
- Enable built-in server metrics
- Set up billing alerts
- Configure backup monitoring

---

## Log Management

### Application Logs

Create centralized logging:

```bash
# Create log directory
mkdir -p /var/log/shenmo_app1
chown www-data:www-data /var/log/shenmo_app1

# Add to php.ini
# error_log = /var/log/shenmo_app1/php_errors.log
```

### Log Rotation

Logrotate is configured in `06_setup_monitoring.sh`:
- Nginx logs: 30 days retention
- Compressed after 1 day
- Automatic cleanup

### Log Analysis Commands

```bash
# Watch PHP errors in real-time
tail -f /var/log/nginx/shenmo_error.log | grep --line-buffered PHP

# Count 404s
awk '$9 == 404' /var/log/nginx/shenmo_access.log | wc -l

# Top 10 IPs
awk '{print $1}' /var/log/nginx/shenmo_access.log | sort | uniq -c | sort -rn | head -10

# Response time percentiles
awk '{print $NF}' /var/log/nginx/shenmo_access.log | sort -n | awk '{a[NR]=$1} END{print "P50:", a[int(NR*0.5)], "P95:", a[int(NR*0.95)], "P99:", a[int(NR*0.99)]}'
```

---

## Alerting Strategy

### Critical Alerts (Page immediately)
- HTTPS site down
- Database connection failed
- Disk space > 90%

### Warning Alerts (Email within 1 hour)
- Response time > 3s
- Error rate > 5%
- SSL certificate expiring < 7 days
- Pending payments > 20 (business critical)

### Informational (Daily digest)
- New user registrations
- Daily revenue
- Server resource usage trends
