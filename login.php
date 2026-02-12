<?php
// login.php - FULL TAILWIND VERSION

require_once 'includes/database.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params(1800);
    session_start();
}

if (isset($_SESSION['id_karyawan'])) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();
$cabang_result = $db->query("SELECT * FROM cabang ORDER BY nama_cabang");
$login_type = $_GET['type'] ?? 'kasir';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/auth.php';
    $auth = new Auth();
    $login_type = $_POST['login_type'] ?? 'kasir';

    if ($login_type == 'admin') {
        $nama_karyawan = $_POST['nama_karyawan'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($auth->loginAdmin($nama_karyawan, $password)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Nama admin atau password salah!';
        }
    } else {
        $nama_karyawan = $_POST['nama_karyawan'] ?? '';
        $id_cabang = $_POST['id_cabang'] ?? '';
        if ($auth->loginKasir($nama_karyawan, $id_cabang)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Login gagal!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Kasir Botol</title>

<!-- TAILWIND CDN -->
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 flex items-center justify-center min-h-screen px-4">

<div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">

    <!-- HEADER -->
    <div class="text-center p-6 border-b">
        <div class="text-6xl">🥤</div>
        <h1 class="text-2xl font-bold text-slate-900">Kasir Botol</h1>
        <p class="text-slate-500 text-sm mt-1">Sistem kasir & gudang</p>
    </div>

    <!-- TABS -->
    <div class="flex border-b">
        <button id="tabKasir" onclick="showTab('kasir')" class="w-1/2 py-3 font-semibold text-center border-b-4 transition 
            <?= $login_type=='kasir'?'border-blue-600 text-blue-600':'border-transparent text-slate-400' ?>">
            👨‍💼 Kasir
        </button>
        <button id="tabAdmin" onclick="showTab('admin')" class="w-1/2 py-3 font-semibold text-center border-b-4 transition 
            <?= $login_type=='admin'?'border-blue-600 text-blue-600':'border-transparent text-slate-400' ?>">
            🛠 Admin
        </button>
    </div>

    <div class="p-6">

        <?php if($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 font-semibold">
            ⚠ <?= $error ?>
        </div>
        <?php endif; ?>

        <!-- FORM KASIR -->
        <form id="formKasir" method="POST" class="<?= $login_type=='kasir'?'block':'hidden' ?> space-y-4">
            <input type="hidden" name="login_type" value="kasir">

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Nama Kasir</label>
                <input type="text" name="nama_karyawan" required
                    class="w-full px-4 py-3 rounded-xl border focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Cabang</label>
                <select name="id_cabang" required
                    class="w-full px-4 py-3 rounded-xl border focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                    <option value="">Pilih Cabang</option>
                    <?php while($c = $cabang_result->fetch_assoc()): ?>
                        <option value="<?= $c['id_cabang'] ?>"><?= htmlspecialchars($c['nama_cabang']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition">
                🚀 Login Kasir
            </button>
        </form>

        <!-- FORM ADMIN -->
        <form id="formAdmin" method="POST" class="<?= $login_type=='admin'?'block':'hidden' ?> space-y-4">
            <input type="hidden" name="login_type" value="admin">

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Nama Admin</label>
                <input type="text" name="nama_karyawan" required
                    class="w-full px-4 py-3 rounded-xl border focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
            </div>

            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition">
                🔐 Login Admin
            </button>
        </form>

    </div>

</div>

<script>
function showTab(type) {
    document.getElementById('formKasir').classList.add('hidden');
    document.getElementById('formAdmin').classList.add('hidden');

    document.getElementById('tabKasir').classList.remove('border-blue-600','text-blue-600');
    document.getElementById('tabAdmin').classList.remove('border-blue-600','text-blue-600');

    if (type === 'kasir') {
        document.getElementById('formKasir').classList.remove('hidden');
        document.getElementById('tabKasir').classList.add('border-blue-600','text-blue-600');
    } else {
        document.getElementById('formAdmin').classList.remove('hidden');
        document.getElementById('tabAdmin').classList.add('border-blue-600','text-blue-600');
    }
}
</script>

</body>
</html>
