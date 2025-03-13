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
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Gestión de Usuarios</h2>
        <a href="index.php?page=admin" class="text-primary hover:underline">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Dashboard
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php 
            $success = $_GET['success'];
            switch ($success) {
                case 'updated':
                    echo 'Usuario actualizado exitosamente.';
                    break;
                case 'created':
                    echo 'Usuario creado exitosamente. Se ha enviado un correo con los datos de acceso.';
                    break;
                default:
                    echo 'Operación completada exitosamente.';
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php 
            $error = $_GET['error'];
            switch ($error) {
                case 'empty_fields':
                    echo 'Por favor complete todos los campos.';
                    break;
                case 'email_exists':
                    echo 'El email ya está registrado por otro usuario.';
                    break;
                case 'invalid_email':
                    echo 'El email ingresado no es válido.';
                    break;
                case 'password_short':
                    echo 'La contraseña debe tener al menos 6 caracteres.';
                    break;
                default:
                    echo 'Ha ocurrido un error. Por favor intente nuevamente.';
            }
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Lista de Usuarios -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold mb-4">Lista de Usuarios</h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Nombre</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Email</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Teléfono</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Rol</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Fecha Registro</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 font-medium"><?php echo $user['nombre']; ?></td>
                            <td class="py-3 px-4 text-gray-600"><?php echo $user['email']; ?></td>
                            <td class="py-3 px-4"><?php echo $user['telefono']; ?></td>
                            <td class="py-3 px-4">
                                <?php if ($user['rol'] === 'admin'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Administrador</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Cliente</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-gray-500 text-sm"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td class="py-3 px-4">
                                <a href="index.php?page=admin-users&action=edit&id=<?php echo $user['id']; ?>" class="text-primary hover:underline inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Formulario de Edición o Creación -->
    <div class="bg-gray-50 p-6 rounded-lg">
        <?php if ($editMode): ?>
            <h3 class="text-xl font-semibold mb-4">Editar Usuario</h3>
            
            <form action="api/admin/users/update.php" method="post">
                <input type="hidden" name="user_id" value="<?php echo $userToEdit['id']; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo $userToEdit['nombre']; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $userToEdit['email']; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                    </div>
                    
                    <div>
                        <label for="telefono" class="block text-gray-700 font-medium mb-2">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" value="<?php echo $userToEdit['telefono']; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                    </div>
                    
                    <div>
                        <label for="rol" class="block text-gray-700 font-medium mb-2">Rol</label>
                        <select id="rol" name="rol" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                            <option value="cliente" <?php echo ($userToEdit['rol'] === 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                            <option value="admin" <?php echo ($userToEdit['rol'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="password" class="block text-gray-700 font-medium mb-2">Nueva Contraseña (dejar en blanco para mantener la actual)</label>
                        <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-sm text-gray-500 mt-1">Mínimo 6 caracteres</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <a href="index.php?page=admin-users" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Actualizar Usuario
                    </button>
                </div>
            </form>
        <?php else: ?>
            <h3 class="text-xl font-semibold mb-4">Crear Nuevo Usuario</h3>
            
            <form action="api/admin/users/create.php" method="post">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre</label>
                        <input type="text" id="nombre" name="nombre" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                        <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                    </div>
                    
                    <div>
                        <label for="telefono" class="block text-gray-700 font-medium mb-2">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                    </div>
                    
                    <div>
                        <label for="rol" class="block text-gray-700 font-medium mb-2">Rol</label>
                        <select id="rol" name="rol" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                            <option value="cliente" selected>Cliente</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-gray-700 font-medium mb-2">Contraseña</label>
                        <div class="flex gap-2">
                            <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                            <button type="button" onclick="generatePassword()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">Generar</button>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Mínimo 6 caracteres</p>
                    </div>
                    
                    <div>
                        <label for="send_email" class="block text-gray-700 font-medium mb-2">Notificación</label>
                        <div class="flex items-center mt-2">
                            <input type="checkbox" id="send_email" name="send_email" value="1" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" checked>
                            <label for="send_email" class="ml-2 block text-sm text-gray-700">
                                Enviar correo con datos de acceso
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-secondary hover:bg-secondary-dark text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Crear Usuario
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function generatePassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('password').value = password;
}
</script>

