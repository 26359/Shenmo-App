<?php
session_start();

$host = "localhost";
$dbname = "shenmo_app";
$user = "root";
$pass = "";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $_SESSION['student_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if ($student && $student['password'] === $current_password) {
            $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
            $update_stmt->bind_param("ss", $new_password, $_SESSION['student_id']);

            if ($update_stmt->execute()) {
                $message = "Password changed successfully!";
                $update_stmt->close();
            } else {
                $message = "Error updating password: " . $update_stmt->error;
                $update_stmt->close();
            }
        } else {
            $message = "Current password is incorrect.";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Change Password - Student Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { width: 100%; max-width: 500px; }
        .card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.4s;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        h1 { color: #2d3748; margin-bottom: 10px; text-align: center; font-size: 1.8rem; }
        .subtitle { text-align: center; color: #718096; margin-bottom: 30px; font-size: 0.95rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 0.9rem; }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15); }
        .btn-group { display: flex; gap: 10px; margin-top: 25px; }
        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #e2e8f0; color: #4a5568; }
        .btn-secondary:hover { background: #cbd5e0; }
        .message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .message.success { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔐 Change Password</h1>
            <p class="subtitle">Update your account password</p>

            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required autofocus>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                    <a href="student_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
