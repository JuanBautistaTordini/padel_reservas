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

    public function getDatabaseSize() {
        try {
            $query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                      FROM information_schema.tables 
                      WHERE table_schema = :database_name";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':database_name', $this->db_name);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['size_mb'] ?: 0;
        } catch (PDOException $e) {
            error_log("Error al obtener tamaño de la base de datos: " . $e->getMessage());
            return 0; // Retorna 0 en caso de error para evitar fallos
        }
    }
}
?>

