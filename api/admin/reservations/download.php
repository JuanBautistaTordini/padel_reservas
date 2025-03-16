<?php
ob_start();
require_once '../../../config/timezone.php'; // Incluir configuración de zona horaria
require_once '../../../config/database.php';
require_once '../../../vendor/tecnickcom/tcpdf/tcpdf.php'; // Ajusta la ruta a TCPDF

ini_set('display_errors', 0);
error_reporting(0);

// Verificar si el usuario es administrador
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

// Conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener filtros de la URL
$courtFilter = isset($_GET['court_id']) ? $_GET['court_id'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Consulta con filtros
$query = "SELECT r.*, u.nombre as user_name, u.email as user_email, c.nombre as court_name 
          FROM reservations r 
          JOIN users u ON r.user_id = u.id 
          JOIN courts c ON r.court_id = c.id 
          WHERE 1=1";
$params = [];
if (!empty($courtFilter)) {
    $query .= " AND r.court_id = :court_id";
    $params[':court_id'] = $courtFilter;
}
if (!empty($dateFilter)) {
    $query .= " AND r.fecha = :fecha";
    $params[':fecha'] = $dateFilter;
}
if (!empty($statusFilter)) {
    $query .= " AND r.estado = :estado";
    $params[':estado'] = $statusFilter;
}
$query .= " ORDER BY r.fecha ASC, r.hora_inicio ASC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear el PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetTitle('Reporte de Reservas');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// Título
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Reporte de Reservas', 0, 1, 'C');

// Fecha de generación
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Ln(5);

// Tabla de datos
$pdf->SetFont('helvetica', '', 10);
$header = ['ID', 'Usuario', 'Cancha', 'Fecha', 'Hora Inicio', 'Hora Fin', 'Precio', 'Estado'];
$widths = [10, 40, 30, 20, 20, 20, 20, 20];

// Encabezados
$pdf->SetFillColor(200, 220, 255);
foreach ($header as $key => $col) {
    $pdf->Cell($widths[$key], 7, $col, 1, 0, 'C', 1);
}
$pdf->Ln();

// Datos
$pdf->SetFont('helvetica', '', 9);
foreach ($reservations as $reservation) {
    $pdf->Cell($widths[0], 6, $reservation['id'], 1);
    $pdf->Cell($widths[1], 6, $reservation['user_name'], 1);
    $pdf->Cell($widths[2], 6, $reservation['court_name'], 1);
    $pdf->Cell($widths[3], 6, date('d/m/Y', strtotime($reservation['fecha'])), 1);
    $pdf->Cell($widths[4], 6, substr($reservation['hora_inicio'], 0, 5), 1);
    $pdf->Cell($widths[5], 6, substr($reservation['hora_fin'], 0, 5), 1);
    $pdf->Cell($widths[6], 6, number_format($reservation['precio'], 2, ',', '.'), 1, 0, 'R');
    $pdf->Cell($widths[7], 6, $reservation['estado'], 1);
    $pdf->Ln();
}

// Descargar el PDF
ob_end_clean();
$pdf->Output('reservas.pdf', 'D');
exit;