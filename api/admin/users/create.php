<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../utils/mail.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../index.php?page=unauthorized');
    exit;
}

// Verificar que se recibieron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = $_POST['rol'] ?? 'cliente';
    $password = $_POST['password'] ?? '';
    $sendEmail = isset($_POST['send_email']) ? true : false;
    
    // Validar campos vacíos
    if (empty($nombre) || empty($email) || empty($telefono) || empty($password)) {
        header('Location: ../../../index.php?page=admin-users&error=empty_fields');
        exit;
    }
    
    // Validar longitud de contraseña
    if (strlen($password) < 6) {
        header('Location: ../../../index.php?page=admin-users&error=password_short');
        exit;
    }
    
    // Validar rol
    if (!in_array($rol, ['cliente', 'admin'])) {
        header('Location: ../../../index.php?page=admin-users&error=invalid_role');
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el email ya existe
    $query = "SELECT COUNT(*) FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        header('Location: ../../../index.php?page=admin-users&error=email_exists');
        exit;
    }
    
    // Encriptar contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $query = "INSERT INTO users (nombre, email, telefono, password, rol) VALUES (:nombre, :email, :telefono, :password, :rol)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':rol', $rol);
    
    if ($stmt->execute()) {
        $userId = $db->lastInsertId();
        
        // Obtener los datos completos del usuario
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Enviar correo con datos de acceso si se solicitó
        if ($sendEmail) {
            Mail::sendWelcomeEmail($user, $password);
        }
        
        header('Location: ../../../index.php?page=admin-users&success=created');
        exit;
    } else {
        header('Location: ../../../index.php?page=admin-users&error=database_error');
        exit;
    }
} else {
    // Si no es una petición POST, redirigir al formulario
    header('Location: ../../../index.php?page=admin-users');
    exit;
}
?>

