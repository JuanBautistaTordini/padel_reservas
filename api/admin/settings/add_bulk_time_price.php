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
    $daysOfWeek = isset($_POST['days_of_week']) ? $_POST['days_of_week'] : [];
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $price = $_POST['price'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Validar campos vacíos
    if (empty($daysOfWeek) || empty($startTime) || empty($endTime) || $price === '') {
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
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($daysOfWeek as $dayOfWeek) {
        // Verificar si hay superposición de rangos de tiempo para el mismo día
        $query = "SELECT COUNT(*) FROM time_prices 
                  WHERE day_of_week = :day_of_week 
                  AND ((start_time <= :start_time AND end_time > :start_time) 
                  OR (start_time < :end_time AND end_time >= :end_time)
                  OR (:start_time <= start_time AND :end_time >= end_time))";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':day_of_week', $dayOfWeek);
        $stmt->bindParam(':start_time', $startTime);
        $stmt->bindParam(':end_time', $endTime);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // Si hay superposición, saltamos este día y continuamos con el siguiente
            $errorCount++;
            continue;
        }
        
        // Insertar nuevo precio por turno para este día
        $query = "INSERT INTO time_prices (day_of_week, start_time, end_time, price, description) 
                  VALUES (:day_of_week, :start_time, :end_time, :price, :description)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':day_of_week', $dayOfWeek);
        $stmt->bindParam(':start_time', $startTime);
        $stmt->bindParam(':end_time', $endTime);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $description);
        
        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    // Redirigir con mensaje apropiado
    if ($successCount > 0 && $errorCount == 0) {
        header('Location: ../../../index.php?page=admin-settings&success=bulk_price_added');
    } else if ($successCount > 0 && $errorCount > 0) {
        header('Location: ../../../index.php?page=admin-settings&success=partial_bulk_price_added&error=some_days_overlapping');
    } else {
        header('Location: ../../../index.php?page=admin-settings&error=all_days_overlapping');
    }
    exit;
} else {
    // Si no es una solicitud POST, redirigir a la página de configuración
    header('Location: ../../../index.php?page=admin-settings');
    exit;
}