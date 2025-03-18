<?php
require_once '../../config/timezone.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Permitir solicitudes AJAX
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener parámetros
$courtId = isset($_GET['court_id']) ? intval($_GET['court_id']) : null;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validar parámetros
if (!$courtId) {
    echo json_encode(['error' => 'Se requiere el ID de la cancha']);
    exit;
}

// Verificar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD']);
    exit;
}

// Consultar reservas para la cancha y fecha especificadas
$query = "SELECT id, hora_inicio, hora_fin, estado 
          FROM reservations 
          WHERE court_id = :court_id 
          AND fecha = :fecha 
          AND estado != 'cancelada' 
          ORDER BY hora_inicio ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':court_id', $courtId);
$stmt->bindParam(':fecha', $date);
$stmt->execute();

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Devolver los datos en formato JSON
echo json_encode([
    'court_id' => $courtId,
    'date' => $date,
    'reservations' => $reservations,
    'timestamp' => date('Y-m-d H:i:s')
]);