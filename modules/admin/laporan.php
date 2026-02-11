<?php
// modules/admin/laporan.php - VERSI TAILWIND v3
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

// Hitung total kerugian untuk laporan
$total_kerugian = 0;
if ($stock_loss->num_rows > 0) {
    $temp_result = $stock_loss;
    while ($row = $temp_result->fetch_assoc()) {
        $total_kerugian += $row['total_kerugian'];
    }
    $stock_loss->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Laporan Admin - Sistem Kasir Botol</title>

    <!-- Tailwind CSS v3 -->
    <link href="../../src/output.css" rel="stylesheet">

    <style>
        /* Custom untuk print */
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0.5in; }
        }

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            input, select, button, a {
                font-size: 16px !important;
                min-height: 50px;
            }
        }

        /* Responsive table */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl p-4 md:p-5">
<div class="max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white px-6 md:px-8 py-8 md:py-10 rounded-2xl shadow-xl mb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">📊 LAPORAN ADMIN</h1>
        <p class="text-lg md:text-xl opacity-90">Rekap Penjualan & Kehilangan Stok</p>
    </div>

    <!-- NAVIGASI DESKTOP -->
    <div class="flex flex-wrap bg-white rounded-xl shadow-md overflow-hidden mb-8 no-print">
        <a href="../../dashboard.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-purple-700 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-purple-600">
            🏠 DASHBOARD
        </a>
        <a href="../gudang/" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-purple-700 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-purple-600">
            📦 GUDANG
        </a>
        <a href="../kasir/" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-purple-700 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-purple-600">
            💳 KASIR
        </a>
        <a href="laporan.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-purple-700 bg-gray-50 border-b-4 border-purple-600">
            📊 LAPORAN
        </a>
        <a href="settings.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-purple-700 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-purple-600">
            ⚙️ PENGATURAN
        </a>
        <a href="../../logout.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-red-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-red-600">
            🚪 KELUAR
        </a>
    </div>

    <!-- FILTER TANGGAL -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8 no-print">
        <form method="GET" action="">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-end">
                <div>
                    <label class="block text-lg md:text-xl font-bold text-gray-800 mb-2">📅 Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                           class="w-full p-4 text-lg md:text-xl border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all"
                           required>
                </div>
                <div>
                    <label class="block text-lg md:text-xl font-bold text-gray-800 mb-2">📅 Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                           class="w-full p-4 text-lg md:text-xl border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all"
                           required>
                </div>
                <div>
                    <button type="submit" class="w-full p-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-purple-600 to-purple-800 rounded-xl hover:from-purple-700 hover:to-purple-900 transition-all duration-300 shadow-lg">
                        🔍 TAMPILKAN
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- LAPORAN PENJUALAN -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
        <div class="flex items-center mb-6 border-l-8 border-purple-600 pl-5">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">💰 LAPORAN PENJUALAN</h2>
        </div>

        <?php if ($sales_report->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="w-full text-base md:text-lg">
                    <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Tanggal</th>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Jumlah Transaksi</th>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Total Penjualan</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $sales_report->fetch_assoc()): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="p-4 md:p-5 text-gray-700"><?php echo tanggalIndo($row['tanggal']); ?></td>
                            <td class="p-4 md:p-5 text-gray-700 font-semibold"><?php echo $row['jumlah_transaksi']; ?></td>
                            <td class="p-4 md:p-5 font-bold text-green-700"><?php echo formatRupiah($row['total_penjualan']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-gray-50 p-8 rounded-xl text-center">
                <div class="text-6xl mb-4">📄</div>
                <p class="text-xl md:text-2xl text-gray-600">Tidak ada data penjualan periode ini.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- LAPORAN KEHILANGAN STOK -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
        <div class="flex items-center mb-6 border-l-8 border-red-600 pl-5">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">⚠️ LAPORAN KEHILANGAN STOK</h2>
        </div>

        <?php if ($stock_loss->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="w-full text-base md:text-lg">
                    <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Nama Barang</th>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Jumlah Hilang</th>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Harga Beli</th>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Total Kerugian</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $stock_loss->fetch_assoc()): ?>
                        <tr class="border-b border-gray-200 hover:bg-red-50 transition">
                            <td class="p-4 md:p-5 text-gray-800 font-medium"><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                            <td class="p-4 md:p-5 text-red-700 font-bold"><?php echo $row['total_selisih']; ?> unit</td>
                            <td class="p-4 md:p-5 text-gray-700"><?php echo formatRupiah($row['harga_beli']); ?></td>
                            <td class="p-4 md:p-5 text-red-700 font-bold"><?php echo formatRupiah($row['total_kerugian']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-red-50 p-6 rounded-xl mt-6 text-center">
                <p class="text-2xl md:text-3xl font-bold text-red-700">
                    💰 TOTAL KERUGIAN: <?php echo formatRupiah($total_kerugian); ?>
                </p>
            </div>
        <?php else: ?>
            <div class="bg-green-50 p-8 rounded-xl border-l-8 border-green-600">
                <div class="flex items-center gap-4">
                    <div class="text-5xl">✅</div>
                    <div>
                        <p class="text-xl md:text-2xl font-bold text-green-800">Tidak ada kehilangan stok!</p>
                        <p class="text-lg md:text-xl text-green-700">Semua stok aman pada periode ini.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- TOMBOL EXPORT -->
    <div class="flex flex-wrap gap-4 justify-center mt-8 no-print">
        <button onclick="window.print()"
                class="px-8 py-5 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-green-600 to-green-700 rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-300 shadow-lg hover:-translate-y-1">
            🖨️ CETAK LAPORAN
        </button>
        <button onclick="alert('Export Excel sedang dikembangkan')"
                class="px-8 py-5 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-yellow-600 to-orange-600 rounded-xl hover:from-yellow-700 hover:to-orange-700 transition-all duration-300 shadow-lg hover:-translate-y-1">
            📥 EXPORT EXCEL
        </button>
    </div>

    <!-- FOOTER INFO -->
    <div class="text-center mt-8 text-gray-500 text-base md:text-lg no-print">
        <p>© <?php echo date('Y'); ?> Sistem Kasir Botol - Laporan dihasilkan otomatis</p>
        <p class="text-sm mt-2">Periode: <?php echo tanggalIndo($start_date); ?> - <?php echo tanggalIndo($end_date); ?></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Make tables responsive
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });

        // Touch feedback
        document.querySelectorAll('button, a').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            el.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });

        // iOS zoom fix
        document.querySelectorAll('input, select').forEach(el => {
            el.style.fontSize = '16px';
        });

        // Current year for copyright
        const yearEl = document.querySelector('.current-year');
        if (yearEl) yearEl.textContent = new Date().getFullYear();
    });
</script>
</body>
</html>