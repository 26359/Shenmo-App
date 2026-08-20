<?php
session_start();

// Database connection
$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";
$error = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if (!$conn->connect_error) {
        $username = $_POST['user_names'];
        $password = $_POST['user_password'];
        
        // Query to verify credentials
        $sql = "SELECT user_id, user_names, user_country, user_city, use_telephone, user_birthdate 
                FROM shenmo_user 
                WHERE user_names = ? AND user_password = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Authentication successful - set session
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_names'] = $row['user_names'];
        } else {
            $error = "Invalid username or password";
        }
        $stmt->close();
        $conn->close();
    } else {
        $error = "Database connection failed";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login_system.php");
    exit;
}

// Check if logged in
$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'];

// Fetch all users for table display
$users = [];
if ($loggedin) {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if (!$conn->connect_error) {
        $result = $conn->query("SELECT user_id, user_names, user_country, user_city, use_telephone, user_birthdate FROM shenmo_user");
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login System</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; }
        .login-form { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 400px; margin: 50px auto; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; }
        input[type="submit"] { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        input[type="submit"]:hover { background: #2980b9; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .welcome { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .logout { float: right; background: #e74c3c; }
        .logout:hover { background: #c0392b; }
        table { border-collapse: collapse; width: 100%; background: white; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #3498db; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$loggedin): ?>
            <!-- Login Form -->
            <div class="login-form">
                <h2>Login</h2>
                <?php if ($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="post">
                    <label for="user_names">Username:</label>
                    <input type="text" id="user_names" name="user_names" required>
                    
                    <label for="user_password">Password:</label>
                    <input type="password" id="user_password" name="user_password" required>
                    
                    <input type="submit" name="login" value="Login">
                </form>
            </div>
        <?php else: ?>
            <!-- Data Table (shown after login) -->
            <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['user_names']); ?>! <a href="?logout" class="logout" style="color:white;padding:5px 10px;border-radius:3px;text-decoration:none;">Logout</a></div>
            
            <h2>User Records</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Country</th>
                    <th>City</th>
                    <th>Phone</th>
                    <th>Birthdate</th>
                </tr>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_names']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_country']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_city']); ?></td>
                    <td><?php echo htmlspecialchars($user['use_telephone']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_birthdate']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>