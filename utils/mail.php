<?php
// Incluir PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cargar el autoloader de Composer (asumiendo que PHPMailer se instaló con Composer)
// Si no usas Composer, deberás incluir manualmente los archivos de PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

class Mail {
    private static $config;
    
    /**
     * Inicializa la configuración de correo
     */
    private static function initConfig() {
        if (self::$config === null) {
            self::$config = require_once __DIR__ . '/../config/mail_config.php';
        }
    }
    
    /**
     * Configura una instancia de PHPMailer con los ajustes básicos
     * 
     * @return PHPMailer Instancia configurada de PHPMailer
     */
    private static function setupMailer() {
        self::initConfig();
        
        $mail = new PHPMailer(true);
        
        try {
            // Configuración del servidor
            $mail->SMTPDebug = self::$config['debug'];
            $mail->isSMTP();
            $mail->Host = self::$config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = self::$config['username'];
            $mail->Password = self::$config['password'];
            $mail->SMTPSecure = self::$config['encryption'];
            $mail->Port = self::$config['port'];
            $mail->CharSet = 'UTF-8';
            
            // Remitente
            $mail->setFrom(self::$config['from_email'], self::$config['from_name']);
            
            return $mail;
        } catch (Exception $e) {
            error_log("Error al configurar PHPMailer: {$mail->ErrorInfo}");
            return null;
        }
    }
    
    /**
     * Envía un correo electrónico
     * 
     * @param string $to Correo del destinatario
     * @param string $subject Asunto del correo
     * @param string $message Contenido del correo (HTML)
     * @return bool Resultado del envío
     */
    public static function send($to, $subject, $message) {
        $mail = self::setupMailer();
        
        if ($mail === null) {
            return false;
        }
        
        try {
            // Destinatario
            $mail->addAddress($to);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
            
            // Enviar
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar correo: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Estilos comunes para todos los emails
     * 
     * @return string CSS para los emails
     */
    private static function getCommonStyles() {
        return "
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                background-color: #f9f9f9; 
                margin: 0; 
                padding: 0; 
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                background-color: #ffffff; 
                border-radius: 8px; 
                overflow: hidden; 
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
            }
            .header { 
                background-color: #4CAF50; 
                color: white; 
                padding: 20px; 
                text-align: center; 
                background-image: linear-gradient(135deg, #4CAF50, #2E7D32); 
            }
            .logo {
                margin-bottom: 10px;
            }
            .content { 
                padding: 30px; 
                background-color: #ffffff; 
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                background-color: #f5f5f5; 
                color: #666; 
                font-size: 12px; 
                border-top: 1px solid #eeeeee; 
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0; 
                border-radius: 6px; 
                overflow: hidden; 
            }
            table td, table th { 
                padding: 12px; 
                border: 1px solid #e0e0e0; 
            }
            table th { 
                background-color: #f5f5f5; 
                font-weight: 600; 
                text-align: left; 
            }
            .btn {
                display: inline-block;
                background-color: #4CAF50;
                color: white !important;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 4px;
                font-weight: 600;
                margin: 15px 0;
                text-align: center;
                transition: background-color 0.3s;
            }
            .btn:hover {
                background-color: #3d8b40;
            }
            .highlight {
                background-color: #f9f9f9;
                border-left: 4px solid #4CAF50;
                padding: 15px;
                margin: 20px 0;
            }
            .status-confirmed {
                color: #2E7D32;
                font-weight: bold;
            }
            .status-pending {
                color: #F57C00;
                font-weight: bold;
            }
            .status-cancelled {
                color: #D32F2F;
                font-weight: bold;
            }
            .court-info {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
                padding: 10px;
                background-color: #f5f5f5;
                border-radius: 6px;
            }
            .court-icon {
                margin-right: 10px;
                color: #4CAF50;
            }
            .padel-tip {
                background-color: #E8F5E9;
                padding: 15px;
                border-radius: 6px;
                margin: 20px 0;
                font-style: italic;
            }
            .credentials {
                background-color: #f5f5f5;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                padding: 15px;
                margin: 20px 0;
                font-family: monospace;
            }
        ";
    }
    
    /**
     * Notifica al complejo sobre una nueva reserva creada por un cliente
     * 
     * @param array $reservation Datos de la reserva
     * @param array $user Datos del usuario
     * @param array $court Datos de la cancha
     * @return bool Resultado del envío
     */
    public static function notifyNewReservation($reservation, $user, $court) {
        self::initConfig();
        $subject = "🏆 Nueva Reserva de Pista - " . $court['nombre'];
        
        $message = "
        <html>
        <head>
            <title>Nueva Reserva de Pista</title>
            <style>" . self::getCommonStyles() . "</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>
                        <img src='https://via.placeholder.com/150x50/4CAF50/FFFFFF?text=PADEL+CLUB' alt='Padel Club Logo'>
                    </div>
                    <h2>¡Nueva Reserva de Pista Recibida!</h2>
                </div>
                <div class='content'>
                    <div class='court-info'>
                        <span class='court-icon'>🏆</span>
                        <h3 style='margin: 0;'>Pista: {$court['nombre']}</h3>
                    </div>
                    
                    <p>Se ha recibido una nueva reserva con los siguientes detalles:</p>
                    
                    <table>
                        <tr>
                            <th>Pista</th>
                            <td>{$court['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Fecha</th>
                            <td>" . date('d/m/Y', strtotime($reservation['fecha'])) . "</td>
                        </tr>
                        <tr>
                            <th>Horario</th>
                            <td>" . substr($reservation['hora_inicio'], 0, 5) . " - " . substr($reservation['hora_fin'], 0, 5) . "</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td><span class='status-pending'>Pendiente</span></td>
                        </tr>
                    </table>
                    
                    <h3>Datos del Jugador</h3>
                    <table>
                        <tr>
                            <th>Nombre</th>
                            <td>{$user['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{$user['email']}</td>
                        </tr>
                        <tr>
                            <th>Teléfono</th>
                            <td>{$user['telefono']}</td>
                        </tr>
                    </table>
                    
                    <div class='highlight'>
                        <p>Ingrese al panel de administración para confirmar o cancelar esta reserva.</p>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/index.php?page=admin-reservations' class='btn'>Gestionar Reserva</a>
                    </div>
                    
                    <div class='padel-tip'>
                        <strong>Consejo del día:</strong> Recuerde revisar el estado de las pistas antes de confirmar la reserva.
                    </div>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Padel Club. Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no responda a este mensaje.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send(self::$config['complex_email'], $subject, $message);
    }
    
    /**
     * Notifica al complejo sobre una nueva reserva creada manualmente por un administrador
     * 
     * @param array $reservation Datos de la reserva
     * @param array $user Datos del usuario cliente
     * @param array $court Datos de la cancha
     * @param array $admin Datos del administrador que creó la reserva
     * @return bool Resultado del envío
     */
    public static function notifyAdminCreatedReservation($reservation, $user, $court, $admin) {
        self::initConfig();
        $subject = "🎾 Reserva de Pista Creada Manualmente - " . $court['nombre'];
        
        $message = "
        <html>
        <head>
            <title>Reserva de Pista Creada Manualmente</title>
            <style>" . self::getCommonStyles() . "</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>
                        <img src='https://via.placeholder.com/150x50/4CAF50/FFFFFF?text=PADEL+CLUB' alt='Padel Club Logo'>
                    </div>
                    <h2>Reserva de Pista Creada Manualmente</h2>
                </div>
                <div class='content'>
                    <div class='highlight'>
                        <p><strong>Creada por el administrador:</strong> {$admin['nombre']} ({$admin['email']})</p>
                    </div>
                    
                    <div class='court-info'>
                        <span class='court-icon'>🎾</span>
                        <h3 style='margin: 0;'>Pista: {$court['nombre']}</h3>
                    </div>
                    
                    <p>Se ha creado manualmente una nueva reserva con los siguientes detalles:</p>
                    
                    <table>
                        <tr>
                            <th>Pista</th>
                            <td>{$court['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Fecha</th>
                            <td>" . date('d/m/Y', strtotime($reservation['fecha'])) . "</td>
                        </tr>
                        <tr>
                            <th>Horario</th>
                            <td>" . substr($reservation['hora_inicio'], 0, 5) . " - " . substr($reservation['hora_fin'], 0, 5) . "</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td><span class='" . ($reservation['estado'] == 'confirmada' ? 'status-confirmed' : ($reservation['estado'] == 'cancelada' ? 'status-cancelled' : 'status-pending')) . "'>" . ucfirst($reservation['estado']) . "</span></td>
                        </tr>
                    </table>
                    
                    <h3>Datos del Jugador</h3>
                    <table>
                        <tr>
                            <th>Nombre</th>
                            <td>{$user['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{$user['email']}</td>
                        </tr>
                        <tr>
                            <th>Teléfono</th>
                            <td>{$user['telefono']}</td>
                        </tr>
                    </table>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/index.php?page=admin-reservations' class='btn'>Gestionar Reservas</a>
                    </div>
                    
                    <div class='padel-tip'>
                        <strong>Recordatorio:</strong> Las reservas manuales deben ser notificadas al jugador. El sistema ha enviado un correo automáticamente.
                    </div>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Padel Club. Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no responda a este mensaje.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send(self::$config['complex_email'], $subject, $message);
    }
    
    /**
     * Notifica al usuario cliente sobre una reserva creada manualmente por un administrador
     * 
     * @param array $reservation Datos de la reserva
     * @param array $user Datos del usuario cliente
     * @param array $court Datos de la cancha
     * @return bool Resultado del envío
     */
    public static function notifyUserAboutAdminCreatedReservation($reservation, $user, $court) {
        $subject = "🏆 Reserva de Pista Confirmada - " . $court['nombre'];
        
        $message = "
        <html>
        <head>
            <title>Reserva de Pista Confirmada</title>
            <style>" . self::getCommonStyles() . "</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>
                        <img src='https://via.placeholder.com/150x50/4CAF50/FFFFFF?text=PADEL+CLUB' alt='Padel Club Logo'>
                    </div>
                    <h2>¡Su Pista de Pádel Está Reservada!</h2>
                </div>
                <div class='content'>
                    <div class='highlight'>
                        <p>El club ha realizado una reserva para usted.</p>
                    </div>
                    
                    <p>Estimado/a {$user['nombre']},</p>
                    
                    <div class='court-info'>
                        <span class='court-icon'>🏆</span>
                        <h3 style='margin: 0;'>Pista: {$court['nombre']}</h3>
                    </div>
                    
                    <p>Le confirmamos que tiene una pista reservada con los siguientes detalles:</p>
                    
                    <table>
                        <tr>
                            <th>Pista</th>
                            <td>{$court['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Fecha</th>
                            <td>" . date('d/m/Y', strtotime($reservation['fecha'])) . "</td>
                        </tr>
                        <tr>
                            <th>Horario</th>
                            <td>" . substr($reservation['hora_inicio'], 0, 5) . " - " . substr($reservation['hora_fin'], 0, 5) . "</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td><span class='" . ($reservation['estado'] == 'confirmada' ? 'status-confirmed' : ($reservation['estado'] == 'cancelada' ? 'status-cancelled' : 'status-pending')) . "'>" . ucfirst($reservation['estado']) . "</span></td>
                        </tr>
                    </table>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/index.php?page=reservations' class='btn'>Ver Mis Reservas</a>
                    </div>
                    
                    <div class='padel-tip'>
                        <strong>Consejo para su partido:</strong> Recuerde llevar agua suficiente y toalla. ¡Le esperamos en las pistas!
                    </div>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Padel Club. Todos los derechos reservados.</p>
                    <p>Si tiene alguna consulta, comuníquese con nosotros a info@padelclub.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($user['email'], $subject, $message);
    }
    
    /**
     * Notifica al usuario sobre un cambio en su reserva realizado por él mismo
     * 
     * @param array $reservation Datos de la reserva
     * @param array $user Datos del usuario
     * @param array $court Datos de la cancha
     * @param string $action Acción realizada (confirmada, cancelada)
     * @return bool Resultado del envío
     */
    public static function notifyReservationUpdate($reservation, $user, $court, $action) {
        $subject = "🎾 Actualización de Reserva - " . $court['nombre'];
        
        // Determinar el mensaje según la acción
        $actionMessage = "";
        $actionColor = "";
        $actionIcon = "";
        
        switch ($action) {
            case 'confirmada':
                $actionMessage = "¡Su reserva de pista ha sido confirmada!";
                $actionColor = "#2E7D32"; // Verde
                $actionIcon = "✅";
                $statusClass = "status-confirmed";
                break;
            case 'cancelada':
                $actionMessage = "Su reserva de pista ha sido cancelada.";
                $actionColor = "#D32F2F"; // Rojo
                $actionIcon = "❌";
                $statusClass = "status-cancelled";
                break;
            default:
                $actionMessage = "Su reserva de pista ha sido actualizada.";
                $actionColor = "#1976D2"; // Azul
                $actionIcon = "🔄";
                $statusClass = "status-pending";
        }
        
        $message = "
        <html>
        <head>
            <title>Actualización de Reserva de Pista</title>
            <style>" . self::getCommonStyles() . "
                .header { 
                    background-image: linear-gradient(135deg, {$actionColor}, " . self::adjustBrightness($actionColor, -20) . "); 
                }
                .btn {
                    background-color: {$actionColor};
                }
                .btn:hover {
                    background-color: " . self::adjustBrightness($actionColor, -20) . ";
                }
                .highlight {
                    border-left: 4px solid {$actionColor};
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>
                        <img src='https://via.placeholder.com/150x50/FFFFFF/FFFFFF?text=PADEL+CLUB' alt='Padel Club Logo'>
                    </div>
                    <h2>Actualización de Reserva de Pista</h2>
                </div>
                <div class='content'>
                    <p>Estimado/a {$user['nombre']},</p>
                    
                    <div class='highlight'>
                        <p style='font-size: 18px;'>{$actionIcon} <span class='{$statusClass}'>{$actionMessage}</span></p>
                    </div>
                    
                    <div class='court-info'>
                        <span class='court-icon'>🎾</span>
                        <h3 style='margin: 0;'>Pista: {$court['nombre']}</h3>
                    </div>
                    
                    <h3>Detalles de la Reserva</h3>
                    <table>
                        <tr>
                            <th>Pista</th>
                            <td>{$court['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Fecha</th>
                            <td>" . date('d/m/Y', strtotime($reservation['fecha'])) . "</td>
                        </tr>
                        <tr>
                            <th>Horario</th>
                            <td>" . substr($reservation['hora_inicio'], 0, 5) . " - " . substr($reservation['hora_fin'], 0, 5) . "</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td><span class='{$statusClass}'>" . ucfirst($reservation['estado']) . "</span></td>
                        </tr>
                    </table>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/index.php?page=reservations' class='btn'>Ver Mis Reservas</a>
                    </div>
                    
                    " . ($action == 'confirmada' ? "
                    <div class='padel-tip'>
                        <strong>Prepárese para su partido:</strong> Recuerde llegar 15 minutos antes para calentar. ¡Disfrute de su juego!
                    </div>
                    " : "") . "
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Padel Club. Todos los derechos reservados.</p>
                    <p>Si tiene alguna consulta, comuníquese con nosotros a info@padelclub.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($user['email'], $subject, $message);
    }
    
    /**
     * Notifica al usuario sobre un cambio en su reserva realizado por un administrador
     * 
     * @param array $reservation Datos de la reserva
     * @param array $user Datos del usuario
     * @param array $court Datos de la cancha
     * @param string $action Acción realizada (confirmada, cancelada)
     * @param array $admin Datos del administrador que realizó la acción
     * @return bool Resultado del envío
     */
    public static function notifyAdminReservationUpdate($reservation, $user, $court, $action, $admin) {
        $subject = "🏆 Actualización de Reserva por el Club - " . $court['nombre'];
        
        // Determinar el mensaje según la acción
        $actionMessage = "";
        $actionColor = "";
        $actionIcon = "";
        
        switch ($action) {
            case 'confirmada':
                $actionMessage = "¡Su reserva de pista ha sido confirmada por el club!";
                $actionColor = "#2E7D32"; // Verde
                $actionIcon = "✅";
                $statusClass = "status-confirmed";
                break;
            case 'cancelada':
                $actionMessage = "Su reserva de pista ha sido cancelada por el club.";
                $actionColor = "#D32F2F"; // Rojo
                $actionIcon = "❌";
                $statusClass = "status-cancelled";
                break;
            default:
                $actionMessage = "Su reserva de pista ha sido actualizada por el club.";
                $actionColor = "#1976D2"; // Azul
                $actionIcon = "🔄";
                $statusClass = "status-pending";
        }
        
        $message = "
        <html>
        <head>
            <title>Actualización de Reserva por el Club</title>
            <style>" . self::getCommonStyles() . "
                .header { 
                    background-image: linear-gradient(135deg, {$actionColor}, " . self::adjustBrightness($actionColor, -20) . "); 
                }
                .btn {
                    background-color: {$actionColor};
                }
                .btn:hover {
                    background-color: " . self::adjustBrightness($actionColor, -20) . ";
                }
                .highlight {
                    border-left: 4px solid {$actionColor};
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>
                        <img src='https://via.placeholder.com/150x50/FFFFFF/FFFFFF?text=PADEL+CLUB' alt='Padel Club Logo'>
                    </div>
                    <h2>Actualización de Reserva de Pista</h2>
                </div>
                <div class='content'>
                    <div class='highlight'>
                        <p><strong>Actualizada por:</strong> {$admin['nombre']} del Club de Pádel</p>
                    </div>
                    
                    <p>Estimado/a {$user['nombre']},</p>
                    
                    <p style='font-size: 18px;'>{$actionIcon} <span class='{$statusClass}'>{$actionMessage}</span></p>
                    
                    <div class='court-info'>
                        <span class='court-icon'>🏆</span>
                        <h3 style='margin: 0;'>Pista: {$court['nombre']}</h3>
                    </div>
                    
                    <h3>Detalles de la Reserva</h3>
                    <table>
                        <tr>
                            <th>Pista</th>
                            <td>{$court['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Fecha</th>
                            <td>" . date('d/m/Y', strtotime($reservation['fecha'])) . "</td>
                        </tr>
                        <tr>
                            <th>Horario</th>
                            <td>" . substr($reservation['hora_inicio'], 0, 5) . " - " . substr($reservation['hora_fin'], 0, 5) . "</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td><span class='{$statusClass}'>" . ucfirst($reservation['estado']) . "</span></td>
                        </tr>
                    </table>
                    
                    <p>Si tiene alguna consulta sobre esta actualización, por favor contacte al club.</p>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/index.php?page=reservations' class='btn'>Ver Mis Reservas</a>
                    </div>
                    
                    " . ($action == 'confirmada' ? "
                    <div class='padel-tip'>
                        <strong>Consejo del club:</strong> Para un mejor rendimiento, llegue con tiempo para calentar y estirar. ¡Le esperamos en las pistas!
                    </div>
                    " : ($action == 'cancelada' ? "
                    <div class='padel-tip'>
                        <strong>Información:</strong> Puede realizar una nueva reserva en cualquier momento desde su cuenta.
                    </div>
                    " : "")) . "
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Padel Club. Todos los derechos reservados.</p>
                    <p>Si tiene alguna consulta, comuníquese con nosotros a info@padelclub.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($user['email'], $subject, $message);
    }
    
    /**
     * Notifica al administrador cuando un usuario cancela su reserva
     * 
     * @param array $reservation Datos de la reserva
     * @param array $user Datos del usuario que canceló
     * @param array $court Datos de la cancha
     * @return bool Resultado del envío
     */
    public static function notifyAdminAboutUserCancellation($reservation, $user, $court) {
        self::initConfig();
        $subject = "❌ Cancelación de Reserva por Usuario - " . $court['nombre'];
        
        $message = "
        <html>
        <head>
            <title>Cancelación de Reserva por Usuario</title>
            <style>" . self::getCommonStyles() . "
                .header { 
                    background-image: linear-gradient(135deg, #D32F2F, #B71C1C); 
                }
                .btn {
                    background-color: #D32F2F;
                }
                .btn:hover {
                    background-color: #B71C1C;
                }
                .highlight {
                    border-left: 4px solid #D32F2F;
                }
                .cancellation-info {
                    background-color: #FFEBEE;
                    border-left: 4px solid #D32F2F;
                    padding: 15px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>
                        <img src='https://via.placeholder.com/150x50/FFFFFF/FFFFFF?text=PADEL+CLUB' alt='Padel Club Logo'>
                    </div>
                    <h2>Cancelación de Reserva por Usuario</h2>
                </div>
                <div class='content'>
                    <div class='cancellation-info'>
                        <p><strong>❌ Un usuario ha cancelado su reserva</strong></p>
                    </div>
                    
                    <div class='court-info'>
                        <span class='court-icon'>🎾</span>
                        <h3 style='margin: 0;'>Pista: {$court['nombre']}</h3>
                    </div>
                    
                    <p>El usuario <strong>{$user['nombre']}</strong> ha cancelado su reserva con los siguientes detalles:</p>
                    
                    <table>
                        <tr>
                            <th>Pista</th>
                            <td>{$court['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Fecha</th>
                            <td>" . date('d/m/Y', strtotime($reservation['fecha'])) . "</td>
                        </tr>
                        <tr>
                            <th>Horario</th>
                            <td>" . substr($reservation['hora_inicio'], 0, 5) . " - " . substr($reservation['hora_fin'], 0, 5) . "</td>
                        </tr>
                        <tr>
                            <th>Estado anterior</th>
                            <td><span class='" . ($reservation['estado_anterior'] == 'confirmada' ? 'status-confirmed' : 'status-pending') . "'>" . ucfirst($reservation['estado_anterior']) . "</span></td>
                        </tr>
                        <tr>
                            <th>Estado actual</th>
                            <td><span class='status-cancelled'>Cancelada</span></td>
                        </tr>
                    </table>
                    
                    <h3>Datos del Usuario</h3>
                    <table>
                        <tr>
                            <th>Nombre</th>
                            <td>{$user['nombre']}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{$user['email']}</td>
                        </tr>
                        <tr>
                            <th>Teléfono</th>
                            <td>{$user['telefono']}</td>
                        </tr>
                    </table>
                    
                    <div class='highlight'>
                        <p>Esta pista ahora está disponible para nuevas reservas.</p>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/index.php?page=admin-reservations' class='btn'>Gestionar Reservas</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Padel Club. Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no responda a este mensaje.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send(self::$config['complex_email'], $subject, $message);
    }
    
    /**
     * Envía un correo de bienvenida con los datos de acceso a un nuevo usuario
     * 
     * @param array $user Datos del usuario
     * @param string $password Contraseña en texto plano
     * @return bool Resultado del envío
     */
    public static function sendWelcomeEmail($user, $password) {
        $subject = "🎾 Bienvenido a ReservaCanchas - Datos de Acceso";
        
        $message = "
        <html>
        <head>
            <title>Bienvenido a ReservaCanchas</title>
            <style>" . self::getCommonStyles() . "</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>
                        <img src='https://via.placeholder.com/150x50/4CAF50/FFFFFF?text=PADEL+CLUB' alt='Padel Club Logo'>
                    </div>
                    <h2>¡Bienvenido a ReservaCanchas!</h2>
                </div>
                <div class='content'>
                    <p>Estimado/a {$user['nombre']},</p>
                    
                    <p>Le damos la bienvenida a nuestro sistema de reserva de canchas deportivas. Su cuenta ha sido creada exitosamente y ya puede comenzar a disfrutar de nuestras instalaciones.</p>
                    
                    <div class='highlight'>
                        <p>A continuación, encontrará sus datos de acceso:</p>
                    </div>
                    
                    <div class='credentials'>
                        <p><strong>Email:</strong> {$user['email']}</p>
                        <p><strong>Contraseña:</strong> {$password}</p>
                    </div>
                    
                    <p>Por razones de seguridad, le recomendamos cambiar su contraseña después del primer inicio de sesión.</p>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/index.php?page=login' class='btn'>Iniciar Sesión</a>
                    </div>
                    
                    <div class='padel-tip'>
                        <strong>Consejo:</strong> Explore nuestro sistema para ver la disponibilidad de canchas y realizar reservas de manera rápida y sencilla.
                    </div>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Padel Club. Todos los derechos reservados.</p>
                    <p>Si tiene alguna consulta, comuníquese con nosotros a info@padelclub.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($user['email'], $subject, $message);
    }
    
    /**
     * Obtiene el correo del complejo
     * 
     * @return string Correo del complejo
     */
    public static function getComplexEmail() {
        self::initConfig();
        return self::$config['complex_email'];
    }
    
    /**
     * Ajusta el brillo de un color hexadecimal
     * 
     * @param string $hex Color en formato hexadecimal
     * @param int $steps Pasos para ajustar el brillo (positivo = más claro, negativo = más oscuro)
     * @return string Color ajustado en formato hexadecimal
     */
    private static function adjustBrightness($hex, $steps) {
        // Eliminar el # si existe
        $hex = ltrim($hex, '#');
        
        // Convertir a RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Ajustar el brillo
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        // Convertir de nuevo a hexadecimal
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}
?>

