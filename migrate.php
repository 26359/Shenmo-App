<?php
// Run once to add file columns — safe to run multiple times
$conn = new mysqli("fdb1028.awardspace.net", "4783798_shenmoapp", "muganwa123", "4783798_shenmoapp");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$migrations = [
    "ALTER TABLE homework ADD COLUMN IF NOT EXISTS file_path VARCHAR(500) NULL AFTER description",
    "ALTER TABLE homework ADD COLUMN IF NOT EXISTS lesson_id INT NULL",
    "ALTER TABLE certificates ADD COLUMN IF NOT EXISTS pdf_path VARCHAR(500) NULL AFTER issue_date",
    "ALTER TABLE homework MODIFY COLUMN lesson_id INT NULL",
];

foreach ($migrations as $sql) {
    $conn->query($sql); // ignore errors for already-existing columns
}

echo "<pre>Migration complete.\n";
echo "homework.file_path — stores admin-uploaded homework document\n";
echo "certificates.pdf_path — stores admin-uploaded certificate PDF\n";
echo "</pre>";
echo '<a href="admin_dashboard.php">Go to Admin Dashboard</a>';
$conn->close();
?>
