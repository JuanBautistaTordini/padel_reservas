<?php
session_start();

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../index.php?page=unauthorized');
    exit;
}

// Verificar que se haya proporcionado un nombre de tabla
if (!isset($_GET['table']) || empty($_GET['table'])) {
    header('Location: ../../../index.php?page=database_admin&action_error=optimize');
    exit;
}

$tableName = $_GET['table'];

require_once '../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Verificar que la tabla existe
    $query = "SHOW TABLES LIKE :table_name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':table_name', $tableName);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // La tabla no existe
        header('Location: ../../../index.php?page=database_admin&action_error=optimize');
        exit;
    }
    
    // Optimizar la tabla
    $optimizeQuery = "OPTIMIZE TABLE `$tableName`";
    $stmt = $db->prepare($optimizeQuery);
    
    if ($stmt->execute()) {
        header('Location: ../../../index.php?page=database_admin&action_success=optimize');
    } else {
        header('Location: ../../../index.php?page=database_admin&action_error=optimize');
    }
} catch (PDOException $e) {
    error_log("Error al optimizar tabla $tableName: " . $e->getMessage());
    header('Location: ../../../index.php?page=database_admin&action_error=optimize');
}