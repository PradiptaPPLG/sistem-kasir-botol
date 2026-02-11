<?php
// includes/config.php

// JANGAN panggil session_start() di sini - pindahkan ke setiap file
date_default_timezone_set('Asia/Jakarta');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_toko_botol');

// Site Configuration
define('BASE_URL', 'http://localhost/sistem-kasir-botol/');
define('SITE_NAME', 'Sistem Kasir Botol');

// Session timeout (30 menit) - hanya setting, tidak dijalankan
// ini_set('session.gc_maxlifetime', 1800); // Pindahkan ke file yang butuh
// session_set_cookie_params(1800); // Pindahkan ke file yang butuh

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include functions (tanpa session_start)
require_once 'functions.php';
?>