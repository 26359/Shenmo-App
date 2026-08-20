<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$courses_result = $conn->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY level_number ASC");

$enrollments_result = $conn->query("
    SELECT course_id, payment_status FROM enrollments 
    WHERE student_id = '" . $_SESSION['student_id'] . "'
");

$enrolled_courses = [];
while ($enrollment = $enrollments_result->fetch_assoc()) {
    $enrolled_courses[$enrollment['course_id']] = $enrollment['payment_status'];
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Course Catalog - Student Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); 
            min-height: 100vh; 
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }
        
        .container { max-width: 1200px; margin: 0 auto; position: relative; z-index: 1; }
        
        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 18px 25px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .nav-bar h1 { color: white; font-size: 1.5rem; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .nav-links { display: flex; gap: 10px; }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 0.8s;
        }
        .page-header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .page-header p {
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .course-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.4s;
            animation: slideUp 0.6s;
            position: relative;
            overflow: hidden;
        }
        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
        
        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .course-level {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .course-code {
            color: #718096;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .course-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .course-description {
            color: #4a5568;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .course-duration {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #718096;
            font-size: 0.9rem;
        }
        .course-fee {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }
        
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
        .btn-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(56, 239, 125, 0.4); }
        .btn-secondary { background: linear-gradient(135deg, #a8a8a8 0%, #7c7c7c 100%); color: white; }
        .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124, 124, 124, 0.4); }
        
        .no-data { 
            text-align: center; 
            padding: 60px 20px; 
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            grid-column: 1 / -1;
        }
        .no-data-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <h1>🎓 Abacus Academy</h1>
            <div class="nav-links">
                <a href="student_dashboard.php" class="btn btn-secondary btn-sm">🏠 Dashboard</a>
                <a href="student_payments.php" class="btn btn-primary btn-sm">💳 Payments</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
                <?php require_once __DIR__ . '/includes/share_buttons.php'; ?>
            </div>
        </div>

        <div class="page-header">
            <h1>📚 Course Catalog</h1>
            <p>Explore our abacus courses and start your learning journey</p>
        </div>

        <div class="courses-grid">
            <?php if ($courses_result && $courses_result->num_rows > 0): ?>
                <?php while($course = $courses_result->fetch_assoc()): 
                    $is_enrolled = isset($enrolled_courses[$course['id']]);
                    $payment_status = $is_enrolled ? $enrolled_courses[$course['id']] : null;
                ?>
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-level">Level <?php echo $course['level_number']; ?></div>
                            <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                        </div>
                        
                        <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                        <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
                        
                        <div class="course-meta">
                            <div class="course-duration">
                                <span>⏱️</span>
                                <span><?php echo $course['duration_weeks']; ?> weeks</span>
                            </div>
                            <div class="course-fee">RWF <?php echo number_format($course['fee_amount']); ?></div>
                        </div>
                        
                        <?php if ($is_enrolled && $payment_status === 'paid'): ?>
                            <a href="course_viewer.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                📖 Access Course
                            </a>
                        <?php elseif ($is_enrolled && $payment_status === 'partial'): ?>
                            <a href="student_payments.php" class="btn btn-success">
                                💳 Pay Balance
                            </a>
                        <?php else: ?>
                            <a href="student_payments.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                💳 Enroll Now
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📚</div>
                    No courses available at the moment.<br>
                    Please check back later.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
