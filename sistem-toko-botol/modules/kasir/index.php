<?php
// modules/kasir/index.php
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Sistem Toko</title>
    <link rel="stylesheet" href="../../assets/css/ux-large.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
    <!-- Navigation -->
    <div class="main-nav hide-mobile">
        <a href="../../dashboard.php" class="nav-button">🏠 DASHBOARD</a>
        <a href="transaksi.php?type=pembeli" class="nav-button">👥 PEMBELI</a>
        <a href="transaksi.php?type=penjual" class="nav-button">🏪 PENJUAL</a>
        <a href="scanner.php" class="nav-button">📷 SCANNER</a>
        <a href="../../logout.php" class="nav-button" style="background: var(--danger-color);">🚪 KELUAR</a>
    </div>

    <div class="dashboard-card">
        <h1 style="color: var(--primary-color);">💳 MODUL KASIR</h1>
        <p style="font-size: 18px;">Halo, <?php echo htmlspecialchars($user['nama_karyawan']); ?>! Pilih jenis transaksi:</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
            <!-- Pembeli -->
            <a href="transaksi.php?type=pembeli" style="text-decoration: none;">
                <div class="dashboard-card" style="text-align: center; cursor: pointer; background: #E8F5E9;">
                    <div style="font-size: 48px; margin-bottom: 15px;">👥</div>
                    <h3 style="color: var(--primary-color); margin: 0;">TRANSAKSI PEMBELI</h3>
                    <p style="color: #666;">Harga tetap, untuk pelanggan umum</p>
                    <div style="margin-top: 15px; padding: 10px; background: #C8E6C9; border-radius: 5px;">
                        <strong>Cepat & Mudah</strong>
                    </div>
                </div>
            </a>
            
            <!-- Penjual -->
            <a href="transaksi.php?type=penjual" style="text-decoration: none;">
                <div class="dashboard-card" style="text-align: center; cursor: pointer; background: #FFF9C4;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🏪</div>
                    <h3 style="color: var(--warning-color); margin: 0;">TRANSAKSI PENJUAL</h3>
                    <p style="color: #666;">Harga bisa diubah, untuk reseller</p>
                    <div style="margin-top: 15px; padding: 10px; background: #FFF59D; border-radius: 5px;">
                        <strong>Dengan Tawar Menawar</strong>
                    </div>
                </div>
            </a>
            
            <!-- Scanner -->
            <a href="scanner.php" style="text-decoration: none;">
                <div class="dashboard-card" style="text-align: center; cursor: pointer; background: #E3F2FD;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📷</div>
                    <h3 style="color: #2196F3; margin: 0;">SCANNER BARCODE</h3>
                    <p style="color: #666;">Scan barcode untuk transaksi cepat</p>
                    <div style="margin-top: 15px; padding: 10px; background: #BBDEFB; border-radius: 5px;">
                        <strong>Modern & Cepat</strong>
                    </div>
                </div>
            </a>
            
            <!-- Riwayat -->
            <a href="riwayat.php" style="text-decoration: none;">
                <div class="dashboard-card" style="text-align: center; cursor: pointer; background: #F3E5F5;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📝</div>
                    <h3 style="color: #9C27B0; margin: 0;">RIWAYAT TRANSAKSI</h3>
                    <p style="color: #666;">Lihat semua transaksi Anda</p>
                    <div style="margin-top: 15px; padding: 10px; background: #E1BEE7; border-radius: 5px;">
                        <strong>Cek History</strong>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="dashboard-card">
        <h3>📊 TRANSAKSI HARI INI</h3>
        <?php
        $db = new Database();
        $today = date('Y-m-d');
        
        $stats = $db->query("
            SELECT 
                COUNT(*) as total_transaksi,
                SUM(total_harga) as total_penjualan,
                SUM(CASE WHEN jenis_pembeli = 'pembeli' THEN 1 ELSE 0 END) as pembeli,
                SUM(CASE WHEN jenis_pembeli = 'penjual' THEN 1 ELSE 0 END) as penjual
            FROM transaksi_kasir 
            WHERE id_cabang = ? 
            AND DATE(tanggal) = ?
            AND id_karyawan = ?
        ", [$user['id_cabang'], $today, $user['id_karyawan']])->fetch_assoc();
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;">
            <div style="text-align: center; padding: 15px; background: #F5F5F5; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: var(--primary-color);">
                    <?php echo $stats['total_transaksi'] ?? 0; ?>
                </div>
                <div style="font-size: 14px; color: #666;">Total Transaksi</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #F5F5F5; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: var(--primary-color);">
                    Rp <?php echo number_format($stats['total_penjualan'] ?? 0, 0, ',', '.'); ?>
                </div>
                <div style="font-size: 14px; color: #666;">Total Penjualan</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #F5F5F5; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: var(--primary-color);">
                    <?php echo $stats['pembeli'] ?? 0; ?>
                </div>
                <div style="font-size: 14px; color: #666;">Pembeli</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #F5F5F5; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: var(--warning-color);">
                    <?php echo $stats['penjual'] ?? 0; ?>
                </div>
                <div style="font-size: 14px; color: #666;">Penjual</div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Bottom Spacing -->
    <div class="show-mobile" style="height: 60px;"></div>
</body>
</html>