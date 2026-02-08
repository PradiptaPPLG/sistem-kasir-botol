<?php
// modules/admin/settings.php
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../../dashboard.php');
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_barang') {
        // Add new product
        $nama_barang = $_POST['nama_barang'];
        $kode_barang = $_POST['kode_barang'];
        $harga_beli = $_POST['harga_beli'];
        $harga_jual = $_POST['harga_jual'];
        $satuan = $_POST['satuan'];
        
        $sql = "INSERT INTO barang (nama_barang, kode_barang, harga_beli, harga_jual, satuan) 
                VALUES (?, ?, ?, ?, ?)";
        
        $db->insert($sql, [$nama_barang, $kode_barang, $harga_beli, $harga_jual, $satuan]);
        
        $success = "✅ Barang berhasil ditambahkan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Sistem Toko</title>
    <link rel="stylesheet" href="../../assets/css/ux-large.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
    <!-- Navigation -->
    <div class="main-nav hide-mobile">
        <a href="../../dashboard.php" class="nav-button">🏠 DASHBOARD</a>
        <a href="../gudang/" class="nav-button">📦 GUDANG</a>
        <a href="../kasir/" class="nav-button">💳 KASIR</a>
        <a href="laporan.php" class="nav-button">📊 LAPORAN</a>
        <a href="settings.php" class="nav-button">⚙️ SETTINGS</a>
        <a href="../../logout.php" class="nav-button" style="background: var(--danger-color);">🚪 KELUAR</a>
    </div>

    <div class="dashboard-card">
        <h1 style="color: var(--primary-color);">⚙️ PENGATURAN ADMIN</h1>
        
        <!-- Tabs -->
        <div style="margin: 20px 0;">
            <button onclick="showTab('barang')" class="btn-big btn-green" style="margin: 5px;">
                📦 KELOLA BARANG
            </button>
            <button onclick="showTab('karyawan')" class="btn-big btn-yellow" style="margin: 5px;">
                👥 KELOLA KARYAWAN
            </button>
            <button onclick="showTab('system')" class="btn-big" style="margin: 5px; background: #2196F3; color: white;">
                🔧 SYSTEM
            </button>
        </div>
        
        <!-- Add Product Form -->
        <div id="tab-barang" class="tab-content" style="display: block;">
            <h2>Tambah Barang Baru</h2>
            
            <?php if (isset($success)): ?>
            <div style="background: #C8E6C9; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <p style="color: #2E7D32; margin: 0;"><?php echo $success; ?></p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_barang">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Nama Barang:</label>
                    <input type="text" name="nama_barang" class="input-large" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Kode Barang:</label>
                    <input type="text" name="kode_barang" class="input-large" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Harga Beli (Pembeli):</label>
                    <input type="number" name="harga_beli" class="input-large" required min="0">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Harga Jual (Penjual):</label>
                    <input type="number" name="harga_jual" class="input-large" required min="0">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Satuan:</label>
                    <select name="satuan" class="input-large" required>
                        <option value="botol">Botol</option>
                        <option value="dus">Dus</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-big btn-green">
                    💾 SIMPAN BARANG
                </button>
            </form>
        </div>
        
        <!-- Manage Employees -->
        <div id="tab-karyawan" class="tab-content" style="display: none;">
            <h2>Daftar Karyawan</h2>
            
            <?php
            $karyawan_sql = "SELECT * FROM karyawan WHERE id_cabang = ? ORDER BY nama_karyawan";
            $karyawan_result = $db->query($karyawan_sql, [$user['id_cabang']]);
            ?>
            
            <table class="table-large">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($karyawan = $karyawan_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($karyawan['nama_karyawan']); ?></td>
                        <td><?php echo $karyawan['is_admin'] ? 'Admin' : 'Kasir'; ?></td>
                        <td><?php echo $karyawan['last_login'] ? 'Aktif' : 'Belum login'; ?></td>
                        <td>
                            <button class="btn-big" style="padding: 5px 10px; font-size: 14px;">
                                Edit
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- System Settings -->
        <div id="tab-system" class="tab-content" style="display: none;">
            <h2>Pengaturan Sistem</h2>
            
            <div style="margin: 20px 0;">
                <h3>Backup Database</h3>
                <p>Buat cadangan data sistem.</p>
                <button onclick="backupDatabase()" class="btn-big btn-green">
                    💾 BACKUP SEKARANG
                </button>
            </div>
            
            <div style="margin: 20px 0;">
                <h3>Reset Data Bulanan</h3>
                <p>Reset transaksi bulanan (Hati-hati! Aksi ini tidak dapat dibatalkan).</p>
                <button onclick="resetMonthly()" class="btn-big btn-red">
                    🔄 RESET BULANAN
                </button>
            </div>
        </div>
    </div>
    
    <script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        
        // Show selected tab
        document.getElementById('tab-' + tabName).style.display = 'block';
    }
    
    function backupDatabase() {
        if (confirm('Buat backup database sekarang?')) {
            window.location.href = 'backup.php';
        }
    }
    
    function resetMonthly() {
        if (confirm('PERINGATAN: Reset semua data bulanan? Data tidak dapat dikembalikan!')) {
            if (confirm('Yakin 100%?')) {
                window.location.href = 'reset_monthly.php';
            }
        }
    }
    </script>
</body>
</html>