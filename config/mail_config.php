<?php
// Configuración para PHPMailer
return [
    'host' => 'smtp.gmail.com',  // Servidor SMTP (usa smtp.gmail.com para Gmail)
    'username' => 'juanbautistatordini@gmail.com', // Tu correo electrónico
    'password' => 'daul xtvr jmvh poql', // Tu contraseña o contraseña de aplicación
    'port' => 587, // Puerto SMTP (587 para TLS, 465 para SSL)
    'encryption' => 'tls', // Tipo de encriptación (tls o ssl)
    'from_email' => 'noreply@reservacanchas.com', // Correo desde el que se envían los mensajes
    'from_name' => 'Sistema de Reservas', // Nombre que aparecerá como remitente
    'complex_email' => 'juanbautistatordini@gmail.com', // Correo del complejo deportivo (administrador)
    'debug' => 0, // Nivel de debug (0-4): 0 = sin debug, 4 = debug máximo
];
?>

