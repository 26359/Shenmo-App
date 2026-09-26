<?php
require_once __DIR__ . '/config/database.php';
$config = require __DIR__ . '/config/database.php';

try {
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (mysqli_sql_exception $e) {
    die("Connection failed: " . $e->getMessage());
}

$result = $conn->query("SELECT user_id, user_names, user_country, user_city, use_telephone, user_birthdate FROM shenmo_user");
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Table</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <h2>Users</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Country</th>
            <th>City</th>
            <th>Phone</th>
            <th>Birthdate</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['user_id - page1.php:38']); ?></td>
            <td><?php echo htmlspecialchars($row['user_names - page1.php:39']); ?></td>
            <td><?php echo htmlspecialchars($row['user_country - page1.php:40']); ?></td>
            <td><?php echo htmlspecialchars($row['user_city - page1.php:41']); ?></td>
            <td><?php echo htmlspecialchars($row['use_telephone - page1.php:42']); ?></td>
            <td><?php echo htmlspecialchars($row['user_birthdate - page1.php:43']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php $conn->close(); ?>