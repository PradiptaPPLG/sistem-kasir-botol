<div class="flex">

<aside class="w-64 bg-white shadow-lg min-h-screen p-4 hidden md:block">
    <h2 class="text-2xl font-bold mb-6">🥤 Kasir Botol</h2>

    <nav class="space-y-3 text-lg font-semibold">
        <a href="dashboard.php" class="block p-3 rounded-xl hover:bg-blue-100">🏠 Dashboard</a>
        <a href="modules/kasir/transaksi.php?type=pembeli" class="block p-3 rounded-xl hover:bg-blue-100">💳 Kasir</a>
        <a href="modules/gudang/" class="block p-3 rounded-xl hover:bg-blue-100">📦 Gudang</a>

        <?php if($_SESSION['is_admin'] ?? false): ?>
        <a href="modules/admin/laporan.php" class="block p-3 rounded-xl hover:bg-blue-100">📊 Laporan</a>
        <a href="modules/admin/settings.php" class="block p-3 rounded-xl hover:bg-blue-100">⚙️ Pengaturan</a>
        <?php endif; ?>

        <a href="logout.php" class="block p-3 rounded-xl bg-red-500 text-white">🚪 Logout</a>
    </nav>
</aside>

<main class="flex-1">
