<?php
// fix_remaining.php - Fix files with inline credentials

$files = glob('*.php');
$fixed = 0;

foreach ($files as $file) {
    if (in_array($file, ['test_local_db.php', 'test_hosts.php', 'test_current_connection.php', 'migrate.php', 'update_all_db_config.php', 'fix_remaining.php'])) {
        continue;
    }
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Pattern: inline credentials (may have new DB name already)
    $pattern = '/\$host\s*=\s*"fdb1028\.awardspace\.net";\s*\$dbname\s*=\s*"[^"]*";\s*\$user\s*=\s*"[^"]*";\s*\$pass\s*=\s*"[^"]*";/s';
    $replacement = "require_once __DIR__ . '/config/database.php';\n\$config = require __DIR__ . '/config/database.php';";
    
    $content = preg_replace($pattern, $replacement, $content);
    
    // Also fix any remaining direct mysqli calls with $host,$user,$pass,$dbname
    $content = preg_replace('/new mysqli\(\$host,\s*\$user,\s*\$pass,\s*\$dbname\)/', 'new mysqli($config[\'host\'], $config[\'user\'], $config[\'pass\'], $config[\'dbname\'])', $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
        $fixed++;
    }
}

echo "\nDone. Fixed $fixed files.\n";
?>