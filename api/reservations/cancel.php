<?php
session_start();
require_once '../../config/database.php';
require_once '../../utils/mail.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php?page=login');
    exit;
}

// Verificar que se recibieron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $reservationId = $_POST['reservation_id'] ?? '';
    
    // Validar campos vacíos
    if (empty($reservationId)) {
        header('Location: ../../index.php?page=reservations');
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que la reserva pertenece al usuario y no está cancelada
    $query = "SELECT r.*, c.nombre as court_name, c.id as court_id 
              FROM reservations r 
              JOIN courts c ON r.court_id = c.id 
              WHERE r.id = :id AND r.user_id = :user_id AND r.estado != 'cancelada'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reservationId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        header('Location: ../../index.php?page=reservations&error=not_found');
        exit;
    }
    
    // Actualizar estado de la reserva a cancelada
    $query = "UPDATE reservations SET estado = 'cancelada' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reservationId);
    
    if ($stmt->execute()) {
        // Obtener datos del usuario
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener datos de la cancha
        $query = "SELECT * FROM courts WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reservation['court_id']);
        $stmt->execute();
        $court = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Guardar el estado anterior para la notificación al administrador
        $estado_anterior = $reservation['estado'];
        
        // Actualizar el estado en el array de reserva
        $reservation['estado'] = 'cancelada';
        $reservation['estado_anterior'] = $estado_anterior;
        
        // Enviar correo al usuario
        $mailSent = Mail::notifyReservationUpdate($reservation, $user, $court, 'cancelada');
        
        // Registrar en el log si el correo no se envió
        if (!$mailSent) {
            error_log("Error al enviar correo de notificación de cancelación de reserva al usuario. ID: {$reservationId}");
        }
        
        // Enviar correo al administrador
        $adminMailSent = Mail::notifyAdminAboutUserCancellation($reservation, $user, $court);
        
        // Registrar en el log si el correo al administrador no se envió
        if (!$adminMailSent) {
            error_log("Error al enviar correo de notificación de cancelación de reserva al administrador. ID: {$reservationId}");
        }
        
        header('Location: ../../index.php?page=reservations&success=cancelled');
        exit;
    } else {
        header('Location: ../../index.php?page=reservations&error=database_error');
        exit;
    }
} else {
    // Si no es una petición POST, redirigir a la página de reservas
    header('Location: ../../index.php?page=reservations');
    exit;
}
?>

