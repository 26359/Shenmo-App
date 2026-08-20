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

$leaderboard_result = $conn->query("
    SELECT 
        s.student_id,
        s.full_name,
        COUNT(DISTINCT sa.id) as total_achievements,
        COALESCE(SUM(xp.xp_amount), 0) as total_xp,
        COUNT(DISTINCT CASE WHEN sp.is_completed = 1 THEN sp.course_id END) as completed_levels
    FROM students s
    LEFT JOIN student_achievements sa ON s.student_id = sa.student_id
    LEFT JOIN xp_points xp ON s.student_id = xp.student_id
    LEFT JOIN student_progress sp ON s.student_id = sp.student_id
    GROUP BY s.student_id, s.full_name
    ORDER BY total_xp DESC
    LIMIT 20
");

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leaderboard - Abacus Academy</title>
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
        
        .leaderboard-table {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1rem;
        }
        .rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; }
        .rank-2 { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); color: white; }
        .rank-3 { background: linear-gradient(135deg, #b45309 0%, #92400e 100%); color: white; }
        .rank-other { background: #e2e8f0; color: #475569; }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
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
                    <a href="leaderboard.php" class="nav-link active">
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
                    <h2>Leaderboard</h2>
                    <p>See how you rank among other students</p>
                </div>
            </div>
            
            <div class="leaderboard-table">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>Points</th>
                            <th>Achievements</th>
                            <th>Levels Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leaderboard_result && $leaderboard_result->num_rows > 0): ?>
                            <?php $rank = 1; ?>
                            <?php while($student = $leaderboard_result->fetch_assoc()): 
                                $rank_class = $rank == 1 ? 'rank-1' : ($rank == 2 ? 'rank-2' : ($rank == 3 ? 'rank-3' : 'rank-other'));
                            ?>
                                <tr>
                                    <td>
                                        <span class="rank <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar">
                                                <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: #1e293b;">
                                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                                </div>
                                                <div style="font-size: 0.85rem; color: #64748b;">
                                                    <?php echo htmlspecialchars($student['student_id']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="xp-badge">
                                            ⭐ <?php echo number_format($student['total_xp']); ?> XP
                                        </span>
                                    </td>
                                    <td><?php echo $student['total_achievements']; ?> badges</td>
                                    <td><?php echo $student['completed_levels']; ?> levels</td>
                                </tr>
                                <?php $rank++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">
                                    No rankings available yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
