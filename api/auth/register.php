<?php
session_start();
require_once '../../config/database.php';

// Verificar si ya hay una sesión activa
if (isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Verificar que se recibieron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validar campos vacíos
    if (empty($nombre) || empty($email) || empty($telefono) || empty($password)) {
        header('Location: ../../index.php?page=register&error=empty_fields');
        exit;
    }
    
    // Validar longitud de contraseña
    if (strlen($password) < 6) {
        header('Location: ../../index.php?page=register&error=password_short');
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
        header('Location: ../../index.php?page=register&error=email_exists');
        exit;
    }
    
    // Encriptar contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $query = "INSERT INTO users (nombre, email, telefono, password, rol) VALUES (:nombre, :email, :telefono, :password, 'cliente')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':password', $hashedPassword);
    
    if ($stmt->execute()) {
        header('Location: ../../index.php?page=login&success=registered');
        exit;
    } else {
        header('Location: ../../index.php?page=register&error=database_error');
        exit;
    }
} else {
    // Si no es una petición POST, redirigir al formulario
    header('Location: ../../index.php?page=register');
    exit;
}
?>

