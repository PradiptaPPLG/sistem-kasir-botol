<?php
// modules/gudang/stok-masuk.php
require_once '../../includes/database.php';

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

// Get barang list
$barang_result = $db->query("SELECT * FROM barang ORDER BY nama_barang");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = intval($_POST['jumlah']);
    $keterangan = $_POST['keterangan'];
    $catatan = $_POST['catatan'] ?? '';
    
    if ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0!';
    } else {
        try {
            // 1. Insert ke transaksi_gudang
            $sql_transaksi = "
                INSERT INTO transaksi_gudang 
                (id_barang, id_cabang, id_karyawan, jenis, jumlah, keterangan, catatan)
                VALUES (?, ?, ?, 'masuk', ?, ?, ?)
            ";
            $db->insert($sql_transaksi, [$id_barang, $cabang_id, $user_id, $jumlah, $keterangan, $catatan]);
            
            // 2. Cek apakah sudah ada stok hari ini
            $check_sql = "
                SELECT id_stok_gudang FROM stok_gudang 
                WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
            ";
            $check_result = $db->query($check_sql, [$id_barang, $cabang_id]);
            
            if ($check_result->num_rows > 0) {
                // Update stok yang sudah ada
                $update_sql = "
                    UPDATE stok_gudang 
                    SET stok_sistem = stok_sistem + ?,
                        stok_fisik = stok_fisik + ?
                    WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
                ";
                $db->query($update_sql, [$jumlah, $jumlah, $id_barang, $cabang_id]);
            } else {
                // Insert stok baru
                $insert_sql = "
                    INSERT INTO stok_gudang 
                    (id_barang, id_cabang, stok_sistem, stok_fisik, tanggal_update)
                    VALUES (?, ?, ?, ?, CURDATE())
                ";
                $db->insert($insert_sql, [$id_barang, $cabang_id, $jumlah, $jumlah]);
            }
            
            // 3. Get nama barang untuk pesan sukses
            $nama_barang_sql = "SELECT nama_barang FROM barang WHERE id_barang = ?";
            $nama_result = $db->query($nama_barang_sql, [$id_barang]);
            $nama_barang = $nama_result->fetch_assoc()['nama_barang'] ?? 'Barang';
            $success = "Stok <strong>$nama_barang</strong> berhasil ditambahkan: <strong>$jumlah</strong> barang";
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = "Barang sudah ditambahkan hari ini. Silakan edit stok yang sudah ada.";
            } elseif (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $error = "Barang tidak ditemukan dalam sistem.";
            } else {
                $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
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
    <title>Tambah Stok - Sistem Kasir Botol</title>
    <style>
        /* ===== RESET & BASE (SAMA DENGAN INDEX) ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 20px;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }

        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            padding: 30px 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 36px; margin-bottom: 10px; }
        .header p { font-size: 20px; opacity: 0.9; }

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
        .nav-tab:hover { background: #e9ecef; color: #2c3e50; }
        .nav-tab.active {
            background: white;
            color: #3498db;
            border-bottom: 4px solid #3498db;
        }

        /* ===== ALERT ===== */
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

        /* ===== INFO BOX ===== */
        .info-box {
            background: #e8f4fc;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }
        .info-box h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .info-box ul {
            margin-left: 25px;
            font-size: 18px;
        }
        .info-box li { margin-bottom: 8px; }

        /* ===== FORM ===== */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-group { margin-bottom: 25px; }
        label {
            display: block;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        select, input, textarea {
            width: 100%;
            padding: 18px;
            font-size: 20px;
            border: 2px solid #ddd;
            border-radius: 12px;
            background: white;
            transition: all 0.3s;
        }
        select:focus, input:focus, textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
            outline: none;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .btn-submit {
            width: 100%;
            padding: 22px;
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(to right, #27ae60, #2ecc71);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: linear-gradient(to right, #2ecc71, #27ae60);
            transform: translateY(-2px);
        }

        /* ===== NAVIGASI BAWAH ===== */
        .nav-bottom {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        .nav-bottom a:hover { background: #e9ecef; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            body { font-size: 18px; padding: 10px; }
            .header h1 { font-size: 28px; }
            .nav-tab { padding: 15px; font-size: 18px; }
            .form-container { padding: 25px; }
            select, input, textarea { padding: 16px; font-size: 18px; }
            .btn-submit { padding: 20px; font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>➕ TAMBAH STOK GUDANG</h1>
            <p>Input barang baru ke sistem</p>
        </div>

        <!-- NAVIGASI TAB -->
        <div class="nav-tabs">
            <a href="index.php" class="nav-tab">📊 DASHBOARD</a>
            <a href="stok-masuk.php" class="nav-tab active">➕ STOK MASUK</a>
            <a href="stok-keluar.php" class="nav-tab">📤 STOK KELUAR</a>
            <a href="stock-opname.php" class="nav-tab">📋 STOCK OPNAME</a>
        </div>

        <!-- PESAN SUKSES / ERROR -->
        <?php if ($success): ?>
        <div class="alert success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- INFO CARA TAMBAH STOK -->
        <div class="info-box">
            <h3>📝 Cara Menambah Stok:</h3>
            <ul>
                <li>Pilih barang yang ingin ditambahkan</li>
                <li>Masukkan jumlah barang</li>
                <li>Pilih keterangan (pembelian/transfer/retur)</li>
                <li>Tambahkan catatan jika perlu (no invoice, supplier, dll)</li>
                <li>Klik "SIMPAN STOK MASUK"</li>
            </ul>
            <p style="margin-top: 15px; color: #666; font-size: 18px;">
                <strong>Catatan:</strong> Jika barang sudah ditambahkan hari ini, stok akan otomatis ditambah ke entri yang sama.
            </p>
        </div>

        <!-- FORM TAMBAH STOK -->
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label>PILIH BARANG</label>
                    <select name="id_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php while ($barang = $barang_result->fetch_assoc()): ?>
                        <option value="<?php echo $barang['id_barang']; ?>">
                            <?php echo htmlspecialchars($barang['nama_barang']); ?> 
                            (Rp <?php echo number_format($barang['harga_beli'], 0, ',', '.'); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>JUMLAH BARANG</label>
                    <input type="number" name="jumlah" min="1" value="1" required style="text-align: center;">
                </div>

                <div class="form-group">
                    <label>KETERANGAN</label>
                    <select name="keterangan" required>
                        <option value="pembelian">Pembelian Baru</option>
                        <option value="transfer">Transfer dari Cabang</option>
                        <option value="retur">Retur dari Pelanggan</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>CATATAN (Opsional)</label>
                    <textarea name="catatan" placeholder="Contoh: No. Invoice: INV-001, Supplier: PT ABC, dll"></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    💾 SIMPAN STOK MASUK
                </button>
            </form>
        </div>

        <!-- NAVIGASI BAWAH -->
        <div class="nav-bottom">
            <a href="index.php">📦 Kembali ke Gudang</a>
            <a href="../../dashboard.php">🏠 Dashboard</a>
            <a href="../../logout.php" style="background: #ff6b6b; color: white;">🚪 Keluar</a>
        </div>
    </div>
</body>
</html>