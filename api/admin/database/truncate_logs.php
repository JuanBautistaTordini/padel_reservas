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
    // Definir las tablas de logs que se pueden truncar
    // Ajusta esto según las tablas de logs específicas de tu sistema
    $logTables = ['system_logs', 'user_activity_logs', 'error_logs'];
    
    // Verificar si cada tabla existe antes de intentar truncarla
    $success = true;
    foreach ($logTables as $table) {
        // Verificar si la tabla existe
        $checkQuery = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($checkQuery);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // La tabla existe, truncarla
            $truncateQuery = "TRUNCATE TABLE `$table`";
            $stmt = $db->prepare($truncateQuery);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
    }
    
    // También podemos eliminar registros antiguos de otras tablas que no queremos truncar completamente
    // Por ejemplo, eliminar reservas canceladas con más de 6 meses de antigüedad
    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
    $deleteOldReservationsQuery = "DELETE FROM reservations WHERE estado = 'cancelada' AND fecha < :old_date";
    $stmt = $db->prepare($deleteOldReservationsQuery);
    $stmt->bindParam(':old_date', $sixMonthsAgo);
    $stmt->execute();
    
    if ($success) {
        header('Location: ../../../index.php?page=database_admin&action_success=truncate_logs');
    } else {
        header('Location: ../../../index.php?page=database_admin&action_error=truncate_logs');
    }
} catch (PDOException $e) {
    error_log("Error al truncar logs: " . $e->getMessage());
    header('Location: ../../../index.php?page=database_admin&action_error=truncate_logs');
}