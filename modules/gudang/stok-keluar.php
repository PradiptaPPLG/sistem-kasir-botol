<?php
// modules/gudang/stok-keluar.php - VERSI TAILWIND v3
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
$barang_sql = "SELECT * FROM barang ORDER BY nama_barang";
$barang_result = $db->query($barang_sql);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = intval($_POST['jumlah']);
    $keterangan = $_POST['keterangan'];
    $catatan = $_POST['catatan'] ?? '';

    // Cek stok tersedia (ambil stok terakhir)
    $stok_sql = "
        SELECT stok_sistem 
        FROM stok_gudang 
        WHERE id_barang = ? AND id_cabang = ?
        ORDER BY tanggal_update DESC LIMIT 1
    ";
    $stok_result = $db->query($stok_sql, [$id_barang, $cabang_id]);
    $stok_data = $stok_result->fetch_assoc();
    $stok_tersedia = $stok_data['stok_sistem'] ?? 0;

    if ($stok_tersedia < $jumlah) {
        $error = "Stok tidak cukup! Stok tersedia: $stok_tersedia";
    } else {
        try {
            // Insert transaksi gudang
            $sql = "
                INSERT INTO transaksi_gudang 
                (id_barang, id_cabang, id_karyawan, jenis, jumlah, keterangan, catatan)
                VALUES (?, ?, ?, 'keluar', ?, ?, ?)
            ";
            $db->insert($sql, [$id_barang, $cabang_id, $user_id, $jumlah, $keterangan, $catatan]);

            // Cek apakah sudah ada entri stok hari ini
            $check_sql = "
                SELECT id_stok_gudang FROM stok_gudang 
                WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
            ";
            $check_result = $db->query($check_sql, [$id_barang, $cabang_id]);

            if ($check_result->num_rows > 0) {
                // Update stok hari ini
                $update_sql = "
                    UPDATE stok_gudang 
                    SET stok_sistem = stok_sistem - ?,
                        stok_fisik = stok_fisik - ?
                    WHERE id_barang = ? AND id_cabang = ? AND tanggal_update = CURDATE()
                ";
                $db->query($update_sql, [$jumlah, $jumlah, $id_barang, $cabang_id]);
            } else {
                // Insert stok baru dengan nilai berkurang
                $stok_baru = $stok_tersedia - $jumlah;
                $insert_sql = "
                    INSERT INTO stok_gudang 
                    (id_barang, id_cabang, stok_sistem, stok_fisik, tanggal_update)
                    VALUES (?, ?, ?, ?, CURDATE())
                ";
                $db->insert($insert_sql, [$id_barang, $cabang_id, $stok_baru, $stok_baru]);
            }

            // Ambil nama barang untuk pesan sukses
            $nama_sql = "SELECT nama_barang FROM barang WHERE id_barang = ?";
            $nama_res = $db->query($nama_sql, [$id_barang])->fetch_assoc();
            $nama_barang = $nama_res['nama_barang'] ?? 'Barang';

            $success = "Stok keluar berhasil: $jumlah $nama_barang";

        } catch (Exception $e) {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Stok Keluar - Sistem Kasir Botol</title>

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
            box-shadow: 0 15px 30px -10px rgba(230,126,34,0.2);
        }

        /* Stok info custom colors */
        .stok-info-default {
            background-color: #fff3cd;
            color: #856404;
        }
        .stok-info-available {
            background-color: #e8f4fc;
            color: #2980b9;
        }
        .stok-info-empty {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl p-4 md:p-5 text-gray-800">
<div class="max-w-3xl mx-auto">

    <!-- HEADER - Gradient Orange -->
    <div class="bg-gradient-to-r from-orange-600 to-orange-500 text-white px-6 md:px-8 py-8 md:py-10 rounded-2xl shadow-xl mb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">📤 STOK KELUAR GUDANG</h1>
        <p class="text-lg md:text-xl opacity-90">Barang rusak, hilang, transfer, atau dipakai</p>
    </div>

    <!-- NAVIGASI TAB -->
    <div class="flex flex-wrap bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <a href="index.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-orange-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-orange-600">
            📊 DASHBOARD
        </a>
        <a href="stok-masuk.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-orange-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-orange-600">
            ➕ STOK MASUK
        </a>
        <a href="stok-keluar.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-orange-600 bg-white border-b-4 border-orange-600">
            📤 STOK KELUAR
        </a>
        <a href="stock-opname.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-orange-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-orange-600">
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

    <!-- FORM STOK KELUAR -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
        <div class="flex items-center mb-6 border-l-8 border-orange-600 pl-5">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">📤 FORM STOK KELUAR</h2>
        </div>

        <form method="POST" action="">
            <!-- Pilih Barang -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📦 PILIH BARANG
                </label>
                <select name="id_barang" id="select-barang" required
                        onchange="getStok(this.value)"
                        class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-orange-600 focus:ring-4 focus:ring-orange-200 outline-none transition-all appearance-none">
                    <option value="" class="text-lg">-- Pilih Barang --</option>
                    <?php
                    // Reset pointer
                    $barang_result = $db->query($barang_sql);
                    while ($barang = $barang_result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $barang['id_barang']; ?>" class="text-lg">
                            <?php echo htmlspecialchars($barang['nama_barang']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Stok Tersedia -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📊 STOK TERSEDIA
                </label>
                <div id="stok-info"
                     class="w-full p-5 text-lg md:text-xl font-bold rounded-xl border-2 border-yellow-400 stok-info-default">
                    Pilih barang terlebih dahulu
                </div>
            </div>

            <!-- Jumlah Keluar -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    🔢 JUMLAH KELUAR
                </label>
                <input type="number" name="jumlah" id="jumlah"
                       min="1" value="1" required
                       class="w-full p-5 text-xl md:text-2xl border-2 border-gray-300 rounded-xl bg-white focus:border-orange-600 focus:ring-4 focus:ring-orange-200 outline-none transition-all text-center font-bold">
            </div>

            <!-- Alasan Keluar -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📌 ALASAN KELUAR
                </label>
                <select name="keterangan" required
                        class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-orange-600 focus:ring-4 focus:ring-orange-200 outline-none transition-all appearance-none">
                    <option value="transfer" class="text-lg">🏢 Transfer ke Cabang</option>
                    <option value="rusak" class="text-lg">💔 Barang Rusak</option>
                    <option value="dipakai" class="text-lg">🏪 Dipakai Toko</option>
                    <option value="hilang" class="text-lg">❓ Hilang</option>
                    <option value="lainnya" class="text-lg">📝 Lainnya</option>
                </select>
            </div>

            <!-- Catatan -->
            <div class="mb-6">
                <label class="block text-lg md:text-xl font-bold text-gray-800 mb-3">
                    📝 CATATAN (Opsional)
                </label>
                <textarea name="catatan"
                          placeholder="Misal: Alasan detail, tujuan transfer, dll"
                          class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-orange-600 focus:ring-4 focus:ring-orange-200 outline-none transition-all min-h-[120px] resize-y"></textarea>
            </div>

            <!-- Tombol Submit -->
            <button type="submit"
                    class="w-full py-6 px-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-orange-600 to-orange-500 rounded-xl hover:from-orange-700 hover:to-orange-600 hover:-translate-y-1 transition-all duration-300 shadow-lg hover-lift">
                💾 SIMPAN STOK KELUAR
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
        <p>© <?php echo date('Y'); ?> Sistem Kasir Botol - Stok Keluar Gudang</p>
    </div>
</div>

<script>
    function getStok(barangId) {
        if (!barangId) {
            const stokInfo = document.getElementById('stok-info');
            stokInfo.textContent = 'Pilih barang terlebih dahulu';
            stokInfo.className = 'w-full p-5 text-lg md:text-xl font-bold rounded-xl border-2 border-yellow-400 stok-info-default';
            return;
        }

        // Tampilkan loading
        const stokInfo = document.getElementById('stok-info');
        stokInfo.textContent = '⏳ Mengambil data stok...';
        stokInfo.className = 'w-full p-5 text-lg md:text-xl font-bold rounded-xl border-2 border-blue-400 stok-info-available';

        fetch(`get_stok.php?id_barang=${barangId}`)
            .then(response => response.json())
            .then(data => {
                const stok = data.stok || 0;
                const stokInfo = document.getElementById('stok-info');
                const jumlahInput = document.getElementById('jumlah');

                if (stok <= 0) {
                    stokInfo.textContent = `❌ Stok tersedia: ${stok} unit (Tidak tersedia)`;
                    stokInfo.className = 'w-full p-5 text-lg md:text-xl font-bold rounded-xl border-2 border-red-400 stok-info-empty';
                    jumlahInput.disabled = true;
                    jumlahInput.value = 0;
                    jumlahInput.max = 0;
                } else {
                    stokInfo.textContent = `✅ Stok tersedia: ${stok} unit`;
                    stokInfo.className = 'w-full p-5 text-lg md:text-xl font-bold rounded-xl border-2 border-blue-400 stok-info-available';
                    jumlahInput.disabled = false;
                    jumlahInput.max = stok;
                    if (parseInt(jumlahInput.value) > stok) {
                        jumlahInput.value = stok;
                    }
                }
            })
            .catch(err => {
                const stokInfo = document.getElementById('stok-info');
                stokInfo.textContent = '❌ Gagal mengambil stok';
                stokInfo.className = 'w-full p-5 text-lg md:text-xl font-bold rounded-xl border-2 border-red-400 stok-info-empty';
                console.error('Error:', err);
            });
    }

    // Inisialisasi saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Touch feedback
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

        // Cek apakah ada parameter stok di URL
        const urlParams = new URLSearchParams(window.location.search);
        const barangId = urlParams.get('barang');
        if (barangId) {
            const select = document.getElementById('select-barang');
            select.value = barangId;
            getStok(barangId);
        }

        // Validasi jumlah tidak melebihi stok
        const jumlahInput = document.getElementById('jumlah');
        if (jumlahInput) {
            jumlahInput.addEventListener('input', function() {
                const max = parseInt(this.max);
                let value = parseInt(this.value);
                if (value > max) {
                    this.value = max;
                    alert(`Stok tersedia hanya ${max} unit`);
                }
            });
        }
    });
</script>
</body>
</html>