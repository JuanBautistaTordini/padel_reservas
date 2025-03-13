<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'court_reservation';
    private $username = 'root';
    private $password = '';
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        
            // Establecer la zona horaria de MySQL a Argentina/Buenos Aires
            $this->conn->exec("SET time_zone = '-03:00'"); // UTC-3 para Argentina
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }
        
        return $this->conn;
    }
}
?>

