<?php
// modules/gudang/index.php
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
    <title>Gudang - Sistem Toko</title>
    <link rel="stylesheet" href="../../assets/css/ux-large.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
    <!-- Navigation -->
    <div class="main-nav hide-mobile">
        <a href="../../dashboard.php" class="nav-button">🏠 DASHBOARD</a>
        <a href="stok-masuk.php" class="nav-button">➕ STOK MASUK</a>
        <a href="stok-keluar.php" class="nav-button">📤 STOK KELUAR</a>
        <a href="stock-opname.php" class="nav-button">📋 STOCK OPNAME</a>
        <a href="../../logout.php" class="nav-button" style="background: var(--danger-color);">🚪 KELUAR</a>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-menu show-mobile">
        <a href="../../dashboard.php" class="mobile-menu-button">
            <span class="mobile-menu-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="stok-masuk.php" class="mobile-menu-button">
            <span class="mobile-menu-icon">➕</span>
            <span>Stok Masuk</span>
        </a>
        <a href="stok-keluar.php" class="mobile-menu-button">
            <span class="mobile-menu-icon">📤</span>
            <span>Stok Keluar</span>
        </a>
        <a href="stock-opname.php" class="mobile-menu-button">
            <span class="mobile-menu-icon">📋</span>
            <span>Stock Opname</span>
        </a>
        <a href="../../logout.php" class="mobile-menu-button">
            <span class="mobile-menu-icon">🚪</span>
            <span>Keluar</span>
        </a>
    </div>

    <div class="dashboard-card">
        <h1 style="color: var(--primary-color);">📦 MODUL GUDANG</h1>
        <p style="font-size: 18px;">Selamat datang, <?php echo htmlspecialchars($user['nama_karyawan']); ?>!</p>
        
        <div class="grid-container" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
            <!-- Stok Masuk -->
            <a href="stok-masuk.php" style="text-decoration: none;">
                <div class="dashboard-card" style="text-align: center; cursor: pointer;">
                    <div style="font-size: 48px; margin-bottom: 15px;">➕</div>
                    <h3 style="color: var(--primary-color); margin: 0;">STOK MASUK</h3>
                    <p style="color: #666;">Input barang baru ke gudang</p>
                </div>
            </a>
            
            <!-- Stok Keluar -->
            <a href="stok-keluar.php" style="text-decoration: none;">
                <div class="dashboard-card" style="text-align: center; cursor: pointer;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📤</div>
                    <h3 style="color: var(--warning-color); margin: 0;">STOK KELUAR</h3>
                    <p style="color: #666;">Barang keluar dari gudang</p>
                </div>
            </a>
            
            <!-- Stock Opname -->
            <a href="stock-opname.php" style="text-decoration: none;">
                <div class="dashboard-card" style="text-align: center; cursor: pointer;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📋</div>
                    <h3 style="color: #2196F3; margin: 0;">STOCK OPNAME</h3>
                    <p style="color: #666;">Cek stok fisik gudang</p>
                </div>
            </a>
            
            <!-- Laporan -->
            <a href="../../modules/admin/laporan.php" style="text-decoration: none;">
                <div class="dashboard-card" style="text-align: center; cursor: pointer;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📊</div>
                    <h3 style="color: #9C27B0; margin: 0;">LAPORAN</h3>
                    <p style="color: #666;">Lihat laporan stok</p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Quick Info -->
    <div class="dashboard-card">
        <h3>📌 PANDUAN CEPAT</h3>
        <ol style="font-size: 16px;">
            <li><strong>Stok Masuk:</strong> Input barang baru yang datang</li>
            <li><strong>Stok Keluar:</strong> Catat barang yang dipindahkan/rusak</li>
            <li><strong>Stock Opname:</strong> Hitung fisik stok di gudang</li>
            <li><strong>Laporan:</strong> Lihat riwayat dan analisis</li>
        </ol>
    </div>
</body>
</html>