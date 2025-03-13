<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold text-center mb-6">Iniciar Sesión</h2>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php 
            $error = $_GET['error'];
            switch ($error) {
                case 'invalid_credentials':
                    echo 'Email o contraseña incorrectos.';
                    break;
                case 'empty_fields':
                    echo 'Por favor complete todos los campos.';
                    break;
                default:
                    echo 'Ha ocurrido un error. Por favor intente nuevamente.';
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Registro exitoso. Ahora puede iniciar sesión.
        </div>
    <?php endif; ?>
    
    <form action="api/auth/login.php" method="post">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <div class="mb-6">
            <label for="password" class="block text-gray-700 font-medium mb-2">Contraseña</label>
            <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            Iniciar Sesión
        </button>
    </form>
    
    <div class="mt-6 text-center">
        <p class="text-gray-600">¿No tienes una cuenta? <a href="index.php?page=register" class="text-primary hover:underline">Regístrate aquí</a></p>
    </div>
</div>

