<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params(1800);
    session_start();
}
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

$sales_today = $db->query("SELECT SUM(total_harga) total FROM transaksi_kasir WHERE id_cabang=? AND DATE(tanggal)=?", [$id_cabang,$today])->fetch_assoc();
$total_transactions = $db->query("SELECT COUNT(*) total FROM transaksi_kasir WHERE id_cabang=? AND DATE(tanggal)=?", [$id_cabang,$today])->fetch_assoc();

$warnings = [];
if ($is_admin) {
    $result = $db->query("
        SELECT b.nama_barang, sg.stok_sistem, sg.stok_fisik,
               (sg.stok_sistem - sg.stok_fisik) selisih, b.harga_beli
        FROM stok_gudang sg
        JOIN barang b ON sg.id_barang=b.id_barang
        WHERE sg.id_cabang=? AND sg.tanggal_update=? AND sg.stok_sistem>sg.stok_fisik
        LIMIT 5
    ", [$id_cabang,$today]);
    while ($row=$result->fetch_assoc()) $warnings[]=$row;
}

$recent = $db->query("
    SELECT tk.*, b.nama_barang 
    FROM transaksi_kasir tk
    JOIN barang b ON tk.id_barang=b.id_barang
    WHERE tk.id_cabang=?
    ORDER BY tk.tanggal DESC LIMIT 5
", [$id_cabang]);
?>

<?php include 'includes/layout_header.php'; ?>
<?php include 'includes/layout_sidebar.php'; ?>

<div class="p-6 space-y-6">

    <!-- HEADER DASHBOARD -->
    <div class="bg-white shadow rounded-2xl p-6 flex flex-col md:flex-row justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Kasir Botol</h1>
            <p class="text-gray-500">Halo, <b><?= htmlspecialchars($user) ?></b></p>
        </div>
        <div class="px-4 py-2 rounded-full text-white font-semibold 
            <?= $is_admin ? 'bg-yellow-500' : 'bg-green-600' ?>">
            <?= $is_admin ? 'ADMIN' : 'KASIR' ?>
        </div>
    </div>

    <!-- WARNING STOCK -->
    <?php if ($is_admin && !empty($warnings)): ?>
    <div class="bg-red-50 border-l-8 border-red-600 p-6 rounded-2xl shadow">
        <h2 class="text-xl font-bold text-red-700 flex items-center gap-2">⚠️ Kehilangan Stok Terdeteksi</h2>

        <div class="overflow-x-auto mt-4">
            <table class="w-full text-sm">
                <thead class="bg-red-100 text-red-700">
                    <tr>
                        <th class="p-3 text-left">Barang</th>
                        <th class="p-3">Sistem</th>
                        <th class="p-3">Fisik</th>
                        <th class="p-3">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $loss=0; foreach($warnings as $w): 
                        $loss += $w['selisih']*$w['harga_beli']; ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($w['nama_barang']) ?></td>
                        <td class="p-3 text-center"><?= $w['stok_sistem'] ?></td>
                        <td class="p-3 text-center"><?= $w['stok_fisik'] ?></td>
                        <td class="p-3 text-center text-red-600 font-bold"><?= $w['selisih'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 bg-red-200 text-red-800 p-4 rounded-xl font-bold flex justify-between text-lg">
            <span>Estimasi Kerugian</span>
            <span><?= formatRupiah($loss) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="bg-white p-6 rounded-2xl shadow flex flex-col">
            <h3 class="text-gray-500 text-lg">💰 Penjualan Hari Ini</h3>
            <div class="text-3xl font-bold mt-2"><?= formatRupiah($sales_today['total'] ?? 0) ?></div>
            <a href="modules/kasir/transaksi.php?type=pembeli" class="mt-auto bg-blue-600 text-white text-lg py-3 rounded-xl text-center font-bold hover:bg-blue-700">
                + Transaksi Baru
            </a>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow flex flex-col">
            <h3 class="text-gray-500 text-lg">📋 Jumlah Transaksi</h3>
            <div class="text-3xl font-bold mt-2"><?= $total_transactions['total'] ?? 0 ?></div>
            <a href="modules/kasir/" class="mt-auto bg-gray-200 text-gray-800 text-lg py-3 rounded-xl text-center font-bold hover:bg-gray-300">
                Lihat Riwayat
            </a>
        </div>

        <?php if ($is_admin): ?>
        <div class="bg-white p-6 rounded-2xl shadow flex flex-col">
            <h3 class="text-gray-500 text-lg">📦 Gudang</h3>
            <div class="text-3xl font-bold mt-2">Monitoring</div>
            <a href="modules/gudang/" class="mt-auto bg-green-600 text-white text-lg py-3 rounded-xl text-center font-bold hover:bg-green-700">
                Cek Stok Gudang
            </a>
        </div>
        <?php endif; ?>

    </div>

    <!-- TRANSAKSI TERAKHIR -->
    <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex justify-between mb-4">
            <h2 class="text-xl font-bold">📌 Transaksi Terakhir</h2>
            <a href="modules/kasir/" class="text-blue-600 font-semibold">Lihat Semua →</a>
        </div>

        <?php if ($recent->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3">Barang</th>
                        <th class="p-3">Jenis</th>
                        <th class="p-3">Jumlah</th>
                        <th class="p-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($r=$recent->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3"><?= date('H:i', strtotime($r['tanggal'])) ?></td>
                        <td class="p-3"><?= htmlspecialchars($r['nama_barang']) ?></td>
                        <td class="p-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold
                                <?= $r['jenis_pembeli']=='pembeli' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= $r['jenis_pembeli'] ?>
                            </span>
                        </td>
                        <td class="p-3 text-center"><?= $r['jumlah'] ?></td>
                        <td class="p-3 font-bold"><?= formatRupiah($r['total_harga']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-10 text-gray-500 text-lg">
            Belum ada transaksi hari ini.
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/layout_footer.php'; ?>
