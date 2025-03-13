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
    $userId = $_POST['user_id'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validar campos vacíos
    if (empty($userId) || empty($nombre) || empty($email) || empty($telefono) || empty($rol)) {
        header('Location: ../../../index.php?page=admin-users&error=empty_fields');
        exit;
    }
    
    // Validar rol
    if (!in_array($rol, ['cliente', 'admin'])) {
        header('Location: ../../../index.php?page=admin-users');
        exit;
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el email ya existe para otro usuario
    $query = "SELECT COUNT(*) FROM users WHERE email = :email AND id != :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        header('Location: ../../../index.php?page=admin-users&error=email_exists&id=' . $userId);
        exit;
    }
    
    // Preparar la consulta base
    $query = "UPDATE users SET nombre = :nombre, email = :email, telefono = :telefono, rol = :rol";
    $params = [
        ':nombre' => $nombre,
        ':email' => $email,
        ':telefono' => $telefono,
        ':rol' => $rol,
        ':id' => $userId
    ];
    
    // Si se proporcionó una nueva contraseña, actualizarla
    if (!empty($password)) {
        if (strlen($password) < 6) {
            header('Location: ../../../index.php?page=admin-users&error=password_short&id=' . $userId);
            exit;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query .= ", password = :password";
        $params[':password'] = $hashedPassword;
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        header('Location: ../../../index.php?page=admin-users&success=updated');
        exit;
    } else {
        header('Location: ../../../index.php?page=admin-users&error=database_error');
        exit;
    }
} else {
    // Si no es una petición POST, redirigir al panel de administración
    header('Location: ../../../index.php?page=admin-users');
    exit;
}
?>

