<?php
// includes/auth.php
require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        
        // Start session hanya jika belum ada
        if (session_status() === PHP_SESSION_NONE) {
            // Set session timeout sebelum session_start
            ini_set('session.gc_maxlifetime', 1800);
            session_set_cookie_params(1800);
            session_start();
        }
    }
    
    public function loginAdmin($nama_karyawan, $password) {
        $sql = "SELECT * FROM karyawan WHERE nama_karyawan = ? AND is_admin = TRUE";
        $result = $this->db->query($sql, [$nama_karyawan]);
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                $update_sql = "UPDATE karyawan SET last_login = NOW() WHERE id_karyawan = ?";
                $this->db->query($update_sql, [$admin['id_karyawan']]);
                
                $session_id = uniqid('session_', true);
                $session_sql = "INSERT INTO sessions (session_id, id_karyawan, id_cabang, is_admin) VALUES (?, ?, ?, TRUE)";
                $this->db->insert($session_sql, [$session_id, $admin['id_karyawan'], $admin['id_cabang']]);
                
                $_SESSION['session_id'] = $session_id;
                $_SESSION['id_karyawan'] = $admin['id_karyawan'];
                $_SESSION['nama_karyawan'] = $admin['nama_karyawan'];
                $_SESSION['id_cabang'] = $admin['id_cabang'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['is_admin'] = true;
                
                return true;
            }
        }
        return false;
    }
    
    // TAMBAHKAN METHOD INI - YANG HILANG
    public function loginKasir($nama_karyawan, $id_cabang) {
        // Cek jika karyawan kasir sudah ada
        $sql = "SELECT * FROM karyawan WHERE nama_karyawan = ? AND id_cabang = ? AND is_admin = FALSE";
        $result = $this->db->query($sql, [$nama_karyawan, $id_cabang]);
        
        if ($result->num_rows > 0) {
            $karyawan = $result->fetch_assoc();
            
            $update_sql = "UPDATE karyawan SET last_login = NOW() WHERE id_karyawan = ?";
            $this->db->query($update_sql, [$karyawan['id_karyawan']]);
            
            $session_id = uniqid('session_', true);
            $session_sql = "INSERT INTO sessions (session_id, id_karyawan, id_cabang, is_admin) VALUES (?, ?, ?, FALSE)";
            $this->db->insert($session_sql, [$session_id, $karyawan['id_karyawan'], $id_cabang]);
            
            $_SESSION['session_id'] = $session_id;
            $_SESSION['id_karyawan'] = $karyawan['id_karyawan'];
            $_SESSION['nama_karyawan'] = $karyawan['nama_karyawan'];
            $_SESSION['id_cabang'] = $id_cabang;
            $_SESSION['role'] = $karyawan['role'];
            $_SESSION['is_admin'] = false;
            
            return true;
        }
        
        // Jika tidak ada, buat karyawan kasir baru
        $sql_insert = "INSERT INTO karyawan (nama_karyawan, id_cabang, role, is_admin) VALUES (?, ?, 'kasir', FALSE)";
        $id_karyawan = $this->db->insert($sql_insert, [$nama_karyawan, $id_cabang]);
        
        if ($id_karyawan) {
            $session_id = uniqid('session_', true);
            $session_sql = "INSERT INTO sessions (session_id, id_karyawan, id_cabang, is_admin) VALUES (?, ?, ?, FALSE)";
            $this->db->insert($session_sql, [$session_id, $id_karyawan, $id_cabang]);
            
            $_SESSION['session_id'] = $session_id;
            $_SESSION['id_karyawan'] = $id_karyawan;
            $_SESSION['nama_karyawan'] = $nama_karyawan;
            $_SESSION['id_cabang'] = $id_cabang;
            $_SESSION['role'] = 'kasir';
            $_SESSION['is_admin'] = false;
            
            return true;
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['session_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    public function logout() {
        if (isset($_SESSION['session_id'])) {
            $sql = "DELETE FROM sessions WHERE session_id = ?";
            $this->db->query($sql, [$_SESSION['session_id']]);
        }
        
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id_karyawan' => $_SESSION['id_karyawan'],
                'nama_karyawan' => $_SESSION['nama_karyawan'],
                'id_cabang' => $_SESSION['id_cabang'],
                'role' => $_SESSION['role'],
                'is_admin' => $_SESSION['is_admin']
            ];
        }
        return null;
    }
}

$auth = new Auth();
?>