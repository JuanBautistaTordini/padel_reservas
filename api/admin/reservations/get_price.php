<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../utils/price_calculator.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verificar que se recibieron los datos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $fecha = $_GET['fecha'] ?? '';
    $horaInicio = $_GET['hora_inicio'] ?? '';
    $horaFin = $_GET['hora_fin'] ?? '';
    
    // Validar campos vacíos
    if (empty($fecha) || empty($horaInicio) || empty($horaFin)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Empty fields']);
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Calcular el precio
    $price = PriceCalculator::calculatePrice($fecha, $horaInicio.':00', $horaFin.':00', $db);
    
    // Devolver el precio o un mensaje si no está configurado
    header('Content-Type: application/json');
    if ($price === null) {
        echo json_encode(['success' => false, 'message' => 'No hay precio configurado para este horario']);
    } else {
        echo json_encode(['success' => true, 'price' => $price]);
    }
    exit;
} else {
    // Si no es una petición GET, devolver error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>

