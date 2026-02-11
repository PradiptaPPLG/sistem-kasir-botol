<?php
// modules/kasir/index.php - VERSI TAILWIND v3
require_once '../../includes/auth.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$user = $auth->getCurrentUser();
$db = new Database();
$today = date('Y-m-d');

// Get stats hari ini
$stats = $db->query("
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(total_harga) as total_penjualan,
        SUM(CASE WHEN jenis_pembeli = 'pembeli' THEN 1 ELSE 0 END) as pembeli,
        SUM(CASE WHEN jenis_pembeli = 'penjual' THEN 1 ELSE 0 END) as penjual
    FROM transaksi_kasir 
    WHERE id_cabang = ? 
    AND DATE(tanggal) = ?
    AND id_karyawan = ?
", [$user['id_cabang'], $today, $user['id_karyawan']])->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Kasir - Sistem Toko</title>
    <link rel="stylesheet" href="../../assets/css/ux-large.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <!-- Tailwind CSS v3 -->
    <link href="../../src/output.css" rel="stylesheet">

    <style>
        /* Custom untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            a, button, .nav-button, .dashboard-card {
                font-size: 16px !important;
                min-height: 50px;
            }
        }

        /* Custom hover effects */
        .hover-scale:hover {
            transform: scale(1.02);
            transition: transform 0.2s ease;
        }

        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl p-4 md:p-5 text-gray-800 min-h-screen">
<div class="max-w-6xl mx-auto">

    <!-- DESKTOP NAVIGATION - Hidden on mobile -->
    <div class="hidden md:flex flex-wrap bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <a href="../../dashboard.php" class="nav-button flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-blue-600">
            🏠 DASHBOARD
        </a>
        <a href="transaksi.php?type=pembeli" class="nav-button flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-blue-600">
            👥 PEMBELI
        </a>
        <a href="transaksi.php?type=penjual" class="nav-button flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-blue-600">
            🏪 PENJUAL
        </a>
        <a href="scanner.php" class="nav-button flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-blue-600">
            📷 SCANNER
        </a>
        <a href="../../logout.php"
           class="nav-button flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-white bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 transition-all border-b-4 border-transparent hover:border-red-800">
            🚪 KELUAR
        </a>
    </div>

    <!-- MAIN CARD - Modul Kasir -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
        <div class="flex items-center gap-3 mb-2">
            <span class="text-4xl md:text-5xl">💳</span>
            <h1 class="text-3xl md:text-4xl font-bold text-blue-700">MODUL KASIR</h1>
        </div>

        <p class="text-lg md:text-xl text-gray-700 mb-8 border-l-4 border-blue-600 pl-4 py-2 bg-blue-50 rounded-r-xl">
            Halo, <span class="font-bold text-blue-800"><?php echo htmlspecialchars($user['nama_karyawan']); ?></span>! Pilih jenis transaksi:
        </p>

        <!-- Grid Menu -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-4">

            <!-- Pembeli Card -->
            <a href="transaksi.php?type=pembeli" class="block hover-lift transition-all duration-300">
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-2xl shadow-md hover:shadow-xl border-2 border-transparent hover:border-green-500 h-full">
                    <div class="text-6xl md:text-7xl mb-4 text-center">👥</div>
                    <h3 class="text-2xl md:text-3xl font-bold text-blue-700 text-center mb-3">TRANSAKSI PEMBELI</h3>
                    <p class="text-base md:text-lg text-gray-600 text-center mb-4">Harga tetap, untuk pelanggan umum</p>
                    <div class="mt-4 p-3 bg-green-200 rounded-xl text-center font-bold text-green-800">
                        ⚡ Cepat & Mudah
                    </div>
                </div>
            </a>

            <!-- Penjual Card -->
            <a href="transaksi.php?type=penjual" class="block hover-lift transition-all duration-300">
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 rounded-2xl shadow-md hover:shadow-xl border-2 border-transparent hover:border-yellow-500 h-full">
                    <div class="text-6xl md:text-7xl mb-4 text-center">🏪</div>
                    <h3 class="text-2xl md:text-3xl font-bold text-orange-600 text-center mb-3">TRANSAKSI PENJUAL</h3>
                    <p class="text-base md:text-lg text-gray-600 text-center mb-4">Harga bisa diubah, untuk reseller</p>
                    <div class="mt-4 p-3 bg-yellow-200 rounded-xl text-center font-bold text-yellow-800">
                        💰 Dengan Tawar Menawar
                    </div>
                </div>
            </a>

            <!-- Scanner Card -->
            <a href="scanner.php" class="block hover-lift transition-all duration-300">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-2xl shadow-md hover:shadow-xl border-2 border-transparent hover:border-blue-500 h-full">
                    <div class="text-6xl md:text-7xl mb-4 text-center">📷</div>
                    <h3 class="text-2xl md:text-3xl font-bold text-blue-600 text-center mb-3">SCANNER BARCODE</h3>
                    <p class="text-base md:text-lg text-gray-600 text-center mb-4">Scan barcode untuk transaksi cepat</p>
                    <div class="mt-4 p-3 bg-blue-200 rounded-xl text-center font-bold text-blue-800">
                        📱 Modern & Cepat
                    </div>
                </div>
            </a>

            <!-- Riwayat Card -->
            <a href="riwayat.php" class="block hover-lift transition-all duration-300">
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-2xl shadow-md hover:shadow-xl border-2 border-transparent hover:border-purple-500 h-full">
                    <div class="text-6xl md:text-7xl mb-4 text-center">📝</div>
                    <h3 class="text-2xl md:text-3xl font-bold text-purple-700 text-center mb-3">RIWAYAT TRANSAKSI</h3>
                    <p class="text-base md:text-lg text-gray-600 text-center mb-4">Lihat semua transaksi Anda</p>
                    <div class="mt-4 p-3 bg-purple-200 rounded-xl text-center font-bold text-purple-800">
                        📋 Cek History
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- QUICK STATS CARD -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
        <div class="flex items-center gap-3 mb-6">
            <span class="text-3xl md:text-4xl">📊</span>
            <h3 class="text-2xl md:text-3xl font-bold text-gray-800">TRANSAKSI HARI INI</h3>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">

            <!-- Total Transaksi -->
            <div class="bg-gray-50 p-5 rounded-xl text-center hover:shadow-md transition-shadow">
                <div class="text-3xl md:text-4xl font-bold text-blue-700 mb-2">
                    <?php echo $stats['total_transaksi'] ?? 0; ?>
                </div>
                <div class="text-sm md:text-base text-gray-600 font-semibold flex items-center justify-center gap-2">
                    <span class="text-xl">📋</span> Total Transaksi
                </div>
            </div>

            <!-- Total Penjualan -->
            <div class="bg-gray-50 p-5 rounded-xl text-center hover:shadow-md transition-shadow">
                <div class="text-2xl md:text-3xl font-bold text-blue-700 mb-2">
                    Rp <?php echo number_format($stats['total_penjualan'] ?? 0, 0, ',', '.'); ?>
                </div>
                <div class="text-sm md:text-base text-gray-600 font-semibold flex items-center justify-center gap-2">
                    <span class="text-xl">💰</span> Total Penjualan
                </div>
            </div>

            <!-- Pembeli -->
            <div class="bg-gray-50 p-5 rounded-xl text-center hover:shadow-md transition-shadow">
                <div class="text-3xl md:text-4xl font-bold text-blue-700 mb-2">
                    <?php echo $stats['pembeli'] ?? 0; ?>
                </div>
                <div class="text-sm md:text-base text-gray-600 font-semibold flex items-center justify-center gap-2">
                    <span class="text-xl">👥</span> Pembeli
                </div>
            </div>

            <!-- Penjual -->
            <div class="bg-gray-50 p-5 rounded-xl text-center hover:shadow-md transition-shadow">
                <div class="text-3xl md:text-4xl font-bold text-orange-600 mb-2">
                    <?php echo $stats['penjual'] ?? 0; ?>
                </div>
                <div class="text-sm md:text-base text-gray-600 font-semibold flex items-center justify-center gap-2">
                    <span class="text-xl">🏪</span> Penjual
                </div>
            </div>
        </div>

        <!-- Info tambahan -->
        <?php if (($stats['total_transaksi'] ?? 0) > 0): ?>
            <div class="mt-6 p-4 bg-blue-50 rounded-xl border-l-4 border-blue-600">
                <p class="text-base md:text-lg text-gray-700 flex items-center gap-2">
                    <span class="text-2xl">✅</span>
                    Kamu sudah melakukan <span class="font-bold text-blue-700"><?php echo $stats['total_transaksi'] ?? 0; ?></span> transaksi hari ini!
                </p>
            </div>
        <?php else: ?>
            <div class="mt-6 p-4 bg-yellow-50 rounded-xl border-l-4 border-yellow-500">
                <p class="text-base md:text-lg text-gray-700 flex items-center gap-2">
                    <span class="text-2xl">⏰</span>
                    Belum ada transaksi hari ini. Mulai transaksi sekarang!
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- MOBILE BOTTOM NAVIGATION - Visible only on mobile -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-up-lg z-50 py-2 px-4">
        <div class="flex justify-around items-center">
            <a href="../../dashboard.php" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600 transition">
                <span class="text-2xl">🏠</span>
                <span class="text-xs font-bold mt-1">Home</span>
            </a>
            <a href="transaksi.php?type=pembeli" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600 transition">
                <span class="text-2xl">👥</span>
                <span class="text-xs font-bold mt-1">Pembeli</span>
            </a>
            <a href="transaksi.php?type=penjual" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-orange-600 transition">
                <span class="text-2xl">🏪</span>
                <span class="text-xs font-bold mt-1">Penjual</span>
            </a>
            <a href="scanner.php" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600 transition">
                <span class="text-2xl">📷</span>
                <span class="text-xs font-bold mt-1">Scan</span>
            </a>
            <a href="riwayat.php" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-purple-600 transition">
                <span class="text-2xl">📝</span>
                <span class="text-xs font-bold mt-1">Riwayat</span>
            </a>
            <a href="../../logout.php" class="flex flex-col items-center px-3 py-2 text-red-600 hover:text-red-800 transition">
                <span class="text-2xl">🚪</span>
                <span class="text-xs font-bold mt-1">Keluar</span>
            </a>
        </div>
    </div>

    <!-- Mobile Bottom Spacing -->
    <div class="md:hidden h-20"></div>

    <!-- FOOTER -->
    <div class="text-center mt-8 text-gray-500 text-sm md:text-base">
        <p>© <?php echo date('Y'); ?> Sistem Kasir Botol - Modul Kasir</p>
        <p class="text-xs mt-1">Cabang: <?php echo $user['id_cabang'] ?? '-'; ?> | User: <?php echo htmlspecialchars($user['nama_karyawan'] ?? ''); ?></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Touch feedback
        document.querySelectorAll('a, button, .nav-button').forEach(el => {
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

        // Active state untuk mobile nav
        const currentPath = window.location.pathname;
        if (currentPath.includes('kasir/index.php')) {
            // Tidak ada yang aktif di mobile nav untuk halaman ini
        }
    });
</script>
</body>
</html>