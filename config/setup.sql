-- Script SQL para crear la base de datos y las tablas necesarias

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS court_reservation;

-- Seleccionar la base de datos
USE court_reservation;

-- Crear tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de canchas (sin campo de precio)
CREATE TABLE IF NOT EXISTS courts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    superficie VARCHAR(50) NOT NULL,
    disponibilidad_inicio TIME NOT NULL,
    disponibilidad_fin TIME NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    imagen VARCHAR(255) DEFAULT NULL
);

-- Agregar campo imagen si no existe
-- Nota: En SQL puro no podemos verificar si la columna existe antes de agregarla
-- Se recomienda ejecutar este comando manualmente si es necesario
-- ALTER TABLE courts ADD COLUMN imagen VARCHAR(255) DEFAULT NULL;

-- Eliminar campo precio si existe
-- Nota: En SQL puro no podemos verificar si la columna existe antes de eliminarla
-- Se recomienda ejecutar este comando manualmente si es necesario
-- ALTER TABLE courts DROP COLUMN precio;

-- Crear tabla de configuración global
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) DEFAULT NULL
);

-- Insertar configuración de intervalo de tiempo si no existe
INSERT INTO settings (setting_key, setting_value, description)
SELECT 'time_interval', '60', 'Intervalo de tiempo entre turnos en minutos'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'time_interval');

-- Crear tabla de precios por turno
CREATE TABLE IF NOT EXISTS time_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM('all', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL DEFAULT 'all',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description VARCHAR(255) DEFAULT NULL
);

-- Insertar precios por defecto si no existen registros
INSERT INTO time_prices (day_of_week, start_time, end_time, price, description)
SELECT 'all', '08:00:00', '12:00:00', 1000.00, 'Precio mañana (8:00 - 12:00)'
WHERE NOT EXISTS (SELECT 1 FROM time_prices LIMIT 1);

INSERT INTO time_prices (day_of_week, start_time, end_time, price, description)
SELECT 'all', '12:00:00', '17:00:00', 1200.00, 'Precio tarde (12:00 - 17:00)'
WHERE NOT EXISTS (SELECT 1 FROM time_prices LIMIT 1);

INSERT INTO time_prices (day_of_week, start_time, end_time, price, description)
SELECT 'all', '17:00:00', '22:00:00', 1500.00, 'Precio noche (17:00 - 22:00)'
WHERE NOT EXISTS (SELECT 1 FROM time_prices LIMIT 1);

INSERT INTO time_prices (day_of_week, start_time, end_time, price, description)
SELECT 'saturday', '08:00:00', '22:00:00', 1800.00, 'Precio sábado (todo el día)'
WHERE NOT EXISTS (SELECT 1 FROM time_prices LIMIT 1);

INSERT INTO time_prices (day_of_week, start_time, end_time, price, description)
SELECT 'sunday', '08:00:00', '22:00:00', 1800.00, 'Precio domingo (todo el día)'
WHERE NOT EXISTS (SELECT 1 FROM time_prices LIMIT 1);

-- Crear tabla de reservas
CREATE TABLE IF NOT EXISTS reservations (
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
);

-- Agregar campo precio a reservations si no existe
-- Nota: En SQL puro no podemos verificar si la columna existe antes de agregarla
-- Se recomienda ejecutar este comando manualmente si es necesario
-- ALTER TABLE reservations ADD COLUMN precio DECIMAL(10,2) DEFAULT 0.00;

-- Crear usuario administrador por defecto si no existe
INSERT INTO users (nombre, email, telefono, password, rol)
SELECT 'Administrador', 'admin@example.com', '123456789', '$2y$10$YourHashHere', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com');

-- Crear algunas canchas de ejemplo si no existen
INSERT INTO courts (nombre, superficie, disponibilidad_inicio, disponibilidad_fin, estado, imagen)
SELECT 'Cancha 1', 'Césped', '08:00:00', '22:00:00', true, NULL
WHERE NOT EXISTS (SELECT 1 FROM courts LIMIT 1);

INSERT INTO courts (nombre, superficie, disponibilidad_inicio, disponibilidad_fin, estado, imagen)
SELECT 'Cancha 2', 'Cemento', '08:00:00', '22:00:00', true, NULL
WHERE NOT EXISTS (SELECT 1 FROM courts LIMIT 1);

INSERT INTO courts (nombre, superficie, disponibilidad_inicio, disponibilidad_fin, estado, imagen)
SELECT 'Cancha 3', 'Arcilla', '09:00:00', '21:00:00', true, NULL
WHERE NOT EXISTS (SELECT 1 FROM courts LIMIT 1);

-- Nota: La creación del directorio para imágenes no se puede hacer con SQL puro
-- Esto debe hacerse manualmente o a través de un script del sistema operativo