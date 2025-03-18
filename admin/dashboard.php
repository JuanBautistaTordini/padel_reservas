<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=unauthorized');
    exit;
}

require_once 'config/timezone.php'; // Incluir configuración de zona horaria
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
// Total de usuarios
$query = "SELECT COUNT(*) as total FROM users WHERE rol = 'cliente'";
$stmt = $db->prepare($query);
$stmt->execute();
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de canchas
$query = "SELECT COUNT(*) as total FROM courts";
$stmt = $db->prepare($query);
$stmt->execute();
$totalCourts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de reservas
$query = "SELECT COUNT(*) as total FROM reservations";
$stmt = $db->prepare($query);
$stmt->execute();
$totalReservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Reservas pendientes
$query = "SELECT COUNT(*) as total FROM reservations WHERE estado = 'pendiente'";
$stmt = $db->prepare($query);
$stmt->execute();
$pendingReservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Reservas para hoy
$query = "SELECT COUNT(*) as total FROM reservations WHERE fecha = CURDATE() AND estado != 'cancelada'";
$stmt = $db->prepare($query);
$stmt->execute();
$todayReservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Últimas 5 reservas
$query = "SELECT r.*, u.nombre as user_name, c.nombre as court_name 
          FROM reservations r 
          JOIN users u ON r.user_id = u.id 
          JOIN courts c ON r.court_id = c.id 
          ORDER BY r.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$latestReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener reservas por día para la última semana (para el gráfico)
$weekData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayNum = date('w', strtotime($date));
    
    // Traducir días de la semana al español
    $diasEspanol = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $dayName = $diasEspanol[$dayNum];
    
    $query = "SELECT COUNT(*) as total FROM reservations WHERE DATE(created_at) = :date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $weekData[] = [
        'day' => $dayName,
        'count' => $count
    ];
}
?>

<div class="bg-gray-100 p-6 rounded-lg shadow-sm mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-1">Panel de Administración</h2>
            <p class="text-gray-600">
                <i class="far fa-calendar-alt mr-1"></i> <?php echo date('d/m/Y'); ?> | 
                <i class="far fa-clock mr-1"></i> <span id="real-time-clock"><?php echo date('H:i:s'); ?></span>
            </p>
        </div>
        
        <!-- Acciones Rápidas -->
        <div class="flex flex-wrap gap-2 mt-4 md:mt-0">
            <a href="index.php?page=admin-courts" class="bg-white hover:bg-gray-50 text-gray-700 shadow-sm px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center">
                <i class="fa-solid fa-baseball mr-2 text-primary"></i> Canchas
            </a>
            <a href="index.php?page=admin-reservations" class="bg-white hover:bg-gray-50 text-gray-700 shadow-sm px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-secondary"></i> Reservas
            </a>
            <a href="index.php?page=admin-users" class="bg-white hover:bg-gray-50 text-gray-700 shadow-sm px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center">
                <i class="fas fa-users mr-2 text-purple-600"></i> Usuarios
            </a>
            <a href="index.php?page=admin-settings" class="bg-white hover:bg-gray-50 text-gray-700 shadow-sm px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center">
                <i class="fas fa-cog mr-2 text-gray-700"></i> Configuración
            </a>
        </div>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-6 transform transition-all duration-300 hover:shadow-md hover:translate-y-[-5px]">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-500 mb-1">Usuarios</p>
                <h3 class="text-3xl font-bold text-gray-800 flex items-center">
                    <?php echo $totalUsers; ?>
                    <span class="text-xs font-normal ml-2 bg-green-200 text-green-800 px-2 py-1 rounded-full">
                        +2 <i class="fas fa-caret-up"></i>
                    </span>
                </h3>
            </div>
            <div class="text-2xl p-3 bg-blue-200 text-primary rounded-full">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="mt-4 border-t pt-3">
            <a href="index.php?page=admin-users" class="text-sm text-primary hover:underline">Ver detalles →</a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 transform transition-all duration-300 hover:shadow-md hover:translate-y-[-5px]">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-500 mb-1">Canchas</p>
                <h3 class="text-3xl font-bold text-gray-800">
                    <?php echo $totalCourts; ?>
                </h3>
            </div>
            <div class="text-2xl p-3 bg-green-200 text-secondary rounded-full">
                <i class="fa-solid fa-baseball"></i>
            </div>
        </div>
        <div class="mt-4 border-t pt-3">
            <a href="index.php?page=admin-courts" class="text-sm text-primary hover:underline">Ver detalles →</a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 transform transition-all duration-300 hover:shadow-md hover:translate-y-[-5px]">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-500 mb-1">Total Reservas</p>
                <h3 class="text-3xl font-bold text-gray-800 flex items-center">
                    <?php echo $totalReservations; ?>
                    <span class="text-xs font-normal ml-2 bg-green-200 text-green-800 px-2 py-1 rounded-full">
                        +4 <i class="fas fa-caret-up"></i>
                    </span>
                </h3>
            </div>
            <div class="text-2xl p-3 bg-purple-200 text-purple-600 rounded-full">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
        <div class="mt-4 border-t pt-3">
            <a href="index.php?page=admin-reservations" class="text-sm text-primary hover:underline">Ver detalles →</a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 transform transition-all duration-300 hover:shadow-md hover:translate-y-[-5px]">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-500 mb-1">Reservas Pendientes</p>
                <h3 class="text-3xl font-bold text-gray-800">
                    <?php echo $pendingReservations; ?>
                </h3>
            </div>
            <div class="text-2xl p-3 bg-yellow-200 text-yellow-600 rounded-full">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="mt-4 border-t pt-3">
            <a href="index.php?page=admin-reservations&status=pendiente" class="text-sm text-primary hover:underline">Ver detalles →</a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 transform transition-all duration-300 hover:shadow-md hover:translate-y-[-5px]">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-500 mb-1">Hoy</p>
                <h3 class="text-3xl font-bold text-gray-800">
                    <?php echo $todayReservations; ?>
                </h3>
            </div>
            <div class="text-2xl p-3 bg-blue-200 text-blue-600 rounded-full">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="mt-4 border-t pt-3">
        </div>
    </div>
</div>

<!-- Gráfico y Últimas Reservas -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Gráfico de actividad -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-6 flex items-center">
            <i class="fas fa-chart-line text-primary mr-2"></i> Actividad de reservas
        </h3>
        
        <div class="chart-container" style="height: 250px;">
            <?php if ($totalReservations > 0): ?>
                <canvas id="reservationsChart"></canvas>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-full">
                    <i class="fas fa-chart-bar text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-500 text-center">No hay reservas para mostrar en el gráfico</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Resumen rápido -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-tasks text-primary mr-2"></i> Resumen rápido
        </h3>
        
        <div class="space-y-4">
            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                <div class="rounded-full bg-blue-200 p-2 mr-3">
                    <i class="fas fa-user-plus text-blue-500"></i>
                </div>
                <div>
                    <p class="text-sm font-medium">Usuarios nuevos (hoy)</p>
                    <p class="text-xl font-semibold">2</p>
                </div>
            </div>
            
            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                <div class="rounded-full bg-green-200 p-2 mr-3">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <div>
                    <p class="text-sm font-medium">Reservas confirmadas (hoy)</p>
                    <p class="text-xl font-semibold">5</p>
                </div>
            </div>
            
            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                <div class="rounded-full bg-yellow-200 p-2 mr-3">
                    <i class="fas fa-exclamation-circle text-yellow-500"></i>
                </div>
                <div>
                    <p class="text-sm font-medium">Requieren atención</p>
                    <p class="text-xl font-semibold"><?php echo $pendingReservations; ?></p>
                </div>
            </div>
            
            <a href="index.php?page=admin-reservations&action=create" class="block mt-4 bg-primary hover:bg-primary-dark text-white text-center py-2 px-4 rounded-lg transition duration-300 shadow-sm hover:shadow">
                <i class="fas fa-plus mr-2"></i> Nueva reserva
            </a>
        </div>
    </div>
</div>

<!-- Últimas Reservas -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold flex items-center">
            <i class="fas fa-history text-primary mr-2"></i> Últimas Reservas
        </h3>
        <a href="index.php?page=admin-reservations" class="text-sm text-primary hover:underline">
            Ver todas
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($latestReservations) > 0): ?>
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">Usuario</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Cancha</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Horario</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tr-lg">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($latestReservations as $reservation): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-gray-500"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800"><?php echo $reservation['user_name']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-medium">
                                <?php echo $reservation['court_name']; ?>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-gray-800 font-medium">
                                    <?php echo date('d/m/Y', strtotime($reservation['fecha'])); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo substr($reservation['hora_inicio'], 0, 5); ?> - <?php echo substr($reservation['hora_fin'], 0, 5); ?>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <?php 
                                switch ($reservation['estado']) {
                                    case 'pendiente':
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-200 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i> Pendiente
                                        </span>';
                                        break;
                                    case 'confirmada':
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-200 text-green-800">
                                            <i class="fas fa-check mr-1"></i> Confirmada
                                        </span>';
                                        break;
                                    case 'cancelada':
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-200 text-red-800">
                                            <i class="fas fa-times mr-1"></i> Cancelada
                                        </span>';
                                        break;
                                }
                                ?>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <?php if ($reservation['estado'] !== 'cancelada'): ?>
                                        <?php if ($reservation['estado'] !== 'confirmada'): ?>
                                            <form action="api/admin/reservations/update.php" method="post" class="inline">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="status" value="confirmada">
                                                <button type="submit" class="text-green-600 hover:text-green-800 p-1">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="text-red-600 hover:text-red-800 p-1" onclick="openDeleteReservationModal('<?php echo $reservation['id']; ?>', '<?php echo $reservation['user_name']; ?> - <?php echo $reservation['court_name']; ?> - <?php echo date('d/m/Y', strtotime($reservation['fecha'])); ?> (<?php echo substr($reservation['hora_inicio'], 0, 5); ?> - <?php echo substr($reservation['hora_fin'], 0, 5); ?>')">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-400">No disponible</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center py-12">
                <i class="fas fa-calendar-times text-gray-300 text-5xl mb-3"></i>
                <p class="text-gray-500 text-center">No hay reservas para mostrar</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script para el gráfico -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Datos para el gráfico
        const weekData = <?php echo json_encode($weekData); ?>;
        
        <?php if ($totalReservations > 0): ?>
        const ctx = document.getElementById('reservationsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: weekData.map(item => item.day),
                datasets: [{
                    label: 'Actividades Recientes',
                    data: weekData.map(item => item.count),
                    backgroundColor: '#10b981',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 10,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 14
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded' ,function(){
        function updateClock(){
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            document.getElementById('real-time-clock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        // Update clock immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);
    })
</script>

<style>
    /* Animaciones y estilos adicionales */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .chart-container {
        animation: fadeIn 0.9s ease-out;
    }
</style>

<!-- Incluir el modal de cancelación de reservas -->
<?php include 'components/delete-reservation-modal.php'; ?>

<!-- Script para el modal de cancelación -->
<script src="js/reservation-modals.js"></script>

