-- ============================================================
-- Shenmo Abacus Competition Layer
-- Database Schema for Digital Competitions
-- ============================================================

-- ------------------------------------------------------------
-- 1. COMPETITIONS TABLE
-- Stores competition session metadata
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS competitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    level_number INT NOT NULL COMMENT 'Abacus level 1-6, 7=Advanced',
    paper_name VARCHAR(1) NOT NULL COMMENT 'A, B, C, or D',
    mode ENUM('official', 'practice') NOT NULL DEFAULT 'practice',
    total_questions INT DEFAULT 240,
    time_limit_seconds INT DEFAULT 300,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    final_score INT DEFAULT 0,
    max_score INT DEFAULT 0,
    accuracy_percent DECIMAL(5,2) DEFAULT 0.00,
    is_submitted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_level (level_number),
    INDEX idx_mode (mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. COMPETITION_QUESTIONS TABLE
-- Stores generated questions for each attempt
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS competition_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    question_number INT NOT NULL,
    question_type ENUM('addition', 'subtraction', 'mixed', 'multiplication', 'division') NOT NULL,
    operand_count INT NOT NULL COMMENT 'Number of operands in the question',
    operands TEXT NOT NULL COMMENT 'JSON array of operand values',
    operators TEXT NOT NULL COMMENT 'JSON array of operators between operands',
    correct_answer VARCHAR(50) NOT NULL,
    question_text TEXT NOT NULL,
    requires_abacus TINYINT(1) DEFAULT 0 COMMENT '1 when the paper/rules require the abacus',
    question_group VARCHAR(50) COMMENT 'Block identifier such as A1; each block contains 40 questions',
    display_order INT NOT NULL,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    INDEX idx_competition (competition_id),
    INDEX idx_group (question_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. COMPETITION_ANSWERS TABLE
-- Stores student answers with auto-save
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS competition_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    question_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    answer_text VARCHAR(50) DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    points_earned INT DEFAULT 0 COMMENT '1 if correct, 0 otherwise',
    abacus_used TINYINT(1) DEFAULT 0,
    answered_at DATETIME NULL,
    last_saved_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES competition_questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_answer (competition_id, question_id),
    INDEX idx_student (student_id),
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. COMPETITION_RESULTS TABLE
-- Stores final result summaries
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS competition_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    final_score INT DEFAULT 0,
    max_score INT DEFAULT 0,
    accuracy_percent DECIMAL(5,2) DEFAULT 0.00,
    time_taken_seconds INT DEFAULT 0,
    penalty_applied INT DEFAULT 0,
    certificate_eligible TINYINT(1) DEFAULT 0,
    result_details TEXT,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_score (final_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. COMPETITION_TEMPLATES TABLE
-- Stores question type templates per Level + Paper
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS competition_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_number INT NOT NULL,
    paper_name VARCHAR(1) NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    question_type ENUM('addition', 'subtraction', 'mixed', 'multiplication', 'division') NOT NULL,
    operand_count INT NOT NULL,
    operand_min INT NOT NULL,
    operand_max INT NOT NULL,
    requires_abacus TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    UNIQUE KEY unique_template (level_number, paper_name, template_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
