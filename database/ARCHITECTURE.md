# Student Payment & Course Management System
## Technical Architecture Documentation

---

## 1. System Overview

This system extends the existing Student Management System with:
- **Payment Processing**: Track student fee payments with transaction history
- **Course Management**: Create and manage abacus level courses
- **Access Control**: Restrict course content until payment is confirmed
- **Admin Monitoring**: Real-time payment tracking and transaction oversight

---

## 2. Database Schema

### 2.1 Core Tables

#### `courses`
Stores available courses tied to abacus levels.

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Unique course identifier |
| `course_code` | VARCHAR(50) | Unique code (e.g., ABACUS-01) |
| `course_name` | VARCHAR(255) | Display name |
| `description` | TEXT | Course overview |
| `level_number` | INT | Abacus level (1-6) |
| `fee_amount` | DECIMAL(12,2) | Course cost in RWF |
| `duration_weeks` | INT | Course length |
| `is_active` | TINYINT(1) | Availability flag |

#### `payments`
Tracks individual payment transactions.

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Transaction ID |
| `student_id` | VARCHAR(50) | Student identifier |
| `course_id` | INT | Associated course |
| `amount_paid` | DECIMAL(12,2) | Amount in RWF |
| `payment_method` | VARCHAR(50) | cash, card, mobile, etc. |
| `reference_number` | VARCHAR(100) | Unique transaction ref |
| `payment_date` | DATETIME | When payment was made |
| `status` | ENUM | pending, completed, failed, refunded |
| `verified_by` | INT | Admin ID who verified |
| `notes` | TEXT | Additional information |

#### `enrollments`
Links students to courses with payment tracking.

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Enrollment ID |
| `student_id` | VARCHAR(50) | Student identifier |
| `course_id` | INT | Associated course |
| `total_fee` | DECIMAL(12,2) | Full course cost |
| `amount_paid` | DECIMAL(12,2) | Amount paid so far |
| `balance` | DECIMAL(12,2) | **VIRTUAL**: total_fee - amount_paid |
| `payment_status` | ENUM | unpaid, partial, paid, overpaid |
| `enrollment_status` | ENUM | active, suspended, completed, cancelled |
| `enrolled_at` | DATETIME | Enrollment date |
| `completed_at` | DATETIME | Course completion date |

#### `course_content`
Stores course materials with access control.

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Content ID |
| `course_id` | INT | Associated course |
| `title` | VARCHAR(255) | Lesson/material title |
| `content_type` | ENUM | video, pdf, text, quiz |
| `content_url` | VARCHAR(500) | Path to file/video |
| `content_text` | LONGTEXT | Text content |
| `week_number` | INT | Which week this belongs to |
| `sort_order` | INT | Display order within week |
| `is_published` | TINYINT(1) | Visibility flag |

#### `payment_settings`
System-wide configuration.

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Setting ID |
| `setting_key` | VARCHAR(100) | Unique setting name |
| `setting_value` | TEXT | Setting value |
| `description` | TEXT | Human-readable description |

---

## 3. Admin Payment Monitoring Architecture

### 3.1 Admin Dashboard Features

**File: `admin_payments.php`**
- **Payment Monitoring Table**: Real-time list of all transactions
  - Columns: Student Name | Course | Amount (RWF) | Method | Date | Status | Actions
  - Filters: By date range, course, payment status, student
  - Search: By student name, reference number
- **Payment Verification**: Approve/reject pending payments
- **Transaction Details**: View individual payment records with notes
- **Financial Summary**: Total collected, pending, refunded amounts
- **Export Functionality**: Download payment reports as CSV/PDF

### 3.2 Payment Verification Workflow

```
Student submits payment proof
        ↓
Admin reviews payment details
        ↓
Admin clicks "Verify" or "Reject"
        ↓
System updates payment.status = 'completed'
System updates enrollment.amount_paid
System recalculates enrollment.payment_status
        ↓
If payment_status = 'paid':
  Student gains access to course content
```

### 3.3 Admin SQL Queries for Monitoring

```sql
-- Daily payment summary
SELECT 
    DATE(payment_date) as date,
    COUNT(*) as transactions,
    SUM(amount_paid) as total_rwf,
    SUM(CASE WHEN status = 'completed' THEN amount_paid ELSE 0 END) as verified_rwf
FROM payments
WHERE payment_date >= CURDATE() - INTERVAL 30 DAY
GROUP BY DATE(payment_date)
ORDER BY date DESC;

-- Outstanding balances by student
SELECT 
    s.full_name,
    s.student_id,
    c.course_name,
    e.total_fee,
    e.amount_paid,
    e.balance,
    e.payment_status
FROM enrollments e
JOIN students s ON e.student_id = s.student_id
JOIN courses c ON e.course_id = c.id
WHERE e.balance > 0
ORDER BY e.balance DESC;

-- Payment method breakdown
SELECT 
    payment_method,
    COUNT(*) as count,
    SUM(amount_paid) as total_rwf
FROM payments
WHERE status = 'completed'
GROUP BY payment_method;
```

---

## 4. Student Payment Module

### 4.1 Payment Flow

**File: `student_payments.php`**

```
1. Student logs in
        ↓
2. Views "My Courses" section
        ↓
3. Sees enrollment status:
   - Unpaid: Shows "Pay Now" button
   - Partial: Shows "Pay Balance" button
   - Paid: Shows "Access Course" button
        ↓
4. Clicks payment button
        ↓
5. Payment form appears:
   - Course name & total fee (RWF)
   - Amount to pay (pre-filled with balance)
   - Payment method dropdown
   - Upload payment proof (optional)
   - Reference number
        ↓
6. Submits payment
        ↓
7. System creates payment record (status = 'pending')
   Updates enrollment.amount_paid
   Recalculates payment_status
        ↓
8. Student sees confirmation:
   "Payment submitted. Awaiting verification."
        ↓
9. Admin verifies payment
        ↓
10. Student receives notification (via dashboard)
    Course content becomes accessible
```

### 4.2 Payment Form Features

- **Dynamic Amount**: Pre-fills with remaining balance
- **Payment Methods**: Cash, Mobile Money (M-Pesa), Bank Transfer, Card
- **Reference Number**: Auto-generated (format: RCP-YYYYMMDD-XXXXX)
- **Receipt Generation**: PDF receipt after successful submission
- **Payment History**: List of all past transactions

### 4.3 Payment Validation

```php
// Validate payment amount
if ($amount_paid <= 0) {
    $error = "Amount must be greater than 0";
}
if ($amount_paid > $balance) {
    $error = "Amount exceeds outstanding balance";
}

// Check minimum payment requirement
$min_payment = $total_fee * 0.5; // 50% minimum
if ($payment_status === 'unpaid' && $amount_paid < $min_payment) {
    $error = "Minimum payment of RWF " . number_format($min_payment) . " required to access course";
}
```

---

## 5. Course Management Module

### 5.1 Course Access Control

**File: `course_viewer.php`**

```php
// Check if student has paid for this course
function hasCourseAccess($student_id, $course_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT payment_status, enrollment_status 
        FROM enrollments 
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt->bind_param("si", $student_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $enrollment = $result->fetch_assoc();
    $stmt->close();
    
    if (!$enrollment) {
        return false; // Not enrolled
    }
    
    // Access granted if fully paid or meets minimum threshold
    if ($enrollment['payment_status'] === 'paid' || 
        $enrollment['payment_status'] === 'partial') {
        return true;
    }
    
    return false;
}

// Usage in course viewer
if (!hasCourseAccess($_SESSION['student_id'], $course_id)) {
    die("Access denied. Please complete payment to view this course.");
}
```

### 5.2 Course Structure

```
Course (Level X)
├── Week 1
│   ├── Lesson 1: Introduction (text)
│   ├── Lesson 2: Basic Concepts (video)
│   └── Worksheet 1 (pdf)
├── Week 2
│   ├── Lesson 3: Practice Exercises (text)
│   └── Quiz 1 (quiz)
└── Week 3...
```

### 5.3 Content Delivery

- **Progressive Unlocking**: Lessons unlock weekly based on enrollment date
- **Progress Tracking**: Mark lessons as complete
- **Quizzes**: Embedded assessments (future enhancement)
- **Downloadable Resources**: PDFs, practice sheets

---

## 6. File Structure

```
shenmo_app1/
├── database/
│   ├── payment_course_schema.sql       # Complete database schema
│   └── sample_data.sql                # Sample courses & content
├── admin/
│   ├── admin_payments.php             # Payment monitoring dashboard
│   ├── admin_courses.php              # Course management
│   ├── verify_payment.php             # Payment verification action
│   └── export_payments.php            # CSV/PDF export
├── student/
│   ├── student_payments.php           # Payment submission & history
│   ├── course_catalog.php             # Browse available courses
│   ├── course_viewer.php              # Access course content
│   ├── payment_receipt.php            # Generate receipt PDF
│   └── my_courses.php                 # Enrolled courses dashboard
├── includes/
│   ├── payment_functions.php          # Payment helper functions
│   ├── course_functions.php           # Course access control
│   └── pdf_generator.php              # Receipt generation library
├── api/
│   ├── process_payment.php            # Handle payment submission
│   ├── check_access.php               # AJAX: Verify course access
│   └── upload_receipt.php             # Handle file uploads
├── login.php
├── manage_students.php
├── admin_dashboard.php
├── student_dashboard.php
├── change_password.php
└── logout.php
```

---

## 7. Security & Business Rules

### 7.1 Payment Security
- All payment records are immutable once verified
- Reference numbers are unique and sequential
- Admin verification required before course access
- Payment amounts validated against course fees
- Duplicate payment prevention (same reference number)

### 7.2 Access Control Rules
```php
// Course access rules
1. Student must be enrolled in course
2. Enrollment must be 'active'
3. Payment status must be 'paid' OR amount_paid >= minimum_threshold (50%)
4. Course must be 'active' (is_active = 1)
5. Content must be 'published' (is_published = 1)

// If any rule fails → show payment prompt instead of content
```

### 7.3 Data Integrity
- Foreign key constraints prevent orphaned records
- Unique constraints prevent duplicate enrollments
- Generated column ensures balance is always accurate
- Timestamps track all changes

---

## 8. Implementation Sequence

### Phase 1: Database Setup
1. Run `payment_course_schema.sql` in phpMyAdmin
2. Verify tables created successfully
3. Insert sample courses and content

### Phase 2: Admin Payment Monitoring
1. Create `admin_payments.php` with payment table
2. Implement verification workflow
3. Add filters and export functionality
4. Create `admin_courses.php` for course management

### Phase 3: Student Payment Module
1. Create `student_payments.php` with payment form
2. Implement payment submission logic
3. Add payment history view
4. Generate receipt PDFs

### Phase 4: Course Management
1. Create `course_catalog.php` for browsing
2. Implement `course_viewer.php` with access control
3. Build progress tracking
4. Add content upload for admins

### Phase 5: Integration & Testing
1. Integrate with existing student dashboard
2. Test payment flows end-to-end
3. Verify access control logic
4. Test edge cases (refunds, partial payments, etc.)

---

## 9. Sample Data

### Default Courses (Pre-loaded)
```sql
-- Level 1: RWF 300,000
INSERT INTO courses (course_code, course_name, description, level_number, fee_amount, duration_weeks)
VALUES ('ABACUS-01', 'Abacus Level 1 - Beginner', 'Introduction to abacus basics', 1, 300000.00, 12);

-- Levels 2-6 follow same pattern
```

### Sample Payment Record
```sql
INSERT INTO payments (student_id, course_id, amount_paid, payment_method, reference_number, status, payment_date)
VALUES ('2026001', 1, 300000.00, 'mobile_money', 'RCP-20260725-00001', 'completed', NOW());
```

### Sample Enrollment
```sql
INSERT INTO enrollments (student_id, course_id, total_fee, amount_paid, payment_status, enrollment_status)
VALUES ('2026001', 1, 300000.00, 300000.00, 'paid', 'active');
```

---

## 10. API Endpoints (Future Enhancement)

```
POST /api/process_payment.php
  - Accepts: student_id, course_id, amount, method, reference
  - Returns: {success: true, enrollment_id: 123}

POST /api/verify_payment.php
  - Accepts: payment_id, admin_id, action (approve/reject)
  - Returns: {success: true, new_status: "completed"}

GET /api/check_access.php?student_id=XXX&course_id=YYY
  - Returns: {has_access: true, payment_status: "paid"}

GET /api/payment_history.php?student_id=XXX
  - Returns: [{id, course, amount, date, status}, ...]
```

---

## 11. Technology Stack

- **Backend**: PHP 7.4+ (existing stack)
- **Database**: MySQL 5.7+ (XAMPP)
- **Frontend**: HTML5, CSS3, JavaScript (vanilla)
- **PDF Generation**: TCPDF or FPDF library
- **File Uploads**: Secure multipart/form-data handling
- **Security**: Prepared statements, CSRF tokens, session validation

---

## 12. Next Steps

1. **Execute SQL schema** in phpMyAdmin
2. **Create admin payment dashboard** (`admin_payments.php`)
3. **Build student payment form** (`student_payments.php`)
4. **Implement course viewer** with access control
5. **Add receipt generation** functionality
6. **Test complete flow**: Student payment → Admin verification → Course access

---

**Document Version**: 1.0  
**Last Updated**: 2026-07-25  
**Status**: Design Complete - Ready for Implementation
