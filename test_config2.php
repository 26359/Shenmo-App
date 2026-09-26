<?php
// Simulate local HTTP request
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';

require_once 'C:/xampp/htdocs/shenmo/config/database.php';
$config = require 'C:/xampp/htdocs/shenmo/config/database.php';
echo "LOCAL TEST:\n";
echo "Host: {$config['host']}\n";
echo "DB: {$config['dbname']}\n\n";

// Simulate production
$_SERVER['HTTP_HOST'] = 'shenmoapp.atwebpages.com';
$_SERVER['SERVER_ADDR'] = '10.136.226.100';

require_once 'C:/xampp/htdocs/shenmo/config/database.php';
$config = require 'C:/xampp/htdocs/shenmo/config/database.php';
echo "PRODUCTION TEST:\n";
echo "Host: {$config['host']}\n";
echo "DB: {$config['dbname']}\n";