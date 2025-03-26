<?php
session_start();
require_once 'config/timezone.php'; // Incluir configuración de zona horaria
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Reserva de Canchas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/loader.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#3b82f6',
                            dark: '#2563eb',
                        },
                        secondary: {
                            DEFAULT: '#10b981',
                            dark: '#059669',
                        },
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'components/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <?php
        $page = isset($_GET['page']) ? $_GET['page'] : 'home';
        
        switch ($page) {
            case 'home':
                include 'pages/home.php';
                break;
            case 'login':
                include 'pages/login.php';
                break;
            case 'register':
                include 'pages/register.php';
                break;
            case 'courts':
                include 'pages/courts.php';
                break;
            case 'reservations':
                include 'pages/reservations.php';
                break;
            case 'admin':
                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    include 'admin/dashboard.php';
                } else {
                    include 'pages/unauthorized.php';
                }
                break;
            case 'database_admin':
                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    include 'admin/database_admin.php';
                } else {
                    include 'pages/unauthorized.php';
                }
                break;
            case 'admin-courts':
                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    include 'admin/courts.php';
                } else {
                    include 'pages/unauthorized.php';
                }
                break;
            case 'admin-reservations':
                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    include 'admin/reservations.php';
                } else {
                    include 'pages/unauthorized.php';
                }
                break;
            case 'admin-users':
                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    include 'admin/users.php';
                } else {
                    include 'pages/unauthorized.php';
                }
                break;
            case 'admin-settings':
                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                    include 'admin/settings.php';
                } else {
                    include 'pages/unauthorized.php';
                }
                break;
            default:
                include 'pages/404.php';
                break;
        }
        ?>
    </main>
    
    <?php include 'components/footer.php'; ?>
    
    <script src="js/loader.js"></script>
    <script src="js/availability-poller.js"></script>
    <script src="js/availability-ui-updater.js"></script>
    <script src="js/main.js"></script>
</body>
</html>

