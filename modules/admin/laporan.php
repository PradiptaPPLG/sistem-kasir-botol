<?php
// modules/admin/laporan.php
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id_karyawan']) || !$_SESSION['is_admin']) { header('Location: ../../dashboard.php'); exit(); }

$db = new Database();
$cabang_id = $_SESSION['id_cabang'];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$sales_report = $db->query("SELECT DATE(tanggal) as tanggal, COUNT(*) as jumlah_transaksi, SUM(total_harga) as total_penjualan FROM transaksi_kasir WHERE id_cabang = ? AND DATE(tanggal) BETWEEN ? AND ? GROUP BY DATE(tanggal) ORDER BY tanggal DESC", [$cabang_id, $start_date, $end_date]);
$stock_loss = $db->query("SELECT b.nama_barang, SUM(so.selisih) as total_selisih, b.harga_beli, SUM(so.selisih)*b.harga_beli as total_kerugian FROM stock_opname so JOIN barang b ON so.id_barang = b.id_barang WHERE so.id_cabang = ? AND so.tanggal BETWEEN ? AND ? AND so.selisih > 0 GROUP BY so.id_barang ORDER BY total_kerugian DESC", [$cabang_id, $start_date, $end_date]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Admin</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        body { background:#f8fafc; color:#0f172a; padding:20px; }
        .container { max-width:1200px; margin:0 auto; }
        .page-header { background:white; border-radius:20px; padding:24px; margin-bottom:24px; display:flex; align-items:center; gap:16px; }
        .nav-desktop { display:flex; gap:8px; background:white; padding:8px; border-radius:16px; margin-bottom:28px; flex-wrap:wrap; }
        .nav-desktop a { padding:12px 24px; border-radius:12px; text-decoration:none; font-weight:600; color:#475569; display:flex; align-items:center; gap:8px; }
        .nav-desktop a:hover { background:#f1f5f9; }
        .nav-desktop a.active { background:#2563eb; color:white; }
        .filter-card { background:white; border-radius:20px; padding:24px; margin-bottom:28px; box-shadow:0 4px 12px rgba(0,0,0,0.02); }
        .filter-form { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:20px; align-items:end; }
        .form-group label { font-size:14px; font-weight:600; color:#334155; margin-bottom:6px; display:block; }
        .input-field { width:100%; padding:14px 18px; border:1.5px solid #e2e8f0; border-radius:14px; font-size:16px; }
        .btn-filter { background:#2563eb; color:white; border:none; padding:14px 28px; border-radius:14px; font-weight:600; cursor:pointer; }
        .section-card { background:white; border-radius:20px; padding:24px; margin-bottom:28px; }
        .section-title { font-size:22px; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:12px; }
        .table-responsive { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8fafc; padding:16px; text-align:left; font-weight:600; color:#334155; }
        td { padding:16px; border-bottom:1px solid #e2e8f0; }
        .loss-box { background:#fee2e2; padding:18px; border-radius:14px; margin-top:20px; font-weight:700; display:flex; justify-content:space-between; }
        .export-buttons { display:flex; gap:16px; justify-content:center; margin-top:24px; }
        .btn-export { padding:14px 28px; border-radius:14px; font-weight:600; border:none; background:#f1f5f9; color:#0f172a; display:flex; align-items:center; gap:8px; cursor:pointer; }
        .btn-export:hover { background:#e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <span style="font-size:42px;">📊</span>
            <h1>Laporan & Analisis</h1>
        </div>

        <div class="nav-desktop">
            <a href="../../dashboard.php">🏠 Dashboard</a>
            <a href="../gudang/">📦 Gudang</a>
            <a href="../kasir/">💳 Kasir</a>
            <a href="laporan.php" class="active">📊 Laporan</a>
            <a href="settings.php">⚙️ Pengaturan</a>
            <a href="../../logout.php" style="margin-left:auto;">🚪 Keluar</a>
        </div>

        <!-- FILTER -->
        <div class="filter-card">
            <form method="GET">
                <div class="filter-form">
                    <div class="form-group">
                        <label>Dari tanggal</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" class="input-field" required>
                    </div>
                    <div class="form-group">
                        <label>Sampai tanggal</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" class="input-field" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-filter">🔍 Tampilkan</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- PENJUALAN -->
        <div class="section-card">
            <div class="section-title">
                <span>💰</span> Rekap Penjualan
            </div>
            <?php if ($sales_report->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Tanggal</th><th>Transaksi</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php while ($row = $sales_report->fetch_assoc()): ?>
                        <tr>
                            <td><?= tanggalIndo($row['tanggal']) ?></td>
                            <td><?= $row['jumlah_transaksi'] ?></td>
                            <td style="font-weight:600;"><?= formatRupiah($row['total_penjualan']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p style="color:#64748b; text-align:center; padding:32px;">Belum ada data penjualan periode ini.</p>
            <?php endif; ?>
        </div>

        <!-- KEHILANGAN STOK -->
        <div class="section-card">
            <div class="section-title" style="color:#b91c1c;">
                <span>⚠️</span> Kehilangan Stok
            </div>
            <?php if ($stock_loss->num_rows > 0): 
                $total_rugi = 0;
                while ($r = $stock_loss->fetch_assoc()) $total_rugi += $r['total_kerugian'];
                $stock_loss->data_seek(0);
            ?>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Barang</th><th>Jumlah Hilang</th><th>Harga Beli</th><th>Kerugian</th></tr></thead>
                    <tbody>
                        <?php while ($row = $stock_loss->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td style="color:#dc2626;"><?= $row['total_selisih'] ?> unit</td>
                            <td><?= formatRupiah($row['harga_beli']) ?></td>
                            <td style="color:#dc2626; font-weight:600;"><?= formatRupiah($row['total_kerugian']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="loss-box">
                <span>💰 Total Kerugian</span>
                <span style="font-size:22px;"><?= formatRupiah($total_rugi) ?></span>
            </div>
            <?php else: ?>
            <div style="background:#dcfce7; padding:24px; border-radius:16px; color:#166534;">
                ✅ Tidak ada kehilangan stok pada periode ini.
            </div>
            <?php endif; ?>
        </div>

        <div class="export-buttons">
            <button onclick="window.print()" class="btn-export">🖨️ Cetak</button>
            <button onclick="alert('Export Excel segera hadir')" class="btn-export">📥 Export Excel</button>
        </div>
    </div>
</body>
</html>