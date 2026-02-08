<?php
// dashboard.php (MODIFIED - RESPONSIVE + ROLE CHECK)
require_once 'includes/auth.php';
require_once 'includes/database.php';

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();
$is_admin = $auth->isAdmin();

// Redirect kasir to kasir dashboard if trying to access admin features
if (!$is_admin && isset($_GET['admin'])) {
    header('Location: dashboard.php');
    exit();
}

// Get today's date
$today = date('Y-m-d');
$today_indonesia = date('d/m/Y');

// Warning system only for admin
$warnings = [];
if ($is_admin) {
    $warning_sql = "
        SELECT 
            b.nama_barang,
            sg.stok_sistem,
            sg.stok_fisik,
            sg.stok_sistem - sg.stok_fisik as selisih,
            b.harga_beli,
            (sg.stok_sistem - sg.stok_fisik) * b.harga_beli as estimasi_kerugian
        FROM stok_gudang sg
        JOIN barang b ON sg.id_barang = b.id_barang
        WHERE sg.id_cabang = ? 
        AND sg.tanggal_update = ?
        AND sg.stok_sistem > sg.stok_fisik
        ORDER BY selisih DESC
        LIMIT 5
    ";
    
    $warnings = $db->query($warning_sql, [$user['id_cabang'], $today]);
}

// Get today's sales
$sales_sql = "
    SELECT 
        SUM(total_harga) as total_penjualan,
        COUNT(*) as jumlah_transaksi
    FROM transaksi_kasir 
    WHERE id_cabang = ? 
    AND DATE(tanggal) = ?
";

$sales = $db->query($sales_sql, [$user['id_cabang'], $today])->fetch_assoc();

// Get warehouse expenses
$expense_sql = "
    SELECT 
        SUM(jumlah) as total_barang_keluar
    FROM transaksi_gudang 
    WHERE id_cabang = ? 
    AND DATE(tanggal) = ?
    AND jenis = 'keluar'
";

$expenses = $db->query($expense_sql, [$user['id_cabang'], $today])->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/ux-large.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <!-- Desktop Navigation -->
    <div class="main-nav hide-mobile">
        <h1 style="color: var(--primary-color); font-size: 28px; margin-bottom: 15px;">
            SELAMAT DATANG, <span style="color: #2196F3;"><?php echo strtoupper($user['nama_karyawan']); ?></span>!
            <?php if ($is_admin): ?>
            <span style="background: #FF9800; color: white; padding: 5px 10px; border-radius: 5px; font-size: 16px;">
                ADMIN
            </span>
            <?php else: ?>
            <span style="background: #4CAF50; color: white; padding: 5px 10px; border-radius: 5px; font-size: 16px;">
                KASIR
            </span>
            <?php endif; ?>
        </h1>
        
        <div style="margin-bottom: 15px;">
            <a href="modules/kasir/" class="nav-button">💳 KASIR</a>
            <a href="modules/gudang/" class="nav-button">📦 GUDANG</a>
            
            <?php if ($is_admin): ?>
            <a href="modules/admin/laporan.php" class="nav-button">📊 LAPORAN</a>
            <a href="modules/admin/settings.php" class="nav-button">⚙️ SETTINGS</a>
            <?php endif; ?>
            
            <a href="logout.php" class="nav-button" style="background: var(--danger-color);">🚪 KELUAR</a>
        </div>
        
        <div style="font-size: 16px; color: #666; padding: 8px; background: #F5F5F5; border-radius: 5px;">
            <strong>Cabang:</strong> <?php echo $user['id_cabang']; ?> | 
            <strong>Tanggal:</strong> <?php echo $today_indonesia; ?> | 
            <strong>Jam:</strong> <?php echo date('H:i:s'); ?>
        </div>
    </div>
    
    <!-- Mobile Navigation Menu -->
    <div class="mobile-menu show-mobile">
        <a href="modules/kasir/" class="mobile-menu-button <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="mobile-menu-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="modules/kasir/transaksi.php?type=pembeli" class="mobile-menu-button">
            <span class="mobile-menu-icon">💳</span>
            <span>Kasir</span>
        </a>
        <a href="modules/gudang/" class="mobile-menu-button">
            <span class="mobile-menu-icon">📦</span>
            <span>Gudang</span>
        </a>
        <?php if ($is_admin): ?>
        <a href="modules/admin/laporan.php" class="mobile-menu-button">
            <span class="mobile-menu-icon">📊</span>
            <span>Laporan</span>
        </a>
        <?php endif; ?>
        <a href="logout.php" class="mobile-menu-button">
            <span class="mobile-menu-icon">🚪</span>
            <span>Keluar</span>
        </a>
    </div>
    
    <!-- WARNING SYSTEM (Only for Admin) -->
    <?php if ($is_admin && isset($warnings) && $warnings->num_rows > 0): ?>
    <div class="warning-box">
        <h2 style="color: #D32F2F; font-size: 24px; margin-top: 0;">
            ⚠️ PERINGATAN SISTEM!
        </h2>
        
        <p style="font-size: 18px; font-weight: bold; margin-bottom: 12px;">
            Stok sistem lebih besar daripada stok fisik gudang!
        </p>
        
        <p style="font-size: 16px; margin-bottom: 15px;">
            Kemungkinan terjadi kehilangan barang tanpa transaksi.
            Cek stok fisik di gudang Anda!
        </p>
        
        <div style="background: white; padding: 15px; border-radius: 8px;">
            <table class="table-large">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Stok Sistem</th>
                        <th>Stok Fisik</th>
                        <th>Selisih</th>
                        <th>Kerugian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_kerugian = 0;
                    while ($warning = $warnings->fetch_assoc()): 
                        $total_kerugian += $warning['estimasi_kerugian'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($warning['nama_barang']); ?></td>
                        <td><?php echo $warning['stok_sistem']; ?></td>
                        <td><?php echo $warning['stok_fisik']; ?></td>
                        <td class="status-danger"><?php echo $warning['selisih']; ?></td>
                        <td class="status-danger">Rp <?php echo number_format($warning['estimasi_kerugian'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 15px; padding: 12px; background: #FFCDD2; border-radius: 6px;">
                <p style="font-size: 18px; font-weight: bold; margin: 0; color: #D32F2F;">
                    💰 TOTAL KERUGIAN: Rp <?php echo number_format($total_kerugian, 0, ',', '.'); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- DASHBOARD CARDS (Responsive Grid) -->
    <div class="grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;">
        <!-- Today's Sales Card -->
        <div class="dashboard-card">
            <h2 style="margin-top: 0; color: var(--primary-color); font-size: 20px;">💰 PENJUALAN HARI INI</h2>
            <div style="font-size: 36px; font-weight: bold; color: var(--primary-color);" class="big-number">
                Rp <?php echo number_format($sales['total_penjualan'] ?? 0, 0, ',', '.'); ?>
            </div>
            <p style="font-size: 16px; color: #666;">
                <?php echo $sales['jumlah_transaksi'] ?? 0; ?> transaksi hari ini
            </p>
            <div style="margin-top: 10px;">
                <a href="modules/kasir/transaksi.php?type=pembeli" class="btn-big btn-green" style="padding: 10px; font-size: 16px;">
                    ➕ Tambah Penjualan
                </a>
            </div>
        </div>
        
        <!-- Warehouse Activity Card -->
        <div class="dashboard-card">
            <h2 style="margin-top: 0; color: var(--warning-color); font-size: 20px;">📦 AKTIVITAS GUDANG</h2>
            <div style="font-size: 36px; font-weight: bold; color: var(--warning-color);" class="big-number">
                <?php echo $expenses['total_barang_keluar'] ?? 0; ?>
            </div>
            <p style="font-size: 16px; color: #666;">
                Barang keluar hari ini
            </p>
            <div style="margin-top: 10px;">
                <a href="modules/gudang/stock-opname.php" class="btn-big btn-yellow" style="padding: 10px; font-size: 16px;">
                    📋 Cek Stok
                </a>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <div class="dashboard-card">
            <h2 style="margin-top: 0; color: var(--primary-color); font-size: 20px;">⚡ AKSI CEPAT</h2>
            <div style="margin-top: 15px;">
                <a href="modules/kasir/transaksi.php?type=pembeli" 
                   class="btn-big btn-green" 
                   style="display: block; margin-bottom: 8px; padding: 12px; font-size: 16px;">
                    👥 Jual ke Pembeli
                </a>
                <a href="modules/kasir/transaksi.php?type=penjual" 
                   class="btn-big btn-yellow" 
                   style="display: block; margin-bottom: 8px; padding: 12px; font-size: 16px;">
                    🏪 Jual ke Penjual
                </a>
                <a href="modules/gudang/stok-masuk.php" 
                   class="btn-big" 
                   style="display: block; padding: 12px; font-size: 16px; background: #2196F3; color: white;">
                    📥 Stok Masuk
                </a>
            </div>
        </div>
        
        <!-- Admin Only Card -->
        <?php if ($is_admin): ?>
        <div class="dashboard-card">
            <h2 style="margin-top: 0; color: #9C27B0; font-size: 20px;">🔐 FITUR ADMIN</h2>
            <div style="margin-top: 15px;">
                <a href="modules/admin/laporan.php" 
                   class="btn-big" 
                   style="display: block; margin-bottom: 8px; padding: 12px; font-size: 16px; background: #9C27B0; color: white;">
                    📊 Laporan Lengkap
                </a>
                <a href="modules/admin/settings.php" 
                   class="btn-big" 
                   style="display: block; margin-bottom: 8px; padding: 12px; font-size: 16px; background: #607D8B; color: white;">
                    ⚙️ Pengaturan
                </a>
                <a href="modules/gudang/stock-opname.php" 
                   class="btn-big" 
                   style="display: block; padding: 12px; font-size: 16px; background: #FF9800; color: black;">
                    🔍 Audit Stok
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- RECENT TRANSACTIONS -->
    <div class="dashboard-card">
        <h2 style="margin-top: 0; color: var(--primary-color); font-size: 22px;">📝 TRANSAKSI TERAKHIR</h2>
        
        <?php
        $recent_sql = "
            SELECT 
                tk.*,
                b.nama_barang,
                k.nama_karyawan
            FROM transaksi_kasir tk
            JOIN barang b ON tk.id_barang = b.id_barang
            JOIN karyawan k ON tk.id_karyawan = k.id_karyawan
            WHERE tk.id_cabang = ?
            ORDER BY tk.tanggal DESC
            LIMIT 8
        ";
        
        $recent = $db->query($recent_sql, [$user['id_cabang']]);
        ?>
        
        <div style="overflow-x: auto;">
            <table class="table-large">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Barang</th>
                        <th>Jenis</th>
                        <th>Jml</th>
                        <th>Total</th>
                        <th>Kasir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($transaction = $recent->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('H:i', strtotime($transaction['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars(substr($transaction['nama_barang'], 0, 15)) . (strlen($transaction['nama_barang']) > 15 ? '...' : ''); ?></td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 4px; font-size: 12px;
                                background: <?php echo $transaction['jenis_pembeli'] == 'pembeli' ? '#C8E6C9' : '#FFF9C4'; ?>">
                                <?php echo $transaction['jenis_pembeli'] == 'pembeli' ? 'Pembeli' : 'Penjual'; ?>
                            </span>
                        </td>
                        <td><?php echo $transaction['jumlah']; ?></td>
                        <td>Rp <?php echo number_format($transaction['total_harga'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars(substr($transaction['nama_karyawan'], 0, 10)); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 15px; text-align: center;">
            <a href="modules/kasir/transaksi.php" class="btn-big btn-green" style="padding: 10px 20px; font-size: 16px;">
                Lihat Semua Transaksi →
            </a>
        </div>
    </div>
    
    <!-- Mobile Bottom Spacing -->
    <div class="show-mobile" style="height: 60px;"></div>
</body>
</html>