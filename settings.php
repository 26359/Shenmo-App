<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$dbname = "shenmo_app";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student_id = $_SESSION['student_id'];
$student = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'")->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Settings - Abacus Academy</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; min-height: 100vh; }
        
        .dashboard { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-right: 1px solid #e2e8f0;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            z-index: 100;
        }
        
        .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .logo h1 { color: #3b82f6; font-size: 1.5rem; font-weight: 700; }
        
        .nav-menu { list-style: none; padding: 0 10px; }
        .nav-item { margin-bottom: 5px; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #475569;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
            color: #3b82f6;
            transform: translateX(5px);
        }
        .nav-link .icon { font-size: 1.2rem; width: 24px; text-align: center; }
        
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 25px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header-title h2 { color: #1e293b; font-size: 1.8rem; font-weight: 700; }
        .header-title p { color: #64748b; font-size: 0.95rem; margin-top: 5px; }
        
        .settings-section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-info h4 {
            color: #1e293b;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .setting-info p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .toggle {
            position: relative;
            width: 50px;
            height: 26px;
            background: #cbd5e1;
            border-radius: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .toggle.active {
            background: #3b82f6;
        }
        
        .toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle.active .toggle-slider {
            left: 27px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
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
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); 
            color: white; 
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4); 
        }
        .btn-danger { 
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); 
            color: white; 
        }
        .btn-danger:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4); 
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; }
            .dashboard { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="logo">
                <h1>🎓 Abacus Academy</h1>
                <p>Learning Portal</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="student_dashboard.php" class="nav-link">
                        <span class="icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="my_learning.php" class="nav-link">
                        <span class="icon">📚</span>
                        <span>My Learning</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="abacus_levels.php" class="nav-link">
                        <span class="icon">🎯</span>
                        <span>Abacus Levels</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="lessons.php" class="nav-link">
                        <span class="icon">📖</span>
                        <span>Lessons</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="practice.php" class="nav-link">
                        <span class="icon">💪</span>
                        <span>Practice</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="homework.php" class="nav-link">
                        <span class="icon">📝</span>
                        <span>Homework</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="exams.php" class="nav-link">
                        <span class="icon">📋</span>
                        <span>Exams</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="certificates.php" class="nav-link">
                        <span class="icon">🏆</span>
                        <span>Certificates</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link">
                        <span class="icon">📅</span>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="progress.php" class="nav-link">
                        <span class="icon">📈</span>
                        <span>Progress</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="achievements.php" class="nav-link">
                        <span class="icon">⭐</span>
                        <span>Achievements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="leaderboard.php" class="nav-link">
                        <span class="icon">🏅</span>
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="student_payments.php" class="nav-link">
                        <span class="icon">💳</span>
                        <span>Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="messages.php" class="nav-link">
                        <span class="icon">💬</span>
                        <span>Messages</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="notifications.php" class="nav-link">
                        <span class="icon">🔔</span>
                        <span>Notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <span class="icon">👤</span>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link active">
                        <span class="icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div class="header-title">
                    <h2>Settings</h2>
                    <p>Manage your account preferences</p>
                </div>
            </div>
            
            <div class="settings-section">
                <div class="section-title">🔔 Notifications</div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Email Notifications</h4>
                        <p>Receive email updates about homework and exams</p>
                    </div>
                    <div class="toggle active" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Push Notifications</h4>
                        <p>Receive browser push notifications</p>
                    </div>
                    <div class="toggle active" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Homework Reminders</h4>
                        <p>Get reminded before homework deadlines</p>
                    </div>
                    <div class="toggle" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
            </div>
            
            <div class="settings-section">
                <div class="section-title">🎨 Appearance</div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Dark Mode</h4>
                        <p>Switch to dark theme (coming soon)</p>
                    </div>
                    <div class="toggle" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Compact Mode</h4>
                        <p>Use compact spacing for better visibility</p>
                    </div>
                    <div class="toggle" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
            </div>
            
            <div class="settings-section">
                <div class="section-title">🔒 Account</div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Change Password</h4>
                        <p>Update your account password</p>
                    </div>
                    <a href="change_password.php" class="btn btn-primary">Change Password</a>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Delete Account</h4>
                        <p>Permanently delete your account and all data</p>
                    </div>
                    <button class="btn btn-danger" onclick="alert('Account deletion would be confirmed here')">Delete Account</button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
