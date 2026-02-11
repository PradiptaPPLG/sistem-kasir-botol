<?php
// modules/admin/settings.php - VERSI TAILWIND v3
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

$success = '';
$error = '';

// ========== TAMBAH BARANG ==========
if (isset($_POST['action']) && $_POST['action'] === 'add_barang') {
    $kode_barang = trim($_POST['kode_barang']);
    $nama_barang = trim($_POST['nama_barang']);
    $satuan = $_POST['satuan'];
    $harga_beli = (float) $_POST['harga_beli'];
    $harga_jual = (float) $_POST['harga_jual'];
    $stok_minimal = (int) ($_POST['stok_minimal'] ?? 10);

    if (empty($kode_barang) || empty($nama_barang) || $harga_beli <= 0 || $harga_jual <= 0) {
        $error = 'Semua field harus diisi dengan benar!';
    } else {
        try {
            $sql = "INSERT INTO barang (kode_barang, nama_barang, satuan, harga_beli, harga_jual, stok_minimal)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $db->insert($sql, [$kode_barang, $nama_barang, $satuan, $harga_beli, $harga_jual, $stok_minimal]);
            $success = '✅ Barang berhasil ditambahkan!';
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = '❌ Kode barang sudah digunakan!';
            } else {
                $error = '❌ Gagal menambah barang. Coba lagi.';
            }
        }
    }
}

// ========== HAPUS BARANG ==========
if (isset($_GET['delete'])) {
    $id_barang = (int) $_GET['delete'];
    try {
        $db->query("DELETE FROM barang WHERE id_barang = ?", [$id_barang]);
        $success = '✅ Barang berhasil dihapus!';
    } catch (Exception $e) {
        $error = '❌ Tidak dapat menghapus barang ini.';
    }
}

// ========== AMBIL DAFTAR BARANG ==========
$barang_list = $db->query("SELECT * FROM barang ORDER BY id_barang DESC");

// ========== AMBIL DAFTAR KARYAWAN ==========
$karyawan_list = $db->query("SELECT * FROM karyawan WHERE id_cabang = ? ORDER BY nama_karyawan", [$cabang_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Pengaturan Admin - Sistem Kasir Botol</title>

    <!-- Tailwind CSS v3 -->
    <link href="../../src/output.css" rel="stylesheet">

    <style>
        /* Custom untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            input, select, button, a, .tab-btn {
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

        /* Tab transitions */
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-base md:text-xl p-4 md:p-5 text-gray-800">
<div class="max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="bg-gradient-to-r from-slate-700 to-slate-900 text-white px-6 md:px-8 py-8 md:py-10 rounded-2xl shadow-xl mb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">⚙️ PENGATURAN ADMIN</h1>
        <p class="text-lg md:text-xl opacity-90">Kelola Barang, Karyawan, dan Sistem</p>
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
        <a href="settings.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-slate-800 bg-gray-50 border-b-4 border-slate-800">
            ⚙️ PENGATURAN
        </a>
        <a href="../../logout.php" class="flex-1 py-5 px-2 text-center text-lg md:text-xl font-bold text-gray-600 hover:text-red-600 hover:bg-gray-50 transition-all border-b-4 border-transparent hover:border-red-600">
            🚪 KELUAR
        </a>
    </div>

    <!-- PESAN SUKSES / ERROR -->
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-5 mb-6 rounded-xl border-l-8 border-green-600 text-lg md:text-xl flex items-center gap-3">
            <span class="text-2xl">✅</span>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-800 p-5 mb-6 rounded-xl border-l-8 border-red-600 text-lg md:text-xl flex items-center gap-3">
            <span class="text-2xl">❌</span>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- TAB BUTTONS -->
    <div class="flex flex-wrap gap-3 mb-8">
        <button id="tab1-btn" onclick="openTab('barang')"
                class="tab-btn flex-1 py-5 px-4 text-xl md:text-2xl font-bold rounded-xl transition-all duration-300 <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'barang' ? 'bg-slate-800 text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            📦 KELOLA BARANG
        </button>
        <button id="tab2-btn" onclick="openTab('karyawan')"
                class="tab-btn flex-1 py-5 px-4 text-xl md:text-2xl font-bold rounded-xl transition-all duration-300 <?php echo isset($_GET['tab']) && $_GET['tab'] == 'karyawan' ? 'bg-slate-800 text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            👥 KELOLA KARYAWAN
        </button>
        <button id="tab3-btn" onclick="openTab('system')"
                class="tab-btn flex-1 py-5 px-4 text-xl md:text-2xl font-bold rounded-xl transition-all duration-300 <?php echo isset($_GET['tab']) && $_GET['tab'] == 'system' ? 'bg-slate-800 text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            🔧 SISTEM
        </button>
    </div>

    <!-- ========== TAB 1 : KELOLA BARANG ========== -->
    <div id="tab-barang" class="tab-content <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'barang' ? 'active' : ''; ?>">

        <!-- Form Tambah Barang -->
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
            <div class="flex items-center mb-6 border-l-8 border-slate-800 pl-5">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">➕ TAMBAH BARANG BARU</h2>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="add_barang">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-lg md:text-xl font-bold text-gray-800 mb-2">
                            Kode Barang <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="kode_barang"
                               class="w-full p-4 text-lg md:text-xl border-2 border-gray-300 rounded-xl focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all"
                               placeholder="Contoh: BTL-010" required>
                    </div>

                    <div>
                        <label class="block text-lg md:text-xl font-bold text-gray-800 mb-2">
                            Nama Barang <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="nama_barang"
                               class="w-full p-4 text-lg md:text-xl border-2 border-gray-300 rounded-xl focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all"
                               placeholder="Contoh: Aqua 600ml" required>
                    </div>

                    <div>
                        <label class="block text-lg md:text-xl font-bold text-gray-800 mb-2">Satuan</label>
                        <select name="satuan" class="w-full p-4 text-lg md:text-xl border-2 border-gray-300 rounded-xl focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all">
                            <option value="botol">Botol</option>
                            <option value="dus">Dus</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-lg md:text-xl font-bold text-gray-800 mb-2">
                            Harga Beli (Rp) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" name="harga_beli"
                               class="w-full p-4 text-lg md:text-xl border-2 border-gray-300 rounded-xl focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all"
                               min="0" value="0" required>
                    </div>

                    <div>
                        <label class="block text-lg md:text-xl font-bold text-gray-800 mb-2">
                            Harga Jual (Rp) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" name="harga_jual"
                               class="w-full p-4 text-lg md:text-xl border-2 border-gray-300 rounded-xl focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all"
                               min="0" value="0" required>
                    </div>

                    <div>
                        <label class="block text-lg md:text-xl font-bold text-gray-800 mb-2">Stok Minimal</label>
                        <input type="number" name="stok_minimal"
                               class="w-full p-4 text-lg md:text-xl border-2 border-gray-300 rounded-xl focus:border-slate-800 focus:ring-4 focus:ring-slate-200 outline-none transition-all"
                               min="1" value="10">
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                            class="w-full py-5 px-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-green-600 to-green-700 rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-300 shadow-lg hover:-translate-y-1">
                        💾 SIMPAN BARANG
                    </button>
                </div>
            </form>

            <div class="text-right mt-6">
                <a href="tambah_stok.php"
                   class="inline-block px-8 py-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 shadow-lg hover:-translate-y-1">
                    ➕ TAMBAH STOK (ADMIN)
                </a>
            </div>
        </div>

        <!-- Daftar Barang -->
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl">
            <div class="flex items-center mb-6 border-l-8 border-slate-800 pl-5">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">📋 DAFTAR BARANG</h2>
            </div>

            <?php if ($barang_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="w-full text-base md:text-lg">
                        <thead class="bg-gray-100">
                        <tr>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Kode</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Nama</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Satuan</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Harga Beli</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Harga Jual</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Stok Min</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($b = $barang_list->fetch_assoc()): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                <td class="p-4 md:p-5 font-mono"><?php echo htmlspecialchars($b['kode_barang']); ?></td>
                                <td class="p-4 md:p-5 font-medium"><?php echo htmlspecialchars($b['nama_barang']); ?></td>
                                <td class="p-4 md:p-5"><?php echo $b['satuan']; ?></td>
                                <td class="p-4 md:p-5"><?php echo formatRupiah($b['harga_beli']); ?></td>
                                <td class="p-4 md:p-5 font-bold text-green-700"><?php echo formatRupiah($b['harga_jual']); ?></td>
                                <td class="p-4 md:p-5"><?php echo $b['stok_minimal']; ?></td>
                                <td class="p-4 md:p-5">
                                    <a href="?delete=<?php echo $b['id_barang']; ?>"
                                       onclick="return confirm('Hapus barang <?php echo htmlspecialchars($b['nama_barang']); ?>?')"
                                       class="inline-block px-4 py-3 bg-red-600 hover:bg-red-700 text-white text-sm md:text-base font-bold rounded-lg transition-all duration-300 shadow-md">
                                        🗑️ Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 p-10 rounded-xl text-center">
                    <div class="text-7xl mb-4">📦</div>
                    <p class="text-xl md:text-2xl text-gray-600">Belum ada barang.</p>
                    <p class="text-lg text-gray-500 mt-2">Silakan tambahkan barang baru.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== TAB 2 : KELOLA KARYAWAN ========== -->
    <div id="tab-karyawan" class="tab-content <?php echo isset($_GET['tab']) && $_GET['tab'] == 'karyawan' ? 'active' : ''; ?>">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl">
            <div class="flex items-center mb-6 border-l-8 border-slate-800 pl-5">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">👥 DAFTAR KARYAWAN</h2>
            </div>

            <?php if ($karyawan_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="w-full text-base md:text-lg">
                        <thead class="bg-gray-100">
                        <tr>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Nama</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Role</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Status</th>
                            <th class="p-4 md:p-5 text-left font-bold text-gray-800">Terakhir Login</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($k = $karyawan_list->fetch_assoc()): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                <td class="p-4 md:p-5 font-medium"><?php echo htmlspecialchars($k['nama_karyawan']); ?></td>
                                <td class="p-4 md:p-5">
                                    <?php if ($k['is_admin']): ?>
                                        <span class="px-4 py-2 bg-purple-100 text-purple-800 rounded-full text-sm md:text-base font-bold">Admin</span>
                                    <?php else: ?>
                                        <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm md:text-base font-bold">Kasir</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 md:p-5">
                                    <?php if ($k['last_login']): ?>
                                        <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm md:text-base font-bold">🟢 Aktif</span>
                                    <?php else: ?>
                                        <span class="px-4 py-2 bg-gray-100 text-gray-600 rounded-full text-sm md:text-base">⚪ Belum login</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 md:p-5 text-gray-700">
                                    <?php echo $k['last_login'] ? date('d/m/Y H:i', strtotime($k['last_login'])) : '-'; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 p-5 bg-blue-50 rounded-xl border-l-8 border-blue-600">
                    <p class="text-lg md:text-xl text-blue-800 flex items-center gap-3">
                        <span class="text-2xl">ℹ️</span>
                        * Untuk menambah/edit karyawan, hubungi developer.
                    </p>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 p-10 rounded-xl text-center">
                    <div class="text-7xl mb-4">👤</div>
                    <p class="text-xl md:text-2xl text-gray-600">Tidak ada data karyawan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== TAB 3 : SISTEM ========== -->
    <div id="tab-system" class="tab-content <?php echo isset($_GET['tab']) && $_GET['tab'] == 'system' ? 'active' : ''; ?>">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl">
            <div class="flex items-center mb-6 border-l-8 border-slate-800 pl-5">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">🔧 PENGATURAN SISTEM</h2>
            </div>

            <!-- Backup Database -->
            <div class="mb-10 p-6 bg-gray-50 rounded-xl border-2 border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="text-5xl">💾</div>
                    <div class="flex-1">
                        <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Backup Database</h3>
                        <p class="text-lg md:text-xl text-gray-700 mb-5">Buat cadangan data sistem ke file SQL.</p>
                        <button onclick="alert('Fitur backup sedang dikembangkan')"
                                class="px-8 py-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-green-600 to-green-700 rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-300 shadow-lg hover:-translate-y-1">
                            💾 BACKUP SEKARANG
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reset Data Bulanan -->
            <div class="p-6 bg-red-50 rounded-xl border-2 border-red-200">
                <div class="flex items-start gap-4">
                    <div class="text-5xl">⚠️</div>
                    <div class="flex-1">
                        <h3 class="text-2xl md:text-3xl font-bold text-red-800 mb-3">Reset Data Bulanan</h3>
                        <p class="text-lg md:text-xl text-red-700 mb-5 font-semibold">
                            Hapus semua transaksi dan stock opname bulan lalu. (Tidak dapat dikembalikan!)
                        </p>
                        <button onclick="if(confirm('Yakin akan reset data?')) alert('Fitur reset dikunci sementara');"
                                class="px-8 py-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-red-600 to-red-700 rounded-xl hover:from-red-700 hover:to-red-800 transition-all duration-300 shadow-lg hover:-translate-y-1">
                            ⚠️ RESET BULANAN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="text-center mt-8 text-gray-500 text-base md:text-lg">
        <p>© <?php echo date('Y'); ?> Sistem Kasir Botol - Pengaturan Admin</p>
    </div>
</div>

<script>
    function openTab(tabName) {
        // Sembunyikan semua tab
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.remove('active');
        });

        // Nonaktifkan semua tombol tab
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-slate-800', 'text-white', 'shadow-lg');
            btn.classList.add('bg-gray-200', 'text-gray-700');
        });

        // Tampilkan tab yang dipilih
        document.getElementById('tab-' + tabName).classList.add('active');

        // Aktifkan tombol yang sesuai
        if (tabName === 'barang') {
            document.getElementById('tab1-btn').classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById('tab1-btn').classList.add('bg-slate-800', 'text-white', 'shadow-lg');
        } else if (tabName === 'karyawan') {
            document.getElementById('tab2-btn').classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById('tab2-btn').classList.add('bg-slate-800', 'text-white', 'shadow-lg');
        } else if (tabName === 'system') {
            document.getElementById('tab3-btn').classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById('tab3-btn').classList.add('bg-slate-800', 'text-white', 'shadow-lg');
        }

        // Update URL tanpa reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
    }

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
        document.querySelectorAll('button, a, .tab-btn').forEach(el => {
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

        // Auto focus tab dari URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            openTab(tab);
        }
    });
</script>
</body>
</html>