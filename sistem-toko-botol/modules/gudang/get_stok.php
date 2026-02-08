<?php
// modules/gudang/get_stok.php
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();

$id_barang = $_GET['id_barang'] ?? 0;

$sql = "SELECT COALESCE(stok_sistem, 0) as stok FROM stok_gudang WHERE id_barang = ? AND id_cabang = ?";
$result = $db->query($sql, [$id_barang, $user['id_cabang']]);

$stok = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode([
    'stok' => $stok ? $stok['stok'] : 0
]);
?>