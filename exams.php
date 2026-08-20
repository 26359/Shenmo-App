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

$exams_result = $conn->query("
    SELECT e.*, c.course_name, c.level_number,
           er.score, er.grade, er.passed, er.completed_at
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN exam_results er ON e.id = er.exam_id AND er.student_id = '$student_id'
    WHERE e.is_published = 1
    ORDER BY e.exam_date DESC
");

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Exams - Abacus Academy</title>
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
        
        .exams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .exam-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            animation: slideUp 0.5s;
        }
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .exam-info h3 {
            color: #1e293b;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .exam-info p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .exam-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .exam-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 0.9rem;
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
        .badge-upcoming { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-passed { background: #d1fae5; color: #065f46; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        
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
                    <a href="exams.php" class="nav-link active">
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
                    <h2>Exams</h2>
                    <p>Test your knowledge and track your performance</p>
                </div>
            </div>
            
            <div class="exams-grid">
                <?php if ($exams_result && $exams_result->num_rows > 0): ?>
                    <?php while($exam = $exams_result->fetch_assoc()): ?>
                        <div class="exam-card">
                            <div class="exam-header">
                                <div class="exam-info">
                                    <h3>
                                        <?php echo htmlspecialchars($exam['exam_title']); ?>
                                    </h3>
                                    <p>Level <?php echo $exam['level_number']; ?> - <?php echo htmlspecialchars($exam['course_name']); ?></p>
                                </div>
                                <?php if ($exam['score']): ?>
                                    <span class="badge badge-<?php echo $exam['passed'] ? 'passed' : 'failed'; ?>">
                                        <?php echo $exam['passed'] ? '✓ Passed' : '✗ Failed'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-upcoming">Upcoming</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="exam-meta">
                                <div class="exam-meta-item">
                                    <span>📅</span>
                                    <span><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></span>
                                </div>
                                <div class="exam-meta-item">
                                    <span>⏱️</span>
                                    <span><?php echo $exam['duration_minutes']; ?> mins</span>
                                </div>
                                <div class="exam-meta-item">
                                    <span>📊</span>
                                    <span>Total: <?php echo $exam['total_marks']; ?> marks</span>
                                </div>
                                <?php if ($exam['score']): ?>
                                    <div class="exam-meta-item">
                                        <span>🎯</span>
                                        <span>Score: <?php echo $exam['score']; ?>%</span>
                                    </div>
                                    <div class="exam-meta-item">
                                        <span>📝</span>
                                        <span>Grade: <?php echo $exam['grade'] ?: 'N/A'; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$exam['score'] && strtotime($exam['exam_date']) > time()): ?>
                                <button class="btn btn-primary" onclick="alert('Exam functionality would start here')">
                                    📝 Start Exam
                                </button>
                            <?php elseif ($exam['score']): ?>
                                <button class="btn btn-success" onclick="alert('View detailed results')">
                                    📊 View Results
                                </button>
                            <?php else: ?>
                                <span style="color: #64748b; font-size: 0.9rem;">Exam has ended</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">📋</div>
                        <h3>No exams available</h3>
                        <p>Exams will appear here when scheduled by your teacher.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
