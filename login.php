<?php
session_start();

$host = "localhost";
$dbname = "shenmo_app";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    grade_level VARCHAR(10) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    dob DATE,
    address TEXT,
    username VARCHAR(50) NOT NULL DEFAULT '',
    password VARCHAR(255) NOT NULL DEFAULT '',
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_expires DATETIME DEFAULT NULL
)");

$check_username = $conn->query("SHOW COLUMNS FROM students LIKE 'username'");
if ($check_username->num_rows == 0) {
    $conn->query("ALTER TABLE students ADD COLUMN username VARCHAR(50) NOT NULL DEFAULT ''");
    $conn->query("ALTER TABLE students ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT ''");
}

$check_verified = $conn->query("SHOW COLUMNS FROM students LIKE 'email_verified'");
if ($check_verified->num_rows == 0) {
    $conn->query("ALTER TABLE students ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE students ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE students ADD COLUMN verification_expires DATETIME DEFAULT NULL");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($role) || empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        if ($role == 'admin') {
            $sql = "SELECT user_id, user_names, email_verified FROM shenmo_user WHERE user_names = ? AND user_password = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (!$row['email_verified']) {
                    $message = "Please verify your email before logging in. <a href='resend_verification.php?email=" . urlencode($username) . "&type=admin' style='color: #667eea; font-weight: bold;'>Resend verification email</a>";
                } else {
                    $_SESSION['role'] = 'admin';
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['user_names'] = $row['user_names'];
                    header("Location: admin_dashboard.php");
                    exit;
                }
            } else {
                $message = "Invalid admin credentials.";
            }
            $stmt->close();
        } elseif ($role == 'student') {
            $sql = "SELECT student_id, full_name, email_verified FROM students WHERE username = ? AND password = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (!$row['email_verified']) {
                    $message = "Please verify your email before logging in. <a href='resend_verification.php?email=" . urlencode($username) . "&type=student' style='color: #667eea; font-weight: bold;'>Resend verification email</a>";
                } else {
                    $_SESSION['role'] = 'student';
                    $_SESSION['student_id'] = $row['student_id'];
                    $_SESSION['full_name'] = $row['full_name'];
                    header("Location: student_dashboard.php");
                    exit;
                }
            } else {
                $message = "Invalid student credentials.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Abacus Academy</title>
    <link rel="manifest" href="/shenmo_app1/manifest.json">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Shenmo">
    <link rel="apple-touch-icon" href="/shenmo_app1/icons/icon-192.png">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .login-card {
            background: white;
            padding: 45px 40px;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
        .logo { text-align: center; font-size: 3.5rem; margin-bottom: 10px; }
        h1 { text-align: center; color: #2d3748; margin-bottom: 8px; font-size: 1.6rem; }
        .subtitle { text-align: center; color: #718096; margin-bottom: 30px; font-size: 0.95rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 0.9rem; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15); }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); }
        @media(max-width:480px){
            .login-card { padding: 28px 20px; }
            h1 { font-size: 1.3rem; }
            .logo { font-size: 2.8rem; }
        }
        .message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .message.error { background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; border: 1px solid #f5c6cb; }
        .message.error a { color: #721c24; font-weight: bold; }
        .message.error a:hover { color: #491217; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">🎓</div>
        <h1>Student Portal</h1>
        <p class="subtitle">Sign in to access your dashboard</p>

        <?php if ($message): ?>
            <div class="message error"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="role">Login As</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="student">Student</option>
                </select>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Sign In →</button>
        </form>
        
        <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <p style="color: #718096; font-size: 0.95rem; margin-bottom: 15px;">Don't have an account?</p>
            <a href="register.php" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 15px; font-weight: 600;">
                ✨ Create Account
            </a>
        </div>
    </div>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/shenmo_app1/sw.js');
  }
</script>
</body>
</html>
