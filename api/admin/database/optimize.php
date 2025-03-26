<?php
session_start();

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../index.php?page=unauthorized');
    exit;
}

require_once '../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Obtener todas las tablas de la base de datos
    $query = "SHOW TABLES FROM court_reservation";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Optimizar cada tabla
    $success = true;
    foreach ($tables as $table) {
        $optimizeQuery = "OPTIMIZE TABLE `$table`";
        $stmt = $db->prepare($optimizeQuery);
        if (!$stmt->execute()) {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        header('Location: ../../../index.php?page=database_admin&action_success=optimize');
    } else {
        header('Location: ../../../index.php?page=database_admin&action_error=optimize');
    }
} catch (PDOException $e) {
    error_log("Error al optimizar tablas: " . $e->getMessage());
    header('Location: ../../../index.php?page=database_admin&action_error=optimize');
}