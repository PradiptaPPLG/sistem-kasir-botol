<?php
// dashboard.php - VERSI TAILWIND v3 UNTUK LANGSIA

// Start session dengan timeout
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params(1800);
    session_start();
}

// Cek login
if (!isset($_SESSION['id_karyawan'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$user = $_SESSION['nama_karyawan'];
$id_cabang = $_SESSION['id_cabang'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

$today = date('Y-m-d');

// ============ PERBAIKAN UNTUK PDO ============

// 1. GET SALES TODAY - pake fetch()
$sales_today = $db->fetchOne(
        "SELECT SUM(total_harga) as total FROM transaksi_kasir 
     WHERE id_cabang = ? AND DATE(tanggal) = ?",
        [$id_cabang, $today]
);

// 2. GET TOTAL TRANSACTIONS - pake fetch()
$total_transactions = $db->fetchOne(
        "SELECT COUNT(*) as total FROM transaksi_kasir 
     WHERE id_cabang = ? AND DATE(tanggal) = ?",
        [$id_cabang, $today]
);

// 3. GET WARNINGS - pake fetchAll()
$warnings = [];
$total_loss = 0;
if ($is_admin) {
    $warnings = $db->fetchAll("
        SELECT b.nama_barang, sg.stok_sistem, sg.stok_fisik, 
               sg.stok_sistem - sg.stok_fisik as selisih,
               b.harga_beli
        FROM stok_gudang sg
        JOIN barang b ON sg.id_barang = b.id_barang
        WHERE sg.id_cabang = ? AND sg.tanggal_update = ?
          AND sg.stok_sistem > sg.stok_fisik
        LIMIT 3
    ", [$id_cabang, $today]);

    foreach ($warnings as $warning) {
        $total_loss += $warning['selisih'] * $warning['harga_beli'];
    }
}

// 4. GET RECENT TRANSACTIONS - pake fetchAll()
$recent = $db->fetchAll("
    SELECT tk.*, b.nama_barang 
    FROM transaksi_kasir tk
    JOIN barang b ON tk.id_barang = b.id_barang
    WHERE tk.id_cabang = ?
    ORDER BY tk.tanggal DESC
    LIMIT 5
", [$id_cabang]);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Dashboard - Sistem Kasir Botol</title>

    <!-- Tailwind CSS v3 -->
    <link href="./src/output.css" rel="stylesheet">

    <style>
        /* Custom styles yang tidak ada di Tailwind */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .animate-pulse-custom {
            animation: pulse 2s infinite;
        }

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .touch-min-height {
                min-height: 60px;
            }
            input, select, button, a {
                font-size: 16px !important;
            }
        }

        /* Fix untuk scroll table di mobile */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl text-gray-800">

<!-- HEADER -->
<div class="bg-gradient-to-r from-slate-800 to-blue-900 text-white px-4 md:px-6 py-6 md:py-8 shadow-lg">
    <div class="flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3 md:gap-4">
            <div class="text-4xl md:text-5xl">🏪</div>
            <div>
                <div class="text-2xl md:text-3xl font-bold">SISTEM KASIR BOTOL</div>
                <div class="text-sm md:text-base opacity-90">Dashboard Utama</div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-xl md:text-2xl font-bold"><?php echo htmlspecialchars($user); ?></div>
            <div class="inline-block px-4 py-2 text-sm md:text-base font-bold rounded-full <?php echo $is_admin ? 'bg-yellow-500 text-gray-900' : 'bg-green-600 text-white'; ?>">
                <?php echo $is_admin ? 'ADMIN' : 'KASIR'; ?>
            </div>
        </div>
    </div>
</div>

<!-- DESKTOP NAVIGATION - hidden on mobile -->
<div class="hidden md:flex bg-white shadow-md">
    <a href="modules/kasir/transaksi.php?type=pembeli" class="flex-1 py-6 text-center text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-4 border-transparent hover:border-blue-500 transition-all">
        💳 KASIR
    </a>
    <a href="modules/gudang/" class="flex-1 py-6 text-center text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-4 border-transparent hover:border-blue-500 transition-all">
        📦 GUDANG
    </a>
    <a href="dashboard.php" class="flex-1 py-6 text-center text-xl font-bold text-blue-600 bg-gray-50 border-b-4 border-blue-500">
        🏠 DASHBOARD
    </a>
    <?php if ($is_admin): ?>
        <a href="modules/admin/laporan.php" class="flex-1 py-6 text-center text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-4 border-transparent hover:border-blue-500 transition-all">
            📊 LAPORAN
        </a>
    <?php endif; ?>
    <a href="logout.php" class="flex-1 py-6 text-center text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-4 border-transparent hover:border-blue-500 transition-all">
        🚪 KELUAR
    </a>
</div>

<!-- MAIN CONTENT -->
<div class="p-4 md:p-6 pb-24 md:pb-10">

    <!-- WARNINGS (Admin only) -->
    <?php if ($is_admin && !empty($warnings)): ?>
        <div class="bg-gradient-to-r from-orange-50 to-red-50 border-l-8 border-red-600 p-6 mb-8 rounded-xl shadow-lg animate-pulse-custom">
            <div class="flex items-start gap-4 mb-5">
                <div class="text-4xl">⚠️</div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold text-red-700">PERINGATAN KEHILANGAN STOK</div>
                    <div class="text-lg text-gray-700">Stok sistem lebih besar dari stok fisik!</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="w-full bg-white rounded-lg overflow-hidden mb-4">
                    <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 text-left font-bold text-gray-800">Barang</th>
                        <th class="p-4 text-left font-bold text-gray-800">Stok Sistem</th>
                        <th class="p-4 text-left font-bold text-gray-800">Stok Fisik</th>
                        <th class="p-4 text-left font-bold text-gray-800">Selisih</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($warnings as $warning): ?>
                        <tr class="border-b border-gray-200">
                            <td class="p-4"><?php echo htmlspecialchars($warning['nama_barang']); ?></td>
                            <td class="p-4"><?php echo $warning['stok_sistem']; ?></td>
                            <td class="p-4"><?php echo $warning['stok_fisik']; ?></td>
                            <td class="p-4 text-red-600 font-bold"><?php echo $warning['selisih']; ?> botol</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-red-100 p-5 rounded-lg text-center">
                <span class="text-2xl md:text-3xl font-bold text-red-700">💰 ESTIMASI KERUGIAN: <?php echo formatRupiah($total_loss); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- STATS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <!-- Sales Card -->
        <div class="bg-white p-6 rounded-xl shadow-md border-t-8 border-blue-500">
            <div class="flex justify-between items-start mb-4">
                <div class="text-5xl">💰</div>
                <div class="text-right">
                    <div class="text-lg text-gray-600">Hari Ini</div>
                    <div class="text-sm text-gray-500">Penjualan</div>
                </div>
            </div>
            <div class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                <?php echo formatRupiah($sales_today['total'] ?? 0); ?>
            </div>
            <a href="modules/kasir/transaksi.php?type=pembeli" class="block w-full py-5 px-4 bg-gray-100 hover:bg-gray-200 rounded-xl text-center text-xl font-bold text-gray-800 hover:-translate-y-1 transition-all duration-300">
                ➕ Transaksi Baru
            </a>
        </div>

        <!-- Transactions Card -->
        <div class="bg-white p-6 rounded-xl shadow-md border-t-8 border-green-500">
            <div class="flex justify-between items-start mb-4">
                <div class="text-5xl">📝</div>
                <div class="text-right">
                    <div class="text-lg text-gray-600">Hari Ini</div>
                    <div class="text-sm text-gray-500">Transaksi</div>
                </div>
            </div>
            <div class="text-5xl md:text-6xl font-bold text-gray-800 mb-4">
                <?php echo $total_transactions['total'] ?? 0; ?>
            </div>
            <a href="modules/kasir/transaksi.php?type=penjual" class="block w-full py-5 px-4 bg-gray-100 hover:bg-gray-200 rounded-xl text-center text-xl font-bold text-gray-800 hover:-translate-y-1 transition-all duration-300">
                🏪 Ke Penjual
            </a>
        </div>

        <!-- Gudang Card -->
        <div class="bg-white p-6 rounded-xl shadow-md border-t-8 border-orange-500">
            <div class="flex justify-between items-start mb-4">
                <div class="text-5xl">📦</div>
                <div class="text-right">
                    <div class="text-lg text-gray-600">Gudang</div>
                    <div class="text-sm text-gray-500">Kelola Stok</div>
                </div>
            </div>
            <div class="text-5xl md:text-6xl font-bold text-gray-800 mb-4">-</div>
            <a href="modules/gudang/stock-opname.php" class="block w-full py-5 px-4 bg-gray-100 hover:bg-gray-200 rounded-xl text-center text-xl font-bold text-gray-800 hover:-translate-y-1 transition-all duration-300">
                📋 Cek Stok
            </a>
        </div>

        <!-- Admin/Kasir Card -->
        <div class="bg-white p-6 rounded-xl shadow-md border-t-8 <?php echo $is_admin ? 'border-purple-500' : 'border-green-500'; ?>">
            <div class="flex justify-between items-start mb-4">
                <div class="text-5xl"><?php echo $is_admin ? '🔐' : '👤'; ?></div>
                <div class="text-right">
                    <div class="text-lg text-gray-600"><?php echo $is_admin ? 'Admin' : 'Kasir'; ?></div>
                    <div class="text-sm text-gray-500">Mode</div>
                </div>
            </div>
            <div class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                <?php echo $is_admin ? 'Full Akses' : 'Terbatas'; ?>
            </div>
            <?php if ($is_admin): ?>
                <a href="modules/admin/laporan.php" class="block w-full py-5 px-4 bg-gray-100 hover:bg-gray-200 rounded-xl text-center text-xl font-bold text-gray-800 hover:-translate-y-1 transition-all duration-300">
                    📊 Lihat Laporan
                </a>
            <?php else: ?>
                <a href="modules/kasir/" class="block w-full py-5 px-4 bg-gray-100 hover:bg-gray-200 rounded-xl text-center text-xl font-bold text-gray-800 hover:-translate-y-1 transition-all duration-300">
                    📝 Riwayat Saya
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- RECENT TRANSACTIONS -->
    <div class="flex justify-between items-center mb-5">
        <div class="text-2xl md:text-3xl font-bold text-gray-800">📝 TRANSAKSI TERAKHIR</div>
        <a href="modules/kasir/" class="text-lg md:text-xl text-blue-600 hover:text-blue-800 font-semibold hover:underline">
            Lihat Semua →
        </a>
    </div>

    <?php if ($recent->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="w-full bg-white rounded-xl shadow-md overflow-hidden">
                <thead class="bg-gray-100">
                <tr>
                    <th class="p-5 text-left font-bold text-gray-800 text-base md:text-lg">Waktu</th>
                    <th class="p-5 text-left font-bold text-gray-800 text-base md:text-lg">Barang</th>
                    <th class="p-5 text-left font-bold text-gray-800 text-base md:text-lg">Jenis</th>
                    <th class="p-5 text-left font-bold text-gray-800 text-base md:text-lg">Jumlah</th>
                    <th class="p-5 text-left font-bold text-gray-800 text-base md:text-lg">Total</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($transaction = $recent->fetch_assoc()): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                        <td class="p-5 text-gray-700"><?php echo date('H:i', strtotime($transaction['tanggal'])); ?></td>
                        <td class="p-5 text-gray-700"><?php echo htmlspecialchars(substr($transaction['nama_barang'], 0, 20)); ?></td>
                        <td class="p-5">
                            <span class="inline-block px-4 py-2 rounded-full text-sm md:text-base font-bold <?php echo $transaction['jenis_pembeli'] == 'pembeli' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo $transaction['jenis_pembeli'] == 'pembeli' ? 'Pembeli' : 'Penjual'; ?>
                            </span>
                        </td>
                        <td class="p-5 text-gray-700"><?php echo $transaction['jumlah']; ?></td>
                        <td class="p-5 font-bold text-gray-800"><?php echo formatRupiah($transaction['total_harga']); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-white p-12 rounded-xl shadow-md text-center">
            <div class="text-7xl mb-5">📄</div>
            <p class="text-2xl text-gray-600 mb-6">Belum ada transaksi hari ini</p>
            <a href="modules/kasir/transaksi.php"
               class="inline-block px-8 py-5 bg-blue-600 hover:bg-blue-700 text-white text-xl font-bold rounded-xl transition-all duration-300 hover:-translate-y-1 shadow-lg">
                Buat Transaksi Pertama
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- MOBILE NAVIGATION - visible only on mobile -->
<div class="md:hidden flex justify-around bg-white fixed bottom-0 left-0 right-0 py-3 shadow-up-lg z-50 border-t border-gray-200">
    <a href="modules/kasir/transaksi.php?type=pembeli" class="nav-item flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600 transition">
        <span class="text-3xl">💳</span>
        <span class="text-xs font-bold mt-1">Kasir</span>
    </a>
    <a href="modules/gudang/" class="nav-item flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600 transition">
        <span class="text-3xl">📦</span>
        <span class="text-xs font-bold mt-1">Gudang</span>
    </a>
    <a href="dashboard.php" class="nav-item flex flex-col items-center px-3 py-2 text-blue-600">
        <span class="text-3xl">🏠</span>
        <span class="text-xs font-bold mt-1">Home</span>
    </a>
    <?php if ($is_admin): ?>
        <a href="modules/admin/laporan.php" class="nav-item flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600 transition">
            <span class="text-3xl">📊</span>
            <span class="text-xs font-bold mt-1">Laporan</span>
        </a>
    <?php endif; ?>
    <a href="logout.php" class="nav-item flex flex-col items-center px-3 py-2 text-gray-600 hover:text-red-600 transition">
        <span class="text-3xl">🚪</span>
        <span class="text-xs font-bold mt-1">Keluar</span>
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Make tables responsive dengan wrapper
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                wrapper.style.overflowX = 'auto';
                wrapper.style.marginBottom = '1rem';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });

        // Touch feedback
        document.querySelectorAll('a, button').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            el.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });

        // iOS zoom fix
        document.querySelectorAll('input, select, textarea').forEach(el => {
            el.style.fontSize = '16px';
        });
    });

    // Active nav item untuk mobile
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(item => {
        if (item.getAttribute('href') === 'dashboard.php') {
            item.classList.add('text-blue-600');
            item.classList.remove('text-gray-600');
        }
    });
</script>
</body>
</html>