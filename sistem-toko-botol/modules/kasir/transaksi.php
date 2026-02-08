<?php
// modules/kasir/transaksi.php
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();

$type = $_GET['type'] ?? 'pembeli'; // pembeli or penjual

// Get barang list
$barang_sql = "SELECT * FROM barang ORDER BY nama_barang";
$barang_result = $db->query($barang_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = $_POST['jumlah'];
    $jenis_pembeli = $_POST['jenis_pembeli'];
    
    // Get barang info
    $barang_sql = "SELECT * FROM barang WHERE id_barang = ?";
    $barang = $db->query($barang_sql, [$id_barang])->fetch_assoc();
    
    if ($barang) {
        // Calculate price
        if ($jenis_pembeli == 'pembeli') {
            $harga_satuan = $barang['harga_beli'];
            $selisih_keuntungan = 0;
        } else {
            $harga_satuan = $_POST['harga_jual'] ?? $barang['harga_jual'];
            $selisih_keuntungan = $harga_satuan - $barang['harga_beli'];
        }
        
        $total_harga = $jumlah * $harga_satuan;
        
        // Insert transaction
        $sql = "
            INSERT INTO transaksi_kasir 
            (id_barang, id_cabang, id_karyawan, jenis_pembeli, jumlah, harga_satuan, total_harga, selisih_keuntungan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $db->insert($sql, [
            $id_barang,
            $user['id_cabang'],
            $user['id_karyawan'],
            $jenis_pembeli,
            $jumlah,
            $harga_satuan,
            $total_harga,
            $selisih_keuntungan
        ]);
        
        // Update stock in system
        $update_stok_sql = "
            UPDATE stok_gudang 
            SET stok_sistem = stok_sistem - ?,
                tanggal_update = CURDATE()
            WHERE id_barang = ? 
            AND id_cabang = ?
        ";
        
        $db->query($update_stok_sql, [$jumlah, $id_barang, $user['id_cabang']]);
        
        // Success message
        $success = "Transaksi berhasil! Total: Rp " . number_format($total_harga, 0, ',', '.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/ux-large.css">
    <style>
        .tab-container {
            display: flex;
            margin-bottom: 20px;
        }
        
        .tab-button {
            flex: 1;
            padding: 20px;
            font-size: 24px;
            border: none;
            background: #ddd;
            cursor: pointer;
            margin: 0 2px;
        }
        
        .tab-button.active {
            background: var(--primary-color);
            color: white;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .result-box {
            background: #E8F5E9;
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
            border: 3px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- NAVIGATION -->
    <div class="main-nav">
        <a href="../../dashboard.php" class="nav-button">🏠 DASHBOARD</a>
        <a href="transaksi.php?type=pembeli" class="nav-button">👥 PEMBELI</a>
        <a href="transaksi.php?type=penjual" class="nav-button">🏪 PENJUAL</a>
        <a href="../../modules/gudang/" class="nav-button">📦 GUDANG</a>
    </div>
    
    <!-- TABS -->
    <div class="tab-container">
        <button class="tab-button <?php echo $type == 'pembeli' ? 'active' : ''; ?>" 
                onclick="window.location.href='?type=pembeli'">
            <b>👥 PEMBELI</b><br>
            <small>Harga Tetap</small>
        </button>
        <button class="tab-button <?php echo $type == 'penjual' ? 'active' : ''; ?>" 
                onclick="window.location.href='?type=penjual'">
            <b>🏪 PENJUAL</b><br>
            <small>Harga Bisa Diubah</small>
        </button>
    </div>
    
    <div class="dashboard-card">
        <h1 style="color: var(--primary-color); font-size: 32px;">
            <?php echo $type == 'pembeli' ? 'TRANSAKSI PEMBELI' : 'TRANSAKSI PENJUAL'; ?>
        </h1>
        
        <?php if (isset($success)): ?>
        <div style="background: #C8E6C9; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <p style="font-size: 24px; color: #2E7D32; margin: 0;">✅ <?php echo $success; ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="jenis_pembeli" value="<?php echo $type; ?>">
            
            <div class="form-group">
                <label style="font-size: 24px; display: block; margin-bottom: 10px;">PILIH BOTOL:</label>
                <select name="id_barang" class="input-large" required onchange="updateHarga(this.value)">
                    <option value="">-- Pilih Barang --</option>
                    <?php while ($barang = $barang_result->fetch_assoc()): ?>
                    <option value="<?php echo $barang['id_barang']; ?>" 
                            data-harga-beli="<?php echo $barang['harga_beli']; ?>"
                            data-harga-jual="<?php echo $barang['harga_jual']; ?>">
                        <?php echo htmlspecialchars($barang['nama_barang']); ?> 
                        (Rp <?php echo number_format($barang['harga_beli'], 0, ',', '.'); ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label style="font-size: 24px; display: block; margin-bottom: 10px;">JUMLAH:</label>
                <input type="number" 
                       name="jumlah" 
                       class="input-large" 
                       min="1" 
                       value="1" 
                       required
                       onchange="hitungTotal()">
            </div>
            
            <?php if ($type == 'penjual'): ?>
            <div class="form-group">
                <label style="font-size: 24px; display: block; margin-bottom: 10px;">HARGA JUAL:</label>
                <input type="number" 
                       name="harga_jual" 
                       id="harga_jual" 
                       class="input-large" 
                       required
                       onchange="hitungTotal()">
            </div>
            
            <div class="result-box" id="keuntungan_box" style="display: none;">
                <h3 style="margin-top: 0; color: var(--primary-color);">💰 PERHITUNGAN KEUNTUNGAN</h3>
                <p style="font-size: 20px;">
                    <span id="harga_beli_text"></span><br>
                    <span id="harga_jual_text"></span><br>
                    <strong style="color: var(--primary-color); font-size: 22px;">
                        SELISIH KEUNTUNGAN: <span id="selisih_text"></span>
                    </strong>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="result-box" id="total_box" style="display: none;">
                <h3 style="margin-top: 0; color: var(--primary-color);">📊 TOTAL TRANSAKSI</h3>
                <p style="font-size: 24px; font-weight: bold;">
                    TOTAL HARGA: <span id="total_text"></span>
                </p>
            </div>
            
            <button type="submit" class="btn-big btn-green" style="width: 100%; font-size: 28px;">
                <b>💳 PROSES TRANSAKSI</b>
            </button>
        </form>
    </div>
    
    <script>
        let hargaBeli = 0;
        let hargaJual = 0;
        
        function updateHarga(barangId) {
            if (!barangId) return;
            
            const option = document.querySelector(`option[value="${barangId}"]`);
            if (option) {
                hargaBeli = parseInt(option.dataset.hargaBeli);
                hargaJual = parseInt(option.dataset.hargaJual);
                
                // For penjual, set default harga jual
                if (document.getElementById('harga_jual')) {
                    document.getElementById('harga_jual').value = hargaJual;
                    document.getElementById('harga_beli_text').innerHTML = 
                        `Harga Beli: Rp ${formatRupiah(hargaBeli)}`;
                    document.getElementById('keuntungan_box').style.display = 'block';
                }
                
                hitungTotal();
            }
        }
        
        function hitungTotal() {
            const jumlah = parseInt(document.querySelector('input[name="jumlah"]').value) || 1;
            
            <?php if ($type == 'pembeli'): ?>
                // For pembeli
                const total = jumlah * hargaBeli;
                document.getElementById('total_text').innerHTML = `Rp ${formatRupiah(total)}`;
                document.getElementById('total_box').style.display = 'block';
            <?php else: ?>
                // For penjual
                const inputHargaJual = document.getElementById('harga_jual');
                const currentHargaJual = parseInt(inputHargaJual.value) || hargaJual;
                
                const total = jumlah * currentHargaJual;
                const selisih = currentHargaJual - hargaBeli;
                const totalSelisih = selisih * jumlah;
                
                document.getElementById('harga_jual_text').innerHTML = 
                    `Harga Jual: Rp ${formatRupiah(currentHargaJual)}`;
                document.getElementById('selisih_text').innerHTML = 
                    `Rp ${formatRupiah(selisih)}/botol (Total: Rp ${formatRupiah(totalSelisih)})`;
                document.getElementById('total_text').innerHTML = `Rp ${formatRupiah(total)}`;
                document.getElementById('total_box').style.display = 'block';
            <?php endif; ?>
        }
        
        function formatRupiah(angka) {
            return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            const selectBarang = document.querySelector('select[name="id_barang"]');
            if (selectBarang.value) {
                updateHarga(selectBarang.value);
            }
        });
    </script>
</body>
</html>