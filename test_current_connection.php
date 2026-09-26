<?php
$host = "fdb1028.awardspace.net";
$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";

echo "Testing connection...\n";
echo "Host: $host\n";
echo "User: $user\n";
echo "DB: $dbname\n\n";

$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo "✗ CONNECTION FAILED\n";
    echo "Error: " . $conn->connect_error . "\n";
    echo "Errno: " . $conn->connect_errno . "\n";

    switch ($conn->connect_errno) {
        case 1045: echo "→ Access denied (wrong credentials)\n"; break;
        case 2003: echo "→ Can't connect to server (host/port/firewall)\n"; break;
        case 1049: echo "→ Unknown database\n"; break;
        case 2005: echo "→ Unknown host\n"; break;
    }
} else {
    echo "✓ CONNECTED SUCCESSFULLY!\n";
    echo "Server version: " . $conn->server_info . "\n";
    echo "Current DB: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n";

    $result = $conn->query("SHOW TABLES");
    echo "\nTables found:\n";
    while ($row = $result->fetch_row()) {
        echo "  - $row[0]\n";
    }
    $conn->close();
}
?>