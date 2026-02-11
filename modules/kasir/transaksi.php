<?php
// modules/kasir/transaksi.php
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
$user_id = $_SESSION['id_karyawan'];
$cabang_id = $_SESSION['id_cabang'];

$type = $_GET['type'] ?? 'pembeli';

// Get barang list dengan stok tersedia (hanya ambil stok terakhir per barang)
$barang_sql = "
    SELECT b.*, 
           COALESCE(sg.stok_sistem, 0) as stok_tersedia 
    FROM barang b
    LEFT JOIN (
        SELECT id_barang, id_cabang, stok_sistem,
               ROW_NUMBER() OVER (PARTITION BY id_barang, id_cabang ORDER BY tanggal_update DESC) as rn
        FROM stok_gudang
        WHERE id_cabang = ?
    ) sg ON b.id_barang = sg.id_barang AND sg.rn = 1
    WHERE COALESCE(sg.stok_sistem, 0) > 0
    ORDER BY b.nama_barang
";

$barang_result = $db->query($barang_sql, [$cabang_id]);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = intval($_POST['jumlah']);
    $jenis_pembeli = $_POST['jenis_pembeli'];
    
    // Validasi
    if ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0!';
    } else {
        // 1. Cek stok tersedia (ambil stok terakhir)
        $stok_sql = "
            SELECT stok_sistem 
            FROM stok_gudang 
            WHERE id_barang = ? 
              AND id_cabang = ?
            ORDER BY tanggal_update DESC 
            LIMIT 1
        ";
        $stok_result = $db->query($stok_sql, [$id_barang, $cabang_id]);
        $stok_data = $stok_result->fetch_assoc();
        $stok_available = $stok_data['stok_sistem'] ?? 0;
        
        if ($stok_available < $jumlah) {
            $error = "Stok tidak cukup! Tersedia: $stok_available";
        } else {
            // 2. Get harga barang
            $barang_sql = "SELECT * FROM barang WHERE id_barang = ?";
            $barang = $db->query($barang_sql, [$id_barang])->fetch_assoc();
            
            if ($barang) {
                // Hitung harga
                if ($jenis_pembeli == 'pembeli') {
                    $harga_satuan = $barang['harga_beli'];
                    $selisih_keuntungan = 0;
                } else {
                    $harga_satuan = $_POST['harga_jual'] ?? $barang['harga_jual'];
                    $selisih_keuntungan = $harga_satuan - $barang['harga_beli'];
                }
                
                $total_harga = $jumlah * $harga_satuan;
                
                try {
                    // Mulai transaction
                    $db->begin_transaction();
                    
                    // 3. Insert transaksi kasir
                    $sql_transaksi = "
                        INSERT INTO transaksi_kasir 
                        (id_barang, id_cabang, id_karyawan, jenis_pembeli, jumlah, 
                         harga_satuan, total_harga, selisih_keuntungan, tanggal_transaksi)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                    ";
                    
                    $db->insert($sql_transaksi, [
                        $id_barang, $cabang_id, $user_id, $jenis_pembeli, 
                        $jumlah, $harga_satuan, $total_harga, $selisih_keuntungan
                    ]);
                    
                    // 4. Cek apakah sudah ada entry untuk hari ini
                    $cek_hari_ini_sql = "
                        SELECT COUNT(*) as count 
                        FROM stok_gudang 
                        WHERE id_barang = ? 
                          AND id_cabang = ? 
                          AND DATE(tanggal_update) = CURDATE()
                    ";
                    $cek_result = $db->query($cek_hari_ini_sql, [$id_barang, $cabang_id]);
                    $cek_data = $cek_result->fetch_assoc();
                    
                    if ($cek_data['count'] > 0) {
                        // Update stok hari ini
                        $sql_kurangi_stok = "
                            UPDATE stok_gudang 
                            SET stok_sistem = stok_sistem - ?
                            WHERE id_barang = ? 
                              AND id_cabang = ? 
                              AND DATE(tanggal_update) = CURDATE()
                        ";
                        $db->query($sql_kurangi_stok, [$jumlah, $id_barang, $cabang_id]);
                    } else {
                        // Buat entry baru untuk hari ini dengan stok yang sudah dikurangi
                        // Ambil stok terakhir
                        $stok_terakhir_sql = "
                            SELECT stok_sistem 
                            FROM stok_gudang 
                            WHERE id_barang = ? 
                              AND id_cabang = ?
                            ORDER BY tanggal_update DESC 
                            LIMIT 1
                        ";
                        $stok_terakhir_result = $db->query($stok_terakhir_sql, [$id_barang, $cabang_id]);
                        $stok_terakhir_data = $stok_terakhir_result->fetch_assoc();
                        $stok_terakhir = $stok_terakhir_data['stok_sistem'] ?? 0;
                        
                        $stok_baru = $stok_terakhir - $jumlah;
                        
                        // Insert record baru untuk hari ini
                        $sql_insert_stok = "
                            INSERT INTO stok_gudang 
                            (id_barang, id_cabang, stok_sistem, stok_fisik, tanggal_update)
                            VALUES (?, ?, ?, ?, CURDATE())
                        ";
                        $db->insert($sql_insert_stok, [
                            $id_barang, $cabang_id, $stok_baru, $stok_baru
                        ]);
                    }
                    
                    // Commit transaction
                    $db->commit();
                    
                    $success = "Transaksi berhasil! Total: " . formatRupiah($total_harga);
                    
                } catch (Exception $e) {
                    $db->rollback();
                    // Tampilkan error yang lebih user-friendly
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = "Data sudah ada untuk hari ini. Silakan refresh halaman.";
                    } else {
                        $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Sistem Kasir Botol</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 20px;
        }
        
        .header {
            background: linear-gradient(to right, #2c3e50, #4a6491);
            color: white;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 30px;
            margin-bottom: 10px;
        }
        
        .tabs {
            display: flex;
            background: white;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            flex: 1;
            padding: 25px;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            border: none;
            background: #f8f9fa;
            cursor: pointer;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: white;
            color: #2c3e50;
            border-bottom: 4px solid #3498db;
        }
        
        .content {
            max-width: 600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .alert {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-size: 18px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 6px solid #28a745;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 6px solid #dc3545;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        select, input {
            width: 100%;
            padding: 18px;
            font-size: 20px;
            border: 2px solid #ddd;
            border-radius: 12px;
            background: white;
            transition: all 0.3s;
        }
        
        select:focus, input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .stok-info {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            font-size: 18px;
            color: #2980b9;
            text-align: center;
            font-weight: bold;
        }
        
        .harga-info {
            background: #fff3cd;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border-left: 4px solid #f39c12;
        }
        
        .total-info {
            background: #d4edda;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #155724;
            border: 3px solid #28a745;
        }
        
        .btn-submit {
            width: 100%;
            padding: 22px;
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background: linear-gradient(to right, #2980b9, #1c5a7a);
            transform: translateY(-2px);
        }
        
        .nav {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .nav a {
            flex: 1;
            padding: 18px;
            text-align: center;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .nav a:hover {
            background: #e9ecef;
        }
        
        @media (max-width: 768px) {
            body {
                font-size: 18px;
            }
            
            .header {
                padding: 20px 15px;
            }
            
            .header h1 {
                font-size: 26px;
            }
            
            .tab {
                padding: 20px;
                font-size: 20px;
            }
            
            .content {
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .form-container {
                padding: 25px;
            }
            
            select, input {
                padding: 16px;
                font-size: 18px;
            }
            
            .btn-submit {
                padding: 20px;
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo $type == 'pembeli' ? 'TRANSAKSI PEMBELI' : 'TRANSAKSI PENJUAL'; ?></h1>
        <p>Sistem Kasir Botol</p>
    </div>
    
    <div class="tabs">
        <button class="tab <?php echo $type == 'pembeli' ? 'active' : ''; ?>" 
                onclick="window.location.href='?type=pembeli'">
            PEMBELI
        </button>
        <button class="tab <?php echo $type == 'penjual' ? 'active' : ''; ?>" 
                onclick="window.location.href='?type=penjual'">
            PENJUAL
        </button>
    </div>
    
    <div class="content">
        <?php if ($success): ?>
        <div class="alert success">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="" id="transaksi-form">
                <input type="hidden" name="jenis_pembeli" value="<?php echo $type; ?>">
                
                <div class="form-group">
                    <label>PILIH BARANG</label>
                    <select name="id_barang" id="select-barang" required onchange="updateInfo()">
                        <option value="">-- Pilih Barang yang Tersedia --</option>
                        <?php while ($barang = $barang_result->fetch_assoc()): 
                            $stok = $barang['stok_tersedia'] ?? 0;
                            if ($stok > 0): ?>
                        <option value="<?php echo $barang['id_barang']; ?>"
                                data-harga-beli="<?php echo $barang['harga_beli']; ?>"
                                data-harga-jual="<?php echo $barang['harga_jual']; ?>"
                                data-stok="<?php echo $stok; ?>"
                                data-nama="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                            <?php echo htmlspecialchars($barang['nama_barang']); ?> 
                            (Stok: <?php echo $stok; ?>) - 
                            Rp <?php echo number_format($barang['harga_beli'], 0, ',', '.'); ?>
                        </option>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </select>
                    <div id="stok-display" class="stok-info" style="display: none;">
                        Stok tersedia: <span id="stok-value">0</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>JUMLAH</label>
                    <input type="number" 
                           name="jumlah" 
                           id="input-jumlah"
                           min="1" 
                           value="1" 
                           required
                           oninput="hitungTotal()"
                           style="text-align: center;">
                </div>
                
                <?php if ($type == 'penjual'): ?>
                <div class="form-group">
                    <label>HARGA JUAL</label>
                    <input type="number" 
                           name="harga_jual" 
                           id="input-harga-jual"
                           min="0" 
                           required
                           oninput="hitungTotal()"
                           style="text-align: center;">
                </div>
                
                <div id="keuntungan-info" class="harga-info" style="display: none;">
                    <p>PERHITUNGAN:</p>
                    <p>Harga Beli: <span id="harga-beli-text">Rp 0</span></p>
                    <p>Harga Jual: <span id="harga-jual-text">Rp 0</span></p>
                    <p>Keuntungan per botol: <span id="keuntungan-per-text">Rp 0</span></p>
                </div>
                <?php endif; ?>
                
                <div id="total-info" class="total-info" style="display: none;">
                    <p>TOTAL TRANSAKSI:</p>
                    <p id="total-text">Rp 0</p>
                </div>
                
                <button type="submit" class="btn-submit">
                    PROSES TRANSAKSI
                </button>
            </form>
            
            <div class="nav">
                <a href="../../dashboard.php">Dashboard</a>
                <a href="index.php">Menu Kasir</a>
                <?php if ($barang_result->num_rows == 0): ?>
                <a href="../gudang/stok-masuk.php" style="background: #ffc107; color: #333;">
                    Tambah Stok Dulu
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        let hargaBeli = 0;
        let hargaJual = 0;
        let stokTersedia = 0;
        
        function updateInfo() {
            const select = document.getElementById('select-barang');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                hargaBeli = parseFloat(option.dataset.hargaBeli) || 0;
                hargaJual = parseFloat(option.dataset.hargaJual) || 0;
                stokTersedia = parseInt(option.dataset.stok) || 0;
                
                // Tampilkan stok
                document.getElementById('stok-display').style.display = 'block';
                document.getElementById('stok-value').textContent = stokTersedia;
                
                // Set max jumlah
                const inputJumlah = document.getElementById('input-jumlah');
                inputJumlah.max = stokTersedia;
                
                <?php if ($type == 'penjual'): ?>
                // Set default harga jual
                document.getElementById('input-harga-jual').value = hargaJual;
                
                // Tampilkan info keuntungan
                document.getElementById('keuntungan-info').style.display = 'block';
                document.getElementById('harga-beli-text').textContent = formatRupiah(hargaBeli);
                document.getElementById('harga-jual-text').textContent = formatRupiah(hargaJual);
                
                // Hitung keuntungan
                const keuntungan = hargaJual - hargaBeli;
                document.getElementById('keuntungan-per-text').textContent = formatRupiah(keuntungan);
                <?php endif; ?>
                
                hitungTotal();
            } else {
                document.getElementById('stok-display').style.display = 'none';
                <?php if ($type == 'penjual'): ?>
                document.getElementById('keuntungan-info').style.display = 'none';
                <?php endif; ?>
                document.getElementById('total-info').style.display = 'none';
            }
        }
        
        function hitungTotal() {
            const jumlah = parseInt(document.getElementById('input-jumlah').value) || 0;
            
            // Validasi jumlah tidak melebihi stok
            if (jumlah > stokTersedia) {
                document.getElementById('input-jumlah').value = stokTersedia;
                alert(`Jumlah melebihi stok tersedia! Maksimal: ${stokTersedia}`);
                return;
            }
            
            <?php if ($type == 'pembeli'): ?>
            // Pembeli: pakai harga beli
            const total = jumlah * hargaBeli;
            document.getElementById('total-text').textContent = formatRupiah(total);
            document.getElementById('total-info').style.display = 'block';
            <?php else: ?>
            // Penjual: pakai harga jual input
            const hargaJualInput = parseFloat(document.getElementById('input-harga-jual').value) || 0;
            const total = jumlah * hargaJualInput;
            const keuntungan = (hargaJualInput - hargaBeli) * jumlah;
            
            document.getElementById('harga-jual-text').textContent = formatRupiah(hargaJualInput);
            document.getElementById('keuntungan-per-text').textContent = formatRupiah(hargaJualInput - hargaBeli);
            document.getElementById('total-text').textContent = formatRupiah(total) + 
                ` (Keuntungan: ${formatRupiah(keuntungan)})`;
            document.getElementById('total-info').style.display = 'block';
            <?php endif; ?>
        }
        
        function formatRupiah(angka) {
            if (!angka || isNaN(angka)) return 'Rp 0';
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Inisialisasi
        document.addEventListener('DOMContentLoaded', function() {
            const selectBarang = document.getElementById('select-barang');
            if (selectBarang.value) {
                updateInfo();
            }
            
            // Auto-focus
            selectBarang.focus();
        });
    </script>
</body>
</html>