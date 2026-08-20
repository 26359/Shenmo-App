<?php
session_start();

$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || empty($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student = null;
$student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : '';

if (!empty($student_id)) {
    $stmt = $conn->prepare("SELECT student_id, full_name, grade_level, email, phone, dob, address FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $student = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$student && !empty($_SESSION['full_name'])) {
    $student = [
        'student_id' => $student_id,
        'full_name' => $_SESSION['full_name'],
        'grade_level' => '',
        'email' => '',
        'phone' => '',
        'dob' => '',
        'address' => ''
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 18px 25px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .nav-bar h1 { color: #2d3748; font-size: 1.4rem; }
        .nav-links { display: flex; gap: 10px; flex-wrap: wrap; }
        .card { background: white; padding: 35px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); animation: slideUp 0.4s; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        h1 { color: #2d3748; margin-bottom: 25px; font-size: 1.8rem; }
        .welcome { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 18px; border-radius: 12px; margin-bottom: 25px; color: #155724; font-weight: 600; font-size: 1.05rem; border: 1px solid #c3e6cb; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .info-item { background: #f7fafc; padding: 15px; border-radius: 12px; border-left: 4px solid #667eea; }
        .info-item strong { display: block; color: #718096; font-size: 0.85rem; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item span { color: #2d3748; font-size: 1rem; font-weight: 500; }
        .quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .quick-link { display: block; text-align: center; padding: 14px; background: #f7fafc; color: #2d3748; text-decoration: none; border-radius: 12px; font-weight: 600; border: 1px solid #e2e8f0; }
        .quick-link:hover { background: #edf2f7; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; border: none; border-radius: 12px; cursor: pointer; font-size: 15px; font-weight: 600; text-decoration: none; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
        .btn-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(56, 239, 125, 0.4); }
        .btn-danger { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(235, 51, 73, 0.4); }
        .error-msg { background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 600; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <h1>🎓 Student Portal</h1>
            <div class="nav-links">
                <a href="my_learning.php" class="btn btn-primary">📚 My Learning</a>
                <a href="change_password.php" class="btn btn-success">🔑 Change Password</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="card">
            <h1>Student Dashboard</h1>
            <?php if ($student): ?>
                <div class="welcome">
                    Welcome, <?php echo htmlspecialchars($student['full_name - student_dashboard.php:96'] ?? 'Student'); ?>! 👋
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Student ID</strong>
                        <span><?php echo htmlspecialchars($student['student_id - student_dashboard.php:101'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Full Name</strong>
                        <span><?php echo htmlspecialchars($student['full_name - student_dashboard.php:105'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Grade Level</strong>
                        <span>Grade <?php echo htmlspecialchars($student['grade_level - student_dashboard.php:109'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Email</strong>
                        <span><?php echo htmlspecialchars($student['email - student_dashboard.php:113'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Phone</strong>
                        <span><?php echo htmlspecialchars($student['phone - student_dashboard.php:117'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Date of Birth</strong>
                        <span><?php echo htmlspecialchars($student['dob - student_dashboard.php:121'] ?? ''); ?></span>
                    </div>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <strong>Address</strong>
                        <span><?php echo htmlspecialchars($student['address - student_dashboard.php:125'] ?? ''); ?></span>
                    </div>
                </div>

                <div class="quick-links">
                    <a href="my_learning.php" class="quick-link">My Courses</a>
                    <a href="progress.php" class="quick-link">Progress</a>
                    <a href="notifications.php" class="quick-link">Notifications</a>
                    <a href="messages.php" class="quick-link">Messages</a>
                </div>
            <?php else: ?>
                <div class="error-msg">Student record not found. Please contact administration.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
