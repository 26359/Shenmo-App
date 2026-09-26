<?php
// update_all_db_config.php - Run once to update all PHP files

$files = glob('*.php');
$updated = 0;

foreach ($files as $file) {
    if ($file === 'test_local_db.php' || $file === 'test_hosts.php' || $file === 'test_current_connection.php' || $file === 'migrate.php' || $file === 'update_all_db_config.php') {
        continue; // Skip test/migration files
    }
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Pattern 1: Full credential block + mysqli connection
    $pattern1 = '/\$host\s*=\s*"fdb1028\.awardspace\.net";\s*\$dbname\s*=\s*"4783798_shenmoapp";\s*\$user\s*=\s*"4783798_shenmoapp";\s*\$pass\s*=\s*"muganwa123";/s';
    $replacement1 = 'require_once __DIR__ . \'/config/database.php\';\n$config = require __DIR__ . \'/config/database.php\';';
    
    // Pattern 2: mysqli connection with variables
    $pattern2 = '/new mysqli\(\$host,\s*\$user,\s*\$pass,\s*\$dbname\)/';
    $replacement2 = 'new mysqli($config[\'host\'], $config[\'user\'], $config[\'pass\'], $config[\'dbname\'])';
    
    // Pattern 3: mysqli connection with string literals (migrate.php style)
    $pattern3 = '/new mysqli\("fdb1028\.awardspace\.net",\s*"4783798_shenmoapp",\s*"muganwa123",\s*"4783798_shenmoapp"\)/';
    $replacement3 = 'new mysqli($config[\'host\'], $config[\'user\'], $config[\'pass\'], $config[\'dbname\'])';
    
    $content = preg_replace($pattern1, $replacement1, $content);
    $content = preg_replace($pattern2, $replacement2, $content);
    $content = preg_replace($pattern3, $replacement3, $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated: $file\n";
        $updated++;
    }
}

echo "\nDone. Updated $updated files.\n";
?>