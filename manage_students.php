<?php
session_start();

$host = "localhost";
$dbname = "shenmo_app";
$user = "root";
$pass = "";
$message = "";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    grade_level VARCHAR(10) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    dob DATE,
    address TEXT,
    username VARCHAR(50) UNIQUE NOT NULL DEFAULT '',
    password VARCHAR(255) NOT NULL DEFAULT ''
)");

$check_username = $conn->query("SHOW COLUMNS FROM students LIKE 'username'");
if ($check_username->num_rows == 0) {
    $conn->query("ALTER TABLE students ADD COLUMN username VARCHAR(50) NOT NULL DEFAULT ''");
    $conn->query("ALTER TABLE students ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT ''");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_id = $_POST['student_id'];
    $full_name = $_POST['full_name'];
    $grade_level = $_POST['grade_level'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $dob = $_POST['dob'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "INSERT INTO students (student_id, full_name, grade_level, email, phone, address, dob, username, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $student_id, $full_name, $grade_level, $email, $phone, $address, $dob, $username, $password);

    if ($stmt->execute()) {
        $message = "Student added successfully!";
        header("Location: manage_students.php");
        exit;
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $student_id = $_POST['student_id'];
    $full_name = $_POST['full_name'];
    $grade_level = $_POST['grade_level'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $dob = $_POST['dob'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "UPDATE students SET 
            full_name = ?, grade_level = ?, email = ?, phone = ?, 
            address = ?, dob = ?, username = ?, password = ? 
            WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $full_name, $grade_level, $email, $phone, $address, $dob, $username, $password, $student_id);

    if ($stmt->execute()) {
        $message = "Student updated successfully!";
        header("Location: manage_students.php");
        exit;
    } else {
        $message = "Error updating student: " . $stmt->error;
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM students ORDER BY student_id ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $delete_id = $_POST['student_id'];
    $del_stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $del_stmt->bind_param("s", $delete_id);
    if ($del_stmt->execute()) {
        $message = "Student deleted successfully!";
        header("Location: manage_students.php");
        exit;
    } else {
        $message = "Error deleting student: " . $del_stmt->error;
    }
    $del_stmt->close();
}

$edit_student = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $edit_stmt->bind_param("s", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_student = $edit_result->fetch_assoc();
    $edit_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; min-height: 100vh; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar{width:260px;background:linear-gradient(180deg,#1e293b 0%,#0f172a 100%);position:fixed;height:100vh;overflow-y:auto;z-index:200;transition:transform 0.3s}
        .sidebar-logo{padding:25px 20px;border-bottom:1px solid rgba(255,255,255,0.1);text-align:center}
        .sidebar-logo h1{color:#fff;font-size:1.2rem;font-weight:700;margin:0}
        .sidebar-logo p{color:#94a3b8;font-size:0.8rem;margin-top:4px}
        .nav-section{padding:15px 10px 5px;color:#64748b;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px}
        .nav-menu{list-style:none;padding:0 10px}
        .nav-item{margin-bottom:3px}
        .nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;color:#94a3b8;text-decoration:none;border-radius:10px;transition:all 0.2s;font-size:0.9rem;font-weight:500}
        .nav-link:hover,.nav-link.active{background:rgba(59,130,246,0.2);color:#60a5fa}
        .nav-link .icon{font-size:1.1rem;width:22px;text-align:center}
        .main-wrap{flex:1;margin-left:260px;display:flex;flex-direction:column}
        .topbar{background:#fff;padding:15px 25px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 4px rgba(0,0,0,0.08);position:sticky;top:0;z-index:100}
        .topbar-left{display:flex;align-items:center;gap:15px}
        .hamburger{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#475569}
        .topbar h2{color:#1e293b;font-size:1.2rem;font-weight:700;margin:0}
        .container { max-width: 1200px; margin: 0 auto; padding: 25px; }
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150}
        .sidebar-overlay.active{display:block}

        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 30px;
            transition: transform 0.2s;
        }
        .card h2 { color: #2d3748; margin-bottom: 20px; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
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
        .btn-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(245, 87, 108, 0.4); }
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

        .table-container { overflow-x: auto; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 14px 12px; text-align: left; }
        th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; font-size: 0.9rem; }
        tr:nth-child(even) { background: #f7fafc; }
        tr:hover { background: #edf2f7; transition: background 0.2s; }
        .no-data { text-align: center; padding: 40px; color: #a0aec0; font-size: 1rem; }
        .action-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-sm { padding: 8px 16px; font-size: 13px; border-radius: 8px; }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; animation: fadeIn 0.2s; }
        .modal {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .modal h2 { color: #2d3748; margin-bottom: 20px; }
        .modal-close {
            float: right;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #a0aec0;
            line-height: 1;
        }
        .modal-close:hover { color: #2d3748; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .main-wrap{margin-left:0}
            .hamburger{display:block}
            .form-row{grid-template-columns:1fr !important}
        }
        @media(max-width:480px){.container{padding:15px}}
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
            <li class="nav-item"><a href="manage_students.php" class="nav-link active"><span class="icon">🎓</span>Manage Students</a></li>
        </ul>
        <p class="nav-section">Learning</p>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin_homework.php" class="nav-link"><span class="icon">📝</span>Assign Homework</a></li>
            <li class="nav-item"><a href="admin_certificates.php" class="nav-link"><span class="icon">🏆</span>Issue Certificates</a></li>
        </ul>
        <p class="nav-section">Finance</p>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin_payments.php" class="nav-link"><span class="icon">💳</span>Payments</a></li>
        </ul>
        <p class="nav-section">Account</p>
        <ul class="nav-menu">
            <li class="nav-item"><a href="logout.php" class="nav-link"><span class="icon">🚪</span>Logout</a></li>
        </ul>
    </aside>
    <div class="main-wrap">
        <div class="topbar">
            <div class="topbar-left">
                <button class="hamburger" onclick="toggleSidebar()">☰</button>
                <h2>🎓 Student Management</h2>
            </div>
        </div>
    <div class="container">

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false || strpos($message, 'updated') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>✨ <?php echo $edit_student ? 'Edit Student' : 'Add New Student'; ?></h2>
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Student ID *</label>
                        <input type="text" id="student_id" name="student_id" required value="<?php echo $edit_student ? htmlspecialchars($edit_student['student_id']) : ''; ?>" <?php echo $edit_student ? 'readonly' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required value="<?php echo $edit_student ? htmlspecialchars($edit_student['full_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="grade_level">Abacus Level *</label>
                        <select id="grade_level" name="grade_level" required>
                            <option value="">Select Level</option>
                            <?php for($i=1; $i<=6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($edit_student && $edit_student['grade_level'] == $i) ? 'selected' : ''; ?>>Level <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?php echo $edit_student ? htmlspecialchars($edit_student['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required value="<?php echo $edit_student ? htmlspecialchars($edit_student['phone']) : ''; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo $edit_student ? htmlspecialchars($edit_student['dob']) : ''; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address"><?php echo $edit_student ? htmlspecialchars($edit_student['address']) : ''; ?></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Login Username *</label>
                        <input type="text" id="username" name="username" required value="<?php echo $edit_student ? htmlspecialchars($edit_student['username']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Login Password *</label>
                        <input type="text" id="password" name="password" required value="<?php echo $edit_student ? htmlspecialchars($edit_student['password']) : ''; ?>">
                    </div>
                </div>
                <button type="submit" name="<?php echo $edit_student ? 'update_student' : 'add_student'; ?>" class="btn btn-<?php echo $edit_student ? 'warning' : 'success'; ?>">
                    <?php echo $edit_student ? '✏️ Update Student' : '➕ Add Student'; ?>
                </button>
                <?php if ($edit_student): ?>
                    <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>📋 Student Roster</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Abacus Level</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>DOB</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['grade_level']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['dob']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                            echo '<td class="action-btns">
                                    <a href="?edit=' . urlencode($row['student_id']) . '" class="btn btn-warning btn-sm">✏️ Edit</a>
                                    <form method="post" action="" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this student?\');">
                                        <input type="hidden" name="student_id" value="' . htmlspecialchars($row['student_id']) . '">
                                        <button type="submit" name="delete_student" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                    </form>
                                  </td>';
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="8" class="no-data">No students found. Add a new student above.</td></tr>';
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
    </div></div></div>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('active');
}
</script>
</body>
</html>
<?php $conn->close(); ?>
