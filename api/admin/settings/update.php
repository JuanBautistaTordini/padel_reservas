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
    $settingKey = $_POST['setting_key'] ?? '';
    $settingValue = $_POST['setting_value'] ?? '';
    
    // Validar campos vacíos
    if (empty($settingKey) || $settingValue === '') {
        header('Location: ../../../index.php?page=admin-settings&error=empty_fields');
        exit;
    }
    
    // Validaciones específicas según el tipo de configuración
    if ($settingKey === 'time_interval') {
        // Validar que sea un número entre 30 y 240
        if (!is_numeric($settingValue) || $settingValue < 30 || $settingValue > 240) {
            header('Location: ../../../index.php?page=admin-settings&error=invalid_value');
            exit;
        }
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si la configuración ya existe
    $query = "SELECT COUNT(*) FROM settings WHERE setting_key = :setting_key";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':setting_key', $settingKey);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        // Actualizar configuración existente
        $query = "UPDATE settings SET setting_value = :setting_value WHERE setting_key = :setting_key";
    } else {
        // Insertar nueva configuración
        $query = "INSERT INTO settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':setting_key', $settingKey);
    $stmt->bindParam(':setting_value', $settingValue);
    
    if ($stmt->execute()) {
        header('Location: ../../../index.php?page=admin-settings&success=updated');
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

