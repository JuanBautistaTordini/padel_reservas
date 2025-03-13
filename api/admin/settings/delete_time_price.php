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
    
    // Validar campos vacíos
    if (empty($priceId)) {
        header('Location: ../../../index.php?page=admin-settings&error=empty_fields');
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Eliminar precio por turno
    $query = "DELETE FROM time_prices WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $priceId);
    
    if ($stmt->execute()) {
        header('Location: ../../../index.php?page=admin-settings&success=price_deleted');
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

