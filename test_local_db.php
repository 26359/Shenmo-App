<?php
require_once __DIR__ . '/config/database.php';
$config = require __DIR__ . '/config/database.php';

echo "Testing LOCAL connection...\n";
echo "Host: {$config['host']}\n";
echo "DB: {$config['dbname']}\n";
echo "User: {$config['user']}\n\n";

try {
    $conn = new mysqli($config['host'], $config['user'], $config['pass']);
    $conn->set_charset($config['charset']);
    
    if ($conn->connect_error) {
        throw new Exception("Connect failed: " . $conn->connect_error);
    }
    
    echo "✓ CONNECTED SUCCESSFULLY!\n";
    echo "Server version: " . $conn->server_info . "\n";
    
    // Check if database exists, create if not
    $result = $conn->query("SHOW DATABASES LIKE '{$config['dbname']}'");
    if ($result->num_rows === 0) {
        echo "Creating database {$config['dbname']}...\n";
        $conn->query("CREATE DATABASE `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database created\n";
    }
    
    $conn->select_db($config['dbname']);
    echo "Current DB: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n";
    
    // Show tables
    $result = $conn->query("SHOW TABLES");
    echo "\nExisting tables:\n";
    if ($result->num_rows === 0) {
        echo "  (none - need to import schemas)\n";
    } else {
        while ($row = $result->fetch_row()) {
            echo "  - $row[0]\n";
        }
    }
    
    $conn->close();
    echo "\n✓ Ready for schema import!\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nMake sure:\n";
    echo "1. XAMPP MySQL is running (green in control panel)\n";
    echo "2. MySQL port is 3306 (default)\n";
    echo "3. Root password is empty (default XAMPP)\n";
}
?>