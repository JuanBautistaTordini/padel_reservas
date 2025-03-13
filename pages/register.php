<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold text-center mb-6">Registro de Usuario</h2>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php 
            $error = $_GET['error'];
            switch ($error) {
                case 'email_exists':
                    echo 'El email ya está registrado.';
                    break;
                case 'empty_fields':
                    echo 'Por favor complete todos los campos.';
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
    
    <form action="api/auth/register.php" method="post">
        <div class="mb-4">
            <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre Completo</label>
            <input type="text" id="nombre" name="nombre" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <div class="mb-4">
            <label for="telefono" class="block text-gray-700 font-medium mb-2">Teléfono</label>
            <input type="tel" id="telefono" name="telefono" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <div class="mb-6">
            <label for="password" class="block text-gray-700 font-medium mb-2">Contraseña</label>
            <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
            <p class="text-sm text-gray-500 mt-1">La contraseña debe tener al menos 6 caracteres.</p>
        </div>
        
        <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            Registrarse
        </button>
    </form>
    
    <div class="mt-6 text-center">
        <p class="text-gray-600">¿Ya tienes una cuenta? <a href="index.php?page=login" class="text-primary hover:underline">Inicia sesión aquí</a></p>
    </div>
</div>

