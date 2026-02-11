<?php
// modules/admin/tambah_stok.php
// Admin: Tambah stok barang ke gudang (pembelian/transfer/retur)

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
$user_id = $_SESSION['id_karyawan'];
$cabang_id = $_SESSION['id_cabang'];

// Ambil daftar barang (semua, tanpa filter stok)
$barang = $db->query("SELECT * FROM barang ORDER BY nama_barang");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = (int) $_POST['id_barang'];
    $jumlah = (int) $_POST['jumlah'];
    $keterangan = $_POST['keterangan'] ?? 'pembelian';
    $catatan = trim($_POST['catatan'] ?? '');

    if ($jumlah <= 0) {
        $error = '❌ Jumlah harus lebih dari 0!';
    } else {
        try {
            // 1. Insert ke transaksi_gudang (jenis = masuk)
            $sql_transaksi = "
                INSERT INTO transaksi_gudang 
                (id_barang, id_cabang, id_karyawan, jenis, jumlah, keterangan, catatan)
                VALUES (?, ?, ?, 'masuk', ?, ?, ?)
            ";
            $db->insert($sql_transaksi, [$id_barang, $cabang_id, $user_id, $jumlah, $keterangan, $catatan]);

            // 2. Cek apakah sudah ada entry stok untuk hari ini
            $check = $db->query("
                SELECT id_stok_gudang FROM stok_gudang
                WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
            ", [$id_barang, $cabang_id]);

            if ($check->num_rows > 0) {
                // Update stok yang sudah ada hari ini
                $db->query("
                    UPDATE stok_gudang 
                    SET stok_sistem = stok_sistem + ?,
                        stok_fisik = stok_fisik + ?
                    WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
                ", [$jumlah, $jumlah, $id_barang, $cabang_id]);
            } else {
                // Ambil stok terakhir
                $last = $db->query("
                    SELECT stok_sistem FROM stok_gudang
                    WHERE id_barang = ? AND id_cabang = ?
                    ORDER BY tanggal_update DESC LIMIT 1
                ", [$id_barang, $cabang_id])->fetch_assoc();
                $stok_lama = $last['stok_sistem'] ?? 0;
                $stok_baru = $stok_lama + $jumlah;

                // Insert stok baru untuk hari ini
                $db->insert("
                    INSERT INTO stok_gudang (id_barang, id_cabang, stok_sistem, stok_fisik, tanggal_update)
                    VALUES (?, ?, ?, ?, CURDATE())
                ", [$id_barang, $cabang_id, $stok_baru, $stok_baru]);
            }

            // Ambil nama barang untuk pesan sukses
            $nama = $db->query("SELECT nama_barang FROM barang WHERE id_barang = ?", [$id_barang])->fetch_assoc()['nama_barang'];
            $success = "✅ Stok <strong>$nama</strong> berhasil ditambah: <strong>$jumlah</strong> unit";

        } catch (Exception $e) {
            $error = "❌ Gagal menambah stok. " . (strpos($e->getMessage(), 'Duplicate') ? 'Data sudah ada.' : 'Coba lagi.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Stok - Admin</title>
    <style>
        /* ===== RESET & BASE (KONSISTEN DENGAN ADMIN) ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 20px;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }

        .header {
            background: linear-gradient(to right, #2c3e50, #34495e);
            color: white;
            padding: 30px 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 36px; margin-bottom: 10px; }
        .header p { font-size: 20px; opacity: 0.9; }

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

        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            font-size: 20px;
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
            border-color: #2c3e50;
            box-shadow: 0 0 0 3px rgba(44,62,80,0.2);
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
            .nav-desktop { flex-wrap: wrap; }
            .nav-desktop a { padding: 15px; font-size: 18px; }
            .card { padding: 25px; }
            select, input, textarea { padding: 16px; font-size: 18px; }
            .btn-submit { padding: 20px; font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>➕ TAMBAH STOK (ADMIN)</h1>
            <p>Input stok baru ke gudang – khusus admin</p>
        </div>

        <!-- NAVIGASI DESKTOP -->
        <div class="nav-desktop">
            <a href="../../dashboard.php">🏠 DASHBOARD</a>
            <a href="../gudang/">📦 GUDANG</a>
            <a href="../kasir/">💳 KASIR</a>
            <a href="laporan.php">📊 LAPORAN</a>
            <a href="settings.php">⚙️ SETTINGS</a>
            <a href="tambah_stok.php" class="active">➕ TAMBAH STOK</a>
            <a href="../../logout.php">🚪 KELUAR</a>
        </div>

        <!-- PESAN SUKSES/ERROR -->
        <?php if ($success): ?>
        <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- FORM TAMBAH STOK -->
        <div class="card">
            <div class="card-title">➕ FORM TAMBAH STOK</div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>📦 Pilih Barang</label>
                    <select name="id_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php while ($b = $barang->fetch_assoc()): ?>
                        <option value="<?php echo $b['id_barang']; ?>">
                            <?php echo htmlspecialchars($b['nama_barang']); ?> 
                            (<?php echo $b['kode_barang']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>🔢 Jumlah Stok Ditambah</label>
                    <input type="number" name="jumlah" min="1" value="1" required style="text-align: center;">
                </div>

                <div class="form-group">
                    <label>📌 Keterangan</label>
                    <select name="keterangan">
                        <option value="pembelian">Pembelian Baru</option>
                        <option value="transfer">Transfer dari Cabang</option>
                        <option value="retur">Retur Pelanggan</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>📝 Catatan (Opsional)</label>
                    <textarea name="catatan" placeholder="Contoh: Invoice #INV-001, Supplier PT ABC"></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    💾 TAMBAH STOK
                </button>
            </form>
        </div>

        <!-- NAVIGASI BAWAH -->
        <div class="nav-bottom">
            <a href="settings.php">⚙️ Kembali ke Pengaturan</a>
            <a href="../../dashboard.php">🏠 Dashboard</a>
        </div>
    </div>
</body>
</html>