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

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    header("Location: course_catalog.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT e.payment_status, e.enrollment_status, c.course_name, c.description 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ? AND e.course_id = ?
");
$stmt->bind_param("si", $_SESSION['student_id'], $course_id);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$enrollment || $enrollment['payment_status'] !== 'paid') {
    header("Location: student_payments.php?course_id=" . $course_id);
    exit;
}

$content_result = $conn->query("
    SELECT * FROM course_content 
    WHERE course_id = $course_id AND is_published = 1 
    ORDER BY week_number ASC, sort_order ASC
");

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($enrollment['course_name']); ?> - Course Viewer</title>
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
        
        .container { max-width: 900px; margin: 0 auto; position: relative; z-index: 1; }
        
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
        
        .card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.3);
            animation: slideUp 0.6s;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
        
        .course-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
        }
        .course-header h1 {
            color: #2d3748;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .course-header p {
            color: #718096;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .access-granted {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #c3e6cb;
            animation: slideUp 0.6s;
        }
        
        .content-section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
        }
        
        .content-item {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
            transition: all 0.3s;
        }
        .content-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .content-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2d3748;
        }
        .content-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-text { background: #d1ecf1; color: #0c5460; }
        .badge-video { background: #f8d7da; color: #721c24; }
        .badge-pdf { background: #fff3cd; color: #856404; }
        .badge-quiz { background: #d4edda; color: #155724; }
        
        .content-body {
            color: #4a5568;
            line-height: 1.7;
            font-size: 1rem;
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
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: linear-gradient(135deg, #a8a8a8 0%, #7c7c7c 100%); color: white; }
        .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124, 124, 124, 0.4); }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        
        .no-content { 
            text-align: center; 
            padding: 60px 20px; 
            color: #a0aec0;
            font-size: 1.1rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <h1>📚 Course Viewer</h1>
            <div class="nav-links">
                <a href="course_catalog.php" class="btn btn-secondary btn-sm">← Back to Catalog</a>
                <a href="student_dashboard.php" class="btn btn-secondary btn-sm">🏠 Dashboard</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>

        <div class="access-granted">
            ✅ Payment Verified - You have full access to this course
        </div>

        <div class="card">
            <div class="course-header">
                <h1><?php echo htmlspecialchars($enrollment['course_name']); ?></h1>
                <p><?php echo htmlspecialchars($enrollment['description']); ?></p>
            </div>
        </div>

        <div class="card">
            <div class="content-section">
                <div class="section-title">📖 Course Materials</div>
                
                <?php if ($content_result && $content_result->num_rows > 0): ?>
                    <?php while($content = $content_result->fetch_assoc()): ?>
                        <div class="content-item">
                            <div class="content-header">
                                <div class="content-title"><?php echo htmlspecialchars($content['title']); ?></div>
                                <span class="content-badge badge-<?php echo $content['content_type']; ?>">
                                    <?php echo ucfirst($content['content_type']); ?>
                                </span>
                            </div>
                            
                            <?php if ($content['content_type'] == 'text' && $content['content_text']): ?>
                                <div class="content-body">
                                    <?php echo nl2br(htmlspecialchars($content['content_text'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($content['content_type'] == 'pdf' && $content['content_url']): ?>
                                <a href="<?php echo htmlspecialchars($content['content_url']); ?>" class="btn btn-primary btn-sm" target="_blank">
                                    📄 Download PDF
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($content['content_type'] == 'video' && $content['content_url']): ?>
                                <video controls style="width: 100%; border-radius: 10px; margin-top: 10px;">
                                    <source src="<?php echo htmlspecialchars($content['content_url']); ?>" type="video/mp4">
                                    Your browser does not support video playback.
                                </video>
                            <?php endif; ?>
                            
                            <?php if ($content['content_type'] == 'quiz'): ?>
                                <a href="#" class="btn btn-success btn-sm">📝 Take Quiz</a>
                            <?php endif; ?>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 0%"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-content">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📭</div>
                        No content available yet.<br>
                        <small>Course materials will be added by the instructor.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
