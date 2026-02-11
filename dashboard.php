<?php
// dashboard.php - VERSI SUPER CLEAN UNTUK LANGSIA

// Start session dengan timeout
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params(1800);
    session_start();
}

// Cek login
if (!isset($_SESSION['id_karyawan'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$user = $_SESSION['nama_karyawan'];
$id_cabang = $_SESSION['id_cabang'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

$today = date('Y-m-d');

// Get data
$sales_today = $db->query("SELECT SUM(total_harga) as total FROM transaksi_kasir WHERE id_cabang = ? AND DATE(tanggal) = ?", 
                          [$id_cabang, $today])->fetch_assoc();

$total_transactions = $db->query("SELECT COUNT(*) as total FROM transaksi_kasir WHERE id_cabang = ? AND DATE(tanggal) = ?", 
                                [$id_cabang, $today])->fetch_assoc();

// Get warnings for admin
$warnings = [];
if ($is_admin) {
    $warnings_result = $db->query("
        SELECT b.nama_barang, sg.stok_sistem, sg.stok_fisik, 
               sg.stok_sistem - sg.stok_fisik as selisih,
               b.harga_beli
        FROM stok_gudang sg
        JOIN barang b ON sg.id_barang = b.id_barang
        WHERE sg.id_cabang = ? AND sg.tanggal_update = ?
          AND sg.stok_sistem > sg.stok_fisik
        LIMIT 3
    ", [$id_cabang, $today]);
    
    while ($row = $warnings_result->fetch_assoc()) {
        $warnings[] = $row;
    }
}

// Get recent transactions
$recent = $db->query("
    SELECT tk.*, b.nama_barang 
    FROM transaksi_kasir tk
    JOIN barang b ON tk.id_barang = b.id_barang
    WHERE tk.id_cabang = ?
    ORDER BY tk.tanggal DESC
    LIMIT 5
", [$id_cabang]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Kasir Botol</title>
    <style>
        /* RESET & BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 20px; /* BASE FONT BESAR */
            line-height: 1.6;
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(to right, #2c3e50, #4a6491);
            color: white;
            padding: 25px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            font-size: 40px;
        }
        
        .title {
            font-size: 28px;
            font-weight: bold;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-role {
            display: inline-block;
            padding: 8px 15px;
            background: <?php echo $is_admin ? '#f39c12' : '#27ae60'; ?>;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
        }
        
        /* NAVIGATION */
        .nav-desktop {
            display: none;
        }
        
        .nav-mobile {
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            background: white;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            padding: 10px;
            min-width: 70px;
            transition: all 0.3s;
        }
        
        .nav-item.active {
            color: #3498db;
        }
        
        .nav-icon {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .nav-label {
            font-size: 14px;
            font-weight: bold;
        }
        
        /* MAIN CONTENT */
        .main-content {
            padding: 20px;
            padding-bottom: 100px; /* Space for mobile nav */
        }
        
        /* WARNING BOX */
        .warning-box {
            background: linear-gradient(to right, #ffecd2, #fcb69f);
            border-left: 8px solid #e74c3c;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
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
            margin-bottom: 15px;
        }
        
        .warning-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #333;
        }
        
        .warning-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .warning-loss {
            background: #ffebee;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #c62828;
        }
        
        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-top: 6px solid;
        }
        
        .stat-card.sales {
            border-color: #3498db;
        }
        
        .stat-card.transactions {
            border-color: #2ecc71;
        }
        
        .stat-card.gudang {
            border-color: #e67e22;
        }
        
        .stat-card.admin {
            border-color: #9b59b6;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stat-icon {
            font-size: 40px;
        }
        
        .stat-info {
            text-align: right;
        }
        
        .stat-label {
            font-size: 18px;
            color: #666;
        }
        
        .stat-sub {
            font-size: 16px;
            color: #999;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .stat-button {
            display: block;
            width: 100%;
            padding: 18px;
            background: #f8f9fa;
            border: none;
            border-radius: 10px;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .stat-button:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        /* RECENT TRANSACTIONS */
        .section-title {
            font-size: 26px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .transactions-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .transactions-table th {
            background: #f8f9fa;
            padding: 20px;
            text-align: left;
            font-weight: bold;
            color: #333;
            font-size: 18px;
        }
        
        .transactions-table td {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .transaction-type {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .type-pembeli {
            background: #d4edda;
            color: #155724;
        }
        
        .type-penjual {
            background: #fff3cd;
            color: #856404;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 22px;
        }
        
        .no-data-icon {
            font-size: 60px;
            margin-bottom: 20px;
            display: block;
        }
        
        /* RESPONSIVE */
        @media (min-width: 768px) {
            .nav-mobile {
                display: none;
            }
            
            .nav-desktop {
                display: flex;
                background: white;
                padding: 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            
            .nav-desktop a {
                flex: 1;
                padding: 25px;
                text-align: center;
                text-decoration: none;
                color: #666;
                font-size: 20px;
                font-weight: bold;
                transition: all 0.3s;
                border-bottom: 4px solid transparent;
            }
            
            .nav-desktop a:hover,
            .nav-desktop a.active {
                color: #3498db;
                border-bottom-color: #3498db;
                background: #f8f9fa;
            }
            
            .main-content {
                padding-bottom: 40px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                font-size: 18px;
            }
            
            .header {
                padding: 20px 15px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .logo {
                font-size: 32px;
            }
            
            .user-name {
                font-size: 20px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 32px;
            }
            
            .transactions-table th,
            .transactions-table td {
                padding: 15px;
                font-size: 16px;
            }
            
            .section-title {
                font-size: 22px;
            }
        }
        
        /* TOUCH FRIENDLY */
        @media (hover: none) and (pointer: coarse) {
            .stat-button,
            .nav-desktop a,
            .nav-item {
                min-height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            input, select, button {
                font-size: 18px !important;
            }
        }
        
        /* ANIMATIONS */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-top">
            <div class="logo-title">
                <div class="logo">🏪</div>
                <div>
                    <div class="title">SISTEM KASIR BOTOL</div>
                    <div style="font-size: 16px; opacity: 0.9;">Dashboard Utama</div>
                </div>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user); ?></div>
                <div class="user-role"><?php echo $is_admin ? 'ADMIN' : 'KASIR'; ?></div>
            </div>
        </div>
    </div>
    
    <!-- DESKTOP NAVIGATION -->
    <nav class="nav-desktop">
        <a href="modules/kasir/transaksi.php?type=pembeli">💳 KASIR</a>
        <a href="modules/gudang/">📦 GUDANG</a>
        <a href="dashboard.php" class="active">🏠 DASHBOARD</a>
        <?php if ($is_admin): ?>
        <a href="modules/admin/laporan.php">📊 LAPORAN</a>
        <?php endif; ?>
        <a href="logout.php">🚪 KELUAR</a>
    </nav>
    
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- WARNINGS (Admin only) -->
        <?php if ($is_admin && !empty($warnings)): ?>
        <div class="warning-box pulse">
            <div class="warning-header">
                <div class="warning-icon">⚠️</div>
                <div>
                    <div class="warning-title">PERINGATAN KEHILANGAN STOK</div>
                    <div>Stok sistem lebih besar dari stok fisik!</div>
                </div>
            </div>
            
            <table class="warning-table">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Stok Sistem</th>
                        <th>Stok Fisik</th>
                        <th>Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_loss = 0;
                    foreach ($warnings as $warning): 
                        $loss = $warning['selisih'] * $warning['harga_beli'];
                        $total_loss += $loss;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($warning['nama_barang']); ?></td>
                        <td><?php echo $warning['stok_sistem']; ?></td>
                        <td><?php echo $warning['stok_fisik']; ?></td>
                        <td style="color: #e74c3c; font-weight: bold;">
                            <?php echo $warning['selisih']; ?> botol
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="warning-loss">
                💰 ESTIMASI KERUGIAN: <?php echo formatRupiah($total_loss); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- STATS GRID -->
        <div class="stats-grid">
            <!-- Sales Card -->
            <div class="stat-card sales">
                <div class="stat-header">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-label">Hari Ini</div>
                        <div class="stat-sub">Penjualan</div>
                    </div>
                </div>
                <div class="stat-value"><?php echo formatRupiah($sales_today['total'] ?? 0); ?></div>
                <a href="modules/kasir/transaksi.php?type=pembeli" class="stat-button">
                    ➕ Transaksi Baru
                </a>
            </div>
            
            <!-- Transactions Card -->
            <div class="stat-card transactions">
                <div class="stat-header">
                    <div class="stat-icon">📝</div>
                    <div class="stat-info">
                        <div class="stat-label">Hari Ini</div>
                        <div class="stat-sub">Transaksi</div>
                    </div>
                </div>
                <div class="stat-value"><?php echo $total_transactions['total'] ?? 0; ?></div>
                <a href="modules/kasir/transaksi.php?type=penjual" class="stat-button">
                    🏪 Ke Penjual
                </a>
            </div>
            
            <!-- Gudang Card -->
            <div class="stat-card gudang">
                <div class="stat-header">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <div class="stat-label">Gudang</div>
                        <div class="stat-sub">Kelola Stok</div>
                    </div>
                </div>
                <div class="stat-value">-</div>
                <a href="modules/gudang/stock-opname.php" class="stat-button">
                    📋 Cek Stok
                </a>
            </div>
            
            <!-- Admin/Kasir Card -->
            <div class="stat-card <?php echo $is_admin ? 'admin' : 'transactions'; ?>">
                <div class="stat-header">
                    <div class="stat-icon"><?php echo $is_admin ? '🔐' : '👤'; ?></div>
                    <div class="stat-info">
                        <div class="stat-label"><?php echo $is_admin ? 'Admin' : 'Kasir'; ?></div>
                        <div class="stat-sub">Mode</div>
                    </div>
                </div>
                <div class="stat-value"><?php echo $is_admin ? 'Full Akses' : 'Terbatas'; ?></div>
                <?php if ($is_admin): ?>
                <a href="modules/admin/laporan.php" class="stat-button">
                    📊 Lihat Laporan
                </a>
                <?php else: ?>
                <a href="modules/kasir/" class="stat-button">
                    📝 Riwayat Saya
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- RECENT TRANSACTIONS -->
        <div class="section-title">
            <span>📝 TRANSAKSI TERAKHIR</span>
            <a href="modules/kasir/" style="font-size: 18px; color: #3498db; text-decoration: none;">Lihat Semua →</a>
        </div>
        
        <?php if ($recent->num_rows > 0): ?>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Barang</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($transaction = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('H:i', strtotime($transaction['tanggal'])); ?></td>
                    <td><?php echo htmlspecialchars(substr($transaction['nama_barang'], 0, 20)); ?></td>
                    <td>
                        <span class="transaction-type <?php echo $transaction['jenis_pembeli'] == 'pembeli' ? 'type-pembeli' : 'type-penjual'; ?>">
                            <?php echo $transaction['jenis_pembeli'] == 'pembeli' ? 'Pembeli' : 'Penjual'; ?>
                        </span>
                    </td>
                    <td><?php echo $transaction['jumlah']; ?></td>
                    <td style="font-weight: bold;"><?php echo formatRupiah($transaction['total_harga']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <span class="no-data-icon">📄</span>
            <p>Belum ada transaksi hari ini</p>
            <a href="modules/kasir/transaksi.php" 
               style="display: inline-block; margin-top: 20px; padding: 15px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 10px; font-weight: bold;">
                Buat Transaksi Pertama
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- MOBILE NAVIGATION -->
    <nav class="nav-mobile">
        <a href="modules/kasir/transaksi.php?type=pembeli" class="nav-item">
            <span class="nav-icon">💳</span>
            <span class="nav-label">Kasir</span>
        </a>
        <a href="modules/gudang/" class="nav-item">
            <span class="nav-icon">📦</span>
            <span class="nav-label">Gudang</span>
        </a>
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">Home</span>
        </a>
        <?php if ($is_admin): ?>
        <a href="modules/admin/laporan.php" class="nav-item">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Laporan</span>
        </a>
        <?php endif; ?>
        <a href="logout.php" class="nav-item">
            <span class="nav-icon">🚪</span>
            <span class="nav-label">Keluar</span>
        </a>
    </nav>
    
    <script>
        // Make tables scrollable on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                const wrapper = document.createElement('div');
                wrapper.style.overflowX = 'auto';
                wrapper.style.marginBottom = '20px';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            });
            
            // Add touch feedback
            document.querySelectorAll('.stat-button, .nav-item').forEach(btn => {
                btn.addEventListener('touchstart', function() {
                    this.style.opacity = '0.7';
                });
                btn.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
            });
        });
    </script>
</body>
</html>