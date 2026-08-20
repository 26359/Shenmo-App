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

$progress_result = $conn->query("
    SELECT 
        COUNT(DISTINCT e.course_id) as enrolled_courses,
        COUNT(DISTINCT l.id) as total_lessons,
        COUNT(DISTINCT CASE WHEN sp.is_completed = 1 THEN l.id END) as completed_lessons,
        COUNT(DISTINCT h.id) as total_homework,
        COUNT(DISTINCT CASE WHEN h.status = 'submitted' OR h.status = 'graded' THEN h.id END) as completed_homework,
        COUNT(DISTINCT er.id) as total_exams,
        COUNT(DISTINCT CASE WHEN er.passed = 1 THEN er.id END) as passed_exams,
        COALESCE(AVG(CASE WHEN er.score IS NOT NULL THEN er.score END), 0) as avg_exam_score,
        COUNT(DISTINCT a.id) as total_attendance,
        COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) as present_days
    FROM students s
    LEFT JOIN enrollments e ON s.student_id = e.student_id
    LEFT JOIN lessons l ON e.course_id = l.course_id
    LEFT JOIN student_progress sp ON s.student_id = sp.student_id AND l.course_id = sp.course_id
    LEFT JOIN homework h ON s.student_id = h.student_id
    LEFT JOIN exam_results er ON s.student_id = er.student_id
    LEFT JOIN attendance a ON s.student_id = a.student_id
    WHERE s.student_id = '$student_id'
");

$progress_stats = $progress_result->fetch_assoc();

$weekly_activity = $conn->query("
    SELECT 
        DATE(created_at) as activity_date,
        COUNT(*) as activity_count
    FROM xp_points
    WHERE student_id = '$student_id'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY activity_date ASC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress - Abacus Academy</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .progress-section {
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
        }
        
        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 6px;
            transition: width 0.5s ease;
        }
        
        .progress-item {
            margin-bottom: 20px;
        }
        .progress-item:last-child {
            margin-bottom: 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: #475569;
            font-weight: 500;
        }
        
        .activity-chart {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            height: 200px;
            padding: 20px 0;
            overflow-x: auto;
            min-width: 0;
        }
        .activity-bar-wrap { flex: 1; min-width: 32px; display: flex; flex-direction: column; align-items: center; }
        
        .activity-bar {
            flex: 1;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 8px 8px 0 0;
            min-height: 10px;
            transition: all 0.3s;
            position: relative;
        }
        .activity-bar:hover {
            transform: scaleY(1.05);
            filter: brightness(1.1);
        }
        
        .activity-label {
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 260px; position: fixed; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .dashboard { display: block; }
            .hamburger { display: block; }
        }
        @media(max-width:480px){ .main-content { padding: 14px; } .stats-grid { grid-template-columns: 1fr 1fr; } }
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}
        .hamburger{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#475569;margin-right:10px}
    </style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
<div class="dashboard">
        <aside class="sidebar" id="sidebar">
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
                    <a href="progress.php" class="nav-link active">
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
                <div class="header-title" style="display:flex;align-items:center;gap:12px">
                    <button class="hamburger" onclick="toggleSidebar()">☰</button>
                    <div>
                        <h2>Progress Tracking</h2>
                        <p>Monitor your learning journey and achievements</p>
                    </div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $progress_stats['enrolled_courses'] ?? 0; ?></div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $progress_stats['completed_lessons'] ?? 0; ?>/<?php echo $progress_stats['total_lessons'] ?? 0; ?></div>
                    <div class="stat-label">Lessons Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $progress_stats['completed_homework'] ?? 0; ?>/<?php echo $progress_stats['total_homework'] ?? 0; ?></div>
                    <div class="stat-label">Homework Done</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $progress_stats['passed_exams'] ?? 0; ?>/<?php echo $progress_stats['total_exams'] ?? 0; ?></div>
                    <div class="stat-label">Exams Passed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo round($progress_stats['avg_exam_score'] ?? 0); ?>%</div>
                    <div class="stat-label">Average Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $progress_stats['present_days'] ?? 0; ?>%</div>
                    <div class="stat-label">Attendance</div>
                </div>
            </div>
            
            <div class="progress-section">
                <div class="section-title">📊 Weekly Activity</div>
                <div class="activity-chart">
                    <?php
                    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    $activity_data = [];
                    while ($row = $weekly_activity->fetch_assoc()) {
                        $activity_data[$row['activity_date']] = $row['activity_count'];
                    }
                    
                    for ($i = 6; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $count = $activity_data[$date] ?? 0;
                        $height = max(10, $count * 20);
                    ?>
                        <div class="activity-bar-wrap">
                            <div class="activity-bar" style="height: <?php echo $height; ?>px; width:100%;"></div>
                            <div class="activity-label"><?php echo $days[date('w', strtotime($date))]; ?></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            
            <div class="progress-section">
                <div class="section-title">📈 Overall Progress</div>
                
                <div class="progress-item">
                    <div class="progress-label">
                        <span>Lessons Completion</span>
                        <span><?php echo $progress_stats['total_lessons'] > 0 ? round(($progress_stats['completed_lessons'] / $progress_stats['total_lessons']) * 100) : 0; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress_stats['total_lessons'] > 0 ? round(($progress_stats['completed_lessons'] / $progress_stats['total_lessons']) * 100) : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="progress-item">
                    <div class="progress-label">
                        <span>Homework Completion</span>
                        <span><?php echo $progress_stats['total_homework'] > 0 ? round(($progress_stats['completed_homework'] / $progress_stats['total_homework']) * 100) : 0; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress_stats['total_homework'] > 0 ? round(($progress_stats['completed_homework'] / $progress_stats['total_homework']) * 100) : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="progress-item">
                    <div class="progress-label">
                        <span>Exam Performance</span>
                        <span><?php echo $progress_stats['total_exams'] > 0 ? round(($progress_stats['passed_exams'] / $progress_stats['total_exams']) * 100) : 0; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress_stats['total_exams'] > 0 ? round(($progress_stats['passed_exams'] / $progress_stats['total_exams']) * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('active');
}
</script>
</body>
</html>
