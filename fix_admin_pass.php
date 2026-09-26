<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';

require_once 'C:/xampp/htdocs/shenmo/config/database.php';
$config = require 'C:/xampp/htdocs/shenmo/config/database.php';

$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);
$conn->set_charset($config['charset']);

// Generate correct hash for 'admin123'
$newHash = password_hash('admin123', PASSWORD_DEFAULT);
echo "New hash for 'admin123': $newHash\n";

// Update database
$conn->query("UPDATE shenmo_user SET user_password = '$newHash' WHERE user_names = 'admin'");
echo "Updated admin password\n";

// Verify
$result = $conn->query("SELECT user_password FROM shenmo_user WHERE user_names = 'admin'");
$row = $result->fetch_assoc();
$test = password_verify('admin123', $row['user_password']);
echo "Verification: " . ($test ? 'PASS' : 'FAIL') . "\n";

$conn->close();
?>