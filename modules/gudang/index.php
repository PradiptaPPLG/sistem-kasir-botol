<?php
// modules/gudang/index.php - VERSI TAILWIND v3
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
$cabang_id = $_SESSION['id_cabang'];

// Get total stok
$total_stok = $db->query("SELECT SUM(stok_sistem) as total FROM stok_gudang WHERE id_cabang = ?",
        [$cabang_id])->fetch_assoc();

// Get low stock items (stok < 10)
$low_stock = $db->query("
    SELECT b.nama_barang, sg.stok_sistem, b.stok_minimal
    FROM stok_gudang sg
    JOIN barang b ON sg.id_barang = b.id_barang
    WHERE sg.id_cabang = ? AND sg.stok_sistem <= b.stok_minimal
    ORDER BY sg.stok_sistem ASC
    LIMIT 5
", [$cabang_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Dashboard Gudang - Sistem Kasir Botol</title>

    <!-- Tailwind CSS v3 -->
    <link href="../../src/output.css" rel="stylesheet">

    <style>
        /* Custom untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            input, select, button, a, .nav-tab, .action-card {
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

        /* Custom animation untuk hover */
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -10px rgba(0,0,0,0.15);
        }

        .hover-scale:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl p-4 md:p-5 text-gray-800">
<div class="max-w-6xl mx-auto">

    <!-- HEADER - Gradient Hijau -->
    <div class="bg-gradient-to-r from-green-600 to-green-500 text-white px-6 md:px-8 py-8 md:py-10 rounded-2xl shadow-xl mb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">📦 GUDANG</h1>
        <p class="text-lg md:text-xl opacity-90">Kelola stok barang</p>
    </div>

    <!-- NAVIGASI TAB -->
    <div class="flex flex-wrap bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <a href="index.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-green-600 bg-white border-b-4 border-green-600">
            📊 DASHBOARD
        </a>
        <a href="stok-masuk.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-green-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-green-600">
            ➕ STOK MASUK
        </a>
        <a href="stok-keluar.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-green-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-green-600">
            📤 STOK KELUAR
        </a>
        <a href="stock-opname.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-green-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-green-600">
            📋 STOCK OPNAME
        </a>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <!-- Total Stok Card -->
        <div class="bg-white p-6 rounded-xl shadow-md border-t-8 border-blue-500 text-center hover-lift transition-all duration-300">
            <div class="text-5xl md:text-6xl mb-3">📦</div>
            <div class="text-4xl md:text-5xl font-bold text-gray-800 mb-2">
                <?php echo $total_stok['total'] ?? 0; ?>
            </div>
            <div class="text-lg md:text-xl text-gray-600 font-semibold">Total Stok</div>
        </div>

        <!-- Stok Rendah Card -->
        <div class="bg-white p-6 rounded-xl shadow-md border-t-8 border-orange-500 text-center hover-lift transition-all duration-300">
            <div class="text-5xl md:text-6xl mb-3">⚠️</div>
            <div class="text-4xl md:text-5xl font-bold text-gray-800 mb-2">
                <?php echo $low_stock->num_rows; ?>
            </div>
            <div class="text-lg md:text-xl text-gray-600 font-semibold">Stok Rendah</div>
        </div>

        <!-- Cabang Card -->
        <div class="bg-white p-6 rounded-xl shadow-md border-t-8 border-purple-500 text-center hover-lift transition-all duration-300">
            <div class="text-5xl md:text-6xl mb-3">🏪</div>
            <div class="text-4xl md:text-5xl font-bold text-gray-800 mb-2">
                <?php echo $_SESSION['id_cabang']; ?>
            </div>
            <div class="text-lg md:text-xl text-gray-600 font-semibold">Cabang</div>
        </div>
    </div>

    <!-- WARNING STOK RENDAH -->
    <?php if ($low_stock->num_rows > 0): ?>
        <div class="bg-gradient-to-r from-orange-50 to-red-50 border-l-8 border-red-600 p-6 mb-8 rounded-xl shadow-lg hover-scale transition-all duration-300">
            <div class="flex items-start gap-4 mb-5">
                <div class="text-4xl">⚠️</div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold text-red-700">PERINGATAN STOK RENDAH</div>
                    <div class="text-lg text-gray-700">Beberapa barang hampir habis!</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="w-full bg-white rounded-lg overflow-hidden">
                    <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Barang</th>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Stok Saat Ini</th>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Stok Minimal</th>
                        <th class="p-4 md:p-5 text-left font-bold text-gray-800">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($item = $low_stock->fetch_assoc()): ?>
                        <tr class="border-b border-gray-200 hover:bg-red-50 transition">
                            <td class="p-4 md:p-5 font-medium"><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                            <td class="p-4 md:p-5 font-bold <?php echo $item['stok_sistem'] <= $item['stok_minimal'] ? 'text-red-600' : ''; ?>">
                                <?php echo $item['stok_sistem']; ?>
                            </td>
                            <td class="p-4 md:p-5"><?php echo $item['stok_minimal']; ?></td>
                            <td class="p-4 md:p-5">
                                <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm md:text-base font-bold">
                                    ⚠️ PERLU RESTOCK
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-right">
                <a href="stock-opname.php" class="inline-block px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-all duration-300 shadow-md hover:-translate-y-1">
                    🔍 Cek Stok Fisik
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Jika tidak ada stok rendah -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-8 border-green-600 p-6 mb-8 rounded-xl shadow-md">
            <div class="flex items-center gap-4">
                <div class="text-4xl">✅</div>
                <div>
                    <div class="text-xl md:text-2xl font-bold text-green-700">Semua Stok Aman</div>
                    <div class="text-lg text-gray-700">Tidak ada barang dengan stok rendah.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ACTION CARDS (MENU CEPAT) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Tambah Stok -->
        <a href="stok-masuk.php" class="bg-white p-6 rounded-xl shadow-md text-center hover-lift transition-all duration-300 border-2 border-transparent hover:border-green-500 group">
            <div class="text-6xl md:text-7xl mb-4 group-hover:scale-110 transition-transform">➕</div>
            <div class="text-xl md:text-2xl font-bold text-gray-800 mb-2 group-hover:text-green-600">TAMBAH STOK</div>
            <div class="text-base md:text-lg text-gray-600">Input barang baru ke gudang</div>
        </a>

        <!-- Stok Keluar -->
        <a href="stok-keluar.php" class="bg-white p-6 rounded-xl shadow-md text-center hover-lift transition-all duration-300 border-2 border-transparent hover:border-orange-500 group">
            <div class="text-6xl md:text-7xl mb-4 group-hover:scale-110 transition-transform">📤</div>
            <div class="text-xl md:text-2xl font-bold text-gray-800 mb-2 group-hover:text-orange-600">STOK KELUAR</div>
            <div class="text-base md:text-lg text-gray-600">Barang rusak/hilang/transfer</div>
        </a>

        <!-- Stock Opname -->
        <a href="stock-opname.php" class="bg-white p-6 rounded-xl shadow-md text-center hover-lift transition-all duration-300 border-2 border-transparent hover:border-blue-500 group">
            <div class="text-6xl md:text-7xl mb-4 group-hover:scale-110 transition-transform">📋</div>
            <div class="text-xl md:text-2xl font-bold text-gray-800 mb-2 group-hover:text-blue-600">STOCK OPNAME</div>
            <div class="text-base md:text-lg text-gray-600">Cek fisik stok di gudang</div>
        </a>

        <!-- Dashboard Utama -->
        <a href="../../dashboard.php" class="bg-white p-6 rounded-xl shadow-md text-center hover-lift transition-all duration-300 border-2 border-transparent hover:border-purple-500 group">
            <div class="text-6xl md:text-7xl mb-4 group-hover:scale-110 transition-transform">🏠</div>
            <div class="text-xl md:text-2xl font-bold text-gray-800 mb-2 group-hover:text-purple-600">DASHBOARD</div>
            <div class="text-base md:text-lg text-gray-600">Kembali ke menu utama</div>
        </a>
    </div>

    <!-- NAVIGASI BAWAH -->
    <div class="flex flex-col sm:flex-row gap-4 mt-6">
        <a href="../../modules/kasir/"
           class="flex-1 py-5 px-4 text-center text-lg md:text-xl font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl transition-all duration-300 shadow-md hover:-translate-y-1">
            💳 Ke Kasir
        </a>
        <a href="../../logout.php"
           class="flex-1 py-5 px-4 text-center text-lg md:text-xl font-bold text-white bg-gradient-to-r from-red-500 to-red-600 rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-300 shadow-md hover:-translate-y-1">
            🚪 Keluar
        </a>
    </div>

    <!-- FOOTER -->
    <div class="text-center mt-8 text-gray-500 text-base md:text-lg">
        <p>© <?php echo date('Y'); ?> Sistem Kasir Botol - Dashboard Gudang</p>
        <p class="text-sm mt-1">Cabang: <?php echo $_SESSION['id_cabang']; ?> | User: <?php echo htmlspecialchars($_SESSION['nama_karyawan'] ?? ''); ?></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Touch feedback
        document.querySelectorAll('a, button, .nav-tab, .action-card').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            el.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });

        // iOS zoom fix
        document.querySelectorAll('input, select, textarea').forEach(el => {
            if (el) el.style.fontSize = '16px';
        });

        // Table responsive wrapper
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            if (table.parentElement && !table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    });
</script>
</body>
</html>