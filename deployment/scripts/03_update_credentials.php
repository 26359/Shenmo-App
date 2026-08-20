<?php
<?php
/**
 * Script: 03_update_credentials.php
 * Purpose: Update all PHP files to use centralized database config
 * 
 * Run this AFTER deploying files to /var/www/shenmo_app1/
 * Usage: php 03_update_credentials.php
 */

$rootDir = __DIR__ . '/..';  // Project root (shenmo_app1/)
$dbConfigFile = $rootDir . '/config/database.php';
$configDir = $rootDir . '/config/';

// Files to exclude from modification
$excludeFiles = [
    'database.php',
    'app.php',
    'mail.php',
    'update_db_credentials.php',
    '03_update_credentials.php',
];

// Check if database.php exists
if (!file_exists($dbConfigFile)) {
    die("ERROR: config/database.php not found. Create it first.\n");
}

// Get database config content
$dbConfigContent = file_get_contents($dbConfigFile);

// PHP files to update (all .php files in root except config/)
$phpFiles = glob($rootDir . '/*.php');

$updatedCount = 0;
$skippedCount = 0;

foreach ($phpFiles as $filePath) {
    $fileName = basename($filePath);
    
    // Skip excluded files
    if (in_array($fileName, $excludeFiles)) {
        echo "SKIP: $fileName\n";
        $skippedCount++;
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Pattern 1: Replace inline mysqli connection at top of file
    // Matches: $host = "localhost"; $dbname = "..."; $user = "..."; $pass = "...";
    $content = preg_replace(
        '/\$host\s*=\s*["\'][^"\']*["\'];\s*\$dbname\s*=\s*["\'][^"\']*["\'];\s*\$user\s*=\s*["\'][^"\']*["\'];\s*\$pass\s*=\s*["\'][^"\']*["\'];/',
        '',
        $content
    );
    
    // Pattern 2: Replace $conn = new mysqli(...) with centralized config
    $content = preg_replace(
        '/\$conn\s*=\s*new\s+mysqli\s*\(\s*\$host\s*,\s*\$user\s*,\s*\$pass\s*,\s*\$dbname\s*\)/',
        '',
        $content
    );
    
    // Add require_once at the top if not already present
    if (strpos($content, "require_once __DIR__ . '/config/database.php'") === false &&
        strpos($content, "require_once 'config/database.php'") === false) {
        // Insert after <?php or at the beginning
        if (strpos($content, '<?php') !== false) {
            $content = preg_replace(
                '/<\?php/',
                "<?php\nrequire_once __DIR__ . '/config/database.php';",
                $content,
                1
            );
        } else {
            $content = "<?php\nrequire_once __DIR__ . '/config/database.php';\n" . $content;
        }
    }
    
    // Add $config = require ... if not present
    if (strpos($content, '$config = require') === false) {
        $content = preg_replace(
            '/require_once __DIR__ \. \'\/config\/database\.php\';/',
            "require_once __DIR__ . '/config/database.php';\n\$config = require __DIR__ . '/config/database.php';",
            $content,
            1
        );
    }
    
    // Replace $host, $user, $pass, $dbname variables with $config references
    $content = str_replace('$host', '$config[\'host\']', $content);
    $content = str_replace('$user', '$config[\'user\']', $content);
    $content = str_replace('$pass', '$config[\'pass\']', $content);
    $content = str_replace('$dbname', '$config[\'dbname\']', $content);
    
    // Also handle direct string replacements for remaining inline credentials
    // Pattern: $conn = new mysqli("localhost", "root", "", "shenmo_app");
    $content = preg_replace_callback(
        '/\$conn\s*=\s*new\s+mysqli\s*\(\s*["\'][^"\']*["\']\s*,\s*["\'][^"\']*["\']\s*,\s*["\'][^"\']*["\']\s*,\s*["\'][^"\']*["\']\s*\)/',
        function($matches) use ($config) {
            return '$conn = new mysqli($config[\'host\'], $config[\'user\'], $config[\'pass\'], $config[\'dbname\']);';
        },
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "UPDATED: $fileName\n";
        $updatedCount++;
    } else {
        echo "NO CHANGE: $fileName\n";
    }
}

echo "\n=== Summary ===\n";
echo "Updated: $updatedCount files\n";
echo "Skipped: $skippedCount files\n";
echo "\nNext steps:\n";
echo "1. Verify config/database.php has correct credentials\n";
echo "2. Test a few pages to ensure DB connections work\n";
echo "3. Deploy to server\n";
