<?php
// Fix all files with literal \n in require statements

$files = glob('C:\xampp\htdocs\shenmo\*.php');
$fixed = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Fix literal \n in require statements
    $content = str_replace(
        "require_once __DIR__ . '/config/database.php';\n\$config = require __DIR__ . '/config/database.php';",
        "require_once __DIR__ . '/config/database.php';\n\$config = require __DIR__ . '/config/database.php';",
        $content
    );
    
    // More aggressive fix
    $content = preg_replace(
        "/require_once __DIR__ \. '\/config\/database\.php';\\\\n\\\$config = require __DIR__ \. '\/config\/database\.php';/",
        "require_once __DIR__ . '/config/database.php';\n\$config = require __DIR__ . '/config/database.php';",
        $content
    );
    
    // Even more aggressive - replace any \n that's not inside quotes
    $content = preg_replace(
        "/(require_once __DIR__ \. '\/config\/database\.php';)(\\\\n)(\\\$config = require __DIR__ \. '\/config\/database\.php';)/",
        "$1\n$3",
        $content
    );
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Fixed: " . basename($file) . "\n";
        $fixed++;
    }
}

echo "\nDone. Fixed $fixed files.\n";
?>