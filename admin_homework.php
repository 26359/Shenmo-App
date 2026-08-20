<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$host = "fdb1028.awardspace.net"; $dbname = "4783798_shenmoapp"; $user = "4783798_shenmoapp"; $pass = "muganwa123";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = ""; $msg_type = "success";

// ── Helper: insert notification for a student ──────────────────────────────
function notify($conn, $student_id, $title, $msg) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_role, notification_type, title, message, action_url) VALUES (?, 'student', 'homework', ?, ?, 'homework.php')");
    $stmt->bind_param("sss", $student_id, $title, $msg);
    $stmt->execute(); $stmt->close();
}

// ── Assign homework ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_homework'])) {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date    = $_POST['due_date'];
    $target      = $_POST['target'];
    $lesson_id   = !empty($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : null;
    $notif_title = "📝 New Homework: $title";
    $notif_msg   = "You have a new homework assignment: \"$title\". Due: " . date('M d, Y', strtotime($due_date)) . ".";

    if ($target === 'all') {
        $students = $conn->query("SELECT student_id FROM students");
        $count = 0;
        while ($s = $students->fetch_assoc()) {
            $sid = $s['student_id'];
            $stmt = $conn->prepare("INSERT INTO homework (student_id, lesson_id, title, description, due_date, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sisss", $sid, $lesson_id, $title, $description, $due_date);
            $stmt->execute(); $stmt->close();
            notify($conn, $sid, $notif_title, $notif_msg);
            $count++;
        }
        $message = "Homework assigned to $count students!";
    } else {
        $stmt = $conn->prepare("INSERT INTO homework (student_id, lesson_id, title, description, due_date, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("sisss", $target, $lesson_id, $title, $description, $due_date);
        if ($stmt->execute()) {
            notify($conn, $target, $notif_title, $notif_msg);
            $message = "Homework assigned successfully!";
        } else {
            $message = "Error: " . $stmt->error; $msg_type = "error";
        }
        $stmt->close();
    }
}

// ── Delete homework ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_hw'])) {
    $hw_id = (int)$_POST['hw_id'];
    $conn->query("DELETE FROM homework WHERE id=$hw_id");
    $message = "Homework deleted."; $msg_type = "error";
}

$students_list    = $conn->query("SELECT student_id, full_name FROM students ORDER BY full_name");
$lessons_list     = $conn->query("SELECT id, lesson_title FROM lessons ORDER BY lesson_title");
$pending_payments = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status='pending'")->fetch_assoc()['c'] ?? 0;

// Ensure submissions table exists before querying it
$conn->query("CREATE TABLE IF NOT EXISTS homework_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    homework_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hw_student (homework_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$homework_list = $conn->query("
    SELECT h.id, h.title, h.due_date, h.status, h.description,
           s.full_name, l.lesson_title,
           sub.file_path AS submission_file, sub.submitted_at
    FROM homework h
    JOIN students s ON h.student_id = s.student_id
    LEFT JOIN lessons l ON h.lesson_id = l.id
    LEFT JOIN homework_submissions sub ON sub.homework_id = h.id
    ORDER BY h.due_date DESC
    LIMIT 150
");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Homework - Admin</title>
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
        .topbar{background:#fff;padding:15px 25px;display:flex;align-items:center;gap:15px;box-shadow:0 1px 4px rgba(0,0,0,0.08);position:sticky;top:0;z-index:100}
        .hamburger{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#475569}
        .topbar h2{color:#1e293b;font-size:1.2rem;font-weight:700}
        .content{padding:25px;flex:1}
        .card{background:#fff;padding:25px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;margin-bottom:24px}
        .card h3{color:#1e293b;font-size:1.1rem;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px;margin-bottom:16px}
        .form-group{display:flex;flex-direction:column;gap:6px}
        .form-group label{font-size:0.85rem;font-weight:600;color:#475569}
        .form-group input,.form-group select,.form-group textarea{padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:0.9rem;transition:border-color 0.2s;font-family:inherit;background:#fff}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#3b82f6}
        .form-group textarea{resize:vertical;min-height:80px}
        .btn{display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border:none;border-radius:10px;cursor:pointer;font-size:0.9rem;font-weight:600;text-decoration:none;transition:all 0.2s}
        .btn-primary{background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(59,130,246,0.35)}
        .btn-danger{background:linear-gradient(135deg,#ef4444,#f97316);color:#fff}
        .btn-danger:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(239,68,68,0.35)}
        .btn-success{background:linear-gradient(135deg,#10b981,#34d399);color:#fff}
        .btn-success:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(16,185,129,0.35)}
        .btn-sm{padding:6px 12px;font-size:0.8rem}
        .message{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:600;font-size:0.9rem}
        .message.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
        .message.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        .table-wrap{overflow-x:auto;border-radius:10px}
        table{width:100%;border-collapse:collapse;min-width:700px}
        th,td{padding:11px 13px;text-align:left;border-bottom:1px solid #f1f5f9;font-size:0.87rem}
        th{background:#f8fafc;color:#475569;font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.5px}
        tr:hover td{background:#f8fafc}
        .badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600}
        .badge-pending{background:#fef3c7;color:#92400e}
        .badge-submitted{background:#dbeafe;color:#1e40af}
        .badge-graded{background:#d1fae5;color:#065f46}
        .badge-late{background:#fee2e2;color:#991b1b}
        .file-link{display:inline-flex;align-items:center;gap:5px;color:#3b82f6;text-decoration:none;font-size:0.82rem;font-weight:600}
        .file-link:hover{text-decoration:underline}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .main{margin-left:0}
            .hamburger{display:block}
            .form-grid{grid-template-columns:1fr}
        }
        @media(max-width:480px){.content{padding:15px}}
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
            <li class="nav-item"><a href="admin_homework.php" class="nav-link active"><span class="icon">📝</span>Assign Homework</a></li>
            <li class="nav-item"><a href="admin_certificates.php" class="nav-link"><span class="icon">🏆</span>Issue Certificates</a></li>
        </ul>
        <p class="nav-section">Finance</p>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_payments.php" class="nav-link"><span class="icon">💳</span>Payments
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
            <button class="hamburger" onclick="toggleSidebar()">☰</button>
            <h2>📝 Assign Homework</h2>
        </div>
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="card">
                <h3>📤 Deploy New Homework</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" required placeholder="e.g. Abacus Level 2 Practice">
                        </div>
                        <div class="form-group">
                            <label>Due Date *</label>
                            <input type="date" name="due_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Assign To *</label>
                            <select name="target" required>
                                <option value="all">📢 All Students</option>
                                <?php if ($students_list): while ($s = $students_list->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($s['student_id']); ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Related Lesson (optional)</label>
                            <select name="lesson_id">
                                <option value="">— None —</option>
                                <?php if ($lessons_list): while ($l = $lessons_list->fetch_assoc()): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['lesson_title']); ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Description / Instructions</label>
                        <textarea name="description" placeholder="Describe the homework task..."></textarea>
                    </div>
                    <button type="submit" name="assign_homework" class="btn btn-primary">📤 Assign Homework</button>
                </form>
            </div>

            <div class="card">
                <h3>📋 Assigned Homework</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Title</th><th>Student</th><th>Due Date</th><th>Status</th><th>Submission</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($homework_list && $homework_list->num_rows > 0): ?>
                            <?php while ($hw = $homework_list->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($hw['title']); ?></strong>
                                    <?php if ($hw['description']): ?>
                                        <br><small style="color:#94a3b8"><?php echo htmlspecialchars(substr($hw['description'],0,60)); ?>…</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($hw['full_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($hw['due_date'])); ?></td>
                                <td><span class="badge badge-<?php echo $hw['status']; ?>"><?php echo ucfirst($hw['status']); ?></span></td>
                                <td>
                                    <?php if (!empty($hw['submission_file'])): ?>
                                        <a href="<?php echo htmlspecialchars($hw['submission_file']); ?>" target="_blank" class="file-link" style="color:#10b981">
                                            📥 Download
                                        </a>
                                        <?php if ($hw['submitted_at']): ?>
                                            <br><small style="color:#94a3b8"><?php echo date('M d', strtotime($hw['submitted_at'])); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1">Not submitted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Delete this homework?')">
                                        <input type="hidden" name="hw_id" value="<?php echo $hw['id']; ?>">
                                        <button type="submit" name="delete_hw" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8">No homework assigned yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
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

</script>
</body>
</html>
