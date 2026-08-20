<?php
session_start();
$message = '';
$error   = '';

$host   = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user   = "4783798_shenmoapp";
$pass   = "muganwa123";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_names     = trim($_POST['user_names']);
    $user_country   = trim($_POST['user_country']);
    $user_city      = trim($_POST['user_city']);
    $use_telephone  = trim($_POST['use_telephone']);
    $user_password  = trim($_POST['user_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $user_birthdate = $_POST['user_birthdate'] ?? '';
    $email          = trim($_POST['email']);

    if (empty($user_names) || empty($user_country) || empty($user_city) || empty($use_telephone) || empty($user_password) || empty($email)) {
        $error = "Please fill in all required fields.";
    } elseif ($user_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($user_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM shenmo_user WHERE user_names = ?");
        $check->bind_param("s", $user_names);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Admin username already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO shenmo_user (user_names, user_country, user_city, use_telephone, user_password, user_birthdate, email, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssssss", $user_names, $user_country, $user_city, $use_telephone, $user_password, $user_birthdate, $email);
            if ($stmt->execute()) {
                $message = "Account created! You can now <a href='login.php' style='color:#667eea;font-weight:bold'>login here</a>.";
                $_POST = [];
            } else {
                $error = "Registration failed: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
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
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}
        .register-card{background:#fff;padding:40px;border-radius:24px;box-shadow:0 25px 60px rgba(0,0,0,0.3);width:100%;max-width:600px;animation:slideUp 0.5s}
        @keyframes slideUp{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
        .logo{text-align:center;font-size:3rem;margin-bottom:10px}
        h1{text-align:center;color:#2d3748;margin-bottom:8px;font-size:1.8rem}
        .subtitle{text-align:center;color:#718096;margin-bottom:30px;font-size:0.95rem}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:15px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;margin-bottom:8px;font-weight:600;color:#4a5568;font-size:0.9rem}
        .form-group input{width:100%;padding:12px 16px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px;transition:all 0.3s}
        .form-group input:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1)}
        .btn{width:100%;padding:14px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:12px;cursor:pointer;font-size:16px;font-weight:600;margin-top:10px;transition:all 0.3s}
        .btn:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(102,126,234,0.4)}
        .message{padding:14px 18px;border-radius:12px;margin-bottom:20px;text-align:center;font-weight:600;font-size:0.95rem}
        .message.success{background:linear-gradient(135deg,#d4edda,#c3e6cb);color:#155724;border:1px solid #c3e6cb}
        .message.error{background:linear-gradient(135deg,#f8d7da,#f5c6cb);color:#721c24;border:1px solid #f5c6cb}
        .login-link{text-align:center;margin-top:20px;color:#718096;font-size:0.95rem}
        .login-link a{color:#667eea;text-decoration:none;font-weight:600}
        @media(max-width:768px){.form-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">👨‍💼</div>
        <h1>Admin Registration</h1>
        <p class="subtitle">Create an administrator account</p>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="user_names" required value="<?php echo htmlspecialchars($_POST['user_names'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Country *</label>
                    <input type="text" name="user_country" required value="<?php echo htmlspecialchars($_POST['user_country'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="user_city" required value="<?php echo htmlspecialchars($_POST['user_city'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="use_telephone" required value="<?php echo htmlspecialchars($_POST['use_telephone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Birthdate</label>
                    <input type="date" name="user_birthdate" value="<?php echo htmlspecialchars($_POST['user_birthdate'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="user_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
            </div>
            <button type="submit" class="btn">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</body>
</html>
