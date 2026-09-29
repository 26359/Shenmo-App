<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || empty($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
$config = require __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/question_registry.php';

try {
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (mysqli_sql_exception $e) {
    die("Connection failed: " . $e->getMessage());
}

$student_id = $_SESSION['student_id'];

$levelMeta = getLevelMeta();

$student = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'")->fetch_assoc();

$xp_result = $conn->query("
    SELECT SUM(xp_amount) as total_xp, COUNT(*) as sessions, MAX(created_at) as last_practice
    FROM xp_points
    WHERE student_id = '$student_id'
    AND xp_source IN ('practice', 'lesson_complete', 'daily_challenge', 'speed_challenge')
");
$xp_stats = $xp_result->fetch_assoc();
$total_xp = (int)($xp_stats['total_xp'] ?? 0);
$practice_sessions = (int)($xp_stats['sessions'] ?? 0);

$streak_result = $conn->query("
    SELECT practice_date, COUNT(*) as sessions, SUM(xp_amount) as xp
    FROM xp_points xp
    WHERE xp.student_id = '$student_id'
    AND xp.xp_source IN ('practice', 'lesson_complete', 'daily_challenge', 'speed_challenge')
    AND xp.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(xp.created_at)
    ORDER BY practice_date DESC
");

$practice_days = [];
$current_streak = 0;
$last_date = null;
while ($row = $streak_result->fetch_assoc()) {
    $date = $row['practice_date'];
    $practice_days[$date] = $row;
}

$today = date('Y-m-d');
if (isset($practice_days[$today])) {
    $current_streak = 1;
    $check_date = date('Y-m-d', strtotime('-1 day'));
    while (isset($practice_days[$check_date])) {
        $current_streak++;
        $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
    }
}

$practice_history = $conn->query("
    SELECT xp.*, c.course_name
    FROM xp_points xp
    LEFT JOIN courses c ON xp.source_id = c.id
    WHERE xp.student_id = '$student_id'
    AND xp.xp_source IN ('practice', 'lesson_complete', 'daily_challenge', 'speed_challenge')
    ORDER BY xp.created_at DESC
    LIMIT 15
");

$daily_challenge = $conn->query("
    SELECT *
    FROM xp_points
    WHERE student_id = '$student_id'
    AND xp_source = 'daily_challenge'
    AND DATE(created_at) = CURDATE()
    ORDER BY created_at DESC
    LIMIT 1
")->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice - Abacus Academy</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }
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
            transition: transform 0.3s;
        }
        .logo { text-align: center; padding: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
        .logo h1 { color: #ef4444; font-size: 1.5rem; font-weight: 700; }
        .logo p { color: #94a3b8; font-size: 0.8rem; margin-top: 4px; }
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
            background: linear-gradient(135deg, #fee2e2 0%, #fef2f2 100%);
            color: #ef4444;
            transform: translateX(5px);
        }
        .nav-link .icon { font-size: 1.2rem; width: 24px; text-align: center; }
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }

        .topbar {
            background: #fff;
            padding: 16px 24px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .topbar h1 { color: #1e293b; font-size: 1.4rem; font-weight: 700; }
        .topbar p { color: #64748b; font-size: 0.9rem; margin-top: 3px; }
        .student-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #fff;
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-card .stat-icon { font-size: 1.8rem; margin-bottom: 8px; }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: 800; color: #1e293b; }
        .stat-card .stat-label { color: #64748b; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        .stat-card.streak .stat-value { color: #f59e0b; }
        .stat-card.xp .stat-value { color: #8b5cf6; }

        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        .practice-mode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }
        .mode-card {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            text-align: left;
            transition: all 0.25s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .mode-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
            border-color: transparent;
        }
        .mode-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
        }
        .mode-card.mental-math::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .mode-card.flash-cards::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
        .mode-card.abacus-exercises::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .mode-card.speed-challenge::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .mode-card.daily-practice::before { background: linear-gradient(90deg, #ef4444, #f87171); }
        .mode-card.paper-practice::before { background: linear-gradient(90deg, #f97316, #fb923c); }
        .mode-card .mode-icon { font-size: 2.5rem; margin-bottom: 14px; }
        .mode-card .mode-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
        .mode-card .mode-desc { color: #64748b; font-size: 0.88rem; line-height: 1.5; margin-bottom: 14px; }
        .mode-card .mode-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .mode-card.mental-math .mode-btn { background: #eff6ff; color: #2563eb; }
        .mode-card.flash-cards .mode-btn { background: #f3e8ff; color: #7c3aed; }
        .mode-card.abacus-exercises .mode-btn { background: #dcfce7; color: #15803d; }
        .mode-card.speed-challenge .mode-btn { background: #fffbeb; color: #b4530f; }
        .mode-card.daily-practice .mode-btn { background: #fef2f2; color: #b91c1c; }
        .mode-card.paper-practice .mode-btn { background: #fff7ed; color: #c2410c; }
        .mode-card:hover .mode-btn { transform: translateX(2px); }

        .mode-card .level-tag {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .level-tag-beginner { background: #dcfce7; color: #166534; }
        .level-tag-intermediate { background: #fef3c7; color: #92400e; }
        .level-tag-advanced { background: #fee2e2; color: #991b1b; }

        .mode-rules {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 10px;
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.5;
            display: none;
        }
        .mode-card:hover .mode-rules { display: block; }
        .mode-rules strong { color: #475569; }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 24px;
            margin-bottom: 24px;
        }
        .card .card-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .card .card-desc { color: #64748b; font-size: 0.9rem; line-height: 1.6; margin-bottom: 18px; }

        .level-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .level-option {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }
        .level-option:hover { border-color: #ef4444; }
        .level-option.selected { border-color: #ef4444; background: linear-gradient(135deg, #fef2f2, #fef2f2); }
        .level-option .level-num { font-size: 1.3rem; font-weight: 700; color: #ef4444; }
        .level-option .level-name { font-size: 0.9rem; color: #475569; font-weight: 600; }
        .level-option.disabled { opacity: 0.5; cursor: not-allowed; }
        .level-option.disabled:hover { border-color: #e2e8f0; }

        .history-list { display: grid; gap: 14px; }
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .history-info h4 { color: #1e293b; font-size: 0.95rem; font-weight: 600; margin-bottom: 4px; }
        .history-info p { color: #64748b; font-size: 0.82rem; }
        .xp-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .challenge-banner {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #fff;
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .challenge-banner .challenge-text {
            flex: 1;
        }
        .challenge-banner strong { display: block; font-size: 1.05rem; font-weight: 700; }
        .challenge-banner span { font-size: 0.88rem; opacity: 0.95; margin-top: 2px; }
        .challenge-banner .btn { 
            background: #fff; color: #f59e0b; font-weight: 700; flex-shrink: 0;
        }
        .challenge-completed {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .challenge-completed .btn { color: #059669; }

        .streak-calendar {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .streak-day {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
        }
        .streak-day.active { background: linear-gradient(135deg, #ef4444, #f87171); color: #fff; }
        .streak-day.today { box-shadow: 0 0 0 2px #fff; box-shadow: 0 0 0 2px #fbbf24; }

        .empty-state {
            color: #64748b;
            text-align: center;
            padding: 24px 16px;
            line-height: 1.6;
            font-size: 0.9rem;
        }
        .empty-state strong { display: block; color: #94a3b8; font-size: 2rem; margin-bottom: 8px; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(239,68,68,0.35); }
        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #475569;
        }
        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; }
            .dashboard { flex-direction: column; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .practice-mode-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo">
            <h1> Abacus Academy</h1>
            <p>Learning Portal</p>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="student_dashboard.php" class="nav-link"><span class="icon">📊</span><span>Dashboard</span></a></li>
            <li class="nav-item"><a href="my_learning.php" class="nav-link"><span class="icon">📚</span><span>My Learning</span></a></li>
            <li class="nav-item"><a href="abacus_levels.php" class="nav-link"><span class="icon">🎯</span><span>Abacus Levels</span></a></li>
            <li class="nav-item"><a href="lessons.php" class="nav-link"><span class="icon">📖</span><span>Lessons</span></a></li>
            <li class="nav-item"><a href="practice.php" class="nav-link active"><span class="icon">💪</span><span>Practice</span></a></li>
            <li class="nav-item"><a href="homework.php" class="nav-link"><span class="icon">📝</span><span>Homework</span></a></li>
            <li class="nav-item"><a href="exams.php" class="nav-link"><span class="icon">📋</span><span>Exams</span></a></li>
            <li class="nav-item"><a href="certificates.php" class="nav-link"><span class="icon">🏆</span><span>Certificates</span></a></li>
            <li class="nav-item"><a href="progress.php" class="nav-link"><span class="icon">📈</span><span>Progress</span></a></li>
            <li class="nav-item"><a href="achievements.php" class="nav-link"><span class="icon">⭐</span><span>Achievements</a></li>
            <li class="nav-item"><a href="leaderboard.php" class="nav-link"><span class="icon">🏅</span><span>Leaderboard</span></a></li>
            <li class="nav-item"><a href="messages.php" class="nav-link"><span class="icon">💬</span><span>Messages</span></a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><span class="icon">👤</span><span>Profile</span></a></li>
            <li class="nav-item"><a href="settings.php" class="nav-link"><span class="icon">⚙️</span><span>Settings</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Practice Center</h1>
                <p>Sharpen your skills with creative practice modes designed for every level</p>
            </div>
            <span class="student-badge">Student session active</span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-value"><?php echo $current_streak; ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
            <div class="stat-card xp">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?php echo number_format($total_xp); ?></div>
                <div class="stat-label">Total XP</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo $practice_sessions; ?></div>
                <div class="stat-label">Practice Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-value"><?php echo $current_streak >= 3 ? '🔥' : '💪'; ?></div>
                <div class="stat-label">Keep Going!</div>
            </div>
        </div>

        <?php if (!$daily_challenge): ?>
            <div class="challenge-banner">
                <div class="challenge-text">
                    <strong>🎯 Daily Challenge</strong>
                    <span>Solve 10 mental math problems to earn bonus XP and streak points!</span>
                </div>
                <a href="practice_mental_math.php?mode=daily" class="btn">Start Challenge</a>
            </div>
        <?php else: ?>
            <div class="challenge-banner challenge-completed">
                <div class="challenge-text">
                    <strong>✓ Daily Challenge Complete!</strong>
                    <span>You earned <?php echo $daily_challenge['xp_amount']; ?> XP today. Return tomorrow for a new challenge!</span>
                </div>
                <a href="practice_mental_math.php?mode=daily" class="btn">Review Answers</a>
            </div>
        <?php endif; ?>

        <div class="section-title">Practice Modes</div>

        <div class="practice-mode-grid">
            <div class="mode-card mental-math" onclick="location.href='practice_mental_math.php'">
                <div class="level-tag level-tag-beginner">Beginner → Advanced</div>
                <div class="mode-icon">🧮</div>
                <div class="mode-title">Mental Math</div>
                <div class="mode-desc">Train your ability to calculate mentally without using the abacus. Perfect for building number sense and speed.</div>
                <a href="practice_mental_math.php" class="mode-btn">Start Practicing →</a>
                <div class="mode-rules">
                    <strong>Rules:</strong> No abacus allowed. Each question generates dynamically based on your selected level. Track your accuracy and speed. XP earned per correct answer.
                </div>
            </div>

            <div class="mode-card flash-cards" onclick="location.href='practice_flashcards.php'">
                <div class="level-tag level-tag-beginner">Quick Recall</div>
                <div class="mode-icon">🃏</div>
                <div class="mode-title">Flash Cards</div>
                <div class="mode-desc">Rapid-fire question cards that test your immediate recall of addition, subtraction, and multiplication facts.</div>
                <a href="practice_flashcards.php" class="mode-btn">Start Flash Cards →</a>
                <div class="mode-rules">
                    <strong>Rules:</strong> Questions appear one at a time. Type your answer within 10 seconds or the card flips to show the correct answer. Earn bonus XP for speed streaks.
                </div>
            </div>

            <div class="mode-card abacus-exercises" onclick="location.href='practice_abacus.php'">
                <div class="level-tag level-tag-beginner">Beginner → Advanced</div>
                <div class="mode-icon">🎯</div>
                <div class="mode-title">Abacus Exercises</div>
                <div class="mode-desc">Interactive abacus manipulation exercises using the virtual abacus tool. Build muscle memory and proper technique.</div>
                <a href="practice_abacus.php" class="mode-btn">Open Abacus →</a>
                <div class="mode-rules">
                    <strong>Rules:</strong> Use the virtual abacus to solve each problem. The tool validates your bead positions. Track your progress through abacus mastery levels.
                </div>
            </div>

            <div class="mode-card speed-challenge" onclick="location.href='practice_speed.php'">
                <div class="level-tag level-tag-intermediate">Intermediate</div>
                <div class="mode-icon">⚡</div>
                <div class="mode-title">Speed Challenges</div>
                <div class="mode-desc">Race against the clock! Solve as many problems as you can in 2 minutes. Test your speed and accuracy under pressure.</div>
                <a href="practice_speed.php" class="mode-btn">Start Challenge →</a>
                <div class="mode-rules">
                    <strong>Rules:</strong> 2-minute timer. Each correct answer = 1 point. No penalty for wrong answers in Speed mode. Bonus points for streaks of 5+.
                </div>
            </div>

            <div class="mode-card paper-practice" onclick="openPaperPractice()">
                <div class="level-tag level-tag-intermediate">Level I</div>
                <div class="mode-icon">📝</div>
                <div class="mode-title">Paper Practice</div>
                <div class="mode-desc">Full-length practice papers with 240 questions across 6 blocks. No time pressure — focus on accuracy and review.</div>
                <a href="#" onclick="openPaperPractice(); event.stopPropagation();" class="mode-btn">Choose a Paper →</a>
                <div class="mode-rules">
                    <strong>Rules:</strong> Practice mode (no time limit). Move freely between questions. Review answers with detailed mistake analysis before submitting.
                </div>
            </div>

            <div class="mode-card daily-practice" onclick="location.href='practice_daily.php'">
                <div class="level-tag level-tag-beginner">Everyday</div>
                <div class="mode-icon">📅</div>
                <div class="mode-title">Error Review</div>
                <div class="mode-desc">Review your past mistakes from competitions and practice sessions. Focus on problems you've previously answered incorrectly.</div>
                <a href="practice_daily.php" class="mode-btn">Review Mistakes →</a>
                <div class="mode-rules">
                    <strong>Rules:</strong> System pulls your most recent incorrect answers. Re-solve each problem. Earn bonus XP for correcting mistakes you got wrong twice.
                </div>
            </div>
        </div>

        <div class="card" id="paperPracticeCard" style="display: none;">
            <div class="card-title">📋 Paper Practice Setup</div>
            <div class="card-desc">Level I papers have 240 questions across 6 blocks of 40. Choose your paper:</div>
            <div class="level-selector" id="levelSelector">
                <div class="level-option selected" data-paper="A">
                    <div class="level-num">Paper A</div>
                    <div class="level-name">3 one-digit numbers</div>
                </div>
                <div class="level-option" data-paper="B">
                    <div class="level-num">Paper B</div>
                    <div class="level-name">Mixed: 3 + 5 one-digit</div>
                </div>
                <div class="level-option" data-paper="C">
                    <div class="level-num">Paper C</div>
                    <div class="level-name">Mixed: 3 + 5 + 3 one-digit</div>
                </div>
                <div class="level-option" data-paper="D">
                    <div class="level-num">Paper D</div>
                    <div class="level-name">5–10 one-digit (abacus)</div>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="startPaperPractice()" style="flex: 1;">Start Paper Practice</button>
                <button class="btn btn-outline" onclick="cancelPaperPractice()">Cancel</button>
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <div class="section-title">My Practice Streak</div>
            <div class="streak-calendar" id="streakCalendar">
                <?php
                $today = date('Y-m-d');
                $week_ago = date('Y-m-d', strtotime('-13 days'));
                for ($i = 13; $i >= 0; $i--):
                    $day = date('Y-m-d', strtotime("-$i days"));
                    $day_num = date('n/j', strtotime($day));
                    $has_practice = isset($practice_days[$day]);
                    $is_today = ($day === $today);
                    $classes = 'streak-day';
                    if ($has_practice) $classes .= ' active';
                    if ($is_today) $classes .= ' today';
                ?>
                    <div class="<?php echo $classes; ?>">
                        <?php echo $day_num; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title">📊 Recent Practice Activity</div>
            <div class="card-desc">Your latest practice sessions and progress</div>

            <?php if ($practice_history && $practice_history->num_rows > 0): ?>
                <div class="history-list">
                    <?php while($practice = $practice_history->fetch_assoc()): ?>
                        <?php
                        $source_map = [
                            'practice' => 'General Practice',
                            'lesson_complete' => 'Lesson Completion',
                            'daily_challenge' => 'Daily Challenge',
                            'speed_challenge' => 'Speed Challenge',
                        ];
                        $activity_name = $source_map[$practice['xp_source']] ?? ucfirst(str_replace('_', ' ', $practice['xp_source']));
                        ?>
                        <div class="history-item">
                            <div class="history-info">
                                <h4><?php echo $activity_name; ?></h4>
                                <p><?php echo $practice['course_name'] ?: 'Level I Practice'; ?> • <?php echo date('M d, Y g:i A', strtotime($practice['created_at'])); ?></p>
                            </div>
                            <span class="xp-badge">⭐ +<?php echo $practice['xp_amount']; ?> XP</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <strong>💪</strong>
                    No practice sessions yet. Start a practice mode above to begin earning XP!
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    const levelSelector = document.getElementById('levelSelector');
    const levelOptions = levelSelector ? levelSelector.querySelectorAll('.level-option') : [];

    levelOptions.forEach(function(opt) {
        opt.addEventListener('click', function() {
            levelOptions.forEach(function(o) { o.classList.remove('selected'); });
            this.classList.add('selected');
        });
    });

    function openPaperPractice() {
        document.getElementById('paperPracticeCard').style.display = 'block';
        document.getElementById('paperPracticeCard').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function cancelPaperPractice() {
        document.getElementById('paperPracticeCard').style.display = 'none';
    }

    function startPaperPractice() {
        const selected = levelSelector.querySelector('.level-option.selected');
        const paper = selected ? selected.getAttribute('data-paper') : 'A';
        window.location.href = 'competition.php?mode=practice&level=1&paper=' + paper;
    }

    let selectedPaper = 'A';
    levelOptions.forEach(function(opt) {
        opt.addEventListener('click', function() {
            selectedPaper = this.getAttribute('data-paper');
        });
    });
</script>
</body>
</html>
