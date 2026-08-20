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

$lessons_result = $conn->query("
    SELECT l.*, c.course_name, c.level_number, sp.progress_percentage, sp.is_completed
    FROM lessons l
    JOIN courses c ON l.course_id = c.id
    LEFT JOIN student_progress sp ON l.course_id = sp.course_id AND sp.student_id = '$student_id'
    WHERE l.is_published = 1
    ORDER BY c.level_number ASC, l.sort_order ASC
");

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lessons - Abacus Academy</title>
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
        
        .lessons-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .lesson-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            animation: slideUp 0.5s;
        }
        .lesson-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .lesson-info h3 {
            color: #1e293b;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .lesson-info p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .lesson-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .lesson-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .lesson-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
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
        .btn-success { 
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%); 
            color: white; 
        }
        .btn-success:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4); 
        }
        .btn-secondary { 
            background: #e2e8f0; 
            color: #475569; 
        }
        .btn-secondary:hover { 
            background: #cbd5e1; 
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
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-in-progress { background: #fef3c7; color: #92400e; }
        .badge-locked { background: #fee2e2; color: #991b1b; }
        
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
                    <a href="lessons.php" class="nav-link active">
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
                    <h2>Lessons</h2>
                    <p>Continue your abacus learning journey</p>
                </div>
            </div>
            
            <div class="lessons-list">
                <?php if ($lessons_result && $lessons_result->num_rows > 0): ?>
                    <?php while($lesson = $lessons_result->fetch_assoc()): 
                        $is_completed = $lesson['is_completed'] ?? false;
                        $progress = $lesson['progress_percentage'] ?? 0;
                    ?>
                        <div class="lesson-card">
                            <div class="lesson-header">
                                <div class="lesson-info">
                                    <h3>
                                        Level <?php echo $lesson['level_number']; ?> - <?php echo htmlspecialchars($lesson['lesson_title']); ?>
                                        <?php if ($is_completed): ?>
                                            <span class="badge badge-completed">✓ Completed</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p><?php echo htmlspecialchars($lesson['lesson_description']); ?></p>
                                </div>
                            </div>
                            
                            <div class="lesson-meta">
                                <div class="lesson-meta-item">
                                    <span>⏱️</span>
                                    <span><?php echo $lesson['duration_minutes']; ?> minutes</span>
                                </div>
                                <div class="lesson-meta-item">
                                    <span>📚</span>
                                    <span>Level <?php echo $lesson['level_number']; ?></span>
                                </div>
                                <div class="lesson-meta-item">
                                    <span>📊</span>
                                    <span><?php echo round($progress); ?>% complete</span>
                                </div>
                            </div>
                            
                            <div class="lesson-actions">
                                <a href="course_viewer.php?course_id=<?php echo $lesson['course_id']; ?>" class="btn btn-primary">
                                    📖 View Lesson
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">📚</div>
                        <h3>No lessons available yet</h3>
                        <p>Check back later for new content.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
