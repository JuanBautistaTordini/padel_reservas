<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=unauthorized');
    exit;
}

require_once 'config/timezone.php'; // Incluir configuración de zona horaria
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Obtener el tamaño actual de la base de datos
$dbSize = $database->getDatabaseSize();

// Obtener información sobre las tablas
$query = "SELECT 
            table_name, 
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
            table_rows,
            ROUND(data_free / 1024 / 1024, 2) AS fragmented_space_mb,
            engine,
            create_time,
            update_time
          FROM information_schema.tables 
          WHERE table_schema = :database_name 
          ORDER BY (data_length + index_length) DESC";
$stmt = $db->prepare($query);
$database_name = 'court_reservation';
$stmt->bindParam(':database_name', $database_name);
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular el espacio fragmentado total
$totalFragmentedSpace = 0;
foreach ($tables as $table) {
    $totalFragmentedSpace += $table['fragmented_space_mb'];
}

// Verificar si se ha realizado alguna acción
$actionMessage = '';
$actionType = '';

if (isset($_GET['action_success'])) {
    $actionType = 'success';
    switch ($_GET['action_success']) {
        case 'optimize':
            $actionMessage = 'Las tablas han sido optimizadas correctamente.';
            break;
        case 'repair':
            $actionMessage = 'Las tablas han sido reparadas correctamente.';
            break;
        case 'analyze':
            $actionMessage = 'Las tablas han sido analizadas correctamente.';
            break;
        case 'truncate_logs':
            $actionMessage = 'Los registros de logs han sido truncados correctamente.';
            break;
        default:
            $actionMessage = 'La operación se ha completado correctamente.';
    }
} elseif (isset($_GET['action_error'])) {
    $actionType = 'error';
    switch ($_GET['action_error']) {
        case 'optimize':
            $actionMessage = 'Ha ocurrido un error al optimizar las tablas.';
            break;
        case 'repair':
            $actionMessage = 'Ha ocurrido un error al reparar las tablas.';
            break;
        case 'analyze':
            $actionMessage = 'Ha ocurrido un error al analizar las tablas.';
            break;
        case 'truncate_logs':
            $actionMessage = 'Ha ocurrido un error al truncar los registros de logs.';
            break;
        default:
            $actionMessage = 'Ha ocurrido un error al realizar la operación.';
    }
}
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Administración de Base de Datos</h2>
            <p class="text-gray-500 text-sm mt-1">Gestiona y optimiza el rendimiento de la base de datos</p>
        </div>
        <a href="index.php?page=admin" class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center text-sm">
            <i class="fas fa-arrow-left mr-2"></i> Volver al Dashboard
        </a>
    </div>
    
    <?php if ($actionMessage): ?>
        <div class="bg-<?php echo $actionType === 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $actionType === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $actionType === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded mb-6 animate-fade-in">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-<?php echo $actionType === 'success' ? 'check' : 'exclamation'; ?>-circle text-<?php echo $actionType === 'success' ? 'green' : 'red'; ?>-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $actionMessage; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Tarjetas de resumen -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 rounded-lg shadow-md text-white">
            <div class="flex items-center mb-2">
                <div class="p-3 rounded-full bg-white bg-opacity-20 mr-3">
                    <i class="fas fa-database"></i>
                </div>
                <h3 class="text-lg font-semibold">Tamaño Total</h3>
            </div>
            <p class="text-3xl font-bold mb-2"><?php echo number_format($dbSize, 2); ?> MB</p>
            <p class="text-sm text-blue-100">Espacio total utilizado por la base de datos</p>
        </div>
        
        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 p-6 rounded-lg shadow-md text-white">
            <div class="flex items-center mb-2">
                <div class="p-3 rounded-full bg-white bg-opacity-20 mr-3">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="text-lg font-semibold">Espacio Fragmentado</h3>
            </div>
            <p class="text-3xl font-bold mb-2"><?php echo number_format($totalFragmentedSpace, 2); ?> MB</p>
            <p class="text-sm text-yellow-100">Espacio que puede ser liberado mediante optimización</p>
        </div>
        
        <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 rounded-lg shadow-md text-white">
            <div class="flex items-center mb-2">
                <div class="p-3 rounded-full bg-white bg-opacity-20 mr-3">
                    <i class="fas fa-table"></i>
                </div>
                <h3 class="text-lg font-semibold">Tablas</h3>
            </div>
            <p class="text-3xl font-bold mb-2"><?php echo count($tables); ?></p>
            <p class="text-sm text-green-100">Número total de tablas en la base de datos</p>
        </div>
    </div>
    
    <!-- Acciones de administración -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Acciones de Mantenimiento</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="api/admin/database/optimize.php" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg border border-blue-100 transition duration-300 flex flex-col items-center text-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-500 mb-2">
                    <i class="fas fa-rocket"></i>
                </div>
                <h4 class="font-medium text-gray-800 mb-1">Optimizar Tablas</h4>
                <p class="text-sm text-gray-600">Reduce la fragmentación y mejora el rendimiento</p>
            </a>
            
            <a href="api/admin/database/repair.php" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg border border-green-100 transition duration-300 flex flex-col items-center text-center">
                <div class="p-3 rounded-full bg-green-200 text-green-500 mb-2">
                    <i class="fas fa-wrench"></i>
                </div>
                <h4 class="font-medium text-gray-800 mb-1">Reparar Tablas</h4>
                <p class="text-sm text-gray-600">Corrige errores en las tablas dañadas</p>
            </a>
            
            <a href="api/admin/database/analyze.php" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg border border-purple-100 transition duration-300 flex flex-col items-center text-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-500 mb-2">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h4 class="font-medium text-gray-800 mb-1">Analizar Tablas</h4>
                <p class="text-sm text-gray-600">Actualiza las estadísticas para el optimizador</p>
            </a>
            
            <a href="api/admin/database/truncate_logs.php" class="bg-red-50 hover:bg-red-100 p-4 rounded-lg border border-red-100 transition duration-300 flex flex-col items-center text-center">
                <div class="p-3 rounded-full bg-red-200 text-red-500 mb-2">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h4 class="font-medium text-gray-800 mb-1">Limpiar Logs</h4>
                <p class="text-sm text-gray-600">Elimina registros de logs antiguos</p>
            </a>
        </div>
    </div>
    
    <!-- Información de las tablas -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Detalles de Tablas</h3>
            <div class="relative">
                <input type="text" id="tableSearch" placeholder="Buscar tabla..." class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tabla</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamaño (MB)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filas</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Espacio Fragmentado</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Actualización</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="tableList">
                    <?php foreach ($tables as $table): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $table['table_name']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($table['size_mb'], 2); ?> MB</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($table['table_rows']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($table['fragmented_space_mb'] > 0): ?>
                                <span class="text-yellow-500"><?php echo number_format($table['fragmented_space_mb'], 2); ?> MB</span>
                            <?php else: ?>
                                <span class="text-green-500">0.00 MB</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $table['engine']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $table['update_time'] ? date('d/m/Y H:i', strtotime($table['update_time'])) : 'N/A'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex space-x-2">
                                <a href="api/admin/database/optimize_table.php?table=<?php echo $table['table_name']; ?>" class="text-blue-500 hover:text-blue-700" title="Optimizar tabla">
                                    <i class="fas fa-rocket"></i>
                                </a>
                                <a href="api/admin/database/repair_table.php?table=<?php echo $table['table_name']; ?>" class="text-green-500 hover:text-green-700" title="Reparar tabla">
                                    <i class="fas fa-wrench"></i>
                                </a>
                                <a href="api/admin/database/analyze_table.php?table=<?php echo $table['table_name']; ?>" class="text-purple-500 hover:text-purple-700" title="Analizar tabla">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Funcionalidad de búsqueda de tablas
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('tableSearch');
        const tableRows = document.querySelectorAll('#tableList tr');
        
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            tableRows.forEach(row => {
                const tableName = row.querySelector('td:first-child').textContent.toLowerCase();
                if (tableName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>