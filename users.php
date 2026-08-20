<?php
// XAMPP MySQL connection settings
$host = "localhost";
$dbname = "shenmo_app";
$user = "root";
$pass = "";

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch users
$result = $conn->query("SELECT user_id, user_names, user_country, user_city, use_telephone, user_password, user_birthdate FROM shenmo_user");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Users Table</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        table { border-collapse: collapse; width: 100%; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #3498db; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h2>Users Table</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Country</th>
            <th>City</th>
            <th>Phone</th>
            <th>Password</th>
            <th>Birthdate</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
            <td><?php echo htmlspecialchars($row['user_names']); ?></td>
            <td><?php echo htmlspecialchars($row['user_country']); ?></td>
            <td><?php echo htmlspecialchars($row['user_city']); ?></td>
            <td><?php echo htmlspecialchars($row['use_telephone']); ?></td>
            <td><?php echo htmlspecialchars($row['user_password']); ?></td>
            <td><?php echo htmlspecialchars($row['user_birthdate']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php $conn->close(); ?>