<?php
// modules/admin/laporan.php
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../../dashboard.php');
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();

// Default date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get reports
$sales_report = $db->query("
    SELECT 
        DATE(tanggal) as tanggal,
        COUNT(*) as jumlah_transaksi,
        SUM(total_harga) as total_penjualan
    FROM transaksi_kasir 
    WHERE id_cabang = ? 
    AND tanggal BETWEEN ? AND ?
    GROUP BY DATE(tanggal)
    ORDER BY tanggal DESC
", [$user['id_cabang'], $start_date, $end_date]);

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
", [$user['id_cabang'], $start_date, $end_date]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Toko</title>
    <link rel="stylesheet" href="../../assets/css/ux-large.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
    <!-- Navigation -->
    <div class="main-nav hide-mobile">
        <a href="../../dashboard.php" class="nav-button">🏠 DASHBOARD</a>
        <a href="../gudang/" class="nav-button">📦 GUDANG</a>
        <a href="../kasir/" class="nav-button">💳 KASIR</a>
        <a href="laporan.php" class="nav-button">📊 LAPORAN</a>
        <a href="../../logout.php" class="nav-button" style="background: var(--danger-color);">🚪 KELUAR</a>
    </div>

    <div class="dashboard-card">
        <h1 style="color: var(--primary-color);">📊 LAPORAN ADMIN</h1>
        
        <!-- Date Filter -->
        <form method="GET" action="" style="margin: 20px 0;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label style="font-size: 16px; display: block; margin-bottom: 5px;">Dari Tanggal:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="input-large">
                </div>
                <div>
                    <label style="font-size: 16px; display: block; margin-bottom: 5px;">Sampai Tanggal:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="input-large">
                </div>
                <div style="align-self: end;">
                    <button type="submit" class="btn-big btn-green" style="width: 100%;">🔍 FILTER</button>
                </div>
            </div>
        </form>
        
        <!-- Sales Report -->
        <div style="margin: 30px 0;">
            <h2 style="color: var(--primary-color);">💰 LAPORAN PENJUALAN</h2>
            <div style="overflow-x: auto;">
                <table class="table-large">
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
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo $row['jumlah_transaksi']; ?></td>
                            <td>Rp <?php echo number_format($row['total_penjualan'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Stock Loss Report -->
        <div style="margin: 30px 0;">
            <h2 style="color: var(--danger-color);">⚠️ LAPORAN KEHILANGAN STOK</h2>
            <?php if ($stock_loss->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-large">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Jumlah Hilang</th>
                            <th>Harga Satuan</th>
                            <th>Total Kerugian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_kerugian = 0;
                        while ($row = $stock_loss->fetch_assoc()): 
                            $total_kerugian += $row['total_kerugian'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                            <td class="status-danger"><?php echo $row['total_selisih']; ?> unit</td>
                            <td>Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                            <td class="status-danger">Rp <?php echo number_format($row['total_kerugian'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; padding: 15px; background: #FFEBEE; border-radius: 8px;">
                    <p style="font-size: 20px; font-weight: bold; margin: 0; color: #D32F2F;">
                        💰 TOTAL KERUGIAN: Rp <?php echo number_format($total_kerugian, 0, ',', '.'); ?>
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div style="background: #C8E6C9; padding: 20px; border-radius: 8px;">
                <p style="font-size: 18px; color: #2E7D32; margin: 0;">
                    ✅ Tidak ada kehilangan stok pada periode ini.
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Export Buttons -->
        <div style="margin-top: 30px; text-align: center;">
            <button onclick="printReport()" class="btn-big btn-green" style="margin: 5px;">
                🖨️ CETAK LAPORAN
            </button>
            <button onclick="exportToExcel()" class="btn-big btn-yellow" style="margin: 5px;">
                📥 EXPORT EXCEL
            </button>
        </div>
    </div>
    
    <script>
    function printReport() {
        window.print();
    }
    
    function exportToExcel() {
        // Simple export to Excel
        const tables = document.querySelectorAll('table');
        let csv = [];
        
        tables.forEach(table => {
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                const rowData = Array.from(cells).map(cell => `"${cell.textContent}"`).join(',');
                csv.push(rowData);
            });
            csv.push('\n');
        });
        
        const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "laporan_toko.csv");
        document.body.appendChild(link);
        link.click();
    }
    </script>
</body>
</html>