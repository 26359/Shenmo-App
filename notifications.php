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

$notifications_result = $conn->query("
    SELECT * FROM notifications
    WHERE user_id = '$student_id' AND user_role = 'student'
    ORDER BY created_at DESC
    LIMIT 50
");

$unread_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = '$student_id' AND user_role = 'student' AND is_read = 0")->fetch_assoc()['count'];

// Mark all as read when page is opened
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id='$student_id' AND user_role='student' AND is_read=0");

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notifications - Abacus Academy</title>
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
        
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .notification-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            animation: slideUp 0.5s;
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .notification-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .notification-card.unread {
            border-left: 4px solid #3b82f6;
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .icon-homework { background: #dbeafe; }
        .icon-lesson { background: #d1fae5; }
        .icon-exam { background: #fef3c7; }
        .icon-payment { background: #fce7f3; }
        .icon-certificate { background: #ede9fe; }
        .icon-announcement { background: #fee2e2; }
        .icon-message { background: #e0e7ff; }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-content h4 {
            color: #1e293b;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .notification-content p {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        .notification-time {
            color: #94a3b8;
            font-size: 0.85rem;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-unread { background: #dbeafe; color: #1e40af; }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        .no-data-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
                    <a href="notifications.php" class="nav-link active">
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
                    <a href="settings.php" class="nav-link">
                        <span class="icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <div class="header-title">
                    <h2>Notifications</h2>
                    <p>You have <?php echo $unread_count; ?> unread notifications</p>
                </div>
            </div>
            
            <div class="notifications-list">
                <?php if ($notifications_result && $notifications_result->num_rows > 0): ?>
                    <?php while($notif = $notifications_result->fetch_assoc()): 
                        $icon_class = 'icon-' . $notif['notification_type'];
                        $icon_emoji = match($notif['notification_type']) {
                            'homework' => '📝',
                            'lesson' => '📖',
                            'exam' => '📋',
                            'payment' => '💳',
                            'certificate' => '🏆',
                            'announcement' => '📢',
                            'message' => '💬',
                            default => '🔔'
                        };
                    ?>
                        <div class="notification-card <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-icon <?php echo $icon_class; ?>">
                                <?php echo $icon_emoji; ?>
                            </div>
                            <div class="notification-content">
                                <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                                <p><?php echo nl2br(htmlspecialchars($notif['message'])); ?></p>
                                <div class="notification-time">
                                    <?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?>
                                    <?php if ($notif['is_read']): ?>
                                        <span style="color: #10b981; margin-left: 10px;">✓ Read</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">🔔</div>
                        <h3>No notifications yet</h3>
                        <p>You'll receive notifications about homework, lessons, exams, and more.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
