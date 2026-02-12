<?php
// modules/gudang/index.php
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id_karyawan'])) { header('Location: ../../login.php'); exit(); }

$db = new Database();
$cabang_id = $_SESSION['id_cabang'];

$total_stok = $db->query("SELECT SUM(stok_sistem) as total FROM stok_gudang WHERE id_cabang = ?", [$cabang_id])->fetch_assoc();
$low_stock = $db->query("
    SELECT b.nama_barang, sg.stok_sistem, b.stok_minimal
    FROM stok_gudang sg
    JOIN barang b ON sg.id_barang = b.id_barang
    WHERE sg.id_cabang = ? AND sg.stok_sistem <= b.stok_minimal
    ORDER BY sg.stok_sistem ASC LIMIT 5
", [$cabang_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gudang · Kasir Botol</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        body { background:#f8fafc; color:#0f172a; padding:20px; }
        .container { max-width:1200px; margin:0 auto; }
        .page-header {
            background: white; border-radius: 20px; padding: 24px; margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 16px;
        }
        .page-header h1 { font-size: 26px; font-weight: 700; color:#0f172a; margin:0; }
        .nav-tabs {
            display: flex; gap: 6px; background: white; padding: 8px; border-radius: 16px;
            margin-bottom: 28px; flex-wrap: wrap;
        }
        .nav-tabs a {
            padding: 12px 24px; font-size: 16px; font-weight: 600; border-radius: 12px;
            text-decoration: none; color: #475569; display: flex; align-items: center; gap: 8px;
        }
        .nav-tabs a:hover { background:#f1f5f9; }
        .nav-tabs a.active { background:#2563eb; color:white; }
        .stats-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:20px; margin-bottom:32px; }
        .stat-card {
            background: white; padding: 24px; border-radius: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            display: flex; flex-direction: column;
        }
        .stat-icon { font-size: 38px; margin-bottom: 12px; }
        .stat-value { font-size: 32px; font-weight: 700; color:#0f172a; }
        .stat-label { color:#64748b; font-size:15px; }
        .warning-card {
            background: #fffbeb; border-left: 6px solid #f59e0b; border-radius: 18px; padding: 24px; margin-bottom: 32px;
        }
        .warning-title { display: flex; align-items: center; gap: 12px; font-size:20px; font-weight:700; color:#b45309; margin-bottom:18px; }
        .table-responsive { overflow-x:auto; background:white; border-radius:18px; box-shadow:0 4px 12px rgba(0,0,0,0.02); }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8fafc; padding:16px; text-align:left; font-weight:600; color:#334155; }
        td { padding:16px; border-bottom:1px solid #e2e8f0; }
        .btn-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:16px; margin-top:28px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            padding: 16px 24px; border-radius: 14px; font-weight: 600; text-decoration: none;
            background: white; color: #0f172a; border: 1.5px solid #e2e8f0; transition: 0.2s;
        }
        .btn-primary { background: #2563eb; color: white; border: none; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-outline { border:1.5px solid #2563eb; color:#2563eb; background:white; }
        @media (max-width:640px) {
            body { padding:16px; }
            .nav-tabs a { padding:10px 18px; font-size:15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <span style="font-size:42px;">📦</span>
            <h1>Manajemen Gudang</h1>
        </div>

        <div class="nav-tabs">
            <a href="index.php" class="active">📊 Dashboard</a>
            <a href="stok-masuk.php">➕ Stok Masuk</a>
            <a href="stok-keluar.php">📤 Stok Keluar</a>
            <a href="stock-opname.php">📋 Stock Opname</a>
            <a href="../../dashboard.php" style="margin-left:auto;">🏠 Dashboard Utama</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">📦</span>
                <span class="stat-value"><?= $total_stok['total'] ?? 0 ?></span>
                <span class="stat-label">Total Stok Sistem</span>
            </div>
            <div class="stat-card">
                <span class="stat-icon">⚠️</span>
                <span class="stat-value"><?= $low_stock->num_rows ?></span>
                <span class="stat-label">Stok Menipis</span>
            </div>
            <div class="stat-card">
                <span class="stat-icon">🏪</span>
                <span class="stat-value"><?= htmlspecialchars($_SESSION['id_cabang']) ?></span>
                <span class="stat-label">Cabang</span>
            </div>
        </div>

        <?php if ($low_stock->num_rows > 0): ?>
        <div class="warning-card">
            <div class="warning-title">
                <span>⚠️</span> Peringatan Stok Rendah
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Barang</th><th>Stok</th><th>Minimal</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php while ($item = $low_stock->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                            <td><?= $item['stok_sistem'] ?></td>
                            <td><?= $item['stok_minimal'] ?></td>
                            <td style="color:#dc2626; font-weight:600;">🔄 Restock</td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="btn-grid">
            <a href="stok-masuk.php" class="btn btn-primary">
                <span>➕</span> Tambah Stok
            </a>
            <a href="stok-keluar.php" class="btn btn-outline">
                <span>📤</span> Stok Keluar
            </a>
            <a href="stock-opname.php" class="btn btn-outline">
                <span>📋</span> Stock Opname
            </a>
            <a href="../../dashboard.php" class="btn">
                <span>🏠</span> Dashboard
            </a>
        </div>
    </div>
</body>
</html>