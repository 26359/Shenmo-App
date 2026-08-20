<?php
session_start();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? '';
    
    if (empty($role)) {
        $error = 'Please select a role.';
    } else {
        if ($role == 'student') {
            header("Location: register_student.php");
            exit;
        } elseif ($role == 'admin') {
            header("Location: register_admin.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register - Abacus Academy</title>
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
            padding: 45px 40px;
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
            font-size: 3.5rem;
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
        
        .role-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .role-card {
            padding: 25px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .role-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
        }
        
        .role-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
        }
        
        .role-card input[type="radio"] {
            display: none;
        }
        
        .role-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .role-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .role-desc {
            font-size: 0.85rem;
            color: #718096;
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
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">🎓</div>
        <h1>Create Account</h1>
        <p class="subtitle">Join Abacus Academy today</p>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label>I am a:</label>
                <div class="role-cards">
                    <label class="role-card" onclick="selectRole('student')">
                        <input type="radio" name="role" value="student" required>
                        <div class="role-icon">👨‍🎓</div>
                        <div class="role-title">Student</div>
                        <div class="role-desc">Learn abacus</div>
                    </label>
                    <label class="role-card" onclick="selectRole('admin')">
                        <input type="radio" name="role" value="admin" required>
                        <div class="role-icon">👨‍💼</div>
                        <div class="role-title">Admin</div>
                        <div class="role-desc">Manage academy</div>
                    </label>
                </div>
            </div>

            <button type="submit" class="register-btn">Continue →</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

    <script>
        function selectRole(role) {
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.target.closest('.role-card').classList.add('selected');
            event.target.closest('.role-card').querySelector('input[type="radio"]').checked = true;
        }
    </script>
</body>
</html>
