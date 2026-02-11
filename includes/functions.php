<?php
// includes/functions.php
// Kumpulan fungsi helper untuk sistem

function formatRupiah($angka) {
    if (empty($angka) || !is_numeric($angka)) {
        return 'Rp 0';
    }
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function tanggalIndo($date) {
    if (empty($date)) {
        return '-';
    }
    
    $hari = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $bulan = array(
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    $hari_num = date('w', $timestamp);
    $tanggal = date('j', $timestamp);
    $bulan_num = date('n', $timestamp) - 1;
    $tahun = date('Y', $timestamp);
    
    return $hari[$hari_num] . ', ' . $tanggal . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

function getNamaCabang($id_cabang) {
    require_once 'database.php';
    $db = new Database();
    
    $sql = "SELECT nama_cabang FROM cabang WHERE id_cabang = ?";
    $result = $db->query($sql, [$id_cabang]);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['nama_cabang'];
    }
    
    return 'Cabang ' . $id_cabang;
}

function getNamaBarang($id_barang) {
    require_once 'database.php';
    $db = new Database();
    
    $sql = "SELECT nama_barang FROM barang WHERE id_barang = ?";
    $result = $db->query($sql, [$id_barang]);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['nama_barang'];
    }
    
    return 'Barang Tidak Diketahui';
}

function cleanInput($data) {
    if (empty($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['id_karyawan']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function showMessage($type, $message) {
    $colors = [
        'success' => 'bg-green-100 border-green-500 text-green-700',
        'error' => 'bg-red-100 border-red-500 text-red-700',
        'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
        'info' => 'bg-blue-100 border-blue-500 text-blue-700'
    ];
    
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    $icon = $icons[$type] ?? $icons['info'];
    
    return "
        <div class='border-l-4 p-4 mb-4 {$color}'>
            <div class='flex items-center'>
                <div class='text-xl mr-3'>{$icon}</div>
                <div>{$message}</div>
            </div>
        </div>
    ";
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) {
        return '-';
    }
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) {
        return '-';
    }
    return date($format, strtotime($datetime));
}

function calculateAge($date) {
    if (empty($date)) {
        return 0;
    }
    
    $birthDate = new DateTime($date);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y;
}

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    
    return substr($initials, 0, 2);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    // Basic phone validation (Indonesia)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 13;
}

function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isImage($filename) {
    $ext = getFileExtension($filename);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    return in_array($ext, $imageExtensions);
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getCurrentYear() {
    return date('Y');
}

function getCurrentMonth() {
    return date('m');
}

function getCurrentDate() {
    return date('Y-m-d');
}

function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

function addDays($date, $days) {
    $date = new DateTime($date);
    $date->add(new DateInterval("P{$days}D"));
    return $date->format('Y-m-d');
}

function subtractDays($date, $days) {
    $date = new DateTime($date);
    $date->sub(new DateInterval("P{$days}D"));
    return $date->format('Y-m-d');
}

function dateDifference($date1, $date2) {
    $date1 = new DateTime($date1);
    $date2 = new DateTime($date2);
    $interval = $date1->diff($date2);
    return $interval->days;
}

function isWeekend($date) {
    $dayOfWeek = date('w', strtotime($date));
    return $dayOfWeek == 0 || $dayOfWeek == 6; // 0 = Sunday, 6 = Saturday
}

function getDayName($date) {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $dayNumber = date('w', strtotime($date));
    return $days[$dayNumber] ?? 'Unknown';
}

function getMonthName($monthNumber) {
    $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return $months[$monthNumber - 1] ?? 'Unknown';
}

function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decryptData($data, $key) {
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}

function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

function arrayToOptions($array, $selected = '') {
    $options = '';
    foreach ($array as $value => $label) {
        $isSelected = ($value == $selected) ? 'selected' : '';
        $options .= "<option value='{$value}' {$isSelected}>{$label}</option>";
    }
    return $options;
}

function formatNumber($number, $decimals = 0) {
    if (!is_numeric($number)) {
        return '0';
    }
    return number_format($number, $decimals, ',', '.');
}

function calculatePercentage($part, $total) {
    if ($total == 0) {
        return 0;
    }
    return round(($part / $total) * 100, 2);
}

function getOrdinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if (($number % 100) >= 11 && ($number % 100) <= 13) {
        return $number . 'th';
    } else {
        return $number . $ends[$number % 10];
    }
}

function highlightText($text, $search) {
    if (empty($search)) {
        return $text;
    }
    return preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $text);
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'baru saja';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari lalu';
    } else {
        return date('d/m/Y', $time);
    }
}
?>