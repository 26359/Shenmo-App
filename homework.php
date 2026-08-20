<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit;
}

$host = "fdb1028.awardspace.net"; $dbname = "4783798_shenmoapp"; $user = "4783798_shenmoapp"; $pass = "muganwa123";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['student_id'];
$student    = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'")->fetch_assoc();

// Ensure submissions table exists
$conn->query("CREATE TABLE IF NOT EXISTS homework_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    homework_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hw_student (homework_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$message = ""; $msg_type = "success";

// ── Submit homework file ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_hw'])) {
    $hw_id = (int)$_POST['hw_id'];

    if (empty($_FILES['sub_file']['name'])) {
        $message = "Please select a file to submit."; $msg_type = "error";
    } else {
        $allowed = ['pdf','doc','docx'];
        $ext = strtolower(pathinfo($_FILES['sub_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $message = "Only PDF, DOC, DOCX files allowed."; $msg_type = "error";
        } elseif ($_FILES['sub_file']['size'] > 20 * 1024 * 1024) {
            $message = "File must be under 20 MB."; $msg_type = "error";
        } else {
            $filename = time() . '_' . $student_id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['sub_file']['name']));
            $dest     = __DIR__ . '/uploads/submissions/' . $filename;
            if (move_uploaded_file($_FILES['sub_file']['tmp_name'], $dest)) {
                $path = 'uploads/submissions/' . $filename;
                // Upsert submission
                $stmt = $conn->prepare("INSERT INTO homework_submissions (homework_id, student_id, file_path) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE file_path=VALUES(file_path), submitted_at=NOW()");
                $stmt->bind_param("iss", $hw_id, $student_id, $path);
                $stmt->execute(); $stmt->close();
                // Update homework status
                $conn->query("UPDATE homework SET status='submitted', submitted_at=NOW() WHERE id=$hw_id AND student_id='$student_id'");
                $message = "Homework submitted successfully! ✅";
            } else {
                $message = "Upload failed. Please try again."; $msg_type = "error";
            }
        }
    }
}

// Mark notifications as read
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id='$student_id' AND notification_type='homework' AND is_read=0");

$homework_result = $conn->query("
    SELECT h.*, l.lesson_title, c.course_name,
           hs.file_path AS submission_file, hs.submitted_at AS sub_time
    FROM homework h
    LEFT JOIN lessons l ON h.lesson_id = l.id
    LEFT JOIN courses c ON l.course_id = c.id
    LEFT JOIN homework_submissions hs ON hs.homework_id = h.id AND hs.student_id = '$student_id'
    WHERE h.student_id = '$student_id'
    ORDER BY h.due_date ASC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homework - Abacus Academy</title>
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
        .message{padding:14px 18px;border-radius:10px;margin-bottom:18px;font-weight:600;font-size:0.9rem}
        .message.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
        .message.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        .hw-card{background:#fff;padding:22px;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;margin-bottom:16px;transition:all 0.2s}
        .hw-card:hover{box-shadow:0 6px 20px rgba(0,0,0,0.1)}
        .hw-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:10px}
        .hw-title{font-size:1.1rem;font-weight:700;color:#1e293b}
        .hw-desc{color:#64748b;font-size:0.9rem;margin-bottom:12px;line-height:1.5}
        .hw-meta{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:14px}
        .hw-meta-item{display:flex;align-items:center;gap:6px;color:#64748b;font-size:0.85rem}
        .badge{display:inline-block;padding:5px 12px;border-radius:20px;font-size:0.78rem;font-weight:600}
        .badge-pending{background:#fef3c7;color:#92400e}
        .badge-submitted{background:#dbeafe;color:#1e40af}
        .badge-graded{background:#d1fae5;color:#065f46}
        .badge-late{background:#fee2e2;color:#991b1b}
        .hw-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px}
        .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border:none;border-radius:10px;cursor:pointer;font-size:0.88rem;font-weight:600;text-decoration:none;transition:all 0.2s}
        .btn-primary{background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 5px 14px rgba(59,130,246,0.35)}
        .btn-success{background:linear-gradient(135deg,#10b981,#34d399);color:#fff}
        .btn-success:hover{transform:translateY(-2px);box-shadow:0 5px 14px rgba(16,185,129,0.35)}
        .btn-outline{background:#fff;border:2px solid #e2e8f0;color:#475569}
        .btn-outline:hover{border-color:#3b82f6;color:#3b82f6}
        /* Upload inline form */
        .upload-form{background:#f8fafc;border:2px dashed #cbd5e1;border-radius:12px;padding:18px;margin-top:12px}
        .upload-form label{font-size:0.85rem;font-weight:600;color:#475569;display:block;margin-bottom:8px}
        .file-input-wrap{position:relative;display:inline-block}
        .file-input-wrap input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
        .file-input-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:#fff;border:2px solid #e2e8f0;border-radius:10px;font-size:0.88rem;font-weight:600;color:#475569;cursor:pointer;transition:all 0.2s}
        .file-input-btn:hover{border-color:#3b82f6;color:#3b82f6}
        .file-chosen{color:#3b82f6;font-size:0.85rem;margin-left:10px;font-weight:600}
        .feedback-box{background:#f0f9ff;padding:14px;border-radius:10px;margin-top:12px;border-left:4px solid #3b82f6}
        .feedback-box strong{color:#1e40af;font-size:0.85rem}
        .feedback-box p{color:#475569;margin-top:5px;font-size:0.9rem}
        .no-data{text-align:center;padding:60px 20px;color:#64748b}
        .no-data-icon{font-size:4rem;margin-bottom:16px;opacity:0.4}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .main-content{margin-left:0}
            .hamburger{display:block}
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
            <li class="nav-item"><a href="homework.php" class="nav-link active"><span class="icon">📝</span><span>Homework</span></a></li>
            <li class="nav-item"><a href="exams.php" class="nav-link"><span class="icon">📋</span><span>Exams</span></a></li>
            <li class="nav-item"><a href="certificates.php" class="nav-link"><span class="icon">🏆</span><span>Certificates</span></a></li>
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
            <h2>📝 Homework</h2>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($homework_result && $homework_result->num_rows > 0): ?>
            <?php while ($hw = $homework_result->fetch_assoc()):
                $overdue = ($hw['status'] === 'pending' && strtotime($hw['due_date']) < time());
                $display_status = $overdue ? 'late' : $hw['status'];
            ?>
            <div class="hw-card">
                <div class="hw-top">
                    <div class="hw-title"><?php echo htmlspecialchars($hw['title']); ?></div>
                    <span class="badge badge-<?php echo $display_status; ?>"><?php echo ucfirst($display_status); ?></span>
                </div>

                <?php if ($hw['description']): ?>
                    <p class="hw-desc"><?php echo nl2br(htmlspecialchars($hw['description'])); ?></p>
                <?php endif; ?>

                <div class="hw-meta">
                    <?php if ($hw['course_name']): ?>
                        <div class="hw-meta-item">📚 <?php echo htmlspecialchars($hw['course_name']); ?></div>
                    <?php endif; ?>
                    <?php if ($hw['lesson_title']): ?>
                        <div class="hw-meta-item">📖 <?php echo htmlspecialchars($hw['lesson_title']); ?></div>
                    <?php endif; ?>
                    <div class="hw-meta-item">📅 Due: <strong><?php echo date('M d, Y', strtotime($hw['due_date'])); ?></strong></div>
                    <?php if ($hw['grade']): ?>
                        <div class="hw-meta-item">🎯 Grade: <strong><?php echo $hw['grade']; ?>%</strong></div>
                    <?php endif; ?>
                </div>

                <div class="hw-actions">
                    <?php if ($hw['file_path']): ?>
                        <a href="<?php echo htmlspecialchars($hw['file_path']); ?>" target="_blank" class="btn btn-outline">
                            <?php echo str_ends_with($hw['file_path'],'.pdf') ? '📄' : '📝'; ?> View Assignment
                        </a>
                    <?php endif; ?>

                    <?php if ($hw['submission_file']): ?>
                        <a href="<?php echo htmlspecialchars($hw['submission_file']); ?>" target="_blank" class="btn btn-success">
                            ✅ My Submission
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (in_array($hw['status'], ['pending','submitted']) && !$overdue): ?>
                    <div class="upload-form" id="form_<?php echo $hw['id']; ?>" style="<?php echo $hw['status']==='submitted' ? 'display:none' : ''; ?>">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="hw_id" value="<?php echo $hw['id']; ?>">
                            <label><?php echo $hw['submission_file'] ? '🔄 Replace Submission' : '📤 Submit Your Work'; ?> (PDF, DOC, DOCX — max 20 MB)</label>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px">
                                <div class="file-input-wrap">
                                    <div class="file-input-btn" id="btn_<?php echo $hw['id']; ?>">📎 Choose File</div>
                                    <input type="file" name="sub_file" accept=".pdf,.doc,.docx" onchange="showName(this, <?php echo $hw['id']; ?>)" required>
                                </div>
                                <span class="file-chosen" id="name_<?php echo $hw['id']; ?>"></span>
                                <button type="submit" name="submit_hw" class="btn btn-primary">📤 Submit</button>
                            </div>
                        </form>
                    </div>
                    <?php if ($hw['status'] === 'submitted'): ?>
                        <div style="margin-top:10px">
                            <button class="btn btn-outline" onclick="document.getElementById('form_<?php echo $hw['id']; ?>').style.display='block';this.style.display='none'">
                                🔄 Resubmit
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($hw['feedback']): ?>
                    <div class="feedback-box">
                        <strong>Teacher Feedback:</strong>
                        <p><?php echo nl2br(htmlspecialchars($hw['feedback'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">📝</div>
                <h3>No homework assignments yet</h3>
                <p>Your teacher will assign homework here. Check back later!</p>
            </div>
        <?php endif; ?>
    </main>
</div>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('active');
}
function showName(input, id){
    document.getElementById('name_' + id).textContent = input.files[0] ? '✅ ' + input.files[0].name : '';
}
</script>
</body>
</html>
