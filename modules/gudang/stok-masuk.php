<?php
// modules/gudang/stok-masuk.php - VERSI TAILWIND v3
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
$user_id = $_SESSION['id_karyawan'];
$cabang_id = $_SESSION['id_cabang'];

// Get barang list
$barang_result = $db->query("SELECT * FROM barang ORDER BY nama_barang");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = intval($_POST['jumlah']);
    $keterangan = $_POST['keterangan'];
    $catatan = $_POST['catatan'] ?? '';

    if ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0!';
    } else {
        try {
            // 1. Insert ke transaksi_gudang
            $sql_transaksi = "
                INSERT INTO transaksi_gudang 
                (id_barang, id_cabang, id_karyawan, jenis, jumlah, keterangan, catatan)
                VALUES (?, ?, ?, 'masuk', ?, ?, ?)
            ";
            $db->insert($sql_transaksi, [$id_barang, $cabang_id, $user_id, $jumlah, $keterangan, $catatan]);

            // 2. Cek apakah sudah ada stok hari ini
            $check_sql = "
                SELECT id_stok_gudang FROM stok_gudang 
                WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
            ";
            $check_result = $db->query($check_sql, [$id_barang, $cabang_id]);

            if ($check_result->num_rows > 0) {
                // Update stok yang sudah ada
                $update_sql = "
                    UPDATE stok_gudang 
                    SET stok_sistem = stok_sistem + ?,
                        stok_fisik = stok_fisik + ?
                    WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
                ";
                $db->query($update_sql, [$jumlah, $jumlah, $id_barang, $cabang_id]);
            } else {
                // Insert stok baru
                $insert_sql = "
                    INSERT INTO stok_gudang 
                    (id_barang, id_cabang, stok_sistem, stok_fisik, tanggal_update)
                    VALUES (?, ?, ?, ?, CURDATE())
                ";
                $db->insert($insert_sql, [$id_barang, $cabang_id, $jumlah, $jumlah]);
            }

            // 3. Get nama barang untuk pesan sukses
            $nama_barang_sql = "SELECT nama_barang FROM barang WHERE id_barang = ?";
            $nama_result = $db->query($nama_barang_sql, [$id_barang]);
            $nama_barang = $nama_result->fetch_assoc()['nama_barang'] ?? 'Barang';
            $success = "Stok <strong>$nama_barang</strong> berhasil ditambahkan: <strong>$jumlah</strong> barang";

        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = "Barang sudah ditambahkan hari ini. Silakan edit stok yang sudah ada.";
            } elseif (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $error = "Barang tidak ditemukan dalam sistem.";
            } else {
                $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Tambah Stok - Sistem Kasir Botol</title>

    <!-- Tailwind CSS v3 -->
    <link href="../../src/output.css" rel="stylesheet">

    <style>
        /* Custom untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            input, select, button, textarea, a, .nav-tab {
                font-size: 16px !important;
                min-height: 50px;
            }
        }

        /* Custom animation */
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -10px rgba(52,152,219,0.2);
        }

        /* Custom untuk info box */
        .info-box-custom {
            background-color: #e8f4fc;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl p-4 md:p-5 text-gray-800">
<div class="max-w-3xl mx-auto">

    <!-- HEADER - Gradient Biru -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white px-6 md:px-8 py-8 md:py-10 rounded-2xl shadow-xl mb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">➕ TAMBAH STOK GUDANG</h1>
        <p class="text-lg md:text-xl opacity-90">Input barang baru ke sistem</p>
    </div>

    <!-- NAVIGASI TAB -->
    <div class="flex flex-wrap bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <a href="index.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-blue-600">
            📊 DASHBOARD
        </a>
        <a href="stok-masuk.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-blue-600 bg-white border-b-4 border-blue-600">
            ➕ STOK MASUK
        </a>
        <a href="stok-keluar.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-blue-600">
            📤 STOK KELUAR
        </a>
        <a href="stock-opname.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-blue-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-blue-600">
            📋 STOCK OPNAME
        </a>
    </div>

    <!-- PESAN SUKSES / ERROR -->
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-6 mb-6 rounded-xl border-l-8 border-green-600 text-lg md:text-xl flex items-start gap-3 shadow-md">
            <span class="text-2xl mt-1">✅</span>
            <div><?php echo $success; ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-800 p-6 mb-6 rounded-xl border-l-8 border-red-600 text-lg md:text-xl flex items-start gap-3 shadow-md">
            <span class="text-2xl mt-1">❌</span>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <!-- INFO CARA TAMBAH STOK -->
    <div class="bg-blue-50 p-6 md:p-8 mb-8 rounded-xl border-l-8 border-blue-600 shadow-md info-box-custom">
        <div class="flex items-start gap-4 mb-4">
            <div class="text-4xl">📝</div>
            <div>
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Cara Menambah Stok:</h3>
            </div>
        </div>
        <ul class="list-decimal ml-6 md:ml-10 space-y-2 text-lg md:text-xl text-gray-700">
            <li class="mb-2">Pilih barang yang ingin ditambahkan</li>
            <li class="mb-2">Masukkan jumlah barang</li>
            <li class="mb-2">Pilih keterangan (pembelian/transfer/retur)</li>
            <li class="mb-2">Tambahkan catatan jika perlu (no invoice, supplier, dll)</li>
            <li class="mb-2">Klik <span class="font-bold text-green-700">"SIMPAN STOK MASUK"</span></li>
        </ul>
        <div class="mt-5 p-4 bg-yellow-50 rounded-xl border-l-4 border-yellow-500 text-gray-700">
            <p class="text-base md:text-lg font-semibold flex items-center gap-2">
                <span class="text-2xl">📌</span>
                <strong>Catatan:</strong> Jika barang sudah ditambahkan hari ini, stok akan otomatis ditambah ke entri yang sama.
            </p>
        </div>
    </div>

    <!-- FORM TAMBAH STOK -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
        <div class="flex items-center mb-6 border-l-8 border-blue-600 pl-5">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">➕ FORM TAMBAH STOK</h2>
        </div>

        <form method="POST" action="">
            <!-- Pilih Barang -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📦 PILIH BARANG
                </label>
                <select name="id_barang" required
                        class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-200 outline-none transition-all appearance-none">
                    <option value="" class="text-lg">-- Pilih Barang --</option>
                    <?php
                    // Reset pointer
                    $barang_result = $db->query("SELECT * FROM barang ORDER BY nama_barang");
                    while ($barang = $barang_result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $barang['id_barang']; ?>" class="text-lg">
                            <?php echo htmlspecialchars($barang['nama_barang']); ?>
                            (Rp <?php echo number_format($barang['harga_beli'], 0, ',', '.'); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Jumlah Barang -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    🔢 JUMLAH BARANG
                </label>
                <input type="number" name="jumlah"
                       min="1" value="1" required
                       class="w-full p-5 text-xl md:text-2xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-200 outline-none transition-all text-center font-bold">
            </div>

            <!-- Keterangan -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📌 KETERANGAN
                </label>
                <select name="keterangan" required
                        class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-200 outline-none transition-all appearance-none">
                    <option value="pembelian" class="text-lg">💰 Pembelian Baru</option>
                    <option value="transfer" class="text-lg">🏢 Transfer dari Cabang</option>
                    <option value="retur" class="text-lg">🔄 Retur dari Pelanggan</option>
                    <option value="lainnya" class="text-lg">📝 Lainnya</option>
                </select>
            </div>

            <!-- Catatan -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📝 CATATAN (Opsional)
                </label>
                <textarea name="catatan"
                          placeholder="Contoh: No. Invoice: INV-001, Supplier: PT ABC, dll"
                          class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-200 outline-none transition-all min-h-[120px] resize-y"></textarea>
            </div>

            <!-- Tombol Submit -->
            <button type="submit"
                    class="w-full py-6 px-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-green-600 to-green-500 rounded-xl hover:from-green-700 hover:to-green-600 hover:-translate-y-1 transition-all duration-300 shadow-lg hover-lift">
                💾 SIMPAN STOK MASUK
            </button>
        </form>
    </div>

    <!-- NAVIGASI BAWAH -->
    <div class="flex flex-col sm:flex-row gap-4 mt-6">
        <a href="index.php"
           class="flex-1 py-5 px-4 text-center text-lg md:text-xl font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl transition-all duration-300 shadow-md hover:-translate-y-1">
            📦 Kembali ke Gudang
        </a>
        <a href="../../dashboard.php"
           class="flex-1 py-5 px-4 text-center text-lg md:text-xl font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl transition-all duration-300 shadow-md hover:-translate-y-1">
            🏠 Dashboard
        </a>
        <a href="../../logout.php"
           class="flex-1 py-5 px-4 text-center text-lg md:text-xl font-bold text-white bg-gradient-to-r from-red-500 to-red-600 rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-300 shadow-md hover:-translate-y-1">
            🚪 Keluar
        </a>
    </div>

    <!-- FOOTER -->
    <div class="text-center mt-8 text-gray-500 text-base md:text-lg">
        <p>© <?php echo date('Y'); ?> Sistem Kasir Botol - Tambah Stok Gudang</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Touch feedback untuk semua elemen klik
        document.querySelectorAll('a, button, .nav-tab, select, input[type="submit"]').forEach(el => {
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

        // Auto-focus select jika ada parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('focus') === 'select') {
            setTimeout(() => {
                document.querySelector('select[name="id_barang"]')?.focus();
            }, 300);
        }

        // Validasi input number tidak boleh kurang dari 1
        const jumlahInput = document.querySelector('input[name="jumlah"]');
        if (jumlahInput) {
            jumlahInput.addEventListener('input', function() {
                let value = parseInt(this.value);
                if (value < 1 || isNaN(value)) {
                    this.value = 1;
                }
            });
        }
    });

    // Smooth scroll untuk alert
    if (window.location.hash === '#success' || window.location.hash === '#error') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>
</body>
</html>