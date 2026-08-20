<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || empty($_SESSION['student_id'])) {
    header("Location: login.php"); exit;
}

$host = "fdb1028.awardspace.net"; $dbname = "4783798_shenmoapp"; $user = "4783798_shenmoapp"; $pass = "muganwa123";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT student_id, full_name, grade_level, email, phone, dob, address FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_hw      = $conn->query("SELECT COUNT(*) as c FROM homework WHERE student_id='$student_id'")->fetch_assoc()['c'] ?? 0;
$pending_hw    = $conn->query("SELECT COUNT(*) as c FROM homework WHERE student_id='$student_id' AND status='pending'")->fetch_assoc()['c'] ?? 0;
$total_certs   = $conn->query("SELECT COUNT(*) as c FROM certificates WHERE student_id='$student_id'")->fetch_assoc()['c'] ?? 0;
$unread_notifs = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id='$student_id' AND is_read=0")->fetch_assoc()['c'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Abacus Academy</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f8fafc;min-height:100vh}
        .dashboard{display:flex;min-height:100vh}
        .sidebar{width:260px;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);border-right:1px solid #e2e8f0;padding:20px 0;position:fixed;height:100vh;overflow-y:auto;box-shadow:2px 0 10px rgba(0,0,0,0.05);z-index:200;transition:transform 0.3s}
        .logo{text-align:center;padding:20px;border-bottom:1px solid #e2e8f0;margin-bottom:20px}
        .logo h1{color:#3b82f6;font-size:1.3rem;font-weight:700}
        .logo p{color:#94a3b8;font-size:0.8rem;margin-top:4px}
        .nav-menu{list-style:none;padding:0 10px}
        .nav-item{margin-bottom:4px}
        .nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;color:#475569;text-decoration:none;border-radius:12px;transition:all 0.2s;font-weight:500;font-size:0.92rem}
        .nav-link:hover,.nav-link.active{background:linear-gradient(135deg,#dbeafe,#e0e7ff);color:#3b82f6}
        .nav-link .icon{font-size:1.1rem;width:22px;text-align:center}
        .nav-badge{margin-left:auto;background:#ef4444;color:#fff;font-size:0.7rem;padding:2px 7px;border-radius:10px;font-weight:700}
        .main-content{flex:1;margin-left:260px;padding:25px}
        .topbar{background:#fff;padding:14px 20px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);margin-bottom:22px;display:flex;justify-content:space-between;align-items:center}
        .topbar-left{display:flex;align-items:center;gap:14px}
        .hamburger{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#475569}
        .topbar h2{color:#1e293b;font-size:1.2rem;font-weight:700}
        .student-badge{background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;padding:6px 14px;border-radius:20px;font-size:0.85rem;font-weight:600}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
        .stat-card{background:#fff;padding:20px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;display:flex;align-items:center;gap:14px;transition:transform 0.2s}
        .stat-card:hover{transform:translateY(-3px)}
        .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
        .stat-icon.blue{background:#dbeafe} .stat-icon.green{background:#d1fae5}
        .stat-icon.orange{background:#fed7aa} .stat-icon.purple{background:#e9d5ff}
        .stat-info h3{font-size:1.6rem;font-weight:700;color:#1e293b}
        .stat-info p{color:#64748b;font-size:0.82rem;margin-top:2px}
        .info-card{background:#fff;padding:24px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;margin-bottom:24px}
        .info-card h3{color:#1e293b;font-size:1rem;font-weight:700;margin-bottom:16px}
        .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
        .info-item{background:#f8fafc;padding:14px;border-radius:10px;border-left:4px solid #3b82f6}
        .info-item strong{display:block;color:#64748b;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px}
        .info-item span{color:#1e293b;font-size:0.95rem;font-weight:500}
        .actions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px}
        .action-card{background:#fff;padding:20px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;text-decoration:none;color:#1e293b;transition:all 0.2s;display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px}
        .action-card:hover{transform:translateY(-4px);box-shadow:0 10px 25px rgba(0,0,0,0.12);border-color:#3b82f6}
        .action-card .action-icon{font-size:2rem}
        .action-card span{font-size:0.88rem;font-weight:600}
        .section-title{color:#1e293b;font-size:1rem;font-weight:700;margin-bottom:14px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .main-content{margin-left:0}
            .hamburger{display:block}
        }
        @media(max-width:480px){.main-content{padding:14px}.stats-grid{grid-template-columns:1fr 1fr}}
    </style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
<div class="dashboard">
    <aside class="sidebar" id="sidebar">
        <div class="logo"><h1>🎓 Abacus Academy</h1><p>Learning Portal</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="student_dashboard.php" class="nav-link active"><span class="icon">📊</span><span>Dashboard</span></a></li>
            <li class="nav-item"><a href="my_learning.php" class="nav-link"><span class="icon">📚</span><span>My Learning</span></a></li>
            <li class="nav-item"><a href="homework.php" class="nav-link"><span class="icon">📝</span><span>Homework</span><?php if($pending_hw > 0): ?><span class="nav-badge"><?php echo $pending_hw; ?></span><?php endif; ?></a></li>
            <li class="nav-item"><a href="certificates.php" class="nav-link"><span class="icon">🏆</span><span>Certificates</span></a></li>
            <li class="nav-item"><a href="student_payments.php" class="nav-link"><span class="icon">💳</span><span>Payments</span></a></li>
            <li class="nav-item"><a href="notifications.php" class="nav-link"><span class="icon">🔔</span><span>Notifications</span><?php if($unread_notifs > 0): ?><span class="nav-badge"><?php echo $unread_notifs; ?></span><?php endif; ?></a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><span class="icon">👤</span><span>Profile</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><span class="icon">🚪</span><span>Logout</span></a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="hamburger" onclick="toggleSidebar()">☰</button>
                <h2>Dashboard</h2>
            </div>
            <span class="student-badge">👤 <?php echo htmlspecialchars($student['full_name'] ?? 'Student'); ?></span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📝</div>
                <div class="stat-info"><h3><?php echo $total_hw; ?></h3><p>Total Homework</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">⏳</div>
                <div class="stat-info"><h3><?php echo $pending_hw; ?></h3><p>Pending Homework</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">🏆</div>
                <div class="stat-info"><h3><?php echo $total_certs; ?></h3><p>Certificates</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🔔</div>
                <div class="stat-info"><h3><?php echo $unread_notifs; ?></h3><p>Notifications</p></div>
            </div>
        </div>

        <div class="info-card">
            <h3>👤 My Profile</h3>
            <div class="info-grid">
                <div class="info-item"><strong>Student ID</strong><span><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></span></div>
                <div class="info-item"><strong>Full Name</strong><span><?php echo htmlspecialchars($student['full_name'] ?? ''); ?></span></div>
                <div class="info-item"><strong>Abacus Level</strong><span>Level <?php echo htmlspecialchars($student['grade_level'] ?? ''); ?></span></div>
                <div class="info-item"><strong>Email</strong><span><?php echo htmlspecialchars($student['email'] ?? ''); ?></span></div>
                <div class="info-item"><strong>Phone</strong><span><?php echo htmlspecialchars($student['phone'] ?? ''); ?></span></div>
                <div class="info-item"><strong>Date of Birth</strong><span><?php echo htmlspecialchars($student['dob'] ?? ''); ?></span></div>
                <?php if (!empty($student['address'])): ?>
                <div class="info-item" style="grid-column:1/-1"><strong>Address</strong><span><?php echo htmlspecialchars($student['address']); ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <p class="section-title">🚀 Quick Actions</p>
        <div class="actions-grid">
            <a href="homework.php" class="action-card"><div class="action-icon">📝</div><span>Homework</span></a>
            <a href="certificates.php" class="action-card"><div class="action-icon">🏆</div><span>Certificates</span></a>
            <a href="student_payments.php" class="action-card"><div class="action-icon">💳</div><span>Payments</span></a>
            <a href="notifications.php" class="action-card"><div class="action-icon">🔔</div><span>Notifications</span></a>
            <a href="profile.php" class="action-card"><div class="action-icon">👤</div><span>Profile</span></a>
            <a href="logout.php" class="action-card"><div class="action-icon">🚪</div><span>Logout</span></a>
        </div>
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
