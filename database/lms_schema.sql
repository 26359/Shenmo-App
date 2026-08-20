-- ============================================================
-- Abacus LMS - Additional Tables
-- ============================================================

-- ------------------------------------------------------------
-- 1. LESSONS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lesson_title VARCHAR(255) NOT NULL,
    lesson_description TEXT,
    lesson_content LONGTEXT,
    video_url VARCHAR(500),
    notes TEXT,
    duration_minutes INT DEFAULT 30,
    sort_order INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. HOMEWORK TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS homework (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    assigned_by INT COMMENT 'Admin/Teacher user_id',
    status ENUM('pending', 'submitted', 'graded', 'late') DEFAULT 'pending',
    submitted_at DATETIME NULL,
    grade DECIMAL(5,2) NULL,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. EXAMS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    exam_title VARCHAR(255) NOT NULL,
    exam_description TEXT,
    exam_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 60,
    total_marks INT DEFAULT 100,
    passing_marks INT DEFAULT 50,
    is_published TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. EXAM_RESULTS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    grade VARCHAR(10),
    passed TINYINT(1) DEFAULT 0,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_exam_student (exam_id, student_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. CERTIFICATES TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    certificate_number VARCHAR(100) UNIQUE NOT NULL,
    issue_date DATE DEFAULT (CURRENT_DATE),
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. ATTENDANCE TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    remarks TEXT,
    recorded_by INT COMMENT 'Admin/Teacher user_id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (student_id, attendance_date),
    INDEX idx_student (student_id),
    INDEX idx_date (attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. ACHIEVEMENTS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_code VARCHAR(50) UNIQUE NOT NULL,
    achievement_name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100) DEFAULT 'trophy',
    points_reward INT DEFAULT 10,
    criteria_type ENUM('lessons_completed', 'streak_days', 'exam_score', 'attendance_rate', 'homework_completed') NOT NULL,
    criteria_value INT NOT NULL COMMENT 'Threshold to unlock',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. STUDENT_ACHIEVEMENTS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    achievement_id INT NOT NULL,
    earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_achievement (student_id, achievement_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. MESSAGES TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id VARCHAR(50) NOT NULL COMMENT 'Can be student_id or admin user_id',
    sender_role ENUM('student', 'admin', 'teacher') NOT NULL,
    recipient_id VARCHAR(50) NOT NULL,
    recipient_role ENUM('student', 'admin', 'teacher') NOT NULL,
    subject VARCHAR(255),
    message_text TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    parent_message_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_message_id) REFERENCES messages(id) ON DELETE CASCADE,
    INDEX idx_recipient (recipient_id, recipient_role, is_read),
    INDEX idx_sender (sender_id, sender_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 10. NOTIFICATIONS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    user_role ENUM('student', 'admin', 'teacher') NOT NULL,
    notification_type ENUM('homework', 'lesson', 'exam', 'payment', 'certificate', 'announcement', 'message') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, user_role, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 11. STUDENT_PROGRESS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    lesson_id INT NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    completed_lessons INT DEFAULT 0,
    total_lessons INT DEFAULT 0,
    time_spent_minutes INT DEFAULT 0,
    last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_course (student_id, course_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 12. STREAKS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_activity_date DATE NULL,
    total_days_active INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 13. XP_POINTS TABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS xp_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    xp_amount INT NOT NULL,
    xp_source ENUM('lesson_complete', 'homework_submit', 'exam_pass', 'streak_bonus', 'achievement', 'practice') NOT NULL,
    source_id INT NULL COMMENT 'ID of related item (lesson_id, exam_id, etc.)',
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 14. SAMPLE ACHIEVEMENTS
-- ------------------------------------------------------------
INSERT INTO achievements (achievement_code, achievement_name, description, icon, points_reward, criteria_type, criteria_value) VALUES
('fast_learner', 'Fast Learner', 'Complete 5 lessons in one day', 'lightning', 50, 'lessons_completed', 5),
('mental_math_star', 'Mental Math Star', 'Score 100% on 3 mental math exercises', 'calculator', 30, 'exam_score', 100),
('perfect_attendance', 'Perfect Attendance', 'Attend 30 consecutive days', 'calendar', 100, 'attendance_rate', 30),
('homework_champion', 'Homework Champion', 'Submit 10 homework assignments on time', 'homework', 40, 'homework_completed', 10),
('level_master', 'Level Master', 'Complete any abacus level', 'graduation', 200, 'lessons_completed', 20),
('streak_week', 'Week Warrior', 'Maintain a 7-day learning streak', 'fire', 70, 'streak_days', 7),
('speed_demon', 'Speed Demon', 'Complete a speed challenge in under 60 seconds', 'speed', 60, 'exam_score', 95),
('quiz_genius', 'Quiz Genius', 'Score 100% on 5 quizzes', 'target', 80, 'exam_score', 100)
ON DUPLICATE KEY UPDATE achievement_name = achievement_name;

-- ------------------------------------------------------------
-- 15. SAMPLE LESSONS FOR LEVEL 1
-- ------------------------------------------------------------
INSERT INTO lessons (course_id, lesson_title, lesson_description, lesson_content, notes, duration_minutes, sort_order) VALUES
(1, 'Introduction to Abacus', 'Learn the basics of abacus and its history', 'Welcome to Abacus Level 1. In this course, you will learn the basics of abacus calculation...', 'Take notes on the parts of the abacus', 30, 1),
(1, 'Numbers 1-10', 'Learn to represent numbers 1 through 10 on the abacus', 'The abacus has two parts: upper deck and lower deck. Each bead in the lower deck represents 1, while each bead in the upper deck represents 5...', 'Practice moving beads for numbers 1-10', 30, 2),
(1, 'Simple Addition', 'Adding single-digit numbers using the abacus', 'To add numbers, move the appropriate number of beads towards the beam...', 'Complete practice sheet for addition', 30, 3),
(1, 'Simple Subtraction', 'Subtracting single-digit numbers using the abacus', 'To subtract numbers, move beads away from the beam...', 'Complete practice sheet for subtraction', 30, 4),
(1, 'Mixed Operations', 'Combine addition and subtraction', 'Now let us practice both operations together...', 'Review all operations learned so far', 45, 5)
ON DUPLICATE KEY UPDATE lesson_title = lesson_title;

-- ------------------------------------------------------------
-- 16. SAMPLE EXAMS FOR LEVEL 1
-- ------------------------------------------------------------
INSERT INTO exams (course_id, exam_title, exam_description, exam_date, duration_minutes, total_marks, passing_marks, is_published) VALUES
(1, 'Level 1 Final Exam', 'Comprehensive exam covering all Level 1 topics', '2026-08-15 10:00:00', 60, 100, 50, 1),
(1, 'Level 1 Practice Test', 'Practice test to prepare for the final exam', '2026-08-01 10:00:00', 30, 50, 25, 1)
ON DUPLICATE KEY UPDATE exam_title = exam_title;
