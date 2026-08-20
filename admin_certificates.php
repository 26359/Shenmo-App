<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$host = "fdb1028.awardspace.net"; $dbname = "4783798_shenmoapp"; $user = "4783798_shenmoapp"; $pass = "muganwa123";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = ""; $msg_type = "success";

function notifyCert($conn, $student_id, $title, $msg) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_role, notification_type, title, message, action_url) VALUES (?, 'student', 'certificate', ?, ?, 'certificates.php')");
    $stmt->bind_param("sss", $student_id, $title, $msg);
    $stmt->execute(); $stmt->close();
}

// ── Issue certificate ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_cert'])) {
    $student_id = $_POST['student_id'];
    $course_id  = (int)$_POST['course_id'];
    $issue_date = $_POST['issue_date'];
    $cert_number = 'CERT-' . strtoupper(substr(md5($student_id . $course_id . time()), 0, 8));
    $pdf_path   = null;

    // Handle PDF upload
    if (!empty($_FILES['cert_pdf']['name'])) {
        $ext = strtolower(pathinfo($_FILES['cert_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $message = "Certificate file must be a PDF."; $msg_type = "error";
            goto skip_cert;
        }
        if ($_FILES['cert_pdf']['size'] > 20 * 1024 * 1024) {
            $message = "PDF must be under 20 MB."; $msg_type = "error";
            goto skip_cert;
        }
        $filename = $cert_number . '_' . $student_id . '.pdf';
        $dest     = __DIR__ . '/uploads/certificates/' . $filename;
        if (!move_uploaded_file($_FILES['cert_pdf']['tmp_name'], $dest)) {
            $message = "PDF upload failed. Check folder permissions."; $msg_type = "error";
            goto skip_cert;
        }
        $pdf_path = 'uploads/certificates/' . $filename;
    }

    $stmt = $conn->prepare("INSERT INTO certificates (student_id, course_id, certificate_number, issue_date, pdf_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $student_id, $course_id, $cert_number, $issue_date, $pdf_path);
    if ($stmt->execute()) {
        // Get student name and course name for notification
        $sname = $conn->query("SELECT full_name FROM students WHERE student_id='$student_id'")->fetch_assoc()['full_name'] ?? 'Student';
        $cname = $conn->query("SELECT course_name, level_number FROM courses WHERE id=$course_id")->fetch_assoc();
        $level = $cname['level_number'] ?? '';
        $cname = $cname['course_name'] ?? '';
        notifyCert($conn, $student_id,
            "🏆 Certificate Issued!",
            "Congratulations $sname! Your certificate for Level $level — $cname has been issued. Certificate #: $cert_number. You can download it from your Certificates page."
        );
        $message = "Certificate issued and deployed to student portal! #$cert_number";
    } else {
        $message = "Error: " . $stmt->error; $msg_type = "error";
    }
    $stmt->close();
    skip_cert:;
}

// ── Revoke certificate ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_cert'])) {
    $cert_id = (int)$_POST['cert_id'];
    $row = $conn->query("SELECT pdf_path FROM certificates WHERE id=$cert_id")->fetch_assoc();
    if ($row && $row['pdf_path'] && file_exists(__DIR__ . '/' . $row['pdf_path'])) {
        unlink(__DIR__ . '/' . $row['pdf_path']);
    }
    $conn->query("DELETE FROM certificates WHERE id=$cert_id");
    $message = "Certificate revoked."; $msg_type = "error";
}

$students_list    = $conn->query("SELECT student_id, full_name FROM students ORDER BY full_name");
$courses_list     = $conn->query("SELECT id, course_name, level_number FROM courses ORDER BY level_number");
$pending_payments = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$certs_list       = $conn->query("
    SELECT c.id, c.certificate_number, c.issue_date, c.pdf_path,
           s.full_name, co.course_name, co.level_number
    FROM certificates c
    JOIN students s  ON c.student_id = s.student_id
    JOIN courses  co ON c.course_id  = co.id
    ORDER BY c.issue_date DESC
");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Certificates - Admin</title>
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
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:16px}
        .form-group{display:flex;flex-direction:column;gap:6px}
        .form-group label{font-size:0.85rem;font-weight:600;color:#475569}
        .form-group input,.form-group select{padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:0.9rem;transition:border-color 0.2s;font-family:inherit;background:#fff}
        .form-group input:focus,.form-group select:focus{outline:none;border-color:#f59e0b}
        .drop-zone{border:2px dashed #cbd5e1;border-radius:12px;padding:28px;text-align:center;cursor:pointer;transition:all 0.2s;background:#f8fafc;position:relative}
        .drop-zone:hover,.drop-zone.dragover{border-color:#f59e0b;background:#fffbeb}
        .drop-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
        .drop-zone .dz-icon{font-size:2.2rem;margin-bottom:8px}
        .drop-zone p{color:#64748b;font-size:0.88rem;margin:0}
        .drop-zone .dz-name{color:#f59e0b;font-weight:600;font-size:0.9rem;margin-top:6px;display:none}
        .btn{display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border:none;border-radius:10px;cursor:pointer;font-size:0.9rem;font-weight:600;text-decoration:none;transition:all 0.2s}
        .btn-primary{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(245,158,11,0.35)}
        .btn-danger{background:linear-gradient(135deg,#ef4444,#f97316);color:#fff}
        .btn-danger:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(239,68,68,0.35)}
        .btn-sm{padding:6px 12px;font-size:0.8rem}
        .message{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:600;font-size:0.9rem}
        .message.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
        .message.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        .table-wrap{overflow-x:auto;border-radius:10px}
        table{width:100%;border-collapse:collapse;min-width:620px}
        th,td{padding:12px 14px;text-align:left;border-bottom:1px solid #f1f5f9;font-size:0.88rem}
        th{background:#f8fafc;color:#475569;font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.5px}
        tr:hover td{background:#f8fafc}
        .cert-num{font-family:'Courier New',monospace;font-size:0.82rem;color:#64748b}
        .pdf-link{display:inline-flex;align-items:center;gap:5px;color:#f59e0b;text-decoration:none;font-size:0.82rem;font-weight:600}
        .pdf-link:hover{text-decoration:underline}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}
        .info-note{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;font-size:0.85rem;color:#92400e;margin-bottom:18px;display:flex;align-items:flex-start;gap:8px}
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
            <li class="nav-item"><a href="admin_homework.php" class="nav-link"><span class="icon">📝</span>Assign Homework</a></li>
            <li class="nav-item"><a href="admin_certificates.php" class="nav-link active"><span class="icon">🏆</span>Issue Certificates</a></li>
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
            <h2>🏆 Issue Certificates</h2>
        </div>
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="card">
                <h3>🎖️ Issue New Certificate</h3>
                <div class="info-note">
                    ℹ️ Upload the student's completed certificate as a PDF. Once issued, it will appear on their portal and they will receive a notification.
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Student *</label>
                            <select name="student_id" required>
                                <option value="">— Select Student —</option>
                                <?php if ($students_list): while ($s = $students_list->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($s['student_id']); ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Abacus Level / Course *</label>
                            <select name="course_id" required>
                                <option value="">— Select Level —</option>
                                <?php if ($courses_list): while ($c = $courses_list->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id']; ?>">Level <?php echo $c['level_number']; ?> — <?php echo htmlspecialchars($c['course_name']); ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Issue Date *</label>
                            <input type="date" name="issue_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:20px">
                        <label>Certificate PDF * (max 20 MB)</label>
                        <div class="drop-zone" id="dropZone">
                            <input type="file" name="cert_pdf" id="certPdf" accept=".pdf">
                            <div class="dz-icon">📄</div>
                            <p>Drag & drop the certificate PDF here, or <strong>click to browse</strong></p>
                            <div class="dz-name" id="dzName"></div>
                        </div>
                    </div>
                    <button type="submit" name="issue_cert" class="btn btn-primary">🏆 Issue & Deploy Certificate</button>
                </form>
            </div>

            <div class="card">
                <h3>📜 Issued Certificates</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Certificate #</th><th>Student</th><th>Course</th><th>Level</th><th>Issue Date</th><th>PDF</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($certs_list && $certs_list->num_rows > 0): ?>
                            <?php while ($cert = $certs_list->fetch_assoc()): ?>
                            <tr>
                                <td class="cert-num"><?php echo htmlspecialchars($cert['certificate_number']); ?></td>
                                <td><?php echo htmlspecialchars($cert['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($cert['course_name']); ?></td>
                                <td>Level <?php echo $cert['level_number']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($cert['issue_date'])); ?></td>
                                <td>
                                    <?php if ($cert['pdf_path']): ?>
                                        <a href="<?php echo htmlspecialchars($cert['pdf_path']); ?>" target="_blank" class="pdf-link">📄 View PDF</a>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;font-size:0.82rem">No PDF</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Revoke this certificate?')">
                                        <input type="hidden" name="cert_id" value="<?php echo $cert['id']; ?>">
                                        <button type="submit" name="revoke_cert" class="btn btn-danger btn-sm">🗑️ Revoke</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8">No certificates issued yet.</td></tr>
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
const certInput = document.getElementById('certPdf');
const dzName    = document.getElementById('dzName');
const dropZone  = document.getElementById('dropZone');
certInput.addEventListener('change', () => {
    if (certInput.files[0]) { dzName.textContent = '✅ ' + certInput.files[0].name; dzName.style.display = 'block'; }
});
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
        certInput.files = e.dataTransfer.files;
        dzName.textContent = '✅ ' + e.dataTransfer.files[0].name; dzName.style.display = 'block';
    }
});
</script>
</body>
</html>
