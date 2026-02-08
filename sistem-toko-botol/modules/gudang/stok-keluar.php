<?php
// modules/gudang/stok-keluar.php
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();

// Get barang list
$barang_sql = "SELECT * FROM barang ORDER BY nama_barang";
$barang_result = $db->query($barang_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $catatan = $_POST['catatan'] ?? '';
    
    // Cek stok tersedia
    $stok_sql = "SELECT stok_sistem FROM stok_gudang WHERE id_barang = ? AND id_cabang = ?";
    $stok = $db->query($stok_sql, [$id_barang, $user['id_cabang']])->fetch_assoc();
    
    if ($stok && $stok['stok_sistem'] >= $jumlah) {
        // Insert transaksi gudang
        $sql = "
            INSERT INTO transaksi_gudang 
            (id_barang, id_cabang, id_karyawan, jenis, jumlah, keterangan, catatan)
            VALUES (?, ?, ?, 'keluar', ?, ?, ?)
        ";
        
        $db->insert($sql, [
            $id_barang,
            $user['id_cabang'],
            $user['id_karyawan'],
            $jumlah,
            $keterangan,
            $catatan
        ]);
        
        // Update stok gudang
        $update_sql = "
            UPDATE stok_gudang 
            SET stok_sistem = stok_sistem - ?,
                stok_fisik = stok_fisik - ?,
                tanggal_update = CURDATE()
            WHERE id_barang = ? AND id_cabang = ?
        ";
        
        $db->query($update_sql, [$jumlah, $jumlah, $id_barang, $user['id_cabang']]);
        
        $success = "✅ Stok keluar berhasil: $jumlah barang";
    } else {
        $error = "❌ Stok tidak cukup! Stok tersedia: " . ($stok['stok_sistem'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Keluar - Sistem Toko</title>
    <link rel="stylesheet" href="../../assets/css/ux-large.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
    <!-- Navigation -->
    <div class="main-nav hide-mobile">
        <a href="../../dashboard.php" class="nav-button">🏠 DASHBOARD</a>
        <a href="index.php" class="nav-button">📦 GUDANG</a>
        <a href="stok-masuk.php" class="nav-button">➕ STOK MASUK</a>
        <a href="stok-keluar.php" class="nav-button">📤 STOK KELUAR</a>
        <a href="../../logout.php" class="nav-button" style="background: var(--danger-color);">🚪 KELUAR</a>
    </div>

    <div class="dashboard-card">
        <h1 style="color: var(--warning-color);">📤 STOK KELUAR GUDANG</h1>
        
        <?php if (isset($success)): ?>
        <div style="background: #C8E6C9; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p style="font-size: 18px; color: #2E7D32; margin: 0;"><?php echo $success; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div style="background: #FFEBEE; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p style="font-size: 18px; color: #D32F2F; margin: 0;"><?php echo $error; ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div style="margin-bottom: 20px;">
                <label style="font-size: 18px; display: block; margin-bottom: 8px;">
                    <b>PILIH BARANG:</b>
                </label>
                <select name="id_barang" class="input-large" required onchange="getStok(this.value)">
                    <option value="">-- Pilih Barang --</option>
                    <?php while ($barang = $barang_result->fetch_assoc()): ?>
                    <option value="<?php echo $barang['id_barang']; ?>">
                        <?php echo htmlspecialchars($barang['nama_barang']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 18px; display: block; margin-bottom: 8px;">
                    <b>STOK TERSEDIA: <span id="stok-info" style="color: var(--primary-color);">-</span></b>
                </label>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 18px; display: block; margin-bottom: 8px;">
                    <b>JUMLAH KELUAR:</b>
                </label>
                <input type="number" 
                       name="jumlah" 
                       id="jumlah"
                       class="input-large" 
                       min="1" 
                       value="1" 
                       required>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 18px; display: block; margin-bottom: 8px;">
                    <b>ALASAN KELUAR:</b>
                </label>
                <select name="keterangan" class="input-large" required>
                    <option value="transfer">Transfer ke Cabang</option>
                    <option value="rusak">Barang Rusak</option>
                    <option value="dipakai">Dipakai Toko</option>
                    <option value="hilang">Hilang</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 18px; display: block; margin-bottom: 8px;">
                    <b>CATATAN (Opsional):</b>
                </label>
                <textarea name="catatan" 
                          class="input-large" 
                          style="height: 100px; font-size: 16px;"
                          placeholder="Misal: Alasan detail, tujuan, dll"></textarea>
            </div>
            
            <button type="submit" class="btn-big btn-yellow" style="width: 100%;">
                <b>💾 SIMPAN STOK KELUAR</b>
            </button>
        </form>
    </div>
    
    <script>
    function getStok(barangId) {
        if (!barangId) return;
        
        fetch(`get_stok.php?id_barang=${barangId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('stok-info').textContent = data.stok + ' unit';
                
                // Set max value for jumlah input
                const jumlahInput = document.getElementById('jumlah');
                jumlahInput.max = data.stok;
                
                if (data.stok <= 0) {
                    jumlahInput.disabled = true;
                    document.getElementById('stok-info').style.color = 'var(--danger-color)';
                } else {
                    jumlahInput.disabled = false;
                    document.getElementById('stok-info').style.color = 'var(--primary-color)';
                }
            });
    }
    </script>
    
    <!-- Mobile Bottom Spacing -->
    <div class="show-mobile" style="height: 60px;"></div>
</body>
</html>