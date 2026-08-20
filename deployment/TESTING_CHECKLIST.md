# End-to-End Testing Checklist
## Shenmo LMS Production Validation

Use this checklist after deployment to verify all features work correctly.

---

## Prerequisites
- [ ] Application accessible at https://your-domain.com
- [ ] Database migrated successfully
- [ ] SSL certificate valid
- [ ] Netdata monitoring active

---

## 1. Authentication Flow

### 1.1 Student Registration
- [ ] Navigate to https://your-domain.com/register.php
- [ ] Fill in all fields with valid test data
- [ ] Submit registration
- [ ] **Expected**: Redirect to "Check your email" or success message
- [ ] **Verify**: Student record created in database
  ```sql
  SELECT * FROM students WHERE email = 'test@example.com';
  ```

### 1.2 Admin Registration
- [ ] Navigate to https://your-domain.com/register_admin.php
- [ ] Register new admin with test data
- [ ] **Expected**: Admin account created
- [ ] **Verify**: Admin record in database

### 1.3 Email Verification
- [ ] Check email inbox for verification link
- [ ] Click verification link
- [ ] **Expected**: "Email verified successfully" message
- [ ] **Verify**: `email_verified = 1` in database

### 1.4 Student Login
- [ ] Navigate to https://your-domain.com/login.php
- [ ] Select role: "Student"
- [ ] Enter test username and password
- [ ] Click "Sign In"
- [ ] **Expected**: Redirect to student_dashboard.php
- [ ] **Verify**: Session cookie set (check browser dev tools)
- [ ] **Verify**: Dashboard shows student name

### 1.5 Admin Login
- [ ] Logout from student account
- [ ] Login as admin
- [ ] **Expected**: Redirect to admin_dashboard.php
- [ ] **Verify**: Admin dashboard loads with stats

### 1.6 Invalid Login
- [ ] Try login with wrong credentials
- [ ] **Expected**: "Invalid credentials" error message
- [ ] **Verify**: No session created

### 1.7 Unverified User Block
- [ ] Create new user but don't verify email
- [ ] Try to login
- [ ] **Expected**: "Please verify your email" message

---

## 2. Student Dashboard

### 2.1 Dashboard Load
- [ ] Login as student
- [ ] Navigate to student_dashboard.php
- [ ] **Expected**: Dashboard loads without errors
- [ ] **Verify**: Profile info displays correctly
- [ ] **Verify**: Course list visible (if enrolled)

### 2.2 Profile View
- [ ] Click "Profile" or navigate to profile.php
- [ ] **Expected**: Profile page displays student details
- [ ] **Verify**: All fields populated correctly

### 2.3 Change Password
- [ ] Navigate to change_password.php
- [ ] Enter current password and new password
- [ ] Submit
- [ ] **Expected**: Password changed successfully
- [ ] **Verify**: Login with new password works

---

## 3. Course Catalog & Enrollment

### 3.1 Browse Courses
- [ ] Navigate to course_catalog.php
- [ ] **Expected**: List of available courses displayed
- [ ] **Verify**: Course names, fees, descriptions visible
- [ ] **Verify**: Only active courses shown

### 3.2 Course Details
- [ ] Click on a course
- [ ] **Expected**: Course detail page loads
- [ ] **Verify**: Course content, duration, fee displayed

### 3.3 Enrollment (if payment flow exists)
- [ ] Click "Enroll" or "Pay Now"
- [ ] **Expected**: Payment form or enrollment confirmation
- [ ] **Verify**: Correct course and fee displayed

---

## 4. Payment Flow

### 4.1 Submit Payment
- [ ] Navigate to student_payments.php
- [ ] Select course to pay for
- [ ] Enter payment amount (>= minimum threshold)
- [ ] Select payment method
- [ ] Enter reference number
- [ ] Submit payment
- [ ] **Expected**: "Payment submitted" confirmation
- [ ] **Verify**: Payment record created with status='pending'
  ```sql
  SELECT * FROM payments WHERE student_id = 'YOUR_STUDENT_ID' ORDER BY id DESC LIMIT 1;
  ```

### 4.2 Payment Validation
- [ ] Try submitting payment with amount = 0
- [ ] **Expected**: Validation error
- [ ] Try submitting with amount > balance
- [ ] **Expected**: Validation error

### 4.3 Admin Payment Verification
- [ ] Login as admin
- [ ] Navigate to admin_payments.php
- [ ] **Expected**: Payment list shows pending payment
- [ ] Click "Verify" on the pending payment
- [ ] **Expected**: Payment status changes to 'completed'
- [ ] **Verify**: Enrollment payment_status updated
  ```sql
  SELECT payment_status FROM enrollments WHERE student_id = 'YOUR_STUDENT_ID';
  ```

### 4.4 Course Access After Payment
- [ ] Login as student
- [ ] Navigate to the paid course
- [ ] **Expected**: Course content accessible
- [ ] **Verify**: Lessons visible and playable

---

## 5. Course Content

### 5.1 View Course
- [ ] Navigate to course_viewer.php
- [ ] **Expected**: Course structure displayed (weeks, lessons)
- [ ] **Verify**: Week 1 lessons accessible

### 5.2 Lesson Access
- [ ] Click on a lesson
- [ ] **Expected**: Lesson content loads
- [ ] **Verify**: Text/video content displays
- [ ] **Verify**: No "Access Denied" errors

### 5.3 Progress Tracking
- [ ] Mark lesson as complete
- [ ] **Expected**: Progress updates
- [ ] **Verify**: Progress bar or percentage updates
  ```sql
  SELECT * FROM student_progress WHERE student_id = 'YOUR_STUDENT_ID';
  ```

---

## 6. Additional Features

### 6.1 Homework
- [ ] Navigate to homework.php (student view)
- [ ] **Expected**: Homework list displayed
- [ ] Submit homework (if applicable)

### 6.2 Exams
- [ ] Navigate to exams.php
- [ ] **Expected**: Available exams listed
- [ ] Start exam (if published)

### 6.3 Attendance
- [ ] Check attendance.php
- [ ] **Expected**: Attendance calendar/list visible

### 6.4 Achievements
- [ ] Navigate to achievements.php
- [ ] **Expected**: Achievement badges displayed

### 6.5 Leaderboard
- [ ] Navigate to leaderboard.php
- [ ] **Expected**: Rankings displayed
- [ ] **Verify**: Current student appears (if scored)

### 6.6 Messages
- [ ] Navigate to messages.php
- [ ] **Expected**: Message inbox loads
- [ ] Send test message to admin

### 6.7 Notifications
- [ ] Check notifications.php
- [ ] **Expected**: Notification list displayed

### 6.8 Certificates
- [ ] Navigate to certificates.php
- [ ] **Expected**: Available certificates shown

---

## 7. Admin Features

### 7.1 Admin Dashboard
- [ ] Login as admin
- [ ] Navigate to admin_dashboard.php
- [ ] **Expected**: Stats displayed (users, payments, courses)
- [ ] **Verify**: Numbers match database counts

### 7.2 Manage Students
- [ ] Navigate to manage_students.php
- [ ] **Expected**: Student list loads
- [ ] Test search/filter functionality
- [ ] Test student edit/delete (if applicable)

### 7.3 Admin Payments
- [ ] Navigate to admin_payments.php
- [ ] **Expected**: Payment table loads
- [ ] Test filters (date range, status, course)
- [ ] Test search (student name, reference)
- [ ] Test export (CSV/PDF if implemented)

### 7.4 Admin Courses
- [ ] Navigate to course_catalog.php (admin view)
- [ ] Test course creation
- [ ] Test course editing
- [ ] Test content upload

### 7.5 Certificates Management
- [ ] Navigate to admin_certificates.php
- [ ] **Expected**: Certificate list loads
- [ ] Test certificate generation

### 7.6 Homework Management
- [ ] Navigate to admin_homework.php
- [ ] **Expected**: Homework list loads
- [ ] Test homework creation/assignment

---

## 8. Mobile & PWA

### 8.1 PWA Installation
- [ ] Open Chrome DevTools > Application > Manifest
- [ ] **Expected**: manifest.json loaded
- [ ] **Verify**: App name, icons, theme color correct

### 8.2 Service Worker
- [ ] Check Application > Service Workers
- [ ] **Expected**: Service worker registered and activated
- [ ] Test offline mode (dev tools > Network > Offline)

### 8.3 Mobile Responsiveness
- [ ] Open DevTools > Device Toolbar
- [ ] Test iPhone SE (375px)
- [ ] Test iPad (768px)
- [ ] Test Android (412px)
- [ ] **Expected**: All pages usable on mobile
- [ ] **Verify**: No horizontal scroll
- [ ] **Verify**: Touch targets > 44px

---

## 9. Security Validation

### 9.1 HTTPS Enforcement
- [ ] Navigate to http://your-domain.com
- [ ] **Expected**: Redirects to https://
- [ ] **Verify**: No mixed content warnings (check console)

### 9.2 Authentication Guards
- [ ] Logout
- [ ] Try accessing student_dashboard.php directly
- [ ] **Expected**: Redirect to login.php
- [ ] Try accessing admin_dashboard.php directly
- [ ] **Expected**: Redirect to login.php

### 9.3 Input Validation
- [ ] Try SQL injection in login: `admin' OR '1'='1`
- [ ] **Expected**: Login fails (prepared statements prevent injection)
- [ ] Try XSS in registration name field: `<script>alert('xss')</script>`
- [ ] **Expected**: Script not executed (escaped on output)

### 9.4 File Upload Security
- [ ] Try uploading .php file as profile picture
- [ ] **Expected**: Upload rejected or file not executable
- [ ] **Verify**: Upload directory not executable

### 9.5 Session Security
- [ ] Login and check session cookie flags
- [ ] **Expected**: HttpOnly flag set
- [ ] **Expected**: Secure flag set (HTTPS only)
- [ ] **Expected**: SameSite flag set

---

## 10. Performance Validation

### 10.1 Page Load Times
- [ ] Test with Chrome DevTools > Lighthouse
- [ ] Run audit on login.php
- [ ] **Expected**: Performance score > 70
- [ ] Run audit on student_dashboard.php
- [ ] **Expected**: Performance score > 70

### 10.2 Database Query Performance
- [ ] Check Netdata for MySQL slow queries
- [ ] **Expected**: No queries > 2 seconds
- [ ] Review slow query log if any

### 10.3 Concurrent Users
- [ ] Use Apache Bench or similar tool to simulate load:
  ```bash
  ab -n 100 -c 10 https://your-domain.com/login.php
  ```
- [ ] **Expected**: All requests complete without errors
- [ ] **Expected**: Average response time < 1s

---

## 11. Database Validation

### 11.1 Schema Verification
```sql
-- Check all tables exist
SHOW TABLES;

-- Verify foreign keys
SELECT 
    TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = 'shenmo_app';
```

- [ ] All expected tables present
- [ ] Foreign keys configured correctly
- [ ] Indexes on frequently queried columns

### 11.2 Data Integrity
```sql
-- Check for orphaned records
SELECT COUNT(*) FROM enrollments WHERE student_id NOT IN (SELECT student_id FROM students);
SELECT COUNT(*) FROM payments WHERE student_id NOT IN (SELECT student_id FROM students);

-- Check payment calculations
SELECT 
    e.id, 
    e.total_fee, 
    e.amount_paid, 
    e.balance,
    (e.total_fee - e.amount_paid) as expected_balance
FROM enrollments e
WHERE ABS(e.balance - (e.total_fee - e.amount_paid)) > 0.01;
```

- [ ] No orphaned records
- [ ] Payment balances calculated correctly

---

## 12. Backup Verification

### 12.1 Database Backup
```bash
# Test backup script
mysqldump -u shenmo_user -p shenmo_app > /tmp/test_backup.sql
ls -lh /tmp/test_backup.sql
```

- [ ] Backup completes successfully
- [ ] Backup file size reasonable (not 0 bytes)
- [ ] Backup contains valid SQL

### 12.2 Restore Test
```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE shenmo_test;"
# Restore backup
mysql -u shenmo_user -p shenmo_test < /tmp/test_backup.sql
# Verify tables
mysql -u shenmo_user -p shenmo_test -e "SHOW TABLES;"
```

- [ ] Backup restores successfully
- [ ] All tables present after restore

---

## 13. Monitoring Validation

### 13.1 Netdata
- [ ] Access http://YOUR_SERVER_IP:19999
- [ ] **Expected**: Dashboard loads
- [ ] **Verify**: CPU, RAM, Disk metrics visible
- [ ] **Verify**: Nginx, PHP-FPM, MySQL charts present

### 13.2 Health Check
- [ ] Navigate to https://your-domain.com/health.php
- [ ] **Expected**: JSON response with status: "healthy"
- [ ] **Verify**: All checks show "healthy"

### 13.3 Application Metrics
- [ ] Navigate to https://your-domain.com/api/metrics.php
- [ ] **Expected**: JSON response with metrics
- [ ] **Verify**: users_total, payments_today, etc. populated

### 13.4 UptimeRobot
- [ ] Log into UptimeRobot dashboard
- [ ] **Expected**: All monitors showing "Up"
- [ ] **Verify**: Response times recorded

---

## 14. Email Delivery

### 14.1 SMTP Configuration
- [ ] Update config/mail.php with production SMTP credentials
- [ ] Test email sending (registration or password reset)
- [ ] **Expected**: Email received in inbox
- [ ] **Verify**: Email not in spam folder
- [ ] **Verify**: From address matches domain

### 14.2 Email Verification
- [ ] Register new test user
- [ ] **Expected**: Verification email sent
- [ ] Click verification link
- [ ] **Expected**: Email verified successfully

---

## 15. Error Handling

### 15.1 404 Pages
- [ ] Navigate to https://your-domain.com/nonexistent-page
- [ ] **Expected**: Custom 404 or Nginx default 404
- [ ] **Verify**: No sensitive information leaked

### 15.2 PHP Errors
- [ ] Check php.ini: display_errors = Off
- [ ] **Expected**: Errors logged to file, not displayed to users

### 15.3 Database Errors
- [ ] Temporarily stop MySQL
- [ ] Access application
- [ ] **Expected**: Graceful error message or maintenance page
- [ ] Restart MySQL

---

## Final Sign-Off

### Pre-Launch Checklist
- [ ] All tests above passed
- [ ] SSL certificate valid and auto-renewal configured
- [ ] Database backups automated and tested
- [ ] Monitoring and alerts configured
- [ ] SMTP working for transactional emails
- [ ] Production configs deployed (no localhost references)
- [ ] Security hardening applied
- [ ] Rate limiting configured (if applicable)
- [ ] Terms of Service and Privacy Policy pages created (if required)
- [ ] GDPR/compliance requirements met (if applicable)

### Go-Live
- [ ] Update DNS to point to production server
- [ ] Announce launch to stakeholders
- [ ] Monitor UptimeRobot and Netdata for first 24 hours
- [ ] Have rollback plan ready

### Post-Launch
- [ ] Monitor error logs for first 48 hours
- [ ] Review performance metrics daily for first week
- [ ] Collect user feedback
- [ ] Plan for scaling if needed

---

**Tested by**: _______________  
**Date**: _______________  
**Version**: 1.0
