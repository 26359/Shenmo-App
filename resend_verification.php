<?php
session_start();
$message = '';
$error = '';

$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $type = trim($_POST['type']);
    
    if (empty($email) || empty($type)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        if ($type == 'student') {
            $stmt = $conn->prepare("SELECT id, full_name, email_verified FROM students WHERE email = ?");
        } elseif ($type == 'admin') {
            $stmt = $conn->prepare("SELECT user_id, user_names as full_name, email_verified FROM shenmo_user WHERE email = ?");
        } else {
            $error = "Invalid account type.";
        }
        
        if (empty($error)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                $error = "No account found with this email address.";
            } elseif ($user['email_verified']) {
                $message = "Email already verified! You can login now.";
            } else {
                $token = bin2hex(random_bytes(50));
                $expires = date('Y-m-d H:i:s', time() + 86400);
                
                if ($type == 'student') {
                    $update = $conn->prepare("UPDATE students SET verification_token = ?, verification_expires = ? WHERE email = ?");
                } else {
                    $update = $conn->prepare("UPDATE shenmo_user SET verification_token = ?, verification_expires = ? WHERE email = ?");
                }
                $update->bind_param("sss", $token, $expires, $email);
                $update->execute();
                $update->close();
                
                require_once 'includes/mailer.php';
                $mailer = new Mailer();
                $result = $mailer->sendVerificationEmail($email, $user['full_name'], $token, $type);
                
                if ($result['success']) {
                    $message = "Verification email sent! Please check your inbox and click the verification link.";
                } else {
                    $error = "Failed to send email: " . $result['message'];
                }
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resend Verification - Abacus Academy</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 20px;
        }
        
        .resend-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.5s;
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(40px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .logo {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        h1 { 
            text-align: center; 
            color: #2d3748; 
            margin-bottom: 8px; 
            font-size: 1.8rem; 
        }
        
        .subtitle { 
            text-align: center; 
            color: #718096; 
            margin-bottom: 30px; 
            font-size: 0.95rem; 
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #4a5568; 
            font-size: 0.9rem; 
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15); 
        }
        
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
            width: 100%;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); 
        }
        
        .message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .message.success { 
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        
        .message.error { 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #718096;
            font-size: 0.95rem;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="resend-card">
        <div class="logo">📧</div>
        <h1>Resend Verification Email</h1>
        <p class="subtitle">Enter your email to receive a new verification link</p>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your registered email">
            </div>
            
            <div class="form-group">
                <label for="type">Account Type</label>
                <select id="type" name="type" required>
                    <option value="">Select type</option>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Send Verification Email</button>
        </form>

        <div class="login-link">
            Remember your password? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
