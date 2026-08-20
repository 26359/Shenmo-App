<?php
session_start();
$message = '';
$error = '';
$show_otp_form = false;

$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_otp'])) {
    $user_names = trim($_POST['user_names']);
    $user_country = trim($_POST['user_country']);
    $user_city = trim($_POST['user_city']);
    $use_telephone = trim($_POST['use_telephone']);
    $user_password = trim($_POST['user_password']);
    $user_birthdate = $_POST['user_birthdate'] ?? '';
    $email = trim($_POST['email']);

    if (empty($user_names) || empty($user_country) || empty($user_city) || empty($use_telephone) || empty($user_password) || empty($email)) {
        $error = "Please fill in all required fields.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM shenmo_user WHERE user_names = ?");
        $check->bind_param("s", $user_names);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "Admin username already exists.";
        } else {
            $otp = rand(100000, 999999);
            $_SESSION['admin_otp'] = $otp;
            $_SESSION['admin_otp_expiry'] = time() + 300;
            $_SESSION['admin_registration_data'] = [
                'user_names' => $user_names,
                'user_country' => $user_country,
                'user_city' => $user_city,
                'use_telephone' => $use_telephone,
                'user_password' => $user_password,
                'user_birthdate' => $user_birthdate,
                'email' => $email
            ];
            
            $show_otp_form = true;
            $message = "OTP sent successfully! (For testing: Your OTP is <strong>$otp</strong>)";
        }
        $check->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp']);
    
    if (!isset($_SESSION['admin_otp']) || !isset($_SESSION['admin_registration_data'])) {
        $error = "OTP session expired. Please try again.";
    } elseif (time() > $_SESSION['admin_otp_expiry']) {
        $error = "OTP has expired. Please request a new one.";
        unset($_SESSION['admin_otp'], $_SESSION['admin_otp_expiry'], $_SESSION['admin_registration_data']);
    } elseif ($entered_otp != $_SESSION['admin_otp']) {
        $error = "Invalid OTP. Please try again.";
    } else {
        $data = $_SESSION['admin_registration_data'];
        
        $stmt = $conn->prepare("INSERT INTO shenmo_user (user_names, user_country, user_city, use_telephone, user_password, user_birthdate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $data['user_names'], $data['user_country'], $data['user_city'], $data['use_telephone'], $data['user_password'], $data['user_birthdate']);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            $token = bin2hex(random_bytes(50));
            $expires = date('Y-m-d H:i:s', time() + 86400);
            
            $update = $conn->prepare("UPDATE shenmo_user SET verification_token = ?, verification_expires = ? WHERE user_id = ?");
            $update->bind_param("ssi", $token, $expires, $user_id);
            $update->execute();
            $update->close();
            
            require_once 'includes/mailer.php';
            $mailer = new Mailer();
            $result = $mailer->sendVerificationEmail($data['email'], $data['user_names'], $token, 'admin');
            
            $message = "Admin account created successfully! A verification email has been sent to <strong>" . htmlspecialchars($data['email']) . "</strong>. Please check your inbox and click the verification link to activate your account.";
            unset($_SESSION['admin_otp'], $_SESSION['admin_otp_expiry'], $_SESSION['admin_registration_data']);
            $_POST = array();
            $show_otp_form = false;
        } else {
            $error = "Registration failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

if (isset($_GET['resend'])) {
    if (isset($_SESSION['admin_registration_data'])) {
        $otp = rand(100000, 999999);
        $_SESSION['admin_otp'] = $otp;
        $_SESSION['admin_otp_expiry'] = time() + 300;
        $message = "New OTP sent! (For testing: Your OTP is <strong>$otp</strong>)";
        $show_otp_form = true;
    } else {
        $error = "Session expired. Please start again.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Registration - Abacus Academy</title>
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
        
        .register-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); 
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); 
        }
        
        .btn-secondary { 
            background: #e2e8f0; 
            color: #475569; 
        }
        .btn-secondary:hover { 
            background: #cbd5e1; 
        }
        
        .btn-success { 
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%); 
            color: white; 
        }
        .btn-success:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); 
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
        
        .otp-section {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 25px;
            text-align: center;
            border: 2px dashed #667eea;
        }
        
        .otp-section h3 {
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .otp-section p {
            color: #475569;
            margin-bottom: 20px;
        }
        
        .otp-input {
            max-width: 200px;
            margin: 0 auto 15px;
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 8px;
            font-weight: 700;
        }
        
        .hidden {
            display: none;
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">👨‍💼</div>
        <h1>Admin Registration</h1>
        <p class="subtitle">Create an administrator account (requires verification)</p>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!$show_otp_form): ?>
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_names">Username *</label>
                        <input type="text" id="user_names" name="user_names" required value="<?php echo htmlspecialchars($_POST['user_names'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_country">Country *</label>
                        <input type="text" id="user_country" name="user_country" required value="<?php echo htmlspecialchars($_POST['user_country'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="user_city">City *</label>
                        <input type="text" id="user_city" name="user_city" required value="<?php echo htmlspecialchars($_POST['user_city'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="use_telephone">Phone Number *</label>
                        <input type="tel" id="use_telephone" name="use_telephone" required value="<?php echo htmlspecialchars($_POST['use_telephone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="user_birthdate">Birthdate</label>
                        <input type="date" id="user_birthdate" name="user_birthdate" value="<?php echo htmlspecialchars($_POST['user_birthdate'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_password">Password *</label>
                        <input type="password" id="user_password" name="user_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                
                <button type="submit" name="send_otp" class="btn btn-primary">Send OTP</button>
            </form>
        <?php else: ?>
            <div class="otp-section">
                <h3>🔐 Verify Your Identity</h3>
                <p>Enter the 6-digit OTP sent to your email/phone</p>
                
                <form method="post" action="">
                    <div class="form-group">
                        <input type="text" name="otp" class="otp-input" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus>
                    </div>
                    <button type="submit" name="verify_otp" class="btn btn-success">Verify & Create Account</button>
                </form>
                
                <div style="margin-top: 15px;">
                    <a href="?resend=1" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.9rem;">Resend OTP</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</body>
</html>
