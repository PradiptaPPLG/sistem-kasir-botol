<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus session
$_SESSION = array();

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hapus dari database jika ada koneksi
if (isset($_SESSION['session_id'])) {
    require_once 'includes/database.php';
    $db = new Database();
    $db->query("DELETE FROM sessions WHERE session_id = ?", [$_SESSION['session_id']]);
}

// Hancurkan session
session_destroy();

// Redirect ke login
header('Location: login.php');
exit();
?>