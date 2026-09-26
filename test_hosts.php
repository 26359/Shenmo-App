<?php
$hosts = [
    "fdb1028.awardspace.net",
    "mysql.awardspace.net",
    "localhost",
    "127.0.0.1",
    "supportindeed.com",
];

$dbname = "4783798_shenmoapp";
$user = "4783798_shenmoapp";
$pass = "muganwa123";

foreach ($hosts as $host) {
    echo "\n=== Testing: $host ===\n";
    try {
        $conn = new mysqli($host, $user, $pass, $dbname);
        if ($conn->connect_error) {
            echo "✗ Failed: " . $conn->connect_error . " (Errno: " . $conn->connect_errno . ")\n";
        } else {
            echo "✓ SUCCESS!\n";
            echo "Server: " . $conn->server_info . "\n";
            echo "DB: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n";
            $conn->close();
            exit(0); // Stop on first success
        }
    } catch (mysqli_sql_exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
}
echo "\nAll hosts failed.\n";
?>