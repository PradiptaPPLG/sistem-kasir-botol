<?php
// logout.php
session_start();

if (isset($_SESSION['session_id'])) {
    require_once 'includes/database.php';
    $db = new Database();
    $db->query("DELETE FROM sessions WHERE session_id = ?", [$_SESSION['session_id']]);
}

session_destroy();
header('Location: login.php');
exit();
?>