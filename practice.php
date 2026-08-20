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

$practice_history = $conn->query("
    SELECT xp.*, c.course_name
    FROM xp_points xp
    LEFT JOIN courses c ON xp.source_id = c.id
    WHERE xp.student_id = '$student_id'
    AND xp.xp_source IN ('practice', 'lesson_complete')
    ORDER BY xp.created_at DESC
    LIMIT 20
");

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Practice - Abacus Academy</title>
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
        
        .practice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .practice-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            animation: slideUp 0.5s;
        }
        .practice-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .practice-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .practice-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .practice-description {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
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
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4); 
        }
        
        .history-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .history-card h3 {
            color: #1e293b;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-info h4 {
            color: #1e293b;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .history-info p {
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .xp-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
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
                    <a href="practice.php" class="nav-link active">
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
                    <h2>Practice</h2>
                    <p>Sharpen your abacus skills with practice exercises</p>
                </div>
            </div>
            
            <div class="practice-grid">
                <div class="practice-card" onclick="alert('Mental Math practice would start here')">
                    <div class="practice-icon">🧮</div>
                    <div class="practice-title">Mental Math</div>
                    <div class="practice-description">Practice calculations in your head without the abacus</div>
                    <button class="btn btn-primary">Start Practice</button>
                </div>
                
                <div class="practice-card" onclick="alert('Flash Cards practice would start here')">
                    <div class="practice-icon">🃏</div>
                    <div class="practice-title">Flash Cards</div>
                    <div class="practice-description">Quick recall practice with digital flash cards</div>
                    <button class="btn btn-primary">Start Practice</button>
                </div>
                
                <div class="practice-card" onclick="alert('Abacus Exercises would start here')">
                    <div class="practice-icon">🎯</div>
                    <div class="practice-title">Abacus Exercises</div>
                    <div class="practice-description">Interactive abacus manipulation exercises</div>
                    <button class="btn btn-primary">Start Practice</button>
                </div>
                
                <div class="practice-card" onclick="alert('Speed Challenge would start here')">
                    <div class="practice-icon">⚡</div>
                    <div class="practice-title">Speed Challenges</div>
                    <div class="practice-description">Race against time to solve problems quickly</div>
                    <button class="btn btn-primary">Start Challenge</button>
                </div>
                
                <div class="practice-card" onclick="alert('Daily Practice would start here')">
                    <div class="practice-icon">📅</div>
                    <div class="practice-title">Daily Practice</div>
                    <div class="practice-description">Your daily assigned practice problems</div>
                    <button class="btn btn-primary">Start Daily</button>
                </div>
            </div>
            
            <div class="history-card">
                <h3>📊 Recent Practice History</h3>
                <?php if ($practice_history && $practice_history->num_rows > 0): ?>
                    <?php while($practice = $practice_history->fetch_assoc()): ?>
                        <div class="history-item">
                            <div class="history-info">
                                <h4><?php echo ucfirst(str_replace('_', ' ', $practice['xp_source'])); ?></h4>
                                <p><?php echo $practice['course_name'] ?: 'General Practice'; ?> • <?php echo date('M d, Y H:i', strtotime($practice['created_at'])); ?></p>
                            </div>
                            <span class="xp-badge">⭐ +<?php echo $practice['xp_amount']; ?> XP</span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #64748b; text-align: center; padding: 20px;">No practice history yet. Start practicing to earn XP! 💪</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
