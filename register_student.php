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
    $student_id = trim($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $grade_level = trim($_POST['grade_level']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'] ?? '';
    $address = trim($_POST['address']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($student_id) || empty($full_name) || empty($grade_level) || empty($email) || empty($phone) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = $conn->prepare("SELECT student_id FROM students WHERE student_id = ? OR username = ?");
        $check->bind_param("ss", $student_id, $username);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "Student ID or Username already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO students (student_id, full_name, grade_level, email, phone, dob, address, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $student_id, $full_name, $grade_level, $email, $phone, $dob, $address, $username, $password);
            
            if ($stmt->execute()) {
                $token = bin2hex(random_bytes(50));
                $expires = date('Y-m-d H:i:s', time() + 86400);
                
                $update = $conn->prepare("UPDATE students SET verification_token = ?, verification_expires = ? WHERE student_id = ?");
                $update->bind_param("sss", $token, $expires, $student_id);
                $update->execute();
                $update->close();
                
                require_once 'includes/mailer.php';
                $mailer = new Mailer();
                $result = $mailer->sendVerificationEmail($email, $full_name, $token, 'student');
                
                $message = "Registration successful! A verification email has been sent to <strong>$email</strong>. Please check your inbox and click the verification link to activate your account.";
                $_POST = array();
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
    <title>Student Registration - Abacus Academy</title>
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
        
        .register-btn {
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
        
        .register-btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); 
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">👨‍🎓</div>
        <h1>Student Registration</h1>
        <p class="subtitle">Create your student account</p>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="student_id">Student ID *</label>
                    <input type="text" id="student_id" name="student_id" required value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="grade_level">Abacus Level *</label>
                    <select id="grade_level" name="grade_level" required>
                        <option value="">Select Level</option>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (($_POST['grade_level'] ?? '') == $i) ? 'selected' : ''; ?>>Level <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit" class="register-btn">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</body>
</html>
