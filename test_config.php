<?php
require_once 'C:/xampp/htdocs/shenmo/config/database.php';
$config = require 'C:/xampp/htdocs/shenmo/config/database.php';
echo "Host: {$config['host']}\n";
echo "DB: {$config['dbname']}\n";
echo "User: {$config['user']}\n";