-- ============================================================
-- Student Payment & Course Management System
-- Database Schema for shenmo_app
-- ============================================================

-- ------------------------------------------------------------
-- 1. COURSES TABLE
-- Stores available courses/levels for purchase
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50) UNIQUE NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    description TEXT,
    level_number INT NOT NULL COMMENT 'Abacus level (1-6)',
    fee_amount DECIMAL(12, 2) NOT NULL COMMENT 'Course fee in RWF',
    duration_weeks INT DEFAULT 12,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_level (level_number),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. PAYMENTS TABLE
-- Tracks individual student payment transactions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    amount_paid DECIMAL(12, 2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash',
    reference_number VARCHAR(100) UNIQUE,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    verified_by INT COMMENT 'Admin user_id who verified payment',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status),
    INDEX idx_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. ENROLLMENTS TABLE
-- Links students to courses with payment status
-- Controls course access: only accessible when fully paid
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    total_fee DECIMAL(12, 2) NOT NULL,
    amount_paid DECIMAL(12, 2) DEFAULT 0.00,
    balance DECIMAL(12, 2) GENERATED ALWAYS AS (total_fee - amount_paid) VIRTUAL,
    payment_status ENUM('unpaid', 'partial', 'paid', 'overpaid') DEFAULT 'unpaid',
    enrollment_status ENUM('active', 'suspended', 'completed', 'cancelled') DEFAULT 'active',
    enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_student (student_id),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. COURSE_CONTENT TABLE
-- Stores course materials/lessons
-- Access restricted by enrollment payment status
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS course_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content_type ENUM('video', 'pdf', 'text', 'quiz') DEFAULT 'text',
    content_url VARCHAR(500),
    content_text LONGTEXT,
    week_number INT,
    sort_order INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. PAYMENT_SETTINGS TABLE
-- Stores system-wide payment configuration
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO payment_settings (setting_key, setting_value, description) VALUES
('currency', 'RWF', 'Currency code for display'),
('currency_symbol', 'RWF ', 'Currency symbol prefix'),
('payment_gateway', 'manual', 'Payment processing method'),
('allow_partial_payments', '1', 'Allow students to pay in installments'),
('min_installment_percent', '50', 'Minimum percentage required to access course'),
('receipt_prefix', 'RCP-', 'Prefix for payment reference numbers')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- ------------------------------------------------------------
-- 6. INSERT DEFAULT COURSES (Abacus Levels 1-6)
-- ------------------------------------------------------------
INSERT INTO courses (course_code, course_name, description, level_number, fee_amount, duration_weeks) VALUES
('ABACUS-01', 'Abacus Level 1 - Beginner', 'Introduction to abacus basics, numbers 1-10, simple addition and subtraction', 1, 300000.00, 12),
('ABACUS-02', 'Abacus Level 2 - Elementary', 'Building on basics, larger numbers, multiplication fundamentals', 2, 300000.00, 12),
('ABACUS-03', 'Abacus Level 3 - Intermediate', 'Advanced operations, division, speed drills', 3, 300000.00, 12),
('ABACUS-04', 'Abacus Level 4 - Advanced', 'Complex calculations, mental arithmetic techniques', 4, 300000.00, 12),
('ABACUS-05', 'Abacus Level 5 - Expert', 'Expert-level speed and accuracy, competition preparation', 5, 300000.00, 12),
('ABACUS-06', 'Abacus Level 6 - Master', 'Mastery level, teaching fundamentals, advanced problem solving', 6, 300000.00, 12)
ON DUPLICATE KEY UPDATE course_name = course_name;

-- ------------------------------------------------------------
-- 7. SAMPLE CONTENT FOR LEVEL 1
-- ------------------------------------------------------------
INSERT INTO course_content (course_id, title, content_type, content_text, week_number, sort_order) VALUES
(1, 'Introduction to Abacus', 'text', 'Welcome to Abacus Level 1. In this course, you will learn the basics of abacus calculation...', 1, 1),
(1, 'Numbers 1-10', 'text', 'Learn to represent numbers 1 through 10 on the abacus...', 1, 2),
(1, 'Simple Addition', 'text', 'Adding single-digit numbers using the abacus...', 2, 1),
(1, 'Simple Subtraction', 'text', 'Subtracting single-digit numbers using the abacus...', 2, 2),
(1, 'Week 1 Practice Sheet', 'pdf', '/content/level1/week1.pdf', 1, 3)
ON DUPLICATE KEY UPDATE title = title;
