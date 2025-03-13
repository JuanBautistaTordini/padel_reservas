<?php
session_start();
require_once '../../config/timezone.php'; // Incluir configuración de zona horaria
require_once '../../config/database.php';
require_once '../../utils/mail.php';
require_once '../../utils/price_calculator.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
  header('Location: ../../index.php?page=login');
  exit;
}

// Verificar que se recibieron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userId = $_SESSION['user_id'];
  $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
  $courtId = $_POST['court_id'] ?? '';
  $fecha = $_POST['fecha'] ?? '';
  $horaInicio = $_POST['hora_inicio'] ?? '';
  $horaFin = $_POST['hora_fin'] ?? '';
  $precio = $_POST['precio'] ?? '';
  
  // Si es administrador, puede crear reservas para otros usuarios
  $clientId = isset($_POST['client_id']) && $isAdmin ? $_POST['client_id'] : $userId;
  
  // Validar campos vacíos
  if (empty($courtId) || empty($fecha) || empty($horaInicio) || empty($horaFin)) {
      header('Location: ../../index.php?page=courts');
      exit;
  }
  
  // Conectar a la base de datos
  $database = new Database();
  $db = $database->getConnection();

  // Si no se recibió un precio válido, calcularlo
  if (empty($precio) || $precio <= 0) {
      $precio = PriceCalculator::calculatePrice($fecha, $horaInicio.':00', $horaFin.':00', $db);
  }
  
  // Verificar si el horario ya está reservado
  $query = "SELECT COUNT(*) FROM reservations 
            WHERE court_id = :court_id AND fecha = :fecha 
            AND ((hora_inicio <= :hora_inicio AND hora_fin > :hora_inicio) 
            OR (hora_inicio < :hora_fin AND hora_fin >= :hora_fin))
            AND estado != 'cancelada'";
  $stmt = $db->prepare($query);
  $stmt->bindParam(':court_id', $courtId);
  $stmt->bindParam(':fecha', $fecha);
  $stmt->bindParam(':hora_inicio', $horaInicio);
  $stmt->bindParam(':hora_fin', $horaFin);
  $stmt->execute();
  
  $isReserved = (int)$stmt->fetchColumn() > 0;
  
  if ($isReserved) {
      header('Location: ../../index.php?page=courts&date=' . $fecha . '&error=already_reserved');
      exit;
  }
  
  // Estado inicial (los administradores pueden crear reservas ya confirmadas)
  $estado = $isAdmin ? 'confirmada' : 'pendiente';
  
  // Insertar nueva reserva
  $query = "INSERT INTO reservations (user_id, court_id, fecha, hora_inicio, hora_fin, estado, precio) 
            VALUES (:user_id, :court_id, :fecha, :hora_inicio, :hora_fin, :estado, :precio)";
  $stmt = $db->prepare($query);
  $stmt->bindParam(':user_id', $clientId);
  $stmt->bindParam(':court_id', $courtId);
  $stmt->bindParam(':fecha', $fecha);
  $stmt->bindParam(':hora_inicio', $horaInicio);
  $stmt->bindParam(':hora_fin', $horaFin);
  $stmt->bindParam(':estado', $estado);
  $stmt->bindParam(':precio', $precio);
  
  if ($stmt->execute()) {
      $reservationId = $db->lastInsertId();
      
      // Obtener datos de la reserva
      $query = "SELECT * FROM reservations WHERE id = :id";
      $stmt = $db->prepare($query);
      $stmt->bindParam(':id', $reservationId);
      $stmt->execute();
      $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
      
      // Obtener datos del cliente (puede ser el usuario actual o un cliente seleccionado por el admin)
      $query = "SELECT * FROM users WHERE id = :id";
      $stmt = $db->prepare($query);
      $stmt->bindParam(':id', $clientId);
      $stmt->execute();
      $client = $stmt->fetch(PDO::FETCH_ASSOC);
      
      // Obtener datos de la cancha
      $query = "SELECT * FROM courts WHERE id = :id";
      $stmt = $db->prepare($query);
      $stmt->bindParam(':id', $courtId);
      $stmt->execute();
      $court = $stmt->fetch(PDO::FETCH_ASSOC);
      
      // Si es un administrador creando la reserva
      if ($isAdmin && $clientId != $userId) {
          // Obtener datos del administrador
          $query = "SELECT * FROM users WHERE id = :id";
          $stmt = $db->prepare($query);
          $stmt->bindParam(':id', $userId);
          $stmt->execute();
          $admin = $stmt->fetch(PDO::FETCH_ASSOC);
          
          // Enviar correo al complejo con formato de reserva creada por administrador
          Mail::notifyAdminCreatedReservation($reservation, $client, $court, $admin);
          
          // Enviar correo al cliente informándole de la reserva creada por el administrador
          Mail::notifyUserAboutAdminCreatedReservation($reservation, $client, $court);
      } else {
          // Enviar correo al complejo con formato estándar
          Mail::notifyNewReservation($reservation, $client, $court);
      }
      
      header('Location: ../../index.php?page=reservations&success=created');
      exit;
  } else {
      header('Location: ../../index.php?page=courts&date=' . $fecha . '&error=database_error');
      exit;
  }
} else {
  // Si no es una petición POST, redirigir a la página de canchas
  header('Location: ../../index.php?page=courts');
  exit;
}
?>

