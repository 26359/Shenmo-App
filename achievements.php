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

$achievements_result = $conn->query("
    SELECT a.*, sa.earned_at
    FROM achievements a
    LEFT JOIN student_achievements sa ON a.id = sa.achievement_id AND sa.student_id = '$student_id'
    WHERE a.is_active = 1
    ORDER BY sa.earned_at DESC
");

$total_achievements = $conn->query("SELECT COUNT(*) as count FROM student_achievements WHERE student_id = '$student_id'")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Achievements - Abacus Academy</title>
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
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .achievement-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s;
            animation: slideUp 0.5s;
        }
        .achievement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .achievement-card.earned {
            border: 2px solid #fbbf24;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .achievement-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .achievement-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .achievement-description {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .achievement-reward {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .achievement-status {
            margin-top: 15px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .status-earned { color: #065f46; }
        .status-locked { color: #64748b; }
        
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
                    <a href="achievements.php" class="nav-link active">
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
                    <h2>Achievements</h2>
                    <p>You have earned <?php echo $total_achievements; ?> achievements</p>
                </div>
            </div>
            
            <div class="achievements-grid">
                <?php if ($achievements_result && $achievements_result->num_rows > 0): ?>
                    <?php while($achievement = $achievements_result->fetch_assoc()): 
                        $earned = $achievement['earned_at'] ? true : false;
                    ?>
                        <div class="achievement-card <?php echo $earned ? 'earned' : ''; ?>">
                            <div class="achievement-icon"><?php echo htmlspecialchars($achievement['icon']); ?></div>
                            <div class="achievement-title"><?php echo htmlspecialchars($achievement['achievement_name']); ?></div>
                            <div class="achievement-description"><?php echo htmlspecialchars($achievement['description']); ?></div>
                            <div class="achievement-reward">
                                ⭐ +<?php echo $achievement['points_reward']; ?> XP
                            </div>
                            <div class="achievement-status <?php echo $earned ? 'status-earned' : 'status-locked'; ?>">
                                <?php echo $earned ? '✓ Earned on ' . date('M d, Y', strtotime($achievement['earned_at'])) : '🔒 Not yet earned'; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data" style="grid-column: 1 / -1;">
                        <div class="no-data-icon">⭐</div>
                        <h3>No achievements available</h3>
                        <p>Keep learning to unlock achievements!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
