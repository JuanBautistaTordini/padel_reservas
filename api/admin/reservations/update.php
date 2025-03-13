<?php
session_start();
require_once '../../../config/timezone.php'; // Incluir configuración de zona horaria
require_once '../../../config/database.php';
require_once '../../../utils/mail.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../index.php?page=unauthorized');
    exit;
}

// Verificar que se recibieron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = $_SESSION['user_id'];
    $reservationId = $_POST['reservation_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Validar campos vacíos
    if (empty($reservationId) || empty($status)) {
        header('Location: ../../../index.php?page=admin-reservations');
        exit;
    }
    
    // Validar estado
    if (!in_array($status, ['pendiente', 'confirmada', 'cancelada'])) {
        header('Location: ../../../index.php?page=admin-reservations');
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener datos de la reserva antes de actualizarla
    $query = "SELECT r.*, u.id as user_id, c.id as court_id 
              FROM reservations r 
              JOIN users u ON r.user_id = u.id 
              JOIN courts c ON r.court_id = c.id 
              WHERE r.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reservationId);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        header('Location: ../../../index.php?page=admin-reservations&error=not_found');
        exit;
    }
    
    // Verificar si el estado ha cambiado
    if ($reservation['estado'] === $status) {
        header('Location: ../../../index.php?page=admin-reservations&info=no_changes');
        exit;
    }
    
    // Actualizar estado de la reserva
    $query = "UPDATE reservations SET estado = :estado WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':estado', $status);
    $stmt->bindParam(':id', $reservationId);
    
    if ($stmt->execute()) {
        // Obtener datos del usuario
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reservation['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener datos de la cancha
        $query = "SELECT * FROM courts WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reservation['court_id']);
        $stmt->execute();
        $court = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener datos del administrador
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $adminId);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Actualizar el estado en el array de reserva
        $reservation['estado'] = $status;
        
        // Enviar correo al usuario con formato específico para actualizaciones por administrador
        $mailSent = Mail::notifyAdminReservationUpdate($reservation, $user, $court, $status, $admin);
        
        // Registrar en el log si el correo no se envió
        if (!$mailSent) {
            error_log("Error al enviar correo de notificación de actualización de reserva. ID: {$reservationId}, Estado: {$status}");
        }
        
        header('Location: ../../../index.php?page=admin-reservations&success=updated');
        exit;
    } else {
        header('Location: ../../../index.php?page=admin-reservations&error=database_error');
        exit;
    }
} else {
    // Si no es una petición POST, redirigir al panel de administración
    header('Location: ../../../index.php?page=admin-reservations');
    exit;
}
?>

