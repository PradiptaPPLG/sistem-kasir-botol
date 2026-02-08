<?php
// includes/config.php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_toko_botol');

// Site Configuration
define('SITE_URL', 'http://localhost/sistem-toko-botol/');
define('SITE_NAME', 'Sistem Toko Botol');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>