<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$host = "fdb1028.awardspace.net"; $dbname = "4783798_shenmoapp"; $user = "4783798_shenmoapp"; $pass = "muganwa123";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$total_homework = $conn->query("SELECT COUNT(*) as c FROM homework")->fetch_assoc()['c'] ?? 0;
$total_certs = $conn->query("SELECT COUNT(*) as c FROM certificates")->fetch_assoc()['c'] ?? 0;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Abacus Academy</title>
    <link rel="manifest" href="/shenmo_app1/manifest.json">
    <meta name="theme-color" content="#1e293b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Shenmo">
    <link rel="apple-touch-icon" href="/shenmo_app1/icons/icon-192.png">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f1f5f9;min-height:100vh}
        .layout{display:flex;min-height:100vh}

        /* SIDEBAR */
        .sidebar{width:260px;background:linear-gradient(180deg,#1e293b 0%,#0f172a 100%);position:fixed;height:100vh;overflow-y:auto;z-index:200;transition:transform 0.3s}
        .sidebar-logo{padding:25px 20px;border-bottom:1px solid rgba(255,255,255,0.1);text-align:center}
        .sidebar-logo h1{color:#fff;font-size:1.2rem;font-weight:700}
        .sidebar-logo p{color:#94a3b8;font-size:0.8rem;margin-top:4px}
        .nav-section{padding:15px 10px 5px;color:#64748b;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px}
        .nav-menu{list-style:none;padding:0 10px}
        .nav-item{margin-bottom:3px}
        .nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;color:#94a3b8;text-decoration:none;border-radius:10px;transition:all 0.2s;font-size:0.9rem;font-weight:500}
        .nav-link:hover,.nav-link.active{background:rgba(59,130,246,0.2);color:#60a5fa}
        .nav-link .icon{font-size:1.1rem;width:22px;text-align:center}
        .nav-badge{margin-left:auto;background:#ef4444;color:#fff;font-size:0.7rem;padding:2px 7px;border-radius:10px;font-weight:700}

        /* TOPBAR */
        .main{flex:1;margin-left:260px;display:flex;flex-direction:column}
        .topbar{background:#fff;padding:15px 25px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 4px rgba(0,0,0,0.08);position:sticky;top:0;z-index:100}
        .topbar-left{display:flex;align-items:center;gap:15px}
        .hamburger{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#475569}
        .topbar h2{color:#1e293b;font-size:1.2rem;font-weight:700}
        .topbar-right{display:flex;align-items:center;gap:12px}
        .admin-badge{background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;padding:6px 14px;border-radius:20px;font-size:0.85rem;font-weight:600}

        /* CONTENT */
        .content{padding:25px;flex:1}

        /* STATS */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:28px}
        .stat-card{background:#fff;padding:22px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;display:flex;align-items:center;gap:16px;transition:transform 0.2s}
        .stat-card:hover{transform:translateY(-3px)}
        .stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
        .stat-icon.blue{background:#dbeafe} .stat-icon.green{background:#d1fae5}
        .stat-icon.orange{background:#fed7aa} .stat-icon.purple{background:#e9d5ff}
        .stat-info h3{font-size:1.8rem;font-weight:700;color:#1e293b}
        .stat-info p{color:#64748b;font-size:0.85rem;margin-top:2px}

        /* QUICK ACTIONS */
        .section-title{color:#1e293b;font-size:1.1rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .actions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:28px}
        .action-card{background:#fff;padding:24px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;text-decoration:none;color:#1e293b;transition:all 0.2s;display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px}
        .action-card:hover{transform:translateY(-4px);box-shadow:0 10px 25px rgba(0,0,0,0.12);border-color:#3b82f6}
        .action-card .action-icon{font-size:2.5rem}
        .action-card h3{font-size:1rem;font-weight:700}
        .action-card p{color:#64748b;font-size:0.82rem;line-height:1.4}

        /* OVERLAY */
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}

        /* MOBILE */
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .main{margin-left:0}
            .hamburger{display:block}
            .stats-grid{grid-template-columns:1fr 1fr}
            .actions-grid{grid-template-columns:1fr 1fr}
        }
        @media(max-width:480px){
            .stats-grid{grid-template-columns:1fr}
            .actions-grid{grid-template-columns:1fr}
            .content{padding:15px}
        }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <h1>⚙️ Admin Panel</h1>
            <p>Abacus Academy</p>
        </div>
        <p class="nav-section">Main</p>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin_dashboard.php" class="nav-link active"><span class="icon">📊</span>Dashboard</a></li>
            <li class="nav-item"><a href="manage_students.php" class="nav-link"><span class="icon">🎓</span>Manage Students</a></li>
        </ul>
        <p class="nav-section">Learning</p>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin_homework.php" class="nav-link"><span class="icon">📝</span>Assign Homework</a></li>
            <li class="nav-item"><a href="admin_certificates.php" class="nav-link"><span class="icon">🏆</span>Issue Certificates</a></li>
        </ul>
        <p class="nav-section">Finance</p>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_payments.php" class="nav-link">
                    <span class="icon">💳</span>Payments
                    <?php if($pending_payments > 0): ?><span class="nav-badge"><?php echo $pending_payments; ?></span><?php endif; ?>
                </a>
            </li>
        </ul>
        <p class="nav-section">Account</p>
        <ul class="nav-menu">
            <li class="nav-item"><a href="logout.php" class="nav-link"><span class="icon">🚪</span>Logout</a></li>
        </ul>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <button class="hamburger" onclick="toggleSidebar()">☰</button>
                <h2>Dashboard</h2>
            </div>
            <div class="topbar-right">
                <span class="admin-badge">👤 <?php echo htmlspecialchars($_SESSION['user_names'] ?? 'Admin'); ?></span>
                <a href="logout.php" style="color:#ef4444;text-decoration:none;font-weight:600;font-size:0.9rem">Logout</a>
            </div>
        </div>

        <div class="content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">🎓</div>
                    <div class="stat-info"><h3><?php echo $total_students; ?></h3><p>Total Students</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">⏳</div>
                    <div class="stat-info"><h3><?php echo $pending_payments; ?></h3><p>Pending Payments</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">📝</div>
                    <div class="stat-info"><h3><?php echo $total_homework; ?></h3><p>Homework Assigned</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">🏆</div>
                    <div class="stat-info"><h3><?php echo $total_certs; ?></h3><p>Certificates Issued</p></div>
                </div>
            </div>

            <p class="section-title">🚀 Quick Actions</p>
            <div class="actions-grid">
                <a href="manage_students.php" class="action-card">
                    <div class="action-icon">🎓</div>
                    <h3>Manage Students</h3>
                    <p>Add, edit or remove student accounts and profiles</p>
                </a>
                <a href="admin_homework.php" class="action-card">
                    <div class="action-icon">📝</div>
                    <h3>Assign Homework</h3>
                    <p>Deploy homework assignments to students or groups</p>
                </a>
                <a href="admin_certificates.php" class="action-card">
                    <div class="action-icon">🏆</div>
                    <h3>Issue Certificates</h3>
                    <p>Award completion certificates to students</p>
                </a>
                <a href="admin_payments.php" class="action-card">
                    <div class="action-icon">💳</div>
                    <h3>Payment Monitor</h3>
                    <p>Approve payments and view full payment history</p>
                </a>
            </div>
        <!-- SHARE BUTTONS FOOTER -->
        <div style="background:#fff;border-top:1px solid #e2e8f0;padding:16px 25px;text-align:center">
            <p style="color:#94a3b8;font-size:0.8rem;margin-bottom:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Share Abacus Academy</p>
            <?php require_once __DIR__ . '/includes/share_buttons.php'; ?>
        </div>
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
