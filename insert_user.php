<?php
// Connection settings
$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";
$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        $message = "Connection failed: " . $conn->connect_error;
    } else {
        $user_names = $_POST['user_names'];
        $user_country = $_POST['user_country'];
        $user_city = $_POST['user_city'];
        $use_telephone = $_POST['use_telephone'];
        $user_password = $_POST['user_password'];
        $user_birthdate = $_POST['user_birthdate'];
        
        $sql = "INSERT INTO shenmo_user (user_names, user_country, user_city, use_telephone, user_password, user_birthdate) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiss", $user_names, $user_country, $user_city, $use_telephone, $user_password, $user_birthdate);
        
        if ($stmt->execute()) {
            $message = "New record created successfully";
        } else {
            $message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Insert User</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .form-container { background: white; padding: 20px; max-width: 400px; margin: 0 auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input[type="text"], input[type="tel"], input[type="date"], input[type="password"] { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; 
        }
        input[type="submit"] { margin-top: 15px; padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background: #2980b9; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Add New User</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <label for="user_names">Username:</label>
            <input type="text" id="user_names" name="user_names" required>
            
            <label for="user_country">Country:</label>
            <input type="text" id="user_country" name="user_country" required>
            
            <label for="user_city">City:</label>
            <input type="text" id="user_city" name="user_city" required>
            
            <label for="use_telephone">Phone:</label>
            <input type="tel" id="use_telephone" name="use_telephone" required>
            
            <label for="user_password">Password:</label>
            <input type="password" id="user_password" name="user_password" required>
            
            <label for="user_birthdate">Birthdate:</label>
            <input type="date" id="user_birthdate" name="user_birthdate" required>
            
            <input type="submit" value="Submit">
        </form>
    </div>
</body>
</html>