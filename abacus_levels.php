<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student_id = $_SESSION['student_id'];
$student = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'")->fetch_assoc();

$levels_result = $conn->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY level_number ASC");

$enrollments_result = $conn->query("
    SELECT e.*, c.course_name, c.description, c.level_number 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.student_id = '$student_id'
");

$enrolled_courses = [];
while ($enrollment = $enrollments_result->fetch_assoc()) {
    $enrolled_courses[$enrollment['course_id']] = $enrollment;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Abacus Levels - Abacus Academy</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
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
        .logo h1 {
            color: #3b82f6;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0 10px;
        }
        .nav-item {
            margin-bottom: 5px;
        }
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
        .nav-link .icon {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }
        
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
        
        .header-title h2 {
            color: #1e293b;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .levels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .level-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s;
        }
        .level-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(30px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .level-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
        }
        .level-card.level-1::before { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); }
        .level-card.level-2::before { background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); }
        .level-card.level-3::before { background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); }
        .level-card.level-4::before { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); }
        .level-card.level-5::before { background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); }
        .level-card.level-6::before { background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%); }
        
        .level-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .level-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .level-1 .level-badge { background: #d1fae5; color: #065f46; }
        .level-2 .level-badge { background: #dbeafe; color: #1e40af; }
        .level-3 .level-badge { background: #ede9fe; color: #5b21b6; }
        .level-4 .level-badge { background: #fef3c7; color: #92400e; }
        .level-5 .level-badge { background: #fee2e2; color: #991b1b; }
        .level-6 .level-badge { background: #fce7f3; color: #9d174d; }
        
        .level-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-unlocked { background: #d1fae5; color: #065f46; }
        .status-locked { background: #fee2e2; color: #991b1b; }
        .status-in-progress { background: #fef3c7; color: #92400e; }
        
        .level-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .level-description {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .level-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .level-progress {
            margin-bottom: 20px;
        }
        .level-progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #475569;
        }
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 5px;
            transition: width 0.5s ease;
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
            width: 100%;
        }
        .btn-primary { 
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); 
            color: white; 
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4); 
        }
        .btn-success { 
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%); 
            color: white; 
        }
        .btn-success:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); 
        }
        .btn-secondary { 
            background: #e2e8f0; 
            color: #475569; 
        }
        .btn-secondary:hover { 
            background: #cbd5e1; 
        }
        .btn-warning { 
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); 
            color: white; 
        }
        .btn-warning:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4); 
        }
        
        .locked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            z-index: 10;
        }
        .locked-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .locked-text {
            color: #64748b;
            font-weight: 600;
            font-size: 1.1rem;
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
                    <a href="abacus_levels.php" class="nav-link active">
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
                    <h2>Abacus Levels</h2>
                    <p>Master each level to become an abacus expert</p>
                </div>
                <div class="header-actions">
                    <a href="student_payments.php" class="btn btn-warning">💳 Upgrade Access</a>
                </div>
            </div>
            
            <div class="levels-grid">
                <?php while($level = $levels_result->fetch_assoc()): 
                    $is_enrolled = isset($enrolled_courses[$level['id']]);
                    $payment_status = $is_enrolled ? $enrolled_courses[$level['id']]['payment_status'] : 'unpaid';
                    $has_access = $is_enrolled && $payment_status === 'paid';
                    
                    $progress = $conn->query("
                        SELECT sp.progress_percentage, sp.completed_lessons, sp.total_lessons
                        FROM student_progress sp
                        WHERE sp.student_id = '$student_id' AND sp.course_id = " . $level['id'] . "
                    ")->fetch_assoc();
                    
                    $progress_percentage = $progress ? round($progress['progress_percentage']) : 0;
                    $completed_lessons = $progress ? $progress['completed_lessons'] : 0;
                    $total_lessons = $progress ? $progress['total_lessons'] : 0;
                ?>
                    <div class="level-card level-<?php echo $level['level_number']; ?>" style="position: relative;">
                        <?php if (!$has_access): ?>
                            <div class="locked-overlay">
                                <div class="locked-icon">🔒</div>
                                <div class="locked-text">Complete Level <?php echo $level['level_number'] - 1; ?> to unlock</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="level-header">
                            <div class="level-badge">Level <?php echo $level['level_number']; ?></div>
                            <div class="level-status status-<?php echo $has_access ? 'in-progress' : 'locked'; ?>">
                                <?php echo $has_access ? 'In Progress' : 'Locked'; ?>
                            </div>
                        </div>
                        
                        <div class="level-title"><?php echo htmlspecialchars($level['course_name']); ?></div>
                        <div class="level-description"><?php echo htmlspecialchars($level['description']); ?></div>
                        
                        <div class="level-meta">
                            <div style="color: #64748b; font-size: 0.9rem;">
                                ⏱️ <?php echo $level['duration_weeks']; ?> weeks
                            </div>
                            <div style="font-weight: 700; color: #1e293b;">
                                RWF <?php echo number_format($level['fee_amount']); ?>
                            </div>
                        </div>
                        
                        <div class="level-progress">
                            <div class="level-progress-label">
                                <span>Progress</span>
                                <span><?php echo $progress_percentage; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                            </div>
                            <div style="color: #64748b; font-size: 0.85rem; margin-top: 8px;">
                                <?php echo $completed_lessons; ?> of <?php echo $total_lessons; ?> lessons completed
                            </div>
                        </div>
                        
                        <?php if ($has_access): ?>
                            <a href="course_viewer.php?course_id=<?php echo $level['id']; ?>" class="btn btn-primary">
                                📖 Continue Learning
                            </a>
                        <?php elseif ($is_enrolled && $payment_status === 'partial'): ?>
                            <a href="student_payments.php?course_id=<?php echo $level['id']; ?>" class="btn btn-warning">
                                💳 Complete Payment
                            </a>
                        <?php else: ?>
                            <a href="student_payments.php?course_id=<?php echo $level['id']; ?>" class="btn btn-success">
                                🔓 Unlock Level
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conn->close(); ?>
