-- shenmo_user table for admin/system users
CREATE TABLE IF NOT EXISTS shenmo_user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_names VARCHAR(100) UNIQUE NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    user_email VARCHAR(150),
    user_country VARCHAR(100),
    user_city VARCHAR(100),
    use_telephone VARCHAR(20),
    user_birthdate DATE,
    user_role ENUM('admin', 'teacher', 'superadmin') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (user_names)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123 - change in production!)
INSERT INTO shenmo_user (user_names, user_password, user_email, user_role, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@shenmo.com', 'superadmin', 1)
ON DUPLICATE KEY UPDATE user_names = user_names;