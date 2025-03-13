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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validar campos vacíos
    if (empty($email) || empty($password)) {
        header('Location: ../../index.php?page=login&error=empty_fields');
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar usuario por email
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si el usuario existe y la contraseña es correcta
    if ($user && password_verify($password, $user['password'])) {
        // Iniciar sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['rol'];
        
        // Redirigir según el rol
        if ($user['rol'] === 'admin') {
            header('Location: ../../index.php?page=admin');
        } else {
            header('Location: ../../index.php');
        }
        exit;
    } else {
        header('Location: ../../index.php?page=login&error=invalid_credentials');
        exit;
    }
} else {
    // Si no es una petición POST, redirigir al formulario
    header('Location: ../../index.php?page=login');
    exit;
}
?>

