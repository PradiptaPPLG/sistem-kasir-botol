<?php
// modules/admin/laporan.php
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

// Default date range (bulan ini)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Penjualan per hari
$sales_report = $db->query("
    SELECT 
        DATE(tanggal) as tanggal,
        COUNT(*) as jumlah_transaksi,
        SUM(total_harga) as total_penjualan
    FROM transaksi_kasir 
    WHERE id_cabang = ? 
      AND DATE(tanggal) BETWEEN ? AND ?
    GROUP BY DATE(tanggal)
    ORDER BY tanggal DESC
", [$cabang_id, $start_date, $end_date]);

// Laporan kehilangan stok
$stock_loss = $db->query("
    SELECT 
        b.nama_barang,
        SUM(so.selisih) as total_selisih,
        b.harga_beli,
        SUM(so.selisih) * b.harga_beli as total_kerugian
    FROM stock_opname so
    JOIN barang b ON so.id_barang = b.id_barang
    WHERE so.id_cabang = ? 
      AND so.tanggal BETWEEN ? AND ?
      AND so.selisih > 0
    GROUP BY so.id_barang
    ORDER BY total_kerugian DESC
", [$cabang_id, $start_date, $end_date]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Admin - Sistem Kasir Botol</title>
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
            color: #9b59b6;
            border-bottom: 4px solid #9b59b6;
        }

        /* ===== FILTER ===== */
        .filter-box {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }
        .form-group { margin-bottom: 0; }
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
        }
        .btn-filter {
            width: 100%;
            padding: 16px;
            font-size: 20px;
            font-weight: bold;
            background: #9b59b6;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-filter:hover { background: #8e44ad; }

        /* ===== SECTION CARD ===== */
        .section-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 26px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 25px;
            border-left: 6px solid #9b59b6;
            padding-left: 20px;
        }

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
            color: #2c3e50;
        }
        td {
            padding: 18px;
            border-bottom: 1px solid #eee;
        }
        .text-danger { color: #c0392b; font-weight: bold; }
        .text-success { color: #27ae60; font-weight: bold; }

        /* ===== TOTAL KERUGIAN ===== */
        .loss-box {
            background: #ffebee;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: center;
        }
        .loss-box p {
            font-size: 24px;
            font-weight: bold;
            color: #c62828;
        }

        /* ===== BUTTON EXPORT ===== */
        .export-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        .btn-export {
            padding: 16px 30px;
            font-size: 20px;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
        }
        .btn-green { background: #27ae60; }
        .btn-green:hover { background: #2ecc71; }
        .btn-yellow { background: #f39c12; }
        .btn-yellow:hover { background: #e67e22; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            body { font-size: 18px; padding: 10px; }
            .header h1 { font-size: 28px; }
            .nav-desktop { flex-wrap: wrap; }
            .nav-desktop a { padding: 15px; font-size: 18px; }
            .filter-form { grid-template-columns: 1fr; }
            .section-card { padding: 20px; }
            .section-title { font-size: 22px; }
            th, td { padding: 12px; font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>📊 LAPORAN ADMIN</h1>
            <p>Rekap Penjualan & Kehilangan Stok</p>
        </div>

        <!-- NAVIGASI DESKTOP -->
        <div class="nav-desktop">
            <a href="../../dashboard.php">🏠 DASHBOARD</a>
            <a href="../gudang/">📦 GUDANG</a>
            <a href="../kasir/">💳 KASIR</a>
            <a href="laporan.php" class="active">📊 LAPORAN</a>
            <a href="settings.php">⚙️ PENGATURAN</a>
            <a href="../../logout.php">🚪 KELUAR</a>
        </div>

        <!-- FILTER TANGGAL -->
        <div class="filter-box">
            <form method="GET" action="">
                <div class="filter-form">
                    <div class="form-group">
                        <label>📅 Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="input-large" required>
                    </div>
                    <div class="form-group">
                        <label>📅 Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="input-large" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-filter">🔍 TAMPILKAN</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- LAPORAN PENJUALAN -->
        <div class="section-card">
            <div class="section-title">💰 LAPORAN PENJUALAN</div>
            <?php if ($sales_report->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah Transaksi</th>
                            <th>Total Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $sales_report->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo tanggalIndo($row['tanggal']); ?></td>
                            <td><?php echo $row['jumlah_transaksi']; ?></td>
                            <td><?php echo formatRupiah($row['total_penjualan']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p style="font-size: 20px; color: #666; text-align: center;">Tidak ada data penjualan periode ini.</p>
            <?php endif; ?>
        </div>

        <!-- LAPORAN KEHILANGAN STOK -->
        <div class="section-card">
            <div class="section-title" style="border-left-color: #e74c3c;">⚠️ LAPORAN KEHILANGAN STOK</div>
            <?php if ($stock_loss->num_rows > 0): ?>
                <?php 
                $total_kerugian = 0;
                while ($row = $stock_loss->fetch_assoc()) $total_kerugian += $row['total_kerugian'];
                // Reset pointer
                $stock_loss->data_seek(0);
                ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Jumlah Hilang</th>
                            <th>Harga Beli</th>
                            <th>Total Kerugian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stock_loss->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                            <td class="text-danger"><?php echo $row['total_selisih']; ?> unit</td>
                            <td><?php echo formatRupiah($row['harga_beli']); ?></td>
                            <td class="text-danger"><?php echo formatRupiah($row['total_kerugian']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="loss-box">
                <p>💰 TOTAL KERUGIAN: <?php echo formatRupiah($total_kerugian); ?></p>
            </div>
            <?php else: ?>
            <div style="background: #d4edda; padding: 20px; border-radius: 12px;">
                <p style="font-size: 20px; color: #155724; margin: 0;">✅ Tidak ada kehilangan stok pada periode ini.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- TOMBOL EXPORT (dummy) -->
        <div class="export-buttons">
            <button onclick="window.print()" class="btn-export btn-green">🖨️ CETAK</button>
            <button onclick="alert('Export Excel sedang dikembangkan')" class="btn-export btn-yellow">📥 EXPORT EXCEL</button>
        </div>
    </div>
</body>
</html>