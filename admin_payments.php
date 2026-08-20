<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$host = "fdb1028.awardspace.net"; $dbname = "4783798_shenmoapp"; $user = "4783798_shenmoapp"; $pass = "muganwa123";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = ""; $msg_type = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    $payment_id = (int)$_POST['payment_id'];
    $action     = $_POST['action'];
    $admin_id   = $_SESSION['user_id'] ?? 0;
    if ($action === 'approve') {
        $conn->query("UPDATE payments SET status='completed', verified_by=$admin_id WHERE id=$payment_id");
        $message = "Payment approved successfully!";
    } elseif ($action === 'reject') {
        $conn->query("UPDATE payments SET status='failed', verified_by=$admin_id WHERE id=$payment_id");
        $message = "Payment rejected."; $msg_type = "error";
    }
}

$stats = $conn->query("
    SELECT
        SUM(CASE WHEN status='completed' THEN amount_paid ELSE 0 END) as total_verified,
        SUM(CASE WHEN status='pending'   THEN amount_paid ELSE 0 END) as total_pending,
        SUM(CASE WHEN status='failed'    THEN amount_paid ELSE 0 END) as total_failed,
        COUNT(*) as total_transactions,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_count
    FROM payments
")->fetch_assoc();

$pending_count = $stats['pending_count'] ?? 0;

$payments = $conn->query("
    SELECT p.*, s.full_name, c.course_name
    FROM payments p
    JOIN students s ON p.student_id = s.student_id
    JOIN courses  c ON p.course_id  = c.id
    ORDER BY p.payment_date DESC
");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Admin</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f1f5f9;min-height:100vh}
        .layout{display:flex;min-height:100vh}
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
        .main{flex:1;margin-left:260px;display:flex;flex-direction:column}
        .topbar{background:#fff;padding:15px 25px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 4px rgba(0,0,0,0.08);position:sticky;top:0;z-index:100}
        .topbar-left{display:flex;align-items:center;gap:15px}
        .hamburger{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#475569}
        .topbar h2{color:#1e293b;font-size:1.2rem;font-weight:700}
        .content{padding:25px;flex:1}

        /* STATS */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
        .stat-card{background:#fff;padding:20px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;text-align:center}
        .stat-card .s-icon{font-size:2rem;margin-bottom:8px}
        .stat-card .s-val{font-size:1.5rem;font-weight:700;color:#1e293b}
        .stat-card .s-lbl{color:#64748b;font-size:0.8rem;margin-top:3px}

        /* TABS */
        .tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
        .tab-btn{padding:9px 18px;border:2px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;font-size:0.88rem;font-weight:600;color:#475569;transition:all 0.2s}
        .tab-btn.active{background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;border-color:transparent}

        .card{background:#fff;padding:25px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;margin-bottom:24px}
        .card h3{color:#1e293b;font-size:1.1rem;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px}
        .filter-bar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
        .filter-bar input,.filter-bar select{padding:9px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:0.88rem;transition:border-color 0.2s;font-family:inherit}
        .filter-bar input:focus,.filter-bar select:focus{outline:none;border-color:#3b82f6}
        .table-wrap{overflow-x:auto;border-radius:10px}
        table{width:100%;border-collapse:collapse;min-width:650px}
        th,td{padding:12px 14px;text-align:left;border-bottom:1px solid #f1f5f9;font-size:0.88rem}
        th{background:#f8fafc;color:#475569;font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.5px}
        tr:hover td{background:#f8fafc}
        .badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600}
        .badge-completed{background:#d1fae5;color:#065f46}
        .badge-pending{background:#fef3c7;color:#92400e}
        .badge-failed{background:#fee2e2;color:#991b1b}
        .badge-refunded{background:#dbeafe;color:#1e40af}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border:none;border-radius:8px;cursor:pointer;font-size:0.82rem;font-weight:600;text-decoration:none;transition:all 0.2s}
        .btn-success{background:linear-gradient(135deg,#10b981,#34d399);color:#fff}
        .btn-success:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(16,185,129,0.35)}
        .btn-danger{background:linear-gradient(135deg,#ef4444,#f97316);color:#fff}
        .btn-danger:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(239,68,68,0.35)}
        .action-btns{display:flex;gap:6px;flex-wrap:wrap}
        .amount{font-weight:700;color:#1e293b}
        .message{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:600;font-size:0.9rem}
        .message.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
        .message.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        .tab-content{display:none} .tab-content.active{display:block}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .main{margin-left:0}
            .hamburger{display:block}
            .stats-grid{grid-template-columns:1fr 1fr}
        }
        @media(max-width:480px){
            .stats-grid{grid-template-columns:1fr}
            .content{padding:15px}
        }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><h1>⚙️ Admin Panel</h1><p>Abacus Academy</p></div>
        <p class="nav-section">Main</p>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><span class="icon">📊</span>Dashboard</a></li>
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
                <a href="admin_payments.php" class="nav-link active"><span class="icon">💳</span>Payments
                    <?php if($pending_count > 0): ?><span class="nav-badge"><?php echo $pending_count; ?></span><?php endif; ?>
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
                <h2>💳 Payment Monitor</h2>
            </div>
        </div>
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $msg_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="s-icon">💰</div>
                    <div class="s-val">RWF <?php echo number_format($stats['total_verified'] ?? 0); ?></div>
                    <div class="s-lbl">Total Verified</div>
                </div>
                <div class="stat-card">
                    <div class="s-icon">⏳</div>
                    <div class="s-val">RWF <?php echo number_format($stats['total_pending'] ?? 0); ?></div>
                    <div class="s-lbl">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="s-icon">❌</div>
                    <div class="s-val">RWF <?php echo number_format($stats['total_failed'] ?? 0); ?></div>
                    <div class="s-lbl">Failed / Rejected</div>
                </div>
                <div class="stat-card">
                    <div class="s-icon">📊</div>
                    <div class="s-val"><?php echo $stats['total_transactions'] ?? 0; ?></div>
                    <div class="s-lbl">Total Transactions</div>
                </div>
            </div>

            <!-- TABS -->
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('pending')">⏳ Pending Approval <?php if($pending_count > 0) echo "($pending_count)"; ?></button>
                <button class="tab-btn" onclick="switchTab('history')">📋 Payment History</button>
            </div>

            <!-- PENDING TAB -->
            <div class="tab-content active" id="tab-pending">
                <div class="card">
                    <h3>⏳ Payments Awaiting Approval</h3>
                    <div class="table-wrap">
                        <table id="pendingTable">
                            <thead>
                                <tr><th>ID</th><th>Student</th><th>Course</th><th>Amount</th><th>Method</th><th>Date</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($payments) {
                                $payments->data_seek(0);
                                $has_pending = false;
                                while ($row = $payments->fetch_assoc()) {
                                    if ($row['status'] !== 'pending') continue;
                                    $has_pending = true;
                            ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td class="amount">RWF <?php echo number_format($row['amount_paid'], 2); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($row['payment_date'])); ?></td>
                                <td class="action-btns">
                                    <form method="post" style="display:inline" onsubmit="return confirm('Approve this payment?')">
                                        <input type="hidden" name="payment_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="verify_payment" value="approve" class="btn btn-success">✓ Approve</button>
                                    </form>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Reject this payment?')">
                                        <input type="hidden" name="payment_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="verify_payment" value="reject" class="btn btn-danger">✗ Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php } if (!$has_pending): ?>
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8">✅ No pending payments. All caught up!</td></tr>
                            <?php endif; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- HISTORY TAB -->
            <div class="tab-content" id="tab-history">
                <div class="card">
                    <h3>📋 Full Payment History</h3>
                    <div class="filter-bar">
                        <input type="text" id="searchInput" placeholder="🔍 Search student or course...">
                        <select id="statusFilter">
                            <option value="">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div class="table-wrap">
                        <table id="historyTable">
                            <thead>
                                <tr><th>ID</th><th>Student</th><th>Course</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($payments) {
                                $payments->data_seek(0);
                                $has_rows = false;
                                while ($row = $payments->fetch_assoc()) {
                                    $has_rows = true;
                            ?>
                            <tr data-status="<?php echo $row['status']; ?>">
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td class="amount">RWF <?php echo number_format($row['amount_paid'], 2); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($row['payment_date'])); ?></td>
                                <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            </tr>
                            <?php } if (!$has_rows): ?>
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8">No payment records yet.</td></tr>
                            <?php endif; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('active');
}
function switchTab(tab){
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
}
document.getElementById('searchInput')?.addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('#historyTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
document.getElementById('statusFilter')?.addEventListener('change', function(){
    const f = this.value;
    document.querySelectorAll('#historyTable tbody tr').forEach(r => {
        r.style.display = (!f || r.dataset.status === f) ? '' : 'none';
    });
});
</script>
</body>
</html>
