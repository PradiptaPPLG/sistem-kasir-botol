<?php
// modules/gudang/stok-masuk.php
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
    
    // Insert transaksi gudang
    $sql = "
        INSERT INTO transaksi_gudang 
        (id_barang, id_cabang, id_karyawan, jenis, jumlah, keterangan, catatan)
        VALUES (?, ?, ?, 'masuk', ?, ?, ?)
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
        INSERT INTO stok_gudang (id_barang, id_cabang, stok_sistem, stok_fisik, tanggal_update)
        VALUES (?, ?, ?, ?, CURDATE())
        ON DUPLICATE KEY UPDATE 
            stok_sistem = stok_sistem + VALUES(stok_sistem),
            stok_fisik = stok_fisik + VALUES(stok_fisik)
    ";
    
    $db->query($update_sql, [$id_barang, $user['id_cabang'], $jumlah, $jumlah]);
    
    $success = "✅ Stok masuk berhasil ditambahkan: $jumlah barang";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Masuk - Sistem Toko</title>
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
        <h1 style="color: var(--primary-color);">➕ STOK MASUK GUDANG</h1>
        
        <?php if (isset($success)): ?>
        <div style="background: #C8E6C9; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p style="font-size: 18px; color: #2E7D32; margin: 0;"><?php echo $success; ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div style="margin-bottom: 20px;">
                <label style="font-size: 18px; display: block; margin-bottom: 8px;">
                    <b>PILIH BARANG:</b>
                </label>
                <select name="id_barang" class="input-large" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php while ($barang = $barang_result->fetch_assoc()): ?>
                    <option value="<?php echo $barang['id_barang']; ?>">
                        <?php echo htmlspecialchars($barang['nama_barang']); ?> 
                        (Kode: <?php echo $barang['kode_barang']; ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 18px; display: block; margin-bottom: 8px;">
                    <b>JUMLAH BARANG:</b>
                </label>
                <input type="number" 
                       name="jumlah" 
                       class="input-large" 
                       min="1" 
                       value="1" 
                       required>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 18px; display: block; margin-bottom: 8px;">
                    <b>KETERANGAN:</b>
                </label>
                <select name="keterangan" class="input-large" required>
                    <option value="pembelian">Pembelian Baru</option>
                    <option value="transfer">Transfer dari Cabang Lain</option>
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
                          placeholder="Misal: No. Invoice, Supplier, dll"></textarea>
            </div>
            
            <button type="submit" class="btn-big btn-green" style="width: 100%;">
                <b>💾 SIMPAN STOK MASUK</b>
            </button>
        </form>
    </div>
    
    <!-- Mobile Bottom Spacing -->
    <div class="show-mobile" style="height: 60px;"></div>
</body>
</html>