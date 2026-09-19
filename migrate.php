<?php
// Run once to add file columns — safe to run multiple times
$conn = new mysqli("fdb1028.awardspace.net", "4783798_shenmoapp", "muganwa123", "4783798_shenmoapp");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

function addColumnIfNotExists($conn, $table, $column, $sql) {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'");
    $row = $res->fetch_assoc();
    $res->free();
    if ($row['cnt'] == 0) {
        $conn->query($sql);
        echo "Added column `$column` to `$table`.\n";
    } else {
        echo "Column `$column` already exists in `$table` — skipping.\n";
    }
}

addColumnIfNotExists($conn, "homework", "file_path", "ALTER TABLE homework ADD COLUMN file_path VARCHAR(500) NULL AFTER description");
addColumnIfNotExists($conn, "homework", "lesson_id", "ALTER TABLE homework ADD COLUMN lesson_id INT NULL");
addColumnIfNotExists($conn, "certificates", "pdf_path", "ALTER TABLE certificates ADD COLUMN pdf_path VARCHAR(500) NULL AFTER issue_date");

echo "<pre>Migration complete.\n";
echo "homework.file_path — stores admin-uploaded homework document\n";
echo "certificates.pdf_path — stores admin-uploaded certificate PDF\n";
echo "</pre>";
echo '<a href="admin_dashboard.php">Go to Admin Dashboard</a>';
$conn->close();
?>
