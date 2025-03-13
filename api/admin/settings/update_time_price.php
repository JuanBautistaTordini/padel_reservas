<?php
session_start();
require_once '../../../config/database.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../index.php?page=unauthorized');
    exit;
}

// Verificar que se recibieron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $priceId = $_POST['price_id'] ?? '';
    $dayOfWeek = $_POST['day_of_week'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $price = $_POST['price'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Validar campos vacíos
    if (empty($priceId) || empty($dayOfWeek) || empty($startTime) || empty($endTime) || $price === '') {
        header('Location: ../../../index.php?page=admin-settings&error=empty_fields');
        exit;
    }
    
    // Validar que la hora de fin sea posterior a la hora de inicio
    if ($startTime >= $endTime) {
        header('Location: ../../../index.php?page=admin-settings&error=invalid_time_range');
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si hay superposición de rangos de tiempo para el mismo día (excluyendo el precio actual)
    $query = "SELECT COUNT(*) FROM time_prices 
              WHERE day_of_week = :day_of_week 
              AND id != :price_id
              AND ((start_time <= :start_time AND end_time > :start_time) 
              OR (start_time < :end_time AND end_time >= :end_time)
              OR (:start_time <= start_time AND :end_time >= end_time))";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':day_of_week', $dayOfWeek);
    $stmt->bindParam(':price_id', $priceId);
    $stmt->bindParam(':start_time', $startTime);
    $stmt->bindParam(':end_time', $endTime);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        header('Location: ../../../index.php?page=admin-settings&error=overlapping_time_range');
        exit;
    }
    
    // Actualizar precio por turno
    $query = "UPDATE time_prices 
              SET day_of_week = :day_of_week, 
                  start_time = :start_time, 
                  end_time = :end_time, 
                  price = :price, 
                  description = :description 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':day_of_week', $dayOfWeek);
    $stmt->bindParam(':start_time', $startTime);
    $stmt->bindParam(':end_time', $endTime);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':id', $priceId);
    
    if ($stmt->execute()) {
        header('Location: ../../../index.php?page=admin-settings&success=price_updated');
        exit;
    } else {
        header('Location: ../../../index.php?page=admin-settings&error=database_error');
        exit;
    }
} else {
    // Si no es una petición POST, redirigir al panel de administración
    header('Location: ../../../index.php?page=admin-settings');
    exit;
}
?>

