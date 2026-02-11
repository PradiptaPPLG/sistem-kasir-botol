<?php
// modules/admin/tambah_stok.php - VERSI TAILWIND v3
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Tambah Stok - Admin</title>

    <!-- Tailwind CSS v3 -->
    <link href="../../src/output.css" rel="stylesheet">

    <style>
        /* Custom untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            input, select, button, textarea, a {
                font-size: 16px !important;
                min-height: 50px;
            }
        }

        /* Custom untuk transition smooth */
        .transition-custom {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl p-4 md:p-5 text-gray-800">
<div class="max-w-3xl mx-auto">

    <!-- HEADER -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 text-white px-6 md:px-8 py-8 md:py-10 rounded-2xl shadow-xl mb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">➕ TAMBAH STOK (ADMIN)</h1>
        <p class="text-lg md:text-xl opacity-90">Input stok baru ke gudang – khusus admin</p>
    </div>

    <!-- NAVIGASI DESKTOP -->
    <div class="flex flex-wrap bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <a href="../../dashboard.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-slate-800 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-slate-800">
            🏠 DASHBOARD
        </a>
        <a href="../gudang/" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-slate-800 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-slate-800">
            📦 GUDANG
        </a>
        <a href="../kasir/" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-slate-800 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-slate-800">
            💳 KASIR
        </a>
        <a href="laporan.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-slate-800 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-slate-800">
            📊 LAPORAN
        </a>
        <a href="settings.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-slate-800 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-slate-800">
            ⚙️ SETTINGS
        </a>
        <a href="tambah_stok.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-slate-800 bg-gray-50 border-b-4 border-slate-800">
            ➕ TAMBAH STOK
        </a>
        <a href="../../logout.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-red-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-red-600">
            🚪 KELUAR
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

    <!-- FORM TAMBAH STOK -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
        <div class="flex items-center mb-6 border-l-8 border-slate-800 pl-5">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">➕ FORM TAMBAH STOK</h2>
        </div>

        <form method="POST" action="">
            <!-- Pilih Barang -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📦 Pilih Barang
                </label>
                <select name="id_barang" required
                        class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all appearance-none">
                    <option value="" class="text-lg">-- Pilih Barang --</option>
                    <?php while ($b = $barang->fetch_assoc()): ?>
                        <option value="<?php echo $b['id_barang']; ?>" class="text-lg">
                            <?php echo htmlspecialchars($b['nama_barang']); ?>
                            (<?php echo $b['kode_barang']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Jumlah Stok -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    🔢 Jumlah Stok Ditambah
                </label>
                <input type="number" name="jumlah" min="1" value="1" required
                       class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all text-center"
                       style="text-align: center;">
            </div>

            <!-- Keterangan -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📌 Keterangan
                </label>
                <select name="keterangan"
                        class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all appearance-none">
                    <option value="pembelian" class="text-lg">Pembelian Baru</option>
                    <option value="transfer" class="text-lg">Transfer dari Cabang</option>
                    <option value="retur" class="text-lg">Retur Pelanggan</option>
                    <option value="lainnya" class="text-lg">Lainnya</option>
                </select>
            </div>

            <!-- Catatan -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📝 Catatan (Opsional)
                </label>
                <textarea name="catatan"
                          placeholder="Contoh: Invoice #INV-001, Supplier PT ABC"
                          class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all min-h-[120px] resize-y"></textarea>
            </div>

            <!-- Tombol Submit -->
            <button type="submit"
                    class="w-full py-6 px-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-green-600 to-green-500 rounded-xl hover:from-green-700 hover:to-green-600 hover:-translate-y-1 transition-all duration-300 shadow-lg">
                💾 TAMBAH STOK
            </button>
        </form>
    </div>

    <!-- NAVIGASI BAWAH -->
    <div class="flex flex-col sm:flex-row gap-4 mt-6">
        <a href="settings.php"
           class="flex-1 py-5 px-4 text-center text-lg md:text-xl font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl transition-all duration-300 shadow-md hover:-translate-y-1">
            ⚙️ Kembali ke Pengaturan
        </a>
        <a href="../../dashboard.php"
           class="flex-1 py-5 px-4 text-center text-lg md:text-xl font-bold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 shadow-md hover:-translate-y-1">
            🏠 Dashboard
        </a>
    </div>

    <!-- FOOTER -->
    <div class="text-center mt-8 text-gray-500 text-base md:text-lg">
        <p>© <?php echo date('Y'); ?> Sistem Kasir Botol - Tambah Stok Admin</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Touch feedback
        document.querySelectorAll('button, a, select, input[type="submit"]').forEach(el => {
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

        // Auto focus select untuk mobile
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('focus') === 'select') {
            setTimeout(() => {
                document.querySelector('select[name="id_barang"]')?.focus();
            }, 300);
        }
    });

    // Smooth scroll untuk alert
    if (window.location.hash === '#success' || window.location.hash === '#error') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>
</body>
</html>