<?php
// login.php (MODIFIED - TWO OPTIONS)
require_once 'includes/auth.php';
require_once 'includes/database.php';

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();

// Get cabang list
$cabang_result = $db->query("SELECT * FROM cabang ORDER BY nama_cabang");

$login_type = $_GET['type'] ?? 'kasir'; // 'admin' or 'kasir'
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? 'kasir';
    
    if ($login_type == 'admin') {
        // Login Admin
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
        // Login Kasir
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/ux-large.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        .login-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        
        .login-tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            background: #f5f5f5;
            border: none;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .login-tab.active {
            background: var(--primary-color);
            color: white;
            font-weight: bold;
        }
        
        .login-form {
            display: none;
            animation: fadeIn 0.5s;
        }
        
        .login-form.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .error-message {
            background: #FFEBEE;
            color: #D32F2F;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 18px;
            border-left: 4px solid #D32F2F;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle input {
            padding-right: 50px;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo/Header -->
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 40px; color: var(--primary-color); margin-bottom: 10px;">
                🏪
            </div>
            <h1 class="login-title"><?php echo SITE_NAME; ?></h1>
            <p style="font-size: 18px; color: #666;">Sistem Manajemen Toko & Gudang</p>
        </div>
        
        <!-- Error Message -->
        <?php if (!empty($error)): ?>
        <div class="error-message">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <!-- Login Tabs -->
        <div class="login-tabs">
            <button type="button" 
                    class="login-tab <?php echo $login_type == 'kasir' ? 'active' : ''; ?>" 
                    onclick="showLoginTab('kasir')">
                👤 LOGIN KASIR
            </button>
            <button type="button" 
                    class="login-tab <?php echo $login_type == 'admin' ? 'active' : ''; ?>" 
                    onclick="showLoginTab('admin')">
                🔐 LOGIN ADMIN
            </button>
        </div>
        
        <!-- Kasir Login Form -->
        <form method="POST" action="" class="login-form <?php echo $login_type == 'kasir' ? 'active' : ''; ?>" id="kasir-form">
            <input type="hidden" name="login_type" value="kasir">
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 20px; display: block; margin-bottom: 8px;">
                    <b>NAMA ANDA:</b>
                </label>
                <input type="text" 
                       name="nama_karyawan" 
                       class="input-large" 
                       placeholder="Masukkan nama Anda"
                       required
                       style="font-size: 18px;">
                <p style="font-size: 14px; color: #666; margin-top: 5px;">
                    * Tidak perlu password untuk kasir
                </p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 20px; display: block; margin-bottom: 8px;">
                    <b>PILIH CABANG:</b>
                </label>
                <select name="id_cabang" class="input-large" required style="font-size: 18px;">
                    <option value="">-- Pilih Cabang Toko --</option>
                    <?php while ($cabang = $cabang_result->fetch_assoc()): ?>
                    <option value="<?php echo $cabang['id_cabang']; ?>" 
                        <?php echo isset($_POST['id_cabang']) && $_POST['id_cabang'] == $cabang['id_cabang'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cabang['nama_cabang']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" class="btn-big btn-green" style="width: 100%; font-size: 20px;">
                <b>MASUK SEBAGAI KASIR</b>
            </button>
        </form>
        
        <!-- Admin Login Form -->
        <form method="POST" action="" class="login-form <?php echo $login_type == 'admin' ? 'active' : ''; ?>" id="admin-form">
            <input type="hidden" name="login_type" value="admin">
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 20px; display: block; margin-bottom: 8px;">
                    <b>NAMA ADMIN:</b>
                </label>
                <input type="text" 
                       name="nama_karyawan" 
                       class="input-large" 
                       placeholder="Masukkan nama admin"
                       required
                       style="font-size: 18px;"
                       value="<?php echo isset($_POST['login_type']) && $_POST['login_type'] == 'admin' ? htmlspecialchars($_POST['nama_karyawan'] ?? '') : ''; ?>">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 20px; display: block; margin-bottom: 8px;">
                    <b>PASSWORD:</b>
                </label>
                <div class="password-toggle">
                    <input type="password" 
                           name="password" 
                           id="password-input"
                           class="input-large" 
                           placeholder="Masukkan password"
                           required
                           style="font-size: 18px;">
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        👁️
                    </button>
                </div>
            </div>
            
            <div style="margin-bottom: 20px; font-size: 14px; color: #666;">
                <p><strong>Catatan:</strong> Hanya untuk pemilik/manajer toko</p>
            </div>
            
            <button type="submit" class="btn-big btn-green" style="width: 100%; font-size: 20px;">
                <b>MASUK SEBAGAI ADMIN</b>
            </button>
        </form>
        
        <!-- Mobile Info -->
        <div class="show-mobile" style="margin-top: 30px; padding: 15px; background: #F5F5F5; border-radius: 8px;">
            <p style="font-size: 14px; color: #666; margin: 0;">
                <strong>📱 Tips untuk Handphone:</strong><br>
                • Pilih login sesuai peran Anda<br>
                • Kasir: cukup nama & cabang<br>
                • Admin: butuh nama & password
            </p>
        </div>
    </div>
    
    <script>
        function showLoginTab(tabName) {
            // Hide all forms
            document.querySelectorAll('.login-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.login-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected form
            document.getElementById(tabName + '-form').classList.add('active');
            
            // Activate selected tab
            event.target.classList.add('active');
            
            // Update URL
            window.history.pushState({}, '', '?type=' + tabName);
        }
        
        function togglePassword() {
            const passwordInput = document.getElementById('password-input');
            const toggleButton = event.target.closest('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.innerHTML = '👁️‍🗨️';
            } else {
                passwordInput.type = 'password';
                toggleButton.innerHTML = '👁️';
            }
        }
        
        // Initialize based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            
            if (type === 'admin') {
                showLoginTab('admin');
            }
            
            // Auto-focus on first input
            const activeForm = document.querySelector('.login-form.active');
            if (activeForm) {
                const firstInput = activeForm.querySelector('input');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        });
    </script>
</body>
</html>