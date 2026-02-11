<?php
// login.php - VERSI SUPER CLEAN UNTUK LANGSIA
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kasir Botol</title>
    <style>
        /* RESET & BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-size: 20px; /* BASE FONT BESAR */
        }
        
        /* CONTAINER */
        .container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(to right, #2c3e50, #4a6491);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        
        .logo {
            font-size: 60px;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 20px;
            opacity: 0.9;
        }
        
        /* TABS */
        .tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            flex: 1;
            padding: 25px;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            background: #f5f5f5;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            color: #666;
        }
        
        .tab.active {
            background: white;
            color: #2c3e50;
            border-bottom: 4px solid #3498db;
        }
        
        .tab:hover {
            background: #e8f4fc;
        }
        
        /* CONTENT */
        .content {
            padding: 30px;
        }
        
        /* ERROR MESSAGE */
        .error {
            background: #ffebee;
            border-left: 6px solid #f44336;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            font-size: 18px;
            color: #c62828;
        }
        
        .error-icon {
            font-size: 24px;
            margin-right: 15px;
        }
        
        /* FORM ELEMENTS */
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-label {
            display: block;
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .form-input {
            width: 100%;
            padding: 20px;
            font-size: 22px;
            border: 2px solid #ddd;
            border-radius: 12px;
            background: white;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .form-hint {
            font-size: 16px;
            color: #666;
            margin-top: 8px;
            margin-left: 5px;
        }
        
        /* PASSWORD TOGGLE */
        .password-toggle {
            position: relative;
        }
        
        .toggle-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 5px;
        }
        
        /* BUTTON */
        .btn-primary {
            width: 100%;
            padding: 25px;
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #2980b9, #1c5a7a);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* MOBILE INFO */
        .mobile-info {
            display: none;
            background: #f8f9fa;
            padding: 20px;
            margin-top: 25px;
            border-radius: 12px;
            border-left: 4px solid #3498db;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            body {
                padding: 10px;
                font-size: 18px;
            }
            
            .container {
                border-radius: 15px;
            }
            
            .header {
                padding: 25px 15px;
            }
            
            .logo {
                font-size: 50px;
            }
            
            .title {
                font-size: 28px;
            }
            
            .subtitle {
                font-size: 18px;
            }
            
            .tab {
                padding: 20px;
                font-size: 20px;
            }
            
            .content {
                padding: 25px;
            }
            
            .form-input {
                padding: 18px;
                font-size: 20px;
            }
            
            .btn-primary {
                padding: 22px;
                font-size: 22px;
            }
            
            .mobile-info {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            body {
                font-size: 16px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .tab {
                padding: 18px;
                font-size: 18px;
            }
            
            .form-input {
                padding: 16px;
                font-size: 18px;
            }
            
            .btn-primary {
                padding: 20px;
                font-size: 20px;
            }
        }
        
        /* TOUCH FRIENDLY */
        @media (hover: none) and (pointer: coarse) {
            .tab, 
            .btn-primary,
            .toggle-btn {
                min-height: 50px;
            }
            
            .form-input {
                font-size: 20px !important; /* Prevent zoom on iOS */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="logo">🏪</div>
            <h1 class="title">SISTEM KASIR BOTOL</h1>
            <p class="subtitle">Login untuk mengakses sistem</p>
        </div>
        
        <!-- ERROR MESSAGE -->
        <?php if (!empty($error)): ?>
        <div class="error">
            <span class="error-icon">⚠️</span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- TABS -->
        <div class="tabs">
            <button type="button" 
                    class="tab <?php echo $login_type == 'kasir' ? 'active' : ''; ?>" 
                    onclick="showTab('kasir')">
                👤 KASIR
            </button>
            <button type="button" 
                    class="tab <?php echo $login_type == 'admin' ? 'active' : ''; ?>" 
                    onclick="showTab('admin')">
                🔐 ADMIN
            </button>
        </div>
        
        <!-- CONTENT -->
        <div class="content">
            <!-- KASIR FORM -->
            <form method="POST" action="" id="kasir-form" <?php echo $login_type != 'kasir' ? 'style="display:none;"' : ''; ?>>
                <input type="hidden" name="login_type" value="kasir">
                
                <div class="form-group">
                    <label class="form-label">NAMA ANDA</label>
                    <input type="text" 
                           name="nama_karyawan" 
                           class="form-input"
                           placeholder="Masukkan nama Anda"
                           required>
                    <p class="form-hint">* Tidak perlu password untuk kasir</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">PILIH CABANG</label>
                    <select name="id_cabang" class="form-input" required>
                        <option value="">-- Pilih Cabang Toko --</option>
                        <?php while ($cabang = $cabang_result->fetch_assoc()): ?>
                        <option value="<?php echo $cabang['id_cabang']; ?>">
                            <?php echo htmlspecialchars($cabang['nama_cabang']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">
                    🚀 MASUK SEBAGAI KASIR
                </button>
            </form>
            
            <!-- ADMIN FORM -->
            <form method="POST" action="" id="admin-form" <?php echo $login_type != 'admin' ? 'style="display:none;"' : ''; ?>>
                <input type="hidden" name="login_type" value="admin">
                
                <div class="form-group">
                    <label class="form-label">NAMA ADMIN</label>
                    <input type="text" 
                           name="nama_karyawan" 
                           class="form-input"
                           placeholder="Masukkan nama admin"
                           value="<?php echo isset($_POST['login_type']) && $_POST['login_type'] == 'admin' ? htmlspecialchars($_POST['nama_karyawan'] ?? '') : ''; ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">PASSWORD</label>
                    <div class="password-toggle">
                        <input type="password" 
                               name="password" 
                               id="password-input"
                               class="form-input"
                               placeholder="Masukkan password"
                               required>
                        <button type="button" class="toggle-btn" onclick="togglePassword()">
                            👁️
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    🔐 MASUK SEBAGAI ADMIN
                </button>
            </form>
            
            <!-- MOBILE INFO -->
            <div class="mobile-info">
                <p><strong>📱 Cara Login:</strong></p>
                <p>• <strong>Kasir</strong>: cukup nama & cabang</p>
                <p>• <strong>Admin</strong>: butuh nama & password</p>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all forms
            document.getElementById('kasir-form').style.display = 'none';
            document.getElementById('admin-form').style.display = 'none';
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected form and activate tab
            document.getElementById(tabName + '-form').style.display = 'block';
            event.target.classList.add('active');
            
            // Update URL without reloading
            window.history.pushState({}, '', '?type=' + tabName);
            
            // Focus on first input
            const firstInput = document.getElementById(tabName + '-form').querySelector('input, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
        
        function togglePassword() {
            const passwordInput = document.getElementById('password-input');
            const toggleBtn = event.target;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '👁️‍🗨️';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        // Auto-focus on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeForm = document.querySelector('form:not([style*="display:none"])');
            if (activeForm) {
                const firstInput = activeForm.querySelector('input, select');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 300);
                }
            }
            
            // Make sure all buttons are touch-friendly
            document.querySelectorAll('button, input[type="submit"], .tab').forEach(btn => {
                btn.style.minHeight = '50px';
            });
        });
    </script>
</body>
</html>