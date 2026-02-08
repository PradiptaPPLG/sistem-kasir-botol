<?php
// includes/database.php
require_once 'config.php';

class Database {
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Koneksi gagal: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result;
    }
    
    public function insert($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $success = $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();
        
        return $success ? $insert_id : false;
    }
}

// Create global database instance
$db = new Database();
?>