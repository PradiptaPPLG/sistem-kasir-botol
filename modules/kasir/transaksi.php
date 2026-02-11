<?php
// modules/kasir/transaksi.php - VERSI TAILWIND v3
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

$type = $_GET['type'] ?? 'pembeli';

// Get barang list dengan stok tersedia (hanya ambil stok terakhir per barang)
$barang_sql = "
    SELECT b.*, 
           COALESCE(sg.stok_sistem, 0) as stok_tersedia 
    FROM barang b
    LEFT JOIN (
        SELECT id_barang, id_cabang, stok_sistem,
               ROW_NUMBER() OVER (PARTITION BY id_barang, id_cabang ORDER BY tanggal_update DESC) as rn
        FROM stok_gudang
        WHERE id_cabang = ?
    ) sg ON b.id_barang = sg.id_barang AND sg.rn = 1
    WHERE COALESCE(sg.stok_sistem, 0) > 0
    ORDER BY b.nama_barang
";

$barang_result = $db->query($barang_sql, [$cabang_id]);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = intval($_POST['jumlah']);
    $jenis_pembeli = $_POST['jenis_pembeli'];

    // Validasi
    if ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0!';
    } else {
        // 1. Cek stok tersedia (ambil stok terakhir)
        $stok_sql = "
            SELECT stok_sistem 
            FROM stok_gudang 
            WHERE id_barang = ? 
              AND id_cabang = ?
            ORDER BY tanggal_update DESC 
            LIMIT 1
        ";
        $stok_result = $db->query($stok_sql, [$id_barang, $cabang_id]);
        $stok_data = $stok_result->fetch_assoc();
        $stok_available = $stok_data['stok_sistem'] ?? 0;

        if ($stok_available < $jumlah) {
            $error = "Stok tidak cukup! Tersedia: $stok_available";
        } else {
            // 2. Get harga barang
            $barang_sql = "SELECT * FROM barang WHERE id_barang = ?";
            $barang = $db->query($barang_sql, [$id_barang])->fetch_assoc();

            if ($barang) {
                // Hitung harga
                if ($jenis_pembeli == 'pembeli') {
                    $harga_satuan = $barang['harga_beli'];
                    $selisih_keuntungan = 0;
                } else {
                    $harga_satuan = $_POST['harga_jual'] ?? $barang['harga_jual'];
                    $selisih_keuntungan = $harga_satuan - $barang['harga_beli'];
                }

                $total_harga = $jumlah * $harga_satuan;

                try {
                    // Mulai transaction
                    $db->begin_transaction();

                    // 3. Insert transaksi kasir
                    $sql_transaksi = "
                        INSERT INTO transaksi_kasir 
                        (id_barang, id_cabang, id_karyawan, jenis_pembeli, jumlah, 
                         harga_satuan, total_harga, selisih_keuntungan, tanggal_transaksi)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                    ";

                    $db->insert($sql_transaksi, [
                            $id_barang, $cabang_id, $user_id, $jenis_pembeli,
                            $jumlah, $harga_satuan, $total_harga, $selisih_keuntungan
                    ]);

                    // 4. Cek apakah sudah ada entry untuk hari ini
                    $cek_hari_ini_sql = "
                        SELECT COUNT(*) as count 
                        FROM stok_gudang 
                        WHERE id_barang = ? 
                          AND id_cabang = ? 
                          AND DATE(tanggal_update) = CURDATE()
                    ";
                    $cek_result = $db->query($cek_hari_ini_sql, [$id_barang, $cabang_id]);
                    $cek_data = $cek_result->fetch_assoc();

                    if ($cek_data['count'] > 0) {
                        // Update stok hari ini
                        $sql_kurangi_stok = "
                            UPDATE stok_gudang 
                            SET stok_sistem = stok_sistem - ?
                            WHERE id_barang = ? 
                              AND id_cabang = ? 
                              AND DATE(tanggal_update) = CURDATE()
                        ";
                        $db->query($sql_kurangi_stok, [$jumlah, $id_barang, $cabang_id]);
                    } else {
                        // Buat entry baru untuk hari ini dengan stok yang sudah dikurangi
                        // Ambil stok terakhir
                        $stok_terakhir_sql = "
                            SELECT stok_sistem 
                            FROM stok_gudang 
                            WHERE id_barang = ? 
                              AND id_cabang = ?
                            ORDER BY tanggal_update DESC 
                            LIMIT 1
                        ";
                        $stok_terakhir_result = $db->query($stok_terakhir_sql, [$id_barang, $cabang_id]);
                        $stok_terakhir_data = $stok_terakhir_result->fetch_assoc();
                        $stok_terakhir = $stok_terakhir_data['stok_sistem'] ?? 0;

                        $stok_baru = $stok_terakhir - $jumlah;

                        // Insert record baru untuk hari ini
                        $sql_insert_stok = "
                            INSERT INTO stok_gudang 
                            (id_barang, id_cabang, stok_sistem, stok_fisik, tanggal_update)
                            VALUES (?, ?, ?, ?, CURDATE())
                        ";
                        $db->insert($sql_insert_stok, [
                                $id_barang, $cabang_id, $stok_baru, $stok_baru
                        ]);
                    }

                    // Commit transaction
                    $db->commit();

                    $success = "Transaksi berhasil! Total: " . formatRupiah($total_harga);

                } catch (Exception $e) {
                    $db->rollback();
                    // Tampilkan error yang lebih user-friendly
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = "Data sudah ada untuk hari ini. Silakan refresh halaman.";
                    } else {
                        $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
                    }
                }
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
    <title>Kasir - Sistem Kasir Botol</title>

    <!-- Tailwind CSS v3 -->
    <link href="../../src/output.css" rel="stylesheet">

    <style>
        /* Custom untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            input, select, button, a, .tab {
                font-size: 16px !important;
                min-height: 50px;
            }
        }

        /* Custom animations */
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
        }

        /* Stok info */
        .stok-info-custom {
            background-color: #e8f4fc;
            border-left: 4px solid #3498db;
        }

        /* Harga info */
        .harga-info-custom {
            background-color: #fff3cd;
            border-left: 4px solid #f39c12;
        }

        /* Total info */
        .total-info-custom {
            background-color: #d4edda;
            border: 3px solid #28a745;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl text-gray-800 min-h-screen">

<!-- HEADER -->
<div class="bg-gradient-to-r from-slate-800 to-blue-900 text-white px-4 md:px-6 py-6 md:py-8 shadow-lg text-center">
    <h1 class="text-3xl md:text-4xl font-bold mb-2">
        <?php echo $type == 'pembeli' ? '👥 TRANSAKSI PEMBELI' : '🏪 TRANSAKSI PENJUAL'; ?>
    </h1>
    <p class="text-lg md:text-xl opacity-90">Sistem Kasir Botol</p>
</div>

<!-- TABS -->
<div class="flex bg-white border-b-2 border-gray-200">
    <button onclick="window.location.href='?type=pembeli'"
            class="tab flex-1 py-5 md:py-6 text-center text-xl md:text-2xl font-bold transition-all duration-300
                <?php echo $type == 'pembeli'
                    ? 'bg-white text-blue-600 border-b-4 border-blue-600'
                    : 'bg-gray-100 text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
        👥 PEMBELI
    </button>
    <button onclick="window.location.href='?type=penjual'"
            class="tab flex-1 py-5 md:py-6 text-center text-xl md:text-2xl font-bold transition-all duration-300
                <?php echo $type == 'penjual'
                    ? 'bg-white text-blue-600 border-b-4 border-blue-600'
                    : 'bg-gray-100 text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
        🏪 PENJUAL
    </button>
</div>

<!-- MAIN CONTENT -->
<div class="max-w-2xl mx-auto px-4 md:px-5 py-6 md:py-8">

    <!-- ALERT SUKSES / ERROR -->
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-5 md:p-6 mb-6 rounded-xl border-l-8 border-green-600 text-lg md:text-xl flex items-start gap-3 shadow-md">
            <span class="text-2xl mt-1">✅</span>
            <div><?php echo $success; ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-800 p-5 md:p-6 mb-6 rounded-xl border-l-8 border-red-600 text-lg md:text-xl flex items-start gap-3 shadow-md">
            <span class="text-2xl mt-1">❌</span>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <!-- FORM CONTAINER -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl">
        <form method="POST" action="" id="transaksi-form">
            <input type="hidden" name="jenis_pembeli" value="<?php echo $type; ?>">

            <!-- PILIH BARANG -->
            <div class="mb-6">
                <label class="block text-xl md:text-2xl font-bold text-gray-800 mb-3">
                    📦 PILIH BARANG
                </label>
                <select name="id_barang" id="select-barang" required
                        onchange="updateInfo()"
                        class="w-full p-5 text-lg md:text-xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-200 outline-none transition-all appearance-none">
                    <option value="" class="text-lg">-- Pilih Barang yang Tersedia --</option>
                    <?php
                    // Reset pointer
                    $barang_result = $db->query($barang_sql, [$cabang_id]);
                    while ($barang = $barang_result->fetch_assoc()):
                        $stok = $barang['stok_tersedia'] ?? 0;
                        if ($stok > 0):
                            ?>
                            <option value="<?php echo $barang['id_barang']; ?>"
                                    data-harga-beli="<?php echo $barang['harga_beli']; ?>"
                                    data-harga-jual="<?php echo $barang['harga_jual']; ?>"
                                    data-stok="<?php echo $stok; ?>"
                                    data-nama="<?php echo htmlspecialchars($barang['nama_barang']); ?>"
                                    class="text-lg">
                                <?php echo htmlspecialchars($barang['nama_barang']); ?>
                                (Stok: <?php echo $stok; ?>) -
                                Rp <?php echo number_format($barang['harga_beli'], 0, ',', '.'); ?>
                            </option>
                        <?php
                        endif;
                    endwhile;
                    ?>
                </select>

                <!-- Stok Display -->
                <div id="stok-display" class="hidden mt-4 p-4 rounded-xl stok-info-custom text-center">
                    <p class="text-lg md:text-xl font-bold text-blue-800">
                        📊 Stok tersedia: <span id="stok-value" class="text-blue-900">0</span> unit
                    </p>
                </div>
            </div>

            <!-- JUMLAH -->
            <div class="mb-6">
                <label class="block text-xl md:text-2xl font-bold text-gray-800 mb-3">
                    🔢 JUMLAH
                </label>
                <input type="number"
                       name="jumlah"
                       id="input-jumlah"
                       min="1"
                       value="1"
                       required
                       oninput="hitungTotal()"
                       class="w-full p-5 text-2xl md:text-3xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-200 outline-none transition-all text-center font-bold">
            </div>

            <!-- HARGA JUAL (Hanya untuk Penjual) -->
            <?php if ($type == 'penjual'): ?>
                <div class="mb-6">
                    <label class="block text-xl md:text-2xl font-bold text-gray-800 mb-3">
                        💰 HARGA JUAL
                    </label>
                    <input type="number"
                           name="harga_jual"
                           id="input-harga-jual"
                           min="0"
                           required
                           oninput="hitungTotal()"
                           class="w-full p-5 text-xl md:text-2xl border-2 border-gray-300 rounded-xl bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-200 outline-none transition-all text-center font-bold">
                </div>

                <!-- Info Keuntungan -->
                <div id="keuntungan-info" class="hidden mb-6 p-5 rounded-xl harga-info-custom">
                    <p class="text-xl md:text-2xl font-bold text-yellow-800 mb-3">📊 PERHITUNGAN:</p>
                    <div class="space-y-2 text-lg md:text-xl text-gray-700">
                        <p>💰 Harga Beli: <span id="harga-beli-text" class="font-bold">Rp 0</span></p>
                        <p>💵 Harga Jual: <span id="harga-jual-text" class="font-bold">Rp 0</span></p>
                        <p>📈 Keuntungan per botol: <span id="keuntungan-per-text" class="font-bold text-green-700">Rp 0</span></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TOTAL TRANSAKSI -->
            <div id="total-info" class="hidden mb-6 p-5 md:p-6 rounded-xl total-info-custom text-center">
                <p class="text-xl md:text-2xl font-bold text-green-800 mb-2">💳 TOTAL TRANSAKSI:</p>
                <p id="total-text" class="text-3xl md:text-4xl font-bold text-green-700">Rp 0</p>
            </div>

            <!-- BUTTON SUBMIT -->
            <button type="submit"
                    class="w-full py-6 px-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl hover:from-blue-700 hover:to-blue-900 hover:-translate-y-1 transition-all duration-300 shadow-lg hover-lift">
                💾 PROSES TRANSAKSI
            </button>
        </form>

        <!-- NAVIGASI BAWAH -->
        <div class="flex flex-col sm:flex-row gap-3 mt-6">
            <a href="../../dashboard.php"
               class="flex-1 py-4 px-4 text-center text-lg md:text-xl font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl transition-all duration-300 shadow-md hover:-translate-y-1">
                🏠 Dashboard
            </a>
            <a href="index.php"
               class="flex-1 py-4 px-4 text-center text-lg md:text-xl font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl transition-all duration-300 shadow-md hover:-translate-y-1">
                💳 Menu Kasir
            </a>
            <?php if ($barang_result->num_rows == 0): ?>
                <a href="../gudang/stok-masuk.php"
                   class="flex-1 py-4 px-4 text-center text-lg md:text-xl font-bold text-gray-800 bg-yellow-400 hover:bg-yellow-500 rounded-xl transition-all duration-300 shadow-md hover:-translate-y-1">
                    ⚠️ Tambah Stok Dulu
                </a>
            <?php endif; ?>
        </div>

        <!-- Info jika stok kosong -->
        <?php if ($barang_result->num_rows == 0): ?>
            <div class="mt-6 p-5 bg-yellow-50 rounded-xl border-l-8 border-yellow-500">
                <p class="text-lg md:text-xl text-yellow-800 flex items-center gap-3">
                    <span class="text-3xl">📦</span>
                    <span class="font-bold">Tidak ada barang dengan stok tersedia!</span>
                </p>
                <p class="text-base md:text-lg text-gray-700 mt-2 ml-2">
                    Silakan tambah stok terlebih dahulu di menu Gudang.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    let hargaBeli = 0;
    let hargaJual = 0;
    let stokTersedia = 0;

    function updateInfo() {
        const select = document.getElementById('select-barang');
        const option = select.options[select.selectedIndex];

        if (option.value) {
            hargaBeli = parseFloat(option.dataset.hargaBeli) || 0;
            hargaJual = parseFloat(option.dataset.hargaJual) || 0;
            stokTersedia = parseInt(option.dataset.stok) || 0;

            // Tampilkan stok
            document.getElementById('stok-display').classList.remove('hidden');
            document.getElementById('stok-value').textContent = stokTersedia;

            // Set max jumlah
            const inputJumlah = document.getElementById('input-jumlah');
            inputJumlah.max = stokTersedia;

            <?php if ($type == 'penjual'): ?>
            // Set default harga jual
            document.getElementById('input-harga-jual').value = hargaJual;

            // Tampilkan info keuntungan
            document.getElementById('keuntungan-info').classList.remove('hidden');
            document.getElementById('harga-beli-text').textContent = formatRupiah(hargaBeli);
            document.getElementById('harga-jual-text').textContent = formatRupiah(hargaJual);

            // Hitung keuntungan
            const keuntungan = hargaJual - hargaBeli;
            document.getElementById('keuntungan-per-text').textContent = formatRupiah(keuntungan);
            <?php endif; ?>

            hitungTotal();
        } else {
            document.getElementById('stok-display').classList.add('hidden');
            <?php if ($type == 'penjual'): ?>
            document.getElementById('keuntungan-info').classList.add('hidden');
            <?php endif; ?>
            document.getElementById('total-info').classList.add('hidden');
        }
    }

    function hitungTotal() {
        const jumlah = parseInt(document.getElementById('input-jumlah').value) || 0;
        let total = 0;

        // Validasi jumlah tidak melebihi stok
        if (jumlah > stokTersedia) {
            document.getElementById('input-jumlah').value = stokTersedia;
            alert(`⚠️ Jumlah melebihi stok tersedia! Maksimal: ${stokTersedia}`);
            return;
        }

        <?php if ($type == 'pembeli'): ?>
        // Pembeli: pakai harga beli
        total = jumlah * hargaBeli;
        document.getElementById('total-text').textContent = formatRupiah(total);
        document.getElementById('total-info').classList.remove('hidden');
        <?php else: ?>
        // Penjual: pakai harga jual input
        const hargaJualInput = parseFloat(document.getElementById('input-harga-jual').value) || 0;
        total = jumlah * hargaJualInput;
        const keuntungan = (hargaJualInput - hargaBeli) * jumlah;

        document.getElementById('harga-jual-text').textContent = formatRupiah(hargaJualInput);
        document.getElementById('keuntungan-per-text').textContent = formatRupiah(hargaJualInput - hargaBeli);
        document.getElementById('total-text').innerHTML = formatRupiah(total) +
            ` <span class="text-lg md:text-xl text-green-700">(Keuntungan: ${formatRupiah(keuntungan)})</span>`;
        document.getElementById('total-info').classList.remove('hidden');
        <?php endif; ?>
    }

    function formatRupiah(angka) {
        if (!angka || isNaN(angka)) return 'Rp 0';
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Inisialisasi
    document.addEventListener('DOMContentLoaded', function() {
        const selectBarang = document.getElementById('select-barang');
        if (selectBarang.value) {
            updateInfo();
        }

        // Auto-focus
        selectBarang.focus();

        // Touch feedback
        document.querySelectorAll('button, a, .tab, select, input[type="submit"]').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            el.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });

        // iOS zoom fix
        document.querySelectorAll('input, select').forEach(el => {
            if (el) el.style.fontSize = '16px';
        });

        // Validasi input jumlah
        const inputJumlah = document.getElementById('input-jumlah');
        if (inputJumlah) {
            inputJumlah.addEventListener('input', function() {
                let value = parseInt(this.value);
                if (value < 1 || isNaN(value)) {
                    this.value = 1;
                }
            });
        }
    });
</script>
</body>
</html>