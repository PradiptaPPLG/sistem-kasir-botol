<?php
// modules/gudang/stock-opname.php - VERSI TAILWIND v3
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

// Get all barang with current stock (stok terakhir)
$barang_sql = "
    SELECT 
        b.*,
        COALESCE((
            SELECT stok_sistem 
            FROM stok_gudang sg 
            WHERE sg.id_barang = b.id_barang 
              AND sg.id_cabang = ? 
            ORDER BY sg.tanggal_update DESC 
            LIMIT 1
        ), 0) as stok_sistem
    FROM barang b
    ORDER BY b.nama_barang
";

$barang_result = $db->query($barang_sql, [$cabang_id]);

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['item'] ?? [];

    foreach ($items as $id_barang => $data) {
        $stok_fisik = intval($data['stok_fisik']);

        // Get current system stock (latest)
        $current_sql = "
            SELECT stok_sistem 
            FROM stok_gudang 
            WHERE id_barang = ? AND id_cabang = ?
            ORDER BY tanggal_update DESC LIMIT 1
        ";
        $current = $db->query($current_sql, [$id_barang, $cabang_id])->fetch_assoc();
        $stok_sistem = $current ? $current['stok_sistem'] : 0;

        // Insert or update stok_gudang untuk hari ini
        $upsert_sql = "
            INSERT INTO stok_gudang (id_barang, id_cabang, stok_fisik, stok_sistem, tanggal_update)
            VALUES (?, ?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE 
                stok_fisik = VALUES(stok_fisik),
                stok_sistem = VALUES(stok_sistem)
        ";
        $db->query($upsert_sql, [$id_barang, $cabang_id, $stok_fisik, $stok_sistem]);

        // Insert ke stock_opname history
        $selisih = $stok_sistem - $stok_fisik;
        $history_sql = "
            INSERT INTO stock_opname (id_barang, id_cabang, stok_fisik, stok_sistem, selisih, tanggal)
            VALUES (?, ?, ?, ?, ?, CURDATE())
        ";
        $db->query($history_sql, [$id_barang, $cabang_id, $stok_fisik, $stok_sistem, $selisih]);
    }

    $success = "Stock opname berhasil disimpan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Stock Opname - Sistem Kasir Botol</title>

    <!-- Tailwind CSS v3 -->
    <link href="../../src/output.css" rel="stylesheet">

    <style>
        /* Custom untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            input, select, button, a, .nav-tab {
                font-size: 16px !important;
                min-height: 50px;
            }
        }

        /* Status box styles */
        .status-cocok {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .status-hilang {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .status-lebih {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        /* Hover effect */
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -10px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl p-4 md:p-5 text-gray-800">
<div class="max-w-4xl mx-auto">

    <!-- HEADER - Gradient Ungu -->
    <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white px-6 md:px-8 py-8 md:py-10 rounded-2xl shadow-xl mb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">📋 STOCK OPNAME GUDANG</h1>
        <p class="text-lg md:text-xl opacity-90">Cocokkan stok fisik dengan sistem</p>
    </div>

    <!-- NAVIGASI TAB -->
    <div class="flex flex-wrap bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <a href="index.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-purple-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-purple-600">
            📊 DASHBOARD
        </a>
        <a href="stok-masuk.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-purple-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-purple-600">
            ➕ STOK MASUK
        </a>
        <a href="stok-keluar.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-purple-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-purple-600">
            📤 STOK KELUAR
        </a>
        <a href="stock-opname.php" class="nav-tab flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-purple-600 bg-white border-b-4 border-purple-600">
            📋 STOCK OPNAME
        </a>
    </div>

    <!-- PESAN SUKSES -->
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-6 mb-6 rounded-xl border-l-8 border-green-600 text-lg md:text-xl flex items-start gap-3 shadow-md">
            <span class="text-2xl mt-1">✅</span>
            <div><?php echo $success; ?></div>
        </div>
    <?php endif; ?>

    <!-- INFO CARA OPNAME -->
    <div class="bg-blue-50 p-6 md:p-8 mb-8 rounded-xl border-l-8 border-purple-600 shadow-md">
        <div class="flex items-start gap-4 mb-4">
            <div class="text-4xl">📝</div>
            <div>
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Cara Stock Opname:</h3>
            </div>
        </div>
        <ol class="list-decimal ml-6 md:ml-10 space-y-2 text-lg md:text-xl text-gray-700">
            <li class="mb-2">Hitung fisik botol di gudang</li>
            <li class="mb-2">Masukkan jumlah fisik ke kolom <span class="font-bold text-purple-700">"STOK FISIK"</span></li>
            <li class="mb-2">Sistem otomatis bandingkan dengan stok sistem</li>
            <li class="mb-2">Jika ada selisih, muncul warning</li>
            <li class="mb-2">Klik <span class="font-bold text-green-700">"SIMPAN STOCK OPNAME"</span> untuk menyimpan data</li>
        </ol>
    </div>

    <!-- FORM OPNAME -->
    <form method="POST" action="">
        <?php
        // Simpan data barang ke array untuk digunakan di JS
        $barang_list = [];
        while ($barang = $barang_result->fetch_assoc()):
            $barang_list[] = $barang;
            $stok_sistem = $barang['stok_sistem'];
            ?>
            <!-- Item Card -->
            <div class="bg-white p-6 md:p-8 mb-6 rounded-2xl shadow-md border-l-8 border-purple-600 hover-lift transition-all duration-300">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-center">

                    <!-- Info Barang -->
                    <div class="col-span-1">
                        <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
                            <?php echo htmlspecialchars($barang['nama_barang']); ?>
                        </h3>
                        <div class="text-base md:text-lg text-gray-600">
                            <span class="font-semibold">Kode:</span> <?php echo $barang['kode_barang']; ?> <br>
                            <span class="font-semibold">Harga:</span> <?php echo formatRupiah($barang['harga_beli']); ?>
                        </div>
                    </div>

                    <!-- Stok Sistem -->
                    <div class="col-span-1">
                        <div class="bg-blue-50 p-4 md:p-5 rounded-xl text-center">
                            <div class="text-sm md:text-base text-gray-600 uppercase font-bold mb-1">STOK SISTEM</div>
                            <div id="sistem_<?php echo $barang['id_barang']; ?>"
                                 class="text-3xl md:text-4xl font-bold text-blue-700">
                                <?php echo $stok_sistem; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Stok Fisik -->
                    <div class="col-span-1">
                        <div class="text-center">
                            <label class="block text-lg md:text-xl font-bold text-gray-700 mb-2">STOK FISIK</label>
                            <input type="number"
                                   name="item[<?php echo $barang['id_barang']; ?>][stok_fisik]"
                                   id="fisik_<?php echo $barang['id_barang']; ?>"
                                   value="<?php echo $stok_sistem; ?>"
                                   min="0"
                                   oninput="hitungSelisih(<?php echo $barang['id_barang']; ?>,
                                   <?php echo $stok_sistem; ?>,
                                           this.value)"
                                   class="w-32 md:w-40 p-4 text-2xl md:text-3xl text-center border-2 border-gray-300 rounded-xl bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-200 outline-none transition-all font-bold">
                        </div>
                    </div>
                </div>

                <!-- Status Selisih -->
                <div id="status_<?php echo $barang['id_barang']; ?>"
                     class="mt-5 p-4 rounded-xl text-center text-lg md:text-xl font-bold status-cocok">
                    ✅ STOK COCOK
                </div>
            </div>
        <?php endwhile; ?>

        <!-- Tombol Submit -->
        <button type="submit"
                class="w-full py-6 px-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-purple-600 to-purple-800 rounded-xl hover:from-purple-700 hover:to-purple-900 hover:-translate-y-1 transition-all duration-300 shadow-lg mt-4">
            💾 SIMPAN STOCK OPNAME
        </button>
    </form>

    <!-- NAVIGASI BAWAH -->
    <div class="flex flex-col sm:flex-row gap-4 mt-8">
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
        <p>© <?php echo date('Y'); ?> Sistem Kasir Botol - Stock Opname Gudang</p>
    </div>
</div>

<script>
    function hitungSelisih(id, stokSistem, stokFisik) {
        stokFisik = parseInt(stokFisik) || 0;
        const selisih = stokSistem - stokFisik;
        const statusEl = document.getElementById('status_' + id);

        // Reset classes
        statusEl.classList.remove('status-cocok', 'status-hilang', 'status-lebih');

        if (selisih > 0) {
            statusEl.classList.add('status-hilang');
            statusEl.innerHTML = `⚠️ SELISIH: +${selisih} (Kemungkinan barang hilang)`;
        } else if (selisih < 0) {
            statusEl.classList.add('status-lebih');
            statusEl.innerHTML = `📈 SELISIH: ${selisih} (Stok fisik lebih banyak)`;
        } else {
            statusEl.classList.add('status-cocok');
            statusEl.innerHTML = '✅ STOK COCOK';
        }
    }

    // Inisialisasi semua status saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Touch feedback
        document.querySelectorAll('a, button, .nav-tab').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            el.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });

        // iOS zoom fix
        document.querySelectorAll('input[type="number"]').forEach(el => {
            el.style.fontSize = '16px';
        });

        // Hitung status untuk setiap barang
        <?php foreach ($barang_list as $barang): ?>
        hitungSelisih(<?php echo $barang['id_barang']; ?>,
                <?php echo $barang['stok_sistem']; ?>,
            document.getElementById('fisik_<?php echo $barang['id_barang']; ?>')?.value || 0);
        <?php endforeach; ?>
    });
</script>
</body>
</html>