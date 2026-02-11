<?php
// modules/admin/settings.php
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login & admin
if (!isset($_SESSION['id_karyawan']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../../dashboard.php');
    exit();
}

$db = new Database();
$cabang_id = $_SESSION['id_cabang'];

$success = '';
$error = '';

// ========== TAMBAH BARANG ==========
if (isset($_POST['action']) && $_POST['action'] === 'add_barang') {
    $kode_barang = trim($_POST['kode_barang']);
    $nama_barang = trim($_POST['nama_barang']);
    $satuan = $_POST['satuan'];
    $harga_beli = (float) $_POST['harga_beli'];
    $harga_jual = (float) $_POST['harga_jual'];
    $stok_minimal = (int) ($_POST['stok_minimal'] ?? 10);

    if (empty($kode_barang) || empty($nama_barang) || $harga_beli <= 0 || $harga_jual <= 0) {
        $error = 'Semua field harus diisi dengan benar!';
    } else {
        try {
            $sql = "INSERT INTO barang (kode_barang, nama_barang, satuan, harga_beli, harga_jual, stok_minimal)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $db->insert($sql, [$kode_barang, $nama_barang, $satuan, $harga_beli, $harga_jual, $stok_minimal]);
            $success = '✅ Barang berhasil ditambahkan!';
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = '❌ Kode barang sudah digunakan!';
            } else {
                $error = '❌ Gagal menambah barang. Coba lagi.';
            }
        }
    }
}

// ========== HAPUS BARANG ==========
if (isset($_GET['delete'])) {
    $id_barang = (int) $_GET['delete'];
    try {
        $db->query("DELETE FROM barang WHERE id_barang = ?", [$id_barang]);
        $success = '✅ Barang berhasil dihapus!';
    } catch (Exception $e) {
        $error = '❌ Tidak dapat menghapus barang ini.';
    }
}

// ========== AMBIL DAFTAR BARANG ==========
$barang_list = $db->query("SELECT * FROM barang ORDER BY id_barang DESC");

// ========== AMBIL DAFTAR KARYAWAN ==========
$karyawan_list = $db->query("SELECT * FROM karyawan WHERE id_cabang = ? ORDER BY nama_karyawan", [$cabang_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Admin - Sistem Kasir Botol</title>
    <style>
        /* ===== RESET & BASE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 20px;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }

        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(to right, #34495e, #2c3e50);
            color: white;
            padding: 30px 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 36px; margin-bottom: 10px; }
        .header p { font-size: 20px; opacity: 0.9; }

        /* ===== NAVIGASI ===== */
        .nav-desktop {
            display: flex;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .nav-desktop a {
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
        .nav-desktop a:hover { background: #e9ecef; color: #2c3e50; }
        .nav-desktop a.active {
            background: white;
            color: #2c3e50;
            border-bottom: 4px solid #2c3e50;
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

        /* ===== TAB ===== */
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .tab-btn {
            flex: 1;
            padding: 20px;
            font-size: 22px;
            font-weight: bold;
            border: none;
            background: #f8f9fa;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            color: #666;
        }
        .tab-btn.active {
            background: #2c3e50;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        /* ===== CARD ===== */
        .card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .card-title {
            font-size: 26px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 25px;
            border-left: 6px solid #2c3e50;
            padding-left: 20px;
        }

        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .input-large {
            width: 100%;
            padding: 16px;
            font-size: 18px;
            border: 2px solid #ddd;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .input-large:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 3px rgba(44,62,80,0.1);
            outline: none;
        }
        .btn-submit {
            width: 100%;
            padding: 18px;
            font-size: 22px;
            font-weight: bold;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover { background: #2ecc71; }

        /* ===== TABEL ===== */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 18px;
        }
        th {
            background: #f8f9fa;
            padding: 18px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 18px;
            border-bottom: 1px solid #eee;
        }
        .btn-delete {
            padding: 10px 16px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-delete:hover { background: #c0392b; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            body { font-size: 18px; padding: 10px; }
            .header h1 { font-size: 28px; }
            .nav-desktop { flex-wrap: wrap; }
            .nav-desktop a { padding: 15px; font-size: 18px; }
            .tab-btn { font-size: 18px; padding: 15px; }
            .card { padding: 20px; }
            .card-title { font-size: 22px; }
            th, td { padding: 12px; font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>⚙️ PENGATURAN ADMIN</h1>
            <p>Kelola Barang, Karyawan, dan Sistem</p>
        </div>

        <!-- NAVIGASI DESKTOP -->
        <div class="nav-desktop">
            <a href="../../dashboard.php">🏠 DASHBOARD</a>
            <a href="../gudang/">📦 GUDANG</a>
            <a href="../kasir/">💳 KASIR</a>
            <a href="laporan.php">📊 LAPORAN</a>
            <a href="settings.php" class="active">⚙️ PENGATURAN</a>
            <a href="../../logout.php">🚪 KELUAR</a>
        </div>

        <!-- PESAN SUKSES / ERROR -->
        <?php if ($success): ?>
        <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- TAB BUTTONS -->
        <div class="tab-buttons">
            <button id="tab1-btn" class="tab-btn active" onclick="openTab('barang')">📦 KELOLA BARANG</button>
            <button id="tab2-btn" class="tab-btn" onclick="openTab('karyawan')">👥 KELOLA KARYAWAN</button>
            <button id="tab3-btn" class="tab-btn" onclick="openTab('system')">🔧 SISTEM</button>
        </div>

        <!-- ========== TAB 1 : KELOLA BARANG ========== -->
        <div id="tab-barang" class="tab-content active">
            <!-- Form Tambah Barang -->
            <div class="card">
                <div class="card-title">➕ TAMBAH BARANG BARU</div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_barang">
                    
                    <div class="form-group">
                        <label>Kode Barang <span style="color: #e74c3c;">*</span></label>
                        <input type="text" name="kode_barang" class="input-large" placeholder="Contoh: BTL-010" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Barang <span style="color: #e74c3c;">*</span></label>
                        <input type="text" name="nama_barang" class="input-large" placeholder="Contoh: Aqua 600ml" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Satuan</label>
                        <select name="satuan" class="input-large">
                            <option value="botol">Botol</option>
                            <option value="dus">Dus</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Harga Beli (Rp) <span style="color: #e74c3c;">*</span></label>
                        <input type="number" name="harga_beli" class="input-large" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Harga Jual (Rp) <span style="color: #e74c3c;">*</span></label>
                        <input type="number" name="harga_jual" class="input-large" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stok Minimal</label>
                        <input type="number" name="stok_minimal" class="input-large" min="1" value="10">
                    </div>
                    
                    <button type="submit" class="btn-submit">💾 SIMPAN BARANG</button>
                </form>
                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="tambah_stok.php" style="display: inline-block; padding: 16px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 10px; font-size: 20px; font-weight: bold;">
                        ➕ TAMBAH STOK (ADMIN)
                    </a>
                </div>
            </div>

            <!-- Daftar Barang -->
            <div class="card">
                <div class="card-title">📋 DAFTAR BARANG</div>
                <?php if ($barang_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Satuan</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Stok Min</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = $barang_list->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['kode_barang']); ?></td>
                                <td><?php echo htmlspecialchars($b['nama_barang']); ?></td>
                                <td><?php echo $b['satuan']; ?></td>
                                <td><?php echo formatRupiah($b['harga_beli']); ?></td>
                                <td><?php echo formatRupiah($b['harga_jual']); ?></td>
                                <td><?php echo $b['stok_minimal']; ?></td>
                                <td>
                                    <a href="?delete=<?php echo $b['id_barang']; ?>" 
                                       onclick="return confirm('Hapus barang <?php echo htmlspecialchars($b['nama_barang']); ?>?')"
                                       class="btn-delete">🗑️ Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p style="font-size: 20px; color: #666; text-align: center;">Belum ada barang.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ========== TAB 2 : KELOLA KARYAWAN ========== -->
        <div id="tab-karyawan" class="tab-content">
            <div class="card">
                <div class="card-title">👥 DAFTAR KARYAWAN</div>
                <?php if ($karyawan_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Terakhir Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($k = $karyawan_list->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($k['nama_karyawan']); ?></td>
                                <td><?php echo $k['is_admin'] ? 'Admin' : 'Kasir'; ?></td>
                                <td><?php echo $k['last_login'] ? 'Aktif' : 'Belum login'; ?></td>
                                <td><?php echo $k['last_login'] ? date('d/m/Y H:i', strtotime($k['last_login'])) : '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>Tidak ada data.</p>
                <?php endif; ?>
                <p style="margin-top: 20px; color: #666;">* Untuk menambah/edit karyawan, hubungi developer.</p>
            </div>
        </div>

        <!-- ========== TAB 3 : SISTEM ========== -->
        <div id="tab-system" class="tab-content">
            <div class="card">
                <div class="card-title">🔧 PENGATURAN SISTEM</div>
                <div style="margin-bottom: 30px;">
                    <h3 style="font-size: 22px; margin-bottom: 15px;">💾 Backup Database</h3>
                    <p style="margin-bottom: 15px;">Buat cadangan data sistem ke file SQL.</p>
                    <button onclick="alert('Fitur backup sedang dikembangkan')" 
                            style="padding: 16px 30px; background: #27ae60; color: white; border: none; border-radius: 10px; font-size: 20px;">
                        💾 BACKUP SEKARANG
                    </button>
                </div>
                <div>
                    <h3 style="font-size: 22px; margin-bottom: 15px;">🔄 Reset Data Bulanan</h3>
                    <p style="margin-bottom: 15px; color: #e74c3c;">Hapus semua transaksi dan stock opname bulan lalu. (Tidak dapat dikembalikan!)</p>
                    <button onclick="if(confirm('Yakin akan reset data?')) alert('Fitur reset dikunci sementara');" 
                            style="padding: 16px 30px; background: #e74c3c; color: white; border: none; border-radius: 10px; font-size: 20px;">
                        ⚠️ RESET BULANAN
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openTab(tabName) {
        // Sembunyikan semua tab
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        // Tampilkan tab yang dipilih
        document.getElementById('tab-' + tabName).classList.add('active');
        // Aktifkan tombol yang sesuai
        if (tabName === 'barang') document.getElementById('tab1-btn').classList.add('active');
        if (tabName === 'karyawan') document.getElementById('tab2-btn').classList.add('active');
        if (tabName === 'system') document.getElementById('tab3-btn').classList.add('active');
    }
    </script>
</body>
</html>