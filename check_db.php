<?php
// Simulate local environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';

require_once 'C:/xampp/htdocs/shenmo/config/database.php';
$config = require 'C:/xampp/htdocs/shenmo/config/database.php';

echo "Config loaded:\n";
echo "  Host: {$config['host']}\n";
echo "  DB: {$config['dbname']}\n";
echo "  User: {$config['user']}\n\n";

$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);
$conn->set_charset($config['charset']);

if ($conn->connect_error) {
    die('Connect failed: ' . $conn->connect_error);
}

echo 'Connected to: ' . $conn->query('SELECT DATABASE()')->fetch_row()[0] . PHP_EOL;

// Check tables
$result = $conn->query('SHOW TABLES');
echo 'Tables:' . PHP_EOL;
while ($row = $result->fetch_row()) {
    echo '  - ' . $row[0] . PHP_EOL;
}

// Check shenmo_user
$result = $conn->query('SELECT user_id, user_names, user_password, user_role FROM shenmo_user');
echo PHP_EOL . 'shenmo_user records:' . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo '  ID: ' . $row['user_id'] . ', User: ' . $row['user_names'] . ', Role: ' . $row['user_role'] . ', Pass: ' . substr($row['user_password'], 0, 20) . '...' . PHP_EOL;
}

// Test password_verify
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$test = password_verify('admin123', $hash);
echo PHP_EOL . 'password_verify test: ' . ($test ? 'PASS' : 'FAIL') . PHP_EOL;

$conn->close();
?>