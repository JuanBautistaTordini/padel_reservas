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
    $courtId = $_POST['court_id'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $superficie = trim($_POST['superficie'] ?? '');
    $disponibilidadInicio = $_POST['disponibilidad_inicio'] ?? '';
    $disponibilidadFin = $_POST['disponibilidad_fin'] ?? '';
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
    
    // Buscar donde se reciben los datos del formulario

    // Validar campos vacíos
    if (empty($courtId) || empty($nombre) || empty($superficie) || empty($disponibilidadInicio) || empty($disponibilidadFin)) {
        header('Location: ../../../index.php?page=admin-courts&error=empty_fields');
        exit;
    }
    
    // Validar que la hora de fin sea posterior a la hora de inicio
    if ($disponibilidadInicio >= $disponibilidadFin) {
        header('Location: ../../../index.php?page=admin-courts&error=invalid_time');
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener la información actual de la cancha
    $query = "SELECT imagen FROM courts WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $courtId);
    $stmt->execute();
    $court = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Procesar la imagen si se ha subido
    $imagenNombre = $court['imagen']; // Mantener la imagen actual por defecto
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../../uploads/courts/';
        $fileInfo = pathinfo($_FILES['imagen']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Validar extensión
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($extension, $allowedExtensions)) {
            header('Location: ../../../index.php?page=admin-courts&error=image_upload');
            exit;
        }
        
        // Validar tamaño (2MB máximo)
        if ($_FILES['imagen']['size'] > 2 * 1024 * 1024) {
            header('Location: ../../../index.php?page=admin-courts&error=image_upload');
            exit;
        }
        
        // Generar nombre único para la imagen
        $imagenNombre = uniqid() . '.' . $extension;
        $uploadFile = $uploadDir . $imagenNombre;
        
        // Mover la imagen al directorio de destino
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadFile)) {
            header('Location: ../../../index.php?page=admin-courts&error=image_upload');
            exit;
        }
        
        // Eliminar la imagen anterior si existe
        if (!empty($court['imagen']) && file_exists($uploadDir . $court['imagen'])) {
            unlink($uploadDir . $court['imagen']);
        }
    }
    
    // Buscar la consulta de actualización 
    $query = "UPDATE courts SET 
              nombre = :nombre, 
              superficie = :superficie, 
              disponibilidad_inicio = :disponibilidad_inicio, 
              disponibilidad_fin = :disponibilidad_fin, 
              estado = :estado,
              imagen = :imagen
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':superficie', $superficie);
    $stmt->bindParam(':disponibilidad_inicio', $disponibilidadInicio);
    $stmt->bindParam(':disponibilidad_fin', $disponibilidadFin);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':imagen', $imagenNombre);

    $stmt->bindParam(':id', $courtId);
    
    if ($stmt->execute()) {
        header('Location: ../../../index.php?page=admin-courts&success=updated');
        exit;
    } else {
        header('Location: ../../../index.php?page=admin-courts&error=database_error');
        exit;
    }
} else {
    // Si no es una petición POST, redirigir al panel de administración
    header('Location: ../../../index.php?page=admin-courts');
    exit;
}
?>

