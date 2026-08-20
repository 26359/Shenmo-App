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

$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? '';

if (empty($token) || empty($type)) {
    $error = "Invalid verification link.";
} else {
    if ($type == 'student') {
        $stmt = $conn->prepare("SELECT id, email_verified, verification_expires FROM students WHERE verification_token = ?");
    } elseif ($type == 'admin') {
        $stmt = $conn->prepare("SELECT user_id, email_verified, verification_expires FROM shenmo_user WHERE verification_token = ?");
    } else {
        $error = "Invalid verification type.";
    }
    
    if (empty($error)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            $error = "Invalid verification token.";
        } elseif ($user['email_verified']) {
            $message = "Email already verified! You can now login.";
        } elseif (strtotime($user['verification_expires']) < time()) {
            $error = "Verification link has expired. Please request a new one.";
        } else {
            if ($type == 'student') {
                $update = $conn->prepare("UPDATE students SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE verification_token = ?");
            } else {
                $update = $conn->prepare("UPDATE shenmo_user SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE verification_token = ?");
            }
            $update->bind_param("s", $token);
            $update->execute();
            $update->close();
            
            $message = "Email verified successfully! You can now login to your account.";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Verification - Abacus Academy</title>
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
        
        .verify-card {
            background: white;
            padding: 45px 40px;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            text-align: center;
            animation: slideUp 0.5s;
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(40px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .logo {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        h1 { 
            color: #2d3748; 
            margin-bottom: 15px; 
            font-size: 1.8rem; 
        }
        
        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
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
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            margin-top: 20px;
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
    <div class="verify-card">
        <div class="logo">📧</div>
        <h1>Email Verification</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <a href="login.php" class="btn btn-primary">Go to Login</a>
    </div>
</body>
</html>
