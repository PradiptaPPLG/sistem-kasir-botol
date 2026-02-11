<?php
// modules/gudang/stok-keluar.php
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
$barang_sql = "SELECT * FROM barang ORDER BY nama_barang";
$barang_result = $db->query($barang_sql);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = intval($_POST['jumlah']);
    $keterangan = $_POST['keterangan'];
    $catatan = $_POST['catatan'] ?? '';
    
    // Cek stok tersedia (ambil stok terakhir)
    $stok_sql = "
        SELECT stok_sistem 
        FROM stok_gudang 
        WHERE id_barang = ? AND id_cabang = ?
        ORDER BY tanggal_update DESC LIMIT 1
    ";
    $stok_result = $db->query($stok_sql, [$id_barang, $cabang_id]);
    $stok_data = $stok_result->fetch_assoc();
    $stok_tersedia = $stok_data['stok_sistem'] ?? 0;
    
    if ($stok_tersedia < $jumlah) {
        $error = "Stok tidak cukup! Stok tersedia: $stok_tersedia";
    } else {
        try {
            // Insert transaksi gudang
            $sql = "
                INSERT INTO transaksi_gudang 
                (id_barang, id_cabang, id_karyawan, jenis, jumlah, keterangan, catatan)
                VALUES (?, ?, ?, 'keluar', ?, ?, ?)
            ";
            $db->insert($sql, [$id_barang, $cabang_id, $user_id, $jumlah, $keterangan, $catatan]);
            
            // Cek apakah sudah ada entri stok hari ini
            $check_sql = "
                SELECT id_stok_gudang FROM stok_gudang 
                WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
            ";
            $check_result = $db->query($check_sql, [$id_barang, $cabang_id]);
            
            if ($check_result->num_rows > 0) {
                // Update stok hari ini
                $update_sql = "
                    UPDATE stok_gudang 
                    SET stok_sistem = stok_sistem - ?,
                        stok_fisik = stok_fisik - ?
                    WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
                ";
                $db->query($update_sql, [$jumlah, $jumlah, $id_barang, $cabang_id]);
            } else {
                // Insert stok baru dengan nilai berkurang
                $stok_baru = $stok_tersedia - $jumlah;
                $insert_sql = "
                    INSERT INTO stok_gudang 
                    (id_barang, id_cabang, stok_sistem, stok_fisik, tanggal_update)
                    VALUES (?, ?, ?, ?, CURDATE())
                ";
                $db->insert($insert_sql, [$id_barang, $cabang_id, $stok_baru, $stok_baru]);
            }
            
            // Ambil nama barang untuk pesan sukses
            $nama_sql = "SELECT nama_barang FROM barang WHERE id_barang = ?";
            $nama_res = $db->query($nama_sql, [$id_barang])->fetch_assoc();
            $nama_barang = $nama_res['nama_barang'] ?? 'Barang';
            
            $success = "Stok keluar berhasil: $jumlah $nama_barang";
            
        } catch (Exception $e) {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Keluar - Sistem Kasir Botol</title>
    <style>
        /* ===== RESET & BASE (SAMA DENGAN STOK-MASUK) ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 20px;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }

        .header {
            background: linear-gradient(to right, #e67e22, #f39c12);
            color: white;
            padding: 30px 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 36px; margin-bottom: 10px; }
        .header p { font-size: 20px; opacity: 0.9; }

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
            color: #e67e22;
            border-bottom: 4px solid #e67e22;
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
            border-color: #e67e22;
            box-shadow: 0 0 0 3px rgba(230,126,34,0.2);
            outline: none;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .stok-info {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            font-size: 20px;
            color: #2980b9;
            text-align: center;
            font-weight: bold;
        }
        .btn-submit {
            width: 100%;
            padding: 22px;
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(to right, #e67e22, #f39c12);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: linear-gradient(to right, #f39c12, #e67e22);
            transform: translateY(-2px);
        }

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
            <h1>📤 STOK KELUAR GUDANG</h1>
            <p>Barang rusak, hilang, transfer, atau dipakai</p>
        </div>

        <!-- NAVIGASI TAB -->
        <div class="nav-tabs">
            <a href="index.php" class="nav-tab">📊 DASHBOARD</a>
            <a href="stok-masuk.php" class="nav-tab">➕ STOK MASUK</a>
            <a href="stok-keluar.php" class="nav-tab active">📤 STOK KELUAR</a>
            <a href="stock-opname.php" class="nav-tab">📋 STOCK OPNAME</a>
        </div>

        <!-- PESAN SUKSES / ERROR -->
        <?php if ($success): ?>
        <div class="alert success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- FORM STOK KELUAR -->
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label>PILIH BARANG</label>
                    <select name="id_barang" id="select-barang" required onchange="getStok(this.value)">
                        <option value="">-- Pilih Barang --</option>
                        <?php while ($barang = $barang_result->fetch_assoc()): ?>
                        <option value="<?php echo $barang['id_barang']; ?>">
                            <?php echo htmlspecialchars($barang['nama_barang']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>STOK TERSEDIA</label>
                    <div id="stok-info" class="stok-info" style="background: #fff3cd; color: #856404;">
                        Pilih barang terlebih dahulu
                    </div>
                </div>

                <div class="form-group">
                    <label>JUMLAH KELUAR</label>
                    <input type="number" name="jumlah" id="jumlah" min="1" value="1" required style="text-align: center;">
                </div>

                <div class="form-group">
                    <label>ALASAN KELUAR</label>
                    <select name="keterangan" required>
                        <option value="transfer">Transfer ke Cabang</option>
                        <option value="rusak">Barang Rusak</option>
                        <option value="dipakai">Dipakai Toko</option>
                        <option value="hilang">Hilang</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>CATATAN (Opsional)</label>
                    <textarea name="catatan" placeholder="Misal: Alasan detail, tujuan transfer, dll"></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    💾 SIMPAN STOK KELUAR
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

    <script>
    function getStok(barangId) {
        if (!barangId) {
            document.getElementById('stok-info').textContent = 'Pilih barang terlebih dahulu';
            return;
        }
        
        fetch(`get_stok.php?id_barang=${barangId}`)
            .then(response => response.json())
            .then(data => {
                const stok = data.stok || 0;
                const stokInfo = document.getElementById('stok-info');
                stokInfo.textContent = `Stok tersedia: ${stok} unit`;
                
                const jumlahInput = document.getElementById('jumlah');
                jumlahInput.max = stok;
                
                if (stok <= 0) {
                    stokInfo.style.background = '#f8d7da';
                    stokInfo.style.color = '#721c24';
                    jumlahInput.disabled = true;
                } else {
                    stokInfo.style.background = '#e8f4fc';
                    stokInfo.style.color = '#2980b9';
                    jumlahInput.disabled = false;
                }
            })
            .catch(err => {
                document.getElementById('stok-info').textContent = 'Gagal mengambil stok';
            });
    }
    </script>
</body>
</html>