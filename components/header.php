<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-3">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="index.php" class="flex items-center gap-2">
                    <span class="text-2xl font-bold bg-gradient-to-r from-green-600 to-green-800 bg-clip-text text-transparent">ReservaCanchas</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:block">
                <ul class="flex space-x-6 items-center">
                    <li>
                        <a href="index.php" class="text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium <?php echo !isset($_GET['page']) ? 'border-b-2 border-green-600' : ''; ?>">
                            Inicio
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['user_id']) && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')): ?>
                        <li>
                            <a href="index.php?page=courts" class="text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium <?php echo isset($_GET['page']) && $_GET['page'] === 'courts' ? 'border-b-2 border-green-600' : ''; ?>">
                                Canchas
                            </a>
                        </li>
                        <li>
                            <a href="index.php?page=reservations" class="text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium <?php echo isset($_GET['page']) && $_GET['page'] === 'reservations' ? 'border-b-2 border-green-600' : ''; ?>">
                                Mis Reservas
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li>
                            <a href="index.php?page=admin" class="text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium <?php echo isset($_GET['page']) && $_GET['page'] === 'admin' ? 'border-b-2 border-green-600' : ''; ?>">
                                Panel Admin
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li>
                            <form action="api/auth/logout.php" method="post" class="inline">
                                <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md transition-colors duration-200 font-medium">
                                    Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="index.php?page=login" class="text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium <?php echo isset($_GET['page']) && $_GET['page'] === 'login' ? 'border-b-2 border-green-600' : ''; ?>">
                                Iniciar Sesión
                            </a>
                        </li>
                        <li>
                            <a href="index.php?page=register" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors duration-200 font-medium">
                                Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-button" class="md:hidden text-gray-700 hover:text-green-600 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="md:hidden hidden pb-4">
            <ul class="flex flex-col space-y-3">
                <li>
                    <a href="index.php" class="block text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium py-2 <?php echo !isset($_GET['page']) ? 'text-green-600 font-semibold' : ''; ?>">
                        Inicio
                    </a>
                </li>
                
                <?php if (isset($_SESSION['user_id']) && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')): ?>
                    <li>
                        <a href="index.php?page=courts" class="block text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium py-2 <?php echo isset($_GET['page']) && $_GET['page'] === 'courts' ? 'text-green-600 font-semibold' : ''; ?>">
                            Canchas
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=reservations" class="block text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium py-2 <?php echo isset($_GET['page']) && $_GET['page'] === 'reservations' ? 'text-green-600 font-semibold' : ''; ?>">
                            Mis Reservas
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li>
                        <a href="index.php?page=admin" class="block text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium py-2 <?php echo isset($_GET['page']) && $_GET['page'] === 'admin' ? 'text-green-600 font-semibold' : ''; ?>">
                            Panel Admin
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <form action="api/auth/logout.php" method="post">
                            <button type="submit" class="w-full text-left px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md transition-colors duration-200 font-medium">
                                Cerrar Sesión
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="index.php?page=login" class="block text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium py-2 <?php echo isset($_GET['page']) && $_GET['page'] === 'login' ? 'text-green-600 font-semibold' : ''; ?>">
                            Iniciar Sesión
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=register" class="block px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors duration-200 font-medium text-center">
                            Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>

<script>
    // Toggle mobile menu
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenu.classList.toggle('hidden');
    });
</script>
