<?php
// modules/gudang/stock-opname.php
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

// Get all barang with current stock (stok terakhir)
$barang_sql = "
    SELECT 
        b.*,
        COALESCE((
            SELECT stok_sistem 
            FROM stok_gudang sg 
            WHERE sg.id_barang = b.id_barang 
              AND sg.id_cabang = ? 
            ORDER BY sg.tanggal_update DESC 
            LIMIT 1
        ), 0) as stok_sistem
    FROM barang b
    ORDER BY b.nama_barang
";

$barang_result = $db->query($barang_sql, [$cabang_id]);

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['item'] ?? [];
    
    foreach ($items as $id_barang => $data) {
        $stok_fisik = intval($data['stok_fisik']);
        
        // Get current system stock (latest)
        $current_sql = "
            SELECT stok_sistem 
            FROM stok_gudang 
            WHERE id_barang = ? AND id_cabang = ?
            ORDER BY tanggal_update DESC LIMIT 1
        ";
        $current = $db->query($current_sql, [$id_barang, $cabang_id])->fetch_assoc();
        $stok_sistem = $current ? $current['stok_sistem'] : 0;
        
        // Insert or update stok_gudang untuk hari ini
        $upsert_sql = "
            INSERT INTO stok_gudang (id_barang, id_cabang, stok_fisik, stok_sistem, tanggal_update)
            VALUES (?, ?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE 
                stok_fisik = VALUES(stok_fisik),
                stok_sistem = VALUES(stok_sistem)
        ";
        $db->query($upsert_sql, [$id_barang, $cabang_id, $stok_fisik, $stok_sistem]);
        
        // Insert ke stock_opname history
        $selisih = $stok_sistem - $stok_fisik;
        $history_sql = "
            INSERT INTO stock_opname (id_barang, id_cabang, stok_fisik, stok_sistem, selisih, tanggal)
            VALUES (?, ?, ?, ?, ?, CURDATE())
        ";
        $db->query($history_sql, [$id_barang, $cabang_id, $stok_fisik, $stok_sistem, $selisih]);
    }
    
    $success = "Stock opname berhasil disimpan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Opname - Sistem Kasir Botol</title>
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
            background: linear-gradient(to right, #9b59b6, #8e44ad);
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
            color: #9b59b6;
            border-bottom: 4px solid #9b59b6;
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

        .info-box {
            background: #e8f4fc;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #9b59b6;
        }
        .info-box h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .info-box ol {
            margin-left: 25px;
            font-size: 18px;
        }
        .info-box li { margin-bottom: 8px; }

        .item-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 6px solid #9b59b6;
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            align-items: center;
        }
        @media (max-width: 768px) {
            .item-card {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
        .item-info h3 {
            font-size: 24px;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .item-detail {
            font-size: 18px;
            color: #666;
        }
        .stok-sistem {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stok-sistem .label {
            font-size: 16px;
            color: #666;
        }
        .stok-sistem .value {
            font-size: 32px;
            font-weight: bold;
            color: #2980b9;
        }
        .stok-fisik {
            text-align: center;
        }
        .stok-fisik label {
            font-size: 18px;
            display: block;
            margin-bottom: 8px;
            color: #666;
        }
        .stok-fisik input {
            width: 120px;
            padding: 15px;
            font-size: 24px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 10px;
        }
        .status-box {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            font-size: 18px;
            text-align: center;
            grid-column: span 3;
        }
        @media (max-width: 768px) {
            .status-box { grid-column: span 1; }
        }
        .status-cocok { background: #d4edda; color: #155724; }
        .status-hilang { background: #f8d7da; color: #721c24; }
        .status-lebih { background: #fff3cd; color: #856404; }

        .btn-submit {
            width: 100%;
            padding: 22px;
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(to right, #9b59b6, #8e44ad);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background: linear-gradient(to right, #8e44ad, #9b59b6);
            transform: translateY(-2px);
        }

        .nav-bottom {
            display: flex;
            gap: 10px;
            margin-top: 30px;
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
            .item-info h3 { font-size: 22px; }
            .stok-sistem .value { font-size: 28px; }
            .stok-fisik input { width: 100px; padding: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>📋 STOCK OPNAME GUDANG</h1>
            <p>Cocokkan stok fisik dengan sistem</p>
        </div>

        <!-- NAVIGASI TAB -->
        <div class="nav-tabs">
            <a href="index.php" class="nav-tab">📊 DASHBOARD</a>
            <a href="stok-masuk.php" class="nav-tab">➕ STOK MASUK</a>
            <a href="stok-keluar.php" class="nav-tab">📤 STOK KELUAR</a>
            <a href="stock-opname.php" class="nav-tab active">📋 STOCK OPNAME</a>
        </div>

        <!-- PESAN SUKSES -->
        <?php if ($success): ?>
        <div class="alert success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <!-- INFO CARA OPNAME -->
        <div class="info-box">
            <h3>📝 Cara Stock Opname:</h3>
            <ol>
                <li>Hitung fisik botol di gudang</li>
                <li>Masukkan jumlah fisik ke kolom "STOK FISIK"</li>
                <li>Sistem otomatis bandingkan dengan stok sistem</li>
                <li>Jika ada selisih, muncul warning</li>
                <li>Klik "SIMPAN STOCK OPNAME" untuk menyimpan data</li>
            </ol>
        </div>

        <!-- FORM OPNAME -->
        <form method="POST" action="">
            <?php while ($barang = $barang_result->fetch_assoc()): 
                $stok_sistem = $barang['stok_sistem'];
            ?>
            <div class="item-card">
                <div class="item-info">
                    <h3><?php echo htmlspecialchars($barang['nama_barang']); ?></h3>
                    <div class="item-detail">
                        Kode: <?php echo $barang['kode_barang']; ?> | 
                        Harga: <?php echo formatRupiah($barang['harga_beli']); ?>
                    </div>
                </div>
                
                <div class="stok-sistem">
                    <div class="label">STOK SISTEM</div>
                    <div class="value" id="sistem_<?php echo $barang['id_barang']; ?>">
                        <?php echo $stok_sistem; ?>
                    </div>
                </div>
                
                <div class="stok-fisik">
                    <label>STOK FISIK</label>
                    <input type="number" 
                           name="item[<?php echo $barang['id_barang']; ?>][stok_fisik]"
                           id="fisik_<?php echo $barang['id_barang']; ?>"
                           value="<?php echo $stok_sistem; ?>"
                           min="0"
                           oninput="hitungSelisih(<?php echo $barang['id_barang']; ?>, 
                                                   <?php echo $stok_sistem; ?>, 
                                                   this.value)">
                </div>
                
                <!-- Status selisih -->
                <div id="status_<?php echo $barang['id_barang']; ?>" class="status-box status-cocok">
                    ✅ STOK COCOK
                </div>
            </div>
            <?php endwhile; ?>

            <button type="submit" class="btn-submit">
                💾 SIMPAN STOCK OPNAME
            </button>
        </form>

        <!-- NAVIGASI BAWAH -->
        <div class="nav-bottom">
            <a href="index.php">📦 Kembali ke Gudang</a>
            <a href="../../dashboard.php">🏠 Dashboard</a>
            <a href="../../logout.php" style="background: #ff6b6b; color: white;">🚪 Keluar</a>
        </div>
    </div>

    <script>
    function hitungSelisih(id, stokSistem, stokFisik) {
        stokFisik = parseInt(stokFisik) || 0;
        const selisih = stokSistem - stokFisik;
        const statusEl = document.getElementById('status_' + id);
        
        if (selisih > 0) {
            statusEl.className = 'status-box status-hilang';
            statusEl.innerHTML = `⚠️ SELISIH: +${selisih} (Kemungkinan barang hilang)`;
        } else if (selisih < 0) {
            statusEl.className = 'status-box status-lebih';
            statusEl.innerHTML = `📈 SELISIH: ${selisih} (Stok fisik lebih banyak)`;
        } else {
            statusEl.className = 'status-box status-cocok';
            statusEl.innerHTML = '✅ STOK COCOK';
        }
    }

    // Inisialisasi semua status saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        <?php while ($barang = $barang_result->fetch_assoc()): ?>
        hitungSelisih(<?php echo $barang['id_barang']; ?>, 
                     <?php echo $barang['stok_sistem']; ?>, 
                     document.getElementById('fisik_<?php echo $barang['id_barang']; ?>').value);
        <?php endwhile; ?>
    });
    </script>
</body>
</html>