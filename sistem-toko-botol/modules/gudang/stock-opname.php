<?php
// modules/gudang/stock-opname.php
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();

// Get all barang
$barang_sql = "
    SELECT 
        b.*,
        COALESCE(sg.stok_sistem, 0) as stok_sistem
    FROM barang b
    LEFT JOIN stok_gudang sg ON b.id_barang = sg.id_barang 
        AND sg.id_cabang = ? 
        AND sg.tanggal_update = CURDATE()
    ORDER BY b.nama_barang
";

$barang_result = $db->query($barang_sql, [$user['id_cabang']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['item'] ?? [];
    
    foreach ($items as $id_barang => $data) {
        $stok_fisik = intval($data['stok_fisik']);
        
        // Get current system stock
        $current_sql = "
            SELECT stok_sistem FROM stok_gudang 
            WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
        ";
        
        $current = $db->query($current_sql, [$id_barang, $user['id_cabang']])->fetch_assoc();
        $stok_sistem = $current ? $current['stok_sistem'] : 0;
        
        // Insert or update stok_gudang
        $upsert_sql = "
            INSERT INTO stok_gudang (id_barang, id_cabang, stok_fisik, stok_sistem, tanggal_update)
            VALUES (?, ?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE 
                stok_fisik = VALUES(stok_fisik),
                stok_sistem = VALUES(stok_sistem)
        ";
        
        $db->query($upsert_sql, [$id_barang, $user['id_cabang'], $stok_fisik, $stok_sistem]);
        
        // Insert to stock_opname history
        $selisih = $stok_sistem - $stok_fisik;
        
        $history_sql = "
            INSERT INTO stock_opname (id_barang, id_cabang, stok_fisik, stok_sistem, selisih, tanggal)
            VALUES (?, ?, ?, ?, ?, CURDATE())
        ";
        
        $db->query($history_sql, [$id_barang, $user['id_cabang'], $stok_fisik, $stok_sistem, $selisih]);
    }
    
    $success = "Stock opname berhasil disimpan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Opname - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/ux-large.css">
    <style>
        .stock-item {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stock-info {
            flex: 1;
        }
        
        .stock-input {
            width: 150px;
            font-size: 24px;
            padding: 15px;
            text-align: center;
        }
        
        .stock-status {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- NAVIGATION -->
    <div class="main-nav">
        <a href="../../dashboard.php" class="nav-button">🏠 DASHBOARD</a>
        <a href="index.php" class="nav-button">📦 GUDANG</a>
        <a href="stok-masuk.php" class="nav-button">➕ STOK MASUK</a>
        <a href="stock-opname.php" class="nav-button">📋 CEK STOK</a>
    </div>
    
    <div class="dashboard-card">
        <h1 style="color: var(--primary-color); font-size: 32px;">📋 STOCK OPNAME GUDANG</h1>
        <p style="font-size: 20px; color: #666;">
            Input stok fisik yang ada di gudang sebenarnya. Sistem akan membandingkan dengan stok sistem.
        </p>
        
        <?php if (isset($success)): ?>
        <div style="background: #C8E6C9; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <p style="font-size: 24px; color: #2E7D32; margin: 0;">✅ <?php echo $success; ?></p>
        </div>
        <?php endif; ?>
        
        <div style="background: #E3F2FD; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #1565C0;">📌 CARA KERJA:</h3>
            <ol style="font-size: 18px;">
                <li>Hitung fisik botol di gudang</li>
                <li>Masukkan jumlah fisik ke kolom "STOK FISIK"</li>
                <li>Sistem otomatis bandingkan dengan stok sistem</li>
                <li>Jika ada selisih, muncul warning merah</li>
                <li>Simpan untuk update data</li>
            </ol>
        </div>
        
        <form method="POST" action="">
            <?php while ($barang = $barang_result->fetch_assoc()): 
                $stok_sistem = $barang['stok_sistem'];
            ?>
            <div class="stock-item">
                <div class="stock-info">
                    <h3 style="margin: 0 0 10px 0; font-size: 22px;">
                        <?php echo htmlspecialchars($barang['nama_barang']); ?>
                    </h3>
                    <div style="font-size: 18px; color: #666;">
                        Kode: <?php echo $barang['kode_barang']; ?> | 
                        Harga: Rp <?php echo number_format($barang['harga_beli'], 0, ',', '.'); ?>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 18px; margin-bottom: 5px;">STOK SISTEM</div>
                    <div style="font-size: 28px; font-weight: bold; color: 
                        <?php echo $stok_sistem > 0 ? 'var(--primary-color)' : '#666'; ?>">
                        <?php echo $stok_sistem; ?>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 18px; margin-bottom: 5px;">STOK FISIK</div>
                    <input type="number" 
                           name="item[<?php echo $barang['id_barang']; ?>][stok_fisik]"
                           class="stock-input"
                           value="<?php echo $stok_sistem; ?>"
                           min="0"
                           required>
                </div>
                
                <div id="status_<?php echo $barang['id_barang']; ?>">
                    <!-- Status akan diupdate oleh JavaScript -->
                </div>
            </div>
            <?php endwhile; ?>
            
            <button type="submit" class="btn-big btn-green" style="width: 100%; font-size: 28px; margin-top: 30px;">
                <b>💾 SIMPAN STOCK OPNAME</b>
            </button>
        </form>
    </div>
    
    <script>
        // Real-time calculation
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[name^="item"]');
            
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    const id = this.name.match(/\[(\d+)\]/)[1];
                    const stokFisik = parseInt(this.value) || 0;
                    
                    // Find system stock value
                    const stockItem = this.closest('.stock-item');
                    const stokSistemEl = stockItem.querySelector('div:nth-child(2) div:last-child');
                    const stokSistem = parseInt(stokSistemEl.textContent) || 0;
                    
                    // Calculate difference
                    const selisih = stokSistem - stokFisik;
                    const statusEl = document.getElementById(`status_${id}`);
                    
                    if (selisih > 0) {
                        statusEl.innerHTML = `
                            <div class="stock-status" style="background: #FFEBEE; color: #D32F2F;">
                                SELISIH: +${selisih}<br>
                                <small>Kemungkinan hilang</small>
                            </div>
                        `;
                    } else if (selisih < 0) {
                        statusEl.innerHTML = `
                            <div class="stock-status" style="background: #E8F5E9; color: #2E7D32;">
                                SELISIH: ${selisih}<br>
                                <small>Stok fisik lebih banyak</small>
                            </div>
                        `;
                    } else {
                        statusEl.innerHTML = `
                            <div class="stock-status" style="background: #FFF3E0; color: #FF9800;">
                                SESUAI<br>
                                <small>Stok cocok</small>
                            </div>
                        `;
                    }
                });
                
                // Trigger initial calculation
                input.dispatchEvent(new Event('input'));
            });
        });
    </script>
</body>
</html>