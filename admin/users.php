<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=unauthorized');
    exit;
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Verificar si hay una acción de edición
$editMode = false;
$userToEdit = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editMode = true;
    $userId = $_GET['id'];
    
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $userToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userToEdit) {
        $editMode = false;
    }
}

// Obtener todos los usuarios
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar usuarios por rol
$adminCount = 0;
$clientCount = 0;
foreach ($users as $user) {
    if ($user['rol'] === 'admin') {
        $adminCount++;
    } else {
        $clientCount++;
    }
}

// Determinar si estamos en modo móvil (para ajustar la vista)
$isMobile = isset($_SERVER['HTTP_USER_AGENT']) && 
    (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || 
     strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false);
?>

<div class="flex flex-col space-y-6">
    <!-- Header con estadísticas -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="bg-primary p-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-white">Gestión de Usuarios</h2>
                    <p class="text-white/80 mt-1">Administra los usuarios del sistema</p>
                </div>
                <div class="flex space-x-2 mt-4 md:mt-0">
                    <a href="index.php?page=admin" class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-white transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> Volver
                    </a>
                    <button id="newUserBtn" class="inline-flex items-center px-4 py-2 bg-white text-secondary font-medium rounded-lg hover:bg-gray-50 transition-all">
                        <i class="fas fa-plus mr-2"></i> Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6">
            <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                <div class="rounded-full bg-blue-100 p-3 mr-4">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Usuarios</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo count($users); ?></p>
                </div>
            </div>
            
            <div class="flex items-center p-4 bg-purple-50 rounded-lg">
                <div class="rounded-full bg-purple-100 p-3 mr-4">
                    <i class="fas fa-user-shield text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Administradores</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $adminCount; ?></p>
                </div>
            </div>
            
            <div class="flex items-center p-4 bg-green-50 rounded-lg">
                <div class="rounded-full bg-green-200 p-3 mr-4">
                    <i class="fas fa-user text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Clientes</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $clientCount; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alertas de notificación -->
    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
    <div class="transition-all duration-500 ease-in-out" id="notification">
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg flex items-start">
                <div class="text-green-500 rounded-full bg-green-100 p-1 mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-green-700 font-medium">Operación exitosa</p>
                    <p class="text-green-600 text-sm mt-1">
                        <?php 
                        $success = $_GET['success'];
                        switch ($success) {
                            case 'updated':
                                echo 'El usuario ha sido actualizado correctamente.';
                                break;
                            case 'created':
                                echo 'El usuario ha sido creado correctamente y se ha enviado un correo con los datos de acceso.';
                                break;
                            default:
                                echo 'La operación se ha completado exitosamente.';
                        }
                        ?>
                    </p>
                </div>
                <button class="text-green-500 hover:text-green-700" onclick="document.getElementById('notification').style.display='none';">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg flex items-start">
                <div class="text-red-500 rounded-full bg-red-100 p-1 mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-red-700 font-medium">Ha ocurrido un error</p>
                    <p class="text-red-600 text-sm mt-1">
                        <?php 
                        $error = $_GET['error'];
                        switch ($error) {
                            case 'empty_fields':
                                echo 'Por favor complete todos los campos requeridos.';
                                break;
                            case 'email_exists':
                                echo 'El email ingresado ya está registrado por otro usuario.';
                                break;
                            case 'invalid_email':
                                echo 'El formato del email ingresado no es válido.';
                                break;
                            case 'password_short':
                                echo 'La contraseña debe tener al menos 6 caracteres.';
                                break;
                            default:
                                echo 'Ha ocurrido un error inesperado. Por favor intente nuevamente.';
                        }
                        ?>
                    </p>
                </div>
                <button class="text-red-500 hover:text-red-700" onclick="document.getElementById('notification').style.display='none';">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Contenido principal - Dos columnas en escritorio, apiladas en móvil -->
    <div class="flex flex-col lg:flex-row lg:space-x-6 space-y-6 lg:space-y-0">
        <!-- Lista de usuarios -->
        <div class="flex-1 bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-800">Lista de Usuarios</h3>
                    <div class="relative">
                        <input type="text" id="searchUser" placeholder="Buscar usuario..." 
                               class="pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full" id="usersTable">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                            <th class="py-3 px-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="bg-<?php echo ($user['rol'] === 'admin') ? 'purple' : 'blue'; ?>-100 rounded-full p-2 mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-<?php echo ($user['rol'] === 'admin') ? 'purple' : 'blue'; ?>-600" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo $user['nombre']; ?></p>
                                            <p class="text-sm text-gray-500"><?php echo $user['email']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <a href="tel:<?php echo $user['telefono']; ?>" class="flex items-center text-gray-600 hover:text-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <?php echo $user['telefono']; ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($user['rol'] === 'admin'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Administrador
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                            </svg>
                                            Cliente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center text-gray-500 text-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <a href="index.php?page=admin-users&action=edit&id=<?php echo $user['id']; ?>" 
                                           class="text-primary hover:text-primary-dark transition-colors p-1 rounded-full hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Si no hay usuarios mostrar mensaje -->
                <?php if (count($users) === 0): ?>
                <div class="text-center py-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <p class="mt-2 text-gray-500">No hay usuarios registrados</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Paginación -->
            <?php if (count($users) > 10): ?>
            <div class="px-6 py-4 border-t flex justify-between items-center">
                <p class="text-sm text-gray-500">Mostrando <span class="font-medium">1</span> a <span class="font-medium">10</span> de <span class="font-medium"><?php echo count($users); ?></span> usuarios</p>
                <div class="flex space-x-1">
                    <button class="px-3 py-1 border rounded hover:bg-gray-50 text-gray-500 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button class="px-3 py-1 border rounded bg-primary text-white hover:bg-primary-dark">1</button>
                    <button class="px-3 py-1 border rounded hover:bg-gray-50 text-gray-700">2</button>
                    <button class="px-3 py-1 border rounded hover:bg-gray-50 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    
        <!-- Formulario (mostrado/ocultado con JavaScript) -->
        <div id="userForm" class="lg:w-96 bg-white rounded-xl shadow-md overflow-hidden" <?php echo (!$editMode && !isset($_GET['error'])) ? 'style="display:none;"' : ''; ?>>
            <div class="bg-gray-50 p-6 border-b">
                <h3 class="text-xl font-semibold text-gray-800">
                    <?php echo $editMode ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?>
                </h3>
                <p class="text-gray-500 text-sm mt-1">
                    <?php echo $editMode ? 'Modifica los datos del usuario' : 'Completa el formulario para crear un nuevo usuario'; ?>
                </p>
            </div>
            
            <div class="p-6">
                <form action="api/admin/users/<?php echo $editMode ? 'update' : 'create'; ?>.php" method="post" id="userFormElement">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="user_id" value="<?php echo $userToEdit['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre completo</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" id="nombre" name="nombre" value="<?php echo $editMode ? $userToEdit['nombre'] : ''; ?>" 
                                       class="pl-10 w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" 
                                       required placeholder="Ingrese nombre completo">
                            </div>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-gray-700 font-medium mb-2">Correo electrónico</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    </svg>
                                </div>
                                <input type="email" id="email" name="email" value="<?php echo $editMode ? $userToEdit['email'] : ''; ?>" 
                                       class="pl-10 w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" 
                                       required placeholder="ejemplo@correo.com">
                            </div>
                        </div>
                        
                        <div>
                            <label for="telefono" class="block text-gray-700 font-medium mb-2">Teléfono</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                    </svg>
                                </div>
                                <input type="tel" id="telefono" name="telefono" value="<?php echo $editMode ? $userToEdit['telefono'] : ''; ?>" 
                                       class="pl-10 w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" 
                                       required placeholder="(Código) Número">
                            </div>
                        </div>
                        
                        <div>
                            <label for="rol" class="block text-gray-700 font-medium mb-2">Rol</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12 1a1 1 0 0 1 .117 1.993L12 3h-1.803L8.74 8.367a1 1 0 0 1-.832.633L7.766 9H5a3 3 0 0 0-2.995 2.824L2 12v1a3 3 0 0 0 2.824 2.995L5 16h7.17a3.001 3.001 0 0 0 5.652-1H18a1 1 0 0 0 .117-1.993L18 13h-1.535a3.018 3.018 0 0 0-.743-1H18a1 1 0 0 0 .117-1.993L18 10h-1.832l-.358-1.791A1 1 0 0 0 14.83 7H12a1 1 0 0 1-.117-1.993L12 5h2.83a3 3 0 0 1 2.936 2 1 0 0 1 2.936 2.418l.017.089L18.132 9H20a3 3 0 0 1 0 6h-2.341a3.02 3.02 0 0 1-2.679 1.994L14.83 17H5a5 5 0 0 1-4.995-4.783L0 12v-1a5 5 0 0 1 4.783-4.995L5 6h2.3l1.457-5.346A1 1 0 0 1 9.535 0H12z" />
                                    </svg>
                                </div>
                                <select id="rol" name="rol" class="pl-10 w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                                    <option value="cliente" <?php echo ($editMode && $userToEdit['rol'] === 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                                    <option value="admin" <?php echo ($editMode && $userToEdit['rol'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-gray-700 font-medium mb-2">
                                <?php echo $editMode ? 'Nueva Contraseña (dejar en blanco para mantener la actual)' : 'Contraseña'; ?>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" 
                                       class="pl-10 w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" 
                                       <?php echo !$editMode ? 'required' : ''; ?> placeholder="<?php echo $editMode ? 'Nueva contraseña (opcional)' : 'Contraseña'; ?>">
                                <div class="absolute inset-y-0 right-0 flex items-center">
                                    <button type="button" id="showPassword" class="h-full px-3 text-gray-500 hover:text-gray-700 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-1 flex items-center justify-between">
                                <p class="text-sm text-gray-500">Mínimo 6 caracteres</p>
                                <?php if (!$editMode): ?>
                                <button type="button" onclick="generatePassword()" class="text-sm text-primary hover:text-primary-dark transition-colors">
                                    Generar contraseña
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!$editMode): ?>
                        <div class="flex items-center mt-4">
                            <input type="checkbox" id="send_email" name="send_email" value="1" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" checked>
                            <label for="send_email" class="ml-2 block text-sm text-gray-700">
                                Enviar correo con datos de acceso
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" id="cancelBtn" class="px-4 py-2 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-<?php echo $editMode ? 'primary' : 'secondary'; ?> hover:bg-<?php echo $editMode ? 'primary' : 'secondary'; ?>-dark text-white font-medium rounded-lg transition-all">
                            <?php echo $editMode ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Función para generar contraseña
function generatePassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('password').value = password;
    
    // Mostrar un mensaje de confirmación en lugar de mostrar la contraseña
    const passwordField = document.getElementById('password');
    const feedbackElement = document.createElement('div');
    feedbackElement.className = 'text-xs text-green-600 mt-1 animate-pulse';
    feedbackElement.textContent = 'Contraseña segura generada correctamente';
    
    // Insertar el mensaje después del campo de contraseña
    passwordField.parentNode.parentNode.appendChild(feedbackElement);
    
    // Eliminar el mensaje después de 2 segundos
    setTimeout(() => {
        feedbackElement.remove();
    }, 2000);
}

// Mostrar/ocultar contraseña
document.getElementById('showPassword').addEventListener('mousedown', function() {
    document.getElementById('password').type = 'text';
});

document.getElementById('showPassword').addEventListener('mouseup', function() {
    document.getElementById('password').type = 'password';
});

document.getElementById('showPassword').addEventListener('mouseleave', function() {
    document.getElementById('password').type = 'password';
});

// Mostrar/ocultar formulario
document.getElementById('newUserBtn').addEventListener('click', function() {
    const formElement = document.getElementById('userForm');
    formElement.style.display = 'block';
    
    // Si es móvil, hacer scroll hasta el formulario
    if (window.innerWidth < 1024) {
        formElement.scrollIntoView({ behavior: 'smooth' });
    }
});

document.getElementById('cancelBtn').addEventListener('click', function() {
    const formElement = document.getElementById('userForm');
    formElement.style.display = 'none';
    
    // Si estamos en modo edición, volver a la lista
    if (window.location.href.includes('action=edit')) {
        window.location.href = 'index.php?page=admin-users';
    }
});

// Búsqueda de usuarios
document.getElementById('searchUser').addEventListener('keyup', function() {
    const value = this.value.toLowerCase();
    const table = document.getElementById('usersTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cellText = cells[j].textContent || cells[j].innerText;
            if (cellText.toLowerCase().indexOf(value) > -1) {
                found = true;
                break;
            }
        }
        
        if (found) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
});

// Cerrar notificaciones automáticamente después de 5 segundos
if (document.getElementById('notification')) {
    setTimeout(() => {
        const notification = document.getElementById('notification');
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 500);
    }, 5000);
}
</script>

