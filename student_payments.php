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

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_payment'])) {
    $course_id = $_POST['course_id'];
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_method = $_POST['payment_method'];
    $reference_number = $_POST['reference_number'];
    $notes = $_POST['notes'];
    
    if ($amount_paid <= 0) {
        $message = "Amount must be greater than 0.";
    } else {
        $course = $conn->query("SELECT * FROM courses WHERE id = $course_id")->fetch_assoc();
        $enrollment = $conn->query("SELECT * FROM enrollments WHERE student_id = '" . $_SESSION['student_id'] . "' AND course_id = $course_id")->fetch_assoc();
        
        if (!$enrollment) {
            $conn->query("INSERT INTO enrollments (student_id, course_id, total_fee, amount_paid, payment_status, enrollment_status) 
                         VALUES ('" . $_SESSION['student_id'] . "', $course_id, " . $course['fee_amount'] . ", $amount_paid, 'partial', 'active')");
            $enrollment_id = $conn->insert_id;
        } else {
            $new_amount = $enrollment['amount_paid'] + $amount_paid;
            $payment_status = $new_amount >= $course['fee_amount'] ? 'paid' : ($new_amount > 0 ? 'partial' : 'unpaid');
            $conn->query("UPDATE enrollments SET amount_paid = $new_amount, payment_status = '$payment_status' WHERE id = " . $enrollment['id']);
            $enrollment_id = $enrollment['id'];
        }
        
        $ref = 'RCP-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $conn->query("INSERT INTO payments (student_id, course_id, amount_paid, payment_method, reference_number, status, notes) 
                     VALUES ('" . $_SESSION['student_id'] . "', $course_id, $amount_paid, '$payment_method', '$ref', 'pending', '$notes')");
        
        $message = "Payment submitted successfully! Reference: $ref";
    }
}

$courses_result = $conn->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY level_number ASC");
$enrollments_result = $conn->query("
    SELECT e.*, c.course_name, c.fee_amount, c.level_number 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.student_id = '" . $_SESSION['student_id'] . "'
    ORDER BY e.enrolled_at DESC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Student Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; min-height: 100vh; }
        .dashboard { display: flex; min-height: 100vh; }
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
        .topbar h2{color:#1e293b;font-size:1.3rem;font-weight:700}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}
        
        .card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 24px; border: 1px solid #e2e8f0; }
        .card h2 { color: #1a202c; margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px; }
        
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 8px; color: #4a5568; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
        .btn-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(56, 239, 125, 0.4); }
        .btn-danger { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(235, 51, 73, 0.4); }
        .btn-secondary { background: linear-gradient(135deg, #a8a8a8 0%, #7c7c7c 100%); color: white; }
        .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124, 124, 124, 0.4); }
        
        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }
        .message.success { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; border: 1px solid #f5c6cb; }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .enrollment-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-left: 5px solid;
            transition: transform 0.3s;
        }
        .enrollment-card:hover { transform: translateX(5px); }
        .enrollment-card.unpaid { border-left-color: #e74c3c; }
        .enrollment-card.partial { border-left-color: #f39c12; }
        .enrollment-card.paid { border-left-color: #27ae60; }
        
        .enrollment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .enrollment-title { font-size: 1.2rem; font-weight: 700; color: #2d3748; }
        .enrollment-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-unpaid { background: #fee; color: #c33; }
        .status-partial { background: #ffeaa7; color: #d68910; }
        .status-paid { background: #d4edda; color: #155724; }
        
        .enrollment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .detail-item { color: #4a5568; }
        .detail-item strong { color: #2d3748; }
        
        .balance-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 10px 0;
        }
        .balance-display.highlight { color: #e74c3c; }
        
        .no-data { text-align: center; padding: 60px 20px; color: #a0aec0; font-size: 1.1rem; }
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .main-content{margin-left:0}
            .hamburger{display:block}
            .form-row{grid-template-columns:1fr !important}
            .enrollment-header{flex-direction:column;align-items:flex-start;gap:8px}
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
            <li class="nav-item"><a href="certificates.php" class="nav-link"><span class="icon">🏆</span><span>Certificates</span></a></li>
            <li class="nav-item"><a href="attendance.php" class="nav-link"><span class="icon">📅</span><span>Attendance</span></a></li>
            <li class="nav-item"><a href="progress.php" class="nav-link"><span class="icon">📈</span><span>Progress</span></a></li>
            <li class="nav-item"><a href="achievements.php" class="nav-link"><span class="icon">⭐</span><span>Achievements</span></a></li>
            <li class="nav-item"><a href="leaderboard.php" class="nav-link"><span class="icon">🏅</span><span>Leaderboard</span></a></li>
            <li class="nav-item"><a href="student_payments.php" class="nav-link active"><span class="icon">💳</span><span>Payments</span></a></li>
            <li class="nav-item"><a href="messages.php" class="nav-link"><span class="icon">💬</span><span>Messages</span></a></li>
            <li class="nav-item"><a href="notifications.php" class="nav-link"><span class="icon">🔔</span><span>Notifications</span></a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><span class="icon">👤</span><span>Profile</span></a></li>
            <li class="nav-item"><a href="settings.php" class="nav-link"><span class="icon">⚙️</span><span>Settings</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><span class="icon">🚪</span><span>Logout</span></a></li>
        </ul>
    </aside>
    <div class="main-content">
        <div class="topbar">
            <button class="hamburger" onclick="toggleSidebar()">☰</button>
            <h2>💳 Payments</h2>
        </div>
        <div style="max-width:900px">

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>💰 Make a Payment</h2>
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_id">Select Course *</label>
                        <select id="course_id" name="course_id" required onchange="updateBalance()">
                            <option value="">Choose a course</option>
                            <?php while($course = $courses_result->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>" data-fee="<?php echo $course['fee_amount']; ?>">
                                    Level <?php echo $course['level_number']; ?> - <?php echo htmlspecialchars($course['course_name']); ?> (RWF <?php echo number_format($course['fee_amount']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount_paid">Amount (RWF) *</label>
                        <input type="number" id="amount_paid" name="amount_paid" required min="1" step="0.01">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reference_number">Reference Number</label>
                        <input type="text" id="reference_number" name="reference_number" placeholder="Optional">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
                <button type="submit" name="make_payment" class="btn btn-primary">💳 Submit Payment</button>
            </form>
        </div>

        <div class="card">
            <h2>📋 My Enrollments</h2>
            <?php if ($enrollments_result && $enrollments_result->num_rows > 0): ?>
                <?php while($enrollment = $enrollments_result->fetch_assoc()): ?>
                    <div class="enrollment-card <?php echo $enrollment['payment_status']; ?>">
                        <div class="enrollment-header">
                            <div class="enrollment-title">
                                Level <?php echo $enrollment['level_number']; ?> - <?php echo htmlspecialchars($enrollment['course_name']); ?>
                            </div>
                            <span class="enrollment-status status-<?php echo $enrollment['payment_status']; ?>">
                                <?php echo ucfirst($enrollment['payment_status']); ?>
                            </span>
                        </div>
                        <div class="enrollment-details">
                            <div class="detail-item">
                                <strong>Total Fee:</strong> RWF <?php echo number_format($enrollment['total_fee'], 2); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Amount Paid:</strong> RWF <?php echo number_format($enrollment['amount_paid'], 2); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Balance:</strong> 
                                <span class="balance-display <?php echo ($enrollment['total_fee'] - $enrollment['amount_paid']) > 0 ? 'highlight' : ''; ?>">
                                    RWF <?php echo number_format($enrollment['total_fee'] - $enrollment['amount_paid'], 2); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($enrollment['payment_status'] != 'paid'): ?>
                            <a href="?pay_course=<?php echo $enrollment['course_id']; ?>" class="btn btn-success">💳 Pay Now</a>
                        <?php else: ?>
                            <a href="course_catalog.php" class="btn btn-primary">📚 Access Course</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <div style="font-size: 3rem; margin-bottom: 15px;">📭</div>
                    No enrollments yet.<br>
                    <small>Make a payment above to enroll in a course.</small>
                </div>
            <?php endif; ?>
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
