<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';
require_once 'C:/xampp/htdocs/shenmo/config/database.php';
$c = require 'C:/xampp/htdocs/shenmo/config/database.php';
echo "Host: {$c['host']}\n";
echo "DB: {$c['dbname']}\n";
?>