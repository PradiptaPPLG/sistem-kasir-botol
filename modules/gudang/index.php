<?php
// modules/gudang/index.php
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['id_karyawan'])) {
    header('Location: ../../login.php');
    exit();
}

$db = new Database();
$cabang_id = $_SESSION['id_cabang'];

// Get total stok
$total_stok = $db->query("SELECT SUM(stok_sistem) as total FROM stok_gudang WHERE id_cabang = ?", 
                         [$cabang_id])->fetch_assoc();

// Get low stock items (stok < 10)
$low_stock = $db->query("
    SELECT b.nama_barang, sg.stok_sistem, b.stok_minimal
    FROM stok_gudang sg
    JOIN barang b ON sg.id_barang = b.id_barang
    WHERE sg.id_cabang = ? AND sg.stok_sistem <= b.stok_minimal
    ORDER BY sg.stok_sistem ASC
    LIMIT 5
", [$cabang_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gudang - Sistem Kasir Botol</title>
    <style>
        /* ===== RESET & BASE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 20px;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(to right, #27ae60, #2ecc71);
            color: white;
            padding: 30px 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 20px;
            opacity: 0.9;
        }

        /* ===== NAVIGASI TAB ===== */
        .nav-tabs {
            display: flex;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .nav-tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-decoration: none;
            color: #666;
            background: #f8f9fa;
            transition: all 0.3s;
            border-bottom: 4px solid transparent;
        }
        .nav-tab:hover {
            background: #e9ecef;
            color: #2c3e50;
        }
        .nav-tab.active {
            background: white;
            color: #27ae60;
            border-bottom: 4px solid #27ae60;
        }

        /* ===== STATS CARD ===== */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-top: 6px solid #3498db;
        }
        .stat-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .stat-label {
            font-size: 18px;
            color: #666;
        }

        /* ===== WARNING BOX ===== */
        .warning-box {
            background: linear-gradient(to right, #ffecd2, #fcb69f);
            border-left: 8px solid #e74c3c;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 12px;
        }
        .warning-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .warning-icon {
            font-size: 32px;
            margin-right: 15px;
        }
        .warning-title {
            font-size: 24px;
            font-weight: bold;
            color: #c0392b;
        }
        .warning-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .warning-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        .warning-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        /* ===== ACTION CARDS ===== */
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .action-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: #27ae60;
        }
        .action-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        .action-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .action-desc {
            font-size: 16px;
            color: #666;
        }

        /* ===== TOMBOL BAWAH ===== */
        .nav-bottom {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .nav-bottom a {
            flex: 1;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .nav-bottom a:hover {
            background: #e9ecef;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            body { font-size: 18px; padding: 10px; }
            .header h1 { font-size: 28px; }
            .nav-tab { padding: 15px; font-size: 18px; }
            .stat-card { padding: 20px; }
            .action-card { padding: 25px; }
            .action-icon { font-size: 50px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>📦 GUDANG</h1>
            <p>Kelola stok barang</p>
        </div>

        <!-- NAVIGASI TAB -->
        <div class="nav-tabs">
            <a href="index.php" class="nav-tab active">📊 DASHBOARD</a>
            <a href="stok-masuk.php" class="nav-tab">➕ STOK MASUK</a>
            <a href="stok-keluar.php" class="nav-tab">📤 STOK KELUAR</a>
            <a href="stock-opname.php" class="nav-tab">📋 STOCK OPNAME</a>
        </div>

        <!-- STATS -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo $total_stok['total'] ?? 0; ?></div>
                <div class="stat-label">Total Stok</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?php echo $low_stock->num_rows; ?></div>
                <div class="stat-label">Stok Rendah</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏪</div>
                <div class="stat-value"><?php echo $_SESSION['id_cabang']; ?></div>
                <div class="stat-label">Cabang</div>
            </div>
        </div>

        <!-- WARNING STOK RENDAH -->
        <?php if ($low_stock->num_rows > 0): ?>
        <div class="warning-box">
            <div class="warning-header">
                <div class="warning-icon">⚠️</div>
                <div>
                    <div class="warning-title">PERINGATAN STOK RENDAH</div>
                    <div>Beberapa barang hampir habis!</div>
                </div>
            </div>
            <table class="warning-table">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Stok Saat Ini</th>
                        <th>Stok Minimal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $low_stock->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                        <td><?php echo $item['stok_sistem']; ?></td>
                        <td><?php echo $item['stok_minimal']; ?></td>
                        <td style="color: #e74c3c; font-weight: bold;">⚠️ PERLU RESTOCK</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- ACTION CARDS (MENU CEPAT) -->
        <div class="actions">
            <a href="stok-masuk.php" class="action-card">
                <div class="action-icon">➕</div>
                <div class="action-title">TAMBAH STOK</div>
                <div class="action-desc">Input barang baru ke gudang</div>
            </a>
            <a href="stok-keluar.php" class="action-card">
                <div class="action-icon">📤</div>
                <div class="action-title">STOK KELUAR</div>
                <div class="action-desc">Barang rusak/hilang/transfer</div>
            </a>
            <a href="stock-opname.php" class="action-card">
                <div class="action-icon">📋</div>
                <div class="action-title">STOCK OPNAME</div>
                <div class="action-desc">Cek fisik stok di gudang</div>
            </a>
            <a href="../../dashboard.php" class="action-card">
                <div class="action-icon">🏠</div>
                <div class="action-title">DASHBOARD</div>
                <div class="action-desc">Kembali ke menu utama</div>
            </a>
        </div>

        <!-- NAVIGASI BAWAH -->
        <div class="nav-bottom">
            <a href="../../modules/kasir/">💳 Ke Kasir</a>
            <a href="../../logout.php" style="background: #ff6b6b; color: white;">🚪 Keluar</a>
        </div>
    </div>
</body>
</html>