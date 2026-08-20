<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit;
}

$host = "fdb1028.awardspace.net"; $dbname = "4783798_shenmoapp"; $user = "4783798_shenmoapp"; $pass = "muganwa123";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['student_id'];

// Mark certificate notifications as read
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id='$student_id' AND notification_type='certificate' AND is_read=0");

// Increment download count on download request
if (isset($_GET['download'])) {
    $cert_id = (int)$_GET['download'];
    $row = $conn->query("SELECT pdf_path FROM certificates WHERE id=$cert_id AND student_id='$student_id'")->fetch_assoc();
    if ($row && $row['pdf_path'] && file_exists(__DIR__ . '/' . $row['pdf_path'])) {
        $conn->query("UPDATE certificates SET download_count = download_count + 1 WHERE id=$cert_id");
        $conn->close();
        $file = __DIR__ . '/' . $row['pdf_path'];
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="certificate_' . $cert_id . '.pdf"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

$certs = $conn->query("
    SELECT c.*, co.course_name, co.level_number
    FROM certificates c
    JOIN courses co ON c.course_id = co.id
    WHERE c.student_id = '$student_id'
    ORDER BY c.issue_date DESC
");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates - Abacus Academy</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f8fafc;min-height:100vh}
        .dashboard{display:flex;min-height:100vh}
        .sidebar{width:260px;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);border-right:1px solid #e2e8f0;padding:20px 0;position:fixed;height:100vh;overflow-y:auto;box-shadow:2px 0 10px rgba(0,0,0,0.05);z-index:100;transition:transform 0.3s}
        .logo{text-align:center;padding:20px;border-bottom:1px solid #e2e8f0;margin-bottom:20px}
        .logo h1{color:#3b82f6;font-size:1.4rem;font-weight:700}
        .logo p{color:#94a3b8;font-size:0.8rem;margin-top:4px}
        .nav-menu{list-style:none;padding:0 10px}
        .nav-item{margin-bottom:4px}
        .nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;color:#475569;text-decoration:none;border-radius:12px;transition:all 0.2s;font-weight:500;font-size:0.92rem}
        .nav-link:hover,.nav-link.active{background:linear-gradient(135deg,#dbeafe,#e0e7ff);color:#3b82f6}
        .nav-link .icon{font-size:1.1rem;width:22px;text-align:center}
        .main-content{flex:1;margin-left:260px;padding:25px}
        .topbar{background:#fff;padding:14px 20px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);margin-bottom:22px;display:flex;align-items:center;gap:14px}
        .hamburger{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#475569}
        .topbar h2{color:#1e293b;font-size:1.4rem;font-weight:700}
        .certs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:22px}
        .cert-card{background:#fff;border-radius:20px;box-shadow:0 4px 15px rgba(0,0,0,0.08);border:1px solid #e2e8f0;overflow:hidden;transition:all 0.3s;animation:slideUp 0.4s}
        .cert-card:hover{transform:translateY(-6px);box-shadow:0 14px 35px rgba(0,0,0,0.13)}
        @keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .cert-header{background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 50%,#d97706 100%);padding:28px 20px;text-align:center}
        .cert-header .trophy{font-size:3.5rem;margin-bottom:8px}
        .cert-header h3{color:#fff;font-size:1.2rem;font-weight:700;text-shadow:0 1px 3px rgba(0,0,0,0.2)}
        .cert-body{padding:20px}
        .cert-level{display:inline-block;background:#fef3c7;color:#92400e;padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:700;margin-bottom:12px}
        .cert-num{font-family:'Courier New',monospace;color:#64748b;font-size:0.82rem;margin-bottom:6px}
        .cert-date{color:#94a3b8;font-size:0.85rem;margin-bottom:16px}
        .cert-actions{display:flex;gap:10px;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border:none;border-radius:10px;cursor:pointer;font-size:0.88rem;font-weight:600;text-decoration:none;transition:all 0.2s}
        .btn-download{background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff}
        .btn-download:hover{transform:translateY(-2px);box-shadow:0 5px 14px rgba(59,130,246,0.35)}
        .btn-view{background:#fff;border:2px solid #e2e8f0;color:#475569}
        .btn-view:hover{border-color:#f59e0b;color:#f59e0b}
        .btn-disabled{background:#f1f5f9;color:#94a3b8;cursor:not-allowed}
        .no-data{text-align:center;padding:70px 20px;color:#64748b}
        .no-data-icon{font-size:4rem;margin-bottom:16px;opacity:0.4}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .main-content{margin-left:0}
            .hamburger{display:block}
            .certs-grid{grid-template-columns:1fr}
        }
        @media(max-width:480px){.main-content{padding:14px}}
    </style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
<div class="dashboard">
    <aside class="sidebar" id="sidebar">
        <div class="logo"><h1>🎓 Abacus Academy</h1><p>Learning Portal</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="student_dashboard.php" class="nav-link"><span class="icon">📊</span><span>Dashboard</span></a></li>
            <li class="nav-item"><a href="my_learning.php" class="nav-link"><span class="icon">📚</span><span>My Learning</span></a></li>
            <li class="nav-item"><a href="abacus_levels.php" class="nav-link"><span class="icon">🎯</span><span>Abacus Levels</span></a></li>
            <li class="nav-item"><a href="lessons.php" class="nav-link"><span class="icon">📖</span><span>Lessons</span></a></li>
            <li class="nav-item"><a href="practice.php" class="nav-link"><span class="icon">💪</span><span>Practice</span></a></li>
            <li class="nav-item"><a href="homework.php" class="nav-link"><span class="icon">📝</span><span>Homework</span></a></li>
            <li class="nav-item"><a href="exams.php" class="nav-link"><span class="icon">📋</span><span>Exams</span></a></li>
            <li class="nav-item"><a href="certificates.php" class="nav-link active"><span class="icon">🏆</span><span>Certificates</span></a></li>
            <li class="nav-item"><a href="attendance.php" class="nav-link"><span class="icon">📅</span><span>Attendance</span></a></li>
            <li class="nav-item"><a href="progress.php" class="nav-link"><span class="icon">📈</span><span>Progress</span></a></li>
            <li class="nav-item"><a href="achievements.php" class="nav-link"><span class="icon">⭐</span><span>Achievements</span></a></li>
            <li class="nav-item"><a href="leaderboard.php" class="nav-link"><span class="icon">🏅</span><span>Leaderboard</span></a></li>
            <li class="nav-item"><a href="student_payments.php" class="nav-link"><span class="icon">💳</span><span>Payments</span></a></li>
            <li class="nav-item"><a href="messages.php" class="nav-link"><span class="icon">💬</span><span>Messages</span></a></li>
            <li class="nav-item"><a href="notifications.php" class="nav-link"><span class="icon">🔔</span><span>Notifications</span></a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><span class="icon">👤</span><span>Profile</span></a></li>
            <li class="nav-item"><a href="settings.php" class="nav-link"><span class="icon">⚙️</span><span>Settings</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <button class="hamburger" onclick="toggleSidebar()">☰</button>
            <h2>🏆 My Certificates</h2>
            <?php require_once 'includes/share_buttons.php'; ?>
        </div>

        <?php if ($certs && $certs->num_rows > 0): ?>
        <div class="certs-grid">
            <?php while ($cert = $certs->fetch_assoc()): ?>
            <div class="cert-card">
                <div class="cert-header">
                    <div class="trophy">🏆</div>
                    <h3>Certificate of Completion</h3>
                </div>
                <div class="cert-body">
                    <div class="cert-level">Level <?php echo $cert['level_number']; ?> — <?php echo htmlspecialchars($cert['course_name']); ?></div>
                    <div class="cert-num"># <?php echo htmlspecialchars($cert['certificate_number']); ?></div>
                    <div class="cert-date">📅 Issued: <?php echo date('F d, Y', strtotime($cert['issue_date'])); ?></div>
                    <div class="cert-actions">
                        <?php if ($cert['pdf_path'] && file_exists(__DIR__ . '/' . $cert['pdf_path'])): ?>
                            <a href="certificates.php?download=<?php echo $cert['id']; ?>" class="btn btn-download">
                                📥 Download PDF
                            </a>
                            <a href="<?php echo htmlspecialchars($cert['pdf_path']); ?>" target="_blank" class="btn btn-view">
                                👁️ View
                            </a>
                        <?php else: ?>
                            <span class="btn btn-disabled">📄 PDF not available yet</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="no-data">
            <div class="no-data-icon">🏆</div>
            <h3>No certificates yet</h3>
            <p>Complete an abacus level and your certificate will appear here once issued by your teacher.</p>
        </div>
        <?php endif; ?>
    </main>
</div>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('active');
}
</script>
</body>
</html>
