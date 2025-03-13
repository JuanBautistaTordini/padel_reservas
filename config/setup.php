<?php
// Este script crea la base de datos y las tablas necesarias

require_once 'database.php';

try {
    // Conectar a MySQL sin seleccionar una base de datos
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear la base de datos si no existe
    $sql = "CREATE DATABASE IF NOT EXISTS court_reservation";
    $pdo->exec($sql);
    
    echo "Base de datos creada o ya existente<br>";
    
    // Seleccionar la base de datos
    $pdo->exec("USE court_reservation");
    
    // Crear tabla de usuarios
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        telefono VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        rol ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    echo "Tabla de usuarios creada<br>";
    
    // Crear tabla de canchas (sin campo de precio)
    $sql = "CREATE TABLE IF NOT EXISTS courts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        superficie VARCHAR(50) NOT NULL,
        disponibilidad_inicio TIME NOT NULL,
        disponibilidad_fin TIME NOT NULL,
        estado BOOLEAN NOT NULL DEFAULT TRUE,
        imagen VARCHAR(255) DEFAULT NULL
    )";
    $pdo->exec($sql);
    
    // Verificar si el campo imagen ya existe, si no, agregarlo
    try {
        $pdo->query("SELECT imagen FROM courts LIMIT 1");
    } catch (PDOException $e) {
        // El campo no existe, lo agregamos
        $pdo->exec("ALTER TABLE courts ADD COLUMN imagen VARCHAR(255) DEFAULT NULL");
        echo "Campo imagen agregado a la tabla courts<br>";
    }
    
    // Si existe el campo precio en courts, eliminarlo ya que ahora el precio es por turno
    try {
        $pdo->query("SELECT precio FROM courts LIMIT 1");
        // El campo existe, lo eliminamos
        $pdo->exec("ALTER TABLE courts DROP COLUMN precio");
        echo "Campo precio eliminado de la tabla courts<br>";
    } catch (PDOException $e) {
        // El campo no existe, no hacemos nada
    }
    
    echo "Tabla de canchas creada<br>";
    
    // Crear tabla de configuración global
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        description VARCHAR(255) DEFAULT NULL
    )";
    $pdo->exec($sql);
    
    // Verificar si ya existe la configuración de intervalo de tiempo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'time_interval'");
    $stmt->execute();
    $timeIntervalExists = (int)$stmt->fetchColumn();
    
    if ($timeIntervalExists === 0) {
        // Insertar configuración por defecto (60 minutos)
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES ('time_interval', '60', 'Intervalo de tiempo entre turnos en minutos')");
        $stmt->execute();
        echo "Configuración de intervalo de tiempo creada<br>";
    }
    
    echo "Tabla de configuración creada<br>";
    
    // Crear tabla de precios por turno
    $sql = "CREATE TABLE IF NOT EXISTS time_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_of_week ENUM('all', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL DEFAULT 'all',
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        description VARCHAR(255) DEFAULT NULL
    )";
    $pdo->exec($sql);
    
    // Verificar si ya existen precios por defecto
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM time_prices");
    $stmt->execute();
    $pricesExist = (int)$stmt->fetchColumn();
    
    if ($pricesExist === 0) {
        // Insertar precios por defecto
        $defaultPrices = [
            ['all', '08:00:00', '12:00:00', 1000.00, 'Precio mañana (8:00 - 12:00)'],
            ['all', '12:00:00', '17:00:00', 1200.00, 'Precio tarde (12:00 - 17:00)'],
            ['all', '17:00:00', '22:00:00', 1500.00, 'Precio noche (17:00 - 22:00)'],
            ['saturday', '08:00:00', '22:00:00', 1800.00, 'Precio sábado (todo el día)'],
            ['sunday', '08:00:00', '22:00:00', 1800.00, 'Precio domingo (todo el día)']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO time_prices (day_of_week, start_time, end_time, price, description) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($defaultPrices as $price) {
            $stmt->execute($price);
        }
        
        echo "Precios por turno por defecto creados<br>";
    }
    
    echo "Tabla de precios por turno creada<br>";
    
    // Crear tabla de reservas
    $sql = "CREATE TABLE IF NOT EXISTS reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        court_id INT NOT NULL,
        fecha DATE NOT NULL,
        hora_inicio TIME NOT NULL,
        hora_fin TIME NOT NULL,
        estado ENUM('pendiente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendiente',
        precio DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (court_id) REFERENCES courts(id)
    )";
    $pdo->exec($sql);
    
    // Verificar si el campo precio ya existe en reservations, si no, agregarlo
    try {
        $pdo->query("SELECT precio FROM reservations LIMIT 1");
    } catch (PDOException $e) {
        // El campo no existe, lo agregamos
        $pdo->exec("ALTER TABLE reservations ADD COLUMN precio DECIMAL(10,2) DEFAULT 0.00");
        echo "Campo precio agregado a la tabla reservations<br>";
    }
    
    echo "Tabla de reservas creada<br>";
    
    // Crear usuario administrador por defecto si no existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@example.com'");
    $stmt->execute();
    $adminExists = (int)$stmt->fetchColumn();
    
    if ($adminExists === 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nombre, email, telefono, password, rol) VALUES ('Administrador', 'admin@example.com', '123456789', :password, 'admin')");
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        
        echo "Usuario administrador creado<br>";
    } else {
        echo "Usuario administrador ya existe<br>";
    }
    
    // Crear directorio para imágenes si no existe
    $uploadDir = '../uploads/courts';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
        echo "Directorio para imágenes creado<br>";
    }
    
    // Crear algunas canchas de ejemplo si no existen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courts");
    $stmt->execute();
    $courtsCount = (int)$stmt->fetchColumn();
    
    if ($courtsCount === 0) {
        $courts = [
            ['Cancha 1', 'Césped', '08:00:00', '22:00:00', true, NULL],
            ['Cancha 2', 'Cemento', '08:00:00', '22:00:00', true, NULL],
            ['Cancha 3', 'Arcilla', '09:00:00', '21:00:00', true, NULL]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO courts (nombre, superficie, disponibilidad_inicio, disponibilidad_fin, estado, imagen) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($courts as $court) {
            $stmt->execute($court);
        }
        
        echo "Canchas de ejemplo creadas<br>";
    } else {
        echo "Ya existen canchas en la base de datos<br>";
    }
    
    echo "Configuración completada con éxito";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

