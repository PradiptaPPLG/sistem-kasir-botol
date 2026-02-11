<?php
// login.php - VERSI TAILWIND v3
require_once 'includes/database.php';

// Start session dengan setting timeout
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params(1800);
    session_start();
}

// Cek jika sudah login
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

        if (!empty($nama_karyawan) && !empty($password)) {
            if ($auth->loginAdmin($nama_karyawan, $password)) {
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Nama admin atau password salah!';
            }
        } else {
            $error = 'Harap isi semua field!';
        }
    } else {
        $nama_karyawan = $_POST['nama_karyawan'] ?? '';
        $id_cabang = $_POST['id_cabang'] ?? '';

        if (!empty($nama_karyawan) && !empty($id_cabang)) {
            if ($auth->loginKasir($nama_karyawan, $id_cabang)) {
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Terjadi kesalahan saat login!';
            }
        } else {
            $error = 'Harap isi nama dan pilih cabang!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Login - Sistem Kasir Botol</title>

    <!-- Tailwind CSS v3 - hasil build -->
    <link href="./src/output.css" rel="stylesheet">

    <style>
        /* Fallback untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            input, select, button, .tab {
                font-size: 16px !important;
                min-height: 50px;
            }
        }

        /* Custom untuk password toggle button */
        .password-toggle-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            padding: 0.5rem;
            color: #666;
            background: none;
            border: none;
            cursor: pointer;
        }

        .password-toggle-btn:hover {
            color: #333;
        }

        /* Fix untuk outline-hidden di v3 */
        .outline-hidden {
            outline: none !important;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-4 md:p-5 font-sans text-base md:text-xl">
<div class="w-full max-w-[500px] bg-white rounded-3xl shadow-2xl overflow-hidden">

    <!-- HEADER -->
    <div class="bg-gradient-to-r from-slate-800 to-blue-900 px-5 py-8 md:px-8 md:py-10 text-center text-white">
        <div class="text-6xl md:text-7xl mb-3">🏪</div>
        <h1 class="text-3xl md:text-4xl font-bold mb-1">SISTEM KASIR BOTOL</h1>
        <p class="text-lg md:text-xl opacity-90">Login untuk mengakses sistem</p>
    </div>

    <!-- ERROR MESSAGE -->
    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border-l-4 border-red-600 p-5 m-6 rounded-lg flex items-start gap-4">
            <span class="text-2xl">⚠️</span>
            <span class="text-base md:text-lg text-red-800"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="flex border-b-2 border-gray-200">
        <button type="button"
                class="tab flex-1 py-6 md:py-7 text-center text-xl md:text-2xl font-bold transition-all duration-300 <?php echo $login_type == 'kasir' ? 'bg-white text-slate-800 border-b-4 border-blue-500' : 'bg-gray-100 text-gray-600 hover:bg-blue-50'; ?>"
                onclick="showTab('kasir')">
            👤 KASIR
        </button>
        <button type="button"
                class="tab flex-1 py-6 md:py-7 text-center text-xl md:text-2xl font-bold transition-all duration-300 <?php echo $login_type == 'admin' ? 'bg-white text-slate-800 border-b-4 border-blue-500' : 'bg-gray-100 text-gray-600 hover:bg-blue-50'; ?>"
                onclick="showTab('admin')">
            🔐 ADMIN
        </button>
    </div>

    <!-- CONTENT -->
    <div class="p-6 md:p-8">
        <!-- KASIR FORM -->
        <form method="POST" action="" id="kasir-form" class="space-y-8 <?php echo $login_type != 'kasir' ? 'hidden' : ''; ?>">
            <input type="hidden" name="login_type" value="kasir">

            <div class="space-y-3">
                <label class="block text-xl md:text-2xl font-bold text-gray-800">NAMA ANDA</label>
                <input type="text"
                       name="nama_karyawan"
                       class="w-full p-5 md:p-6 text-xl md:text-2xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-200 outline-hidden transition-all"
                       placeholder="Masukkan nama Anda"
                       required>
                <p class="text-sm md:text-base text-gray-600 ml-1">* Tidak perlu password untuk kasir</p>
            </div>

            <div class="space-y-3">
                <label class="block text-xl md:text-2xl font-bold text-gray-800">PILIH CABANG</label>
                <select name="id_cabang" class="w-full p-5 md:p-6 text-xl md:text-2xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-200 outline-hidden transition-all" required>
                    <option value="" class="text-lg">-- Pilih Cabang Toko --</option>
                    <?php while ($cabang = $cabang_result->fetch_assoc()): ?>
                        <option value="<?php echo $cabang['id_cabang']; ?>" class="text-lg">
                            <?php echo htmlspecialchars($cabang['nama_cabang']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="w-full py-6 md:py-7 px-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-blue-500 to-blue-700 rounded-xl hover:from-blue-600 hover:to-blue-800 hover:-translate-y-1 hover:shadow-xl active:translate-y-0 transition-all duration-300 mt-4">
                🚀 MASUK SEBAGAI KASIR
            </button>
        </form>

        <!-- ADMIN FORM -->
        <form method="POST" action="" id="admin-form" class="space-y-8 <?php echo $login_type != 'admin' ? 'hidden' : ''; ?>">
            <input type="hidden" name="login_type" value="admin">

            <div class="space-y-3">
                <label class="block text-xl md:text-2xl font-bold text-gray-800">NAMA ADMIN</label>
                <input type="text"
                       name="nama_karyawan"
                       class="w-full p-5 md:p-6 text-xl md:text-2xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-200 outline-hidden transition-all"
                       placeholder="Masukkan nama admin"
                       value="<?php echo isset($_POST['login_type']) && $_POST['login_type'] == 'admin' ? htmlspecialchars($_POST['nama_karyawan'] ?? '') : ''; ?>"
                       required>
            </div>

            <div class="space-y-3">
                <label class="block text-xl md:text-2xl font-bold text-gray-800">PASSWORD</label>
                <div class="relative">
                    <input type="password"
                           name="password"
                           id="password-input"
                           class="w-full p-5 md:p-6 text-xl md:text-2xl border-2 border-gray-300 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-200 outline-hidden transition-all pr-16"
                           placeholder="Masukkan password"
                           required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-6 md:py-7 px-4 text-xl md:text-2xl font-bold text-white bg-gradient-to-r from-blue-500 to-blue-700 rounded-xl hover:from-blue-600 hover:to-blue-800 hover:-translate-y-1 hover:shadow-xl active:translate-y-0 transition-all duration-300 mt-4">
                🔐 MASUK SEBAGAI ADMIN
            </button>
        </form>

        <!-- MOBILE INFO -->
        <div class="md:hidden bg-blue-50 p-5 mt-8 rounded-xl border-l-4 border-blue-500 space-y-2">
            <p class="font-bold text-gray-800 flex items-center gap-2 text-lg">📱 <span class="text-base md:text-lg">Cara Login:</span></p>
            <p class="text-sm md:text-base text-gray-700">• <span class="font-semibold">Kasir</span>: cukup nama & cabang</p>
            <p class="text-sm md:text-base text-gray-700">• <span class="font-semibold">Admin</span>: butuh nama & password</p>
        </div>
    </div>
</div>

<script>
    function showTab(tabName) {
        // Hide all forms
        document.getElementById('kasir-form').classList.add('hidden');
        document.getElementById('admin-form').classList.add('hidden');

        // Show selected form
        document.getElementById(tabName + '-form').classList.remove('hidden');

        // Update tab classes
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('bg-white', 'text-slate-800', 'border-b-4', 'border-blue-500');
            tab.classList.add('bg-gray-100', 'text-gray-600');
        });

        // Activate current tab
        const activeTab = event.currentTarget;
        activeTab.classList.remove('bg-gray-100', 'text-gray-600');
        activeTab.classList.add('bg-white', 'text-slate-800', 'border-b-4', 'border-blue-500');

        // Update URL
        window.history.pushState({}, '', '?type=' + tabName);

        // Focus first input
        const firstInput = document.getElementById(tabName + '-form').querySelector('input, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }

    function togglePassword() {
        const passwordInput = document.getElementById('password-input');
        const toggleBtn = event.currentTarget;

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.textContent = '👁️‍🗨️';
        } else {
            passwordInput.type = 'password';
            toggleBtn.textContent = '👁️';
        }
    }

    // Auto-focus on load
    document.addEventListener('DOMContentLoaded', function() {
        const activeForm = document.querySelector('form:not(.hidden)');
        if (activeForm) {
            const firstInput = activeForm.querySelector('input, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 300);
            }
        }

        // iOS zoom fix
        document.querySelectorAll('input, select').forEach(el => {
            el.style.fontSize = '16px';
        });
    });
</script>
</body>
</html>