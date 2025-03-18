<?php
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php?page=login');
  exit;
}

require_once 'config/timezone.php'; // Incluir configuración de zona horaria
require_once 'config/database.php';
require_once 'utils/price_calculator.php';
$database = new Database();
$db = $database->getConnection();

// Verificar si es una nueva reserva
if (isset($_GET['action']) && $_GET['action'] === 'new' && isset($_GET['court_id']) && isset($_GET['date']) && isset($_GET['time'])) {
  $courtId = $_GET['court_id'];
  $date = $_GET['date'];
  $startTime = $_GET['time'];
  
  // Obtener información de la cancha
  $query = "SELECT * FROM courts WHERE id = :id";
  $stmt = $db->prepare($query);
  $stmt->bindParam(':id', $courtId);
  $stmt->execute();
  $court = $stmt->fetch(PDO::FETCH_ASSOC);

  // Obtener el intervalo de tiempo desde la URL o usar el valor configurado
  $duration = isset($_GET['duration']) ? intval($_GET['duration']) : 60;

  // Calcular hora de fin según la duración
  $endTime = date('H:i', strtotime($startTime) + ($duration * 60));

  // Calcular el precio del turno
  $slotPrice = PriceCalculator::calculatePrice(
      $date, 
      $startTime.':00', 
      $endTime.':00', 
      $db
  );
  
  // Verificar si el horario ya está reservado
  $query = "SELECT COUNT(*) FROM reservations 
            WHERE court_id = :court_id AND fecha = :fecha 
            AND ((hora_inicio <= :hora_inicio AND hora_fin > :hora_inicio) 
            OR (hora_inicio < :hora_fin AND hora_fin >= :hora_fin))
            AND estado != 'cancelada'";
  $stmt = $db->prepare($query);
  $stmt->bindParam(':court_id', $courtId);
  $stmt->bindParam(':fecha', $date);
  $stmt->bindParam(':hora_inicio', $startTime);
  $stmt->bindParam(':hora_fin', $endTime);
  $stmt->execute();
  
  $isReserved = (int)$stmt->fetchColumn() > 0;
  
  if ($isReserved) {
      echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
              Lo sentimos, este horario ya ha sido reservado. Por favor seleccione otro horario.
            </div>';
  } else {
      // Mostrar formulario de confirmación
      ?>
      <div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Encabezado -->
        <div class="bg-primary p-6 text-white">
            <h2 class="text-2xl font-bold text-center">Confirmar Reserva</h2>
            <p class="text-center text-white text-opacity-80 mt-2">Revise los detalles antes de confirmar</p>
        </div>
        
        <!-- Detalles de la reserva -->
        <div class="p-6">
            <div class="bg-gray-50 rounded-xl p-6 mb-6 border border-gray-200">
                <h3 class="font-semibold text-lg mb-4 flex items-center text-gray-800">
                    <i class="fas fa-info-circle text-primary mr-2"></i>
                    Detalles de la Reserva
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-start">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-lg mr-3">
                            <i class="fas fa-baseball-ball text-primary"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Cancha</p>
                            <p class="font-medium text-gray-800"><?php echo $court['nombre']; ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-lg mr-3">
                            <i class="far fa-calendar-alt text-primary"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Fecha</p>
                            <p class="font-medium text-gray-800"><?php echo date('d/m/Y', strtotime($date)); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-lg mr-3">
                            <i class="far fa-clock text-primary"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Horario</p>
                            <p class="font-medium text-gray-800"><?php echo $startTime; ?> - <?php echo $endTime; ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-secondary bg-opacity-10 p-2 rounded-lg mr-3">
                            <i class="fas fa-tag text-secondary"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Precio</p>
                            <p class="font-medium text-gray-800">$<?php echo number_format($slotPrice, 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <p class="text-sm">La reserva quedará pendiente hasta que sea confirmada por un administrador.</p>
                    </div>
                </div>
            </div>
            
            <!-- Formulario de confirmación -->
            <form action="api/reservations/create.php" method="post" onsubmit="window.location.href='index.php?page=reservations&success=created'; return true;">
                <input type="hidden" name="court_id" value="<?php echo $courtId; ?>">
                <input type="hidden" name="fecha" value="<?php echo $date; ?>">
                <input type="hidden" name="hora_inicio" value="<?php echo $startTime; ?>">
                <input type="hidden" name="hora_fin" value="<?php echo $endTime; ?>">
                <input type="hidden" name="precio" value="<?php echo $slotPrice; ?>">
                
                <div class="flex justify-between">
                    <a href="index.php?page=courts&date=<?php echo $date; ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Volver
                    </a>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-check mr-2"></i> Confirmar Reserva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
      <?php
  }
} else {
  // Mostrar lista de reservas del usuario
  $userId = $_SESSION['user_id'];
  
  $query = "SELECT r.*, c.nombre as court_name 
        FROM reservations r 
        JOIN courts c ON r.court_id = c.id 
        WHERE r.user_id = :user_id 
        ORDER BY r.created_at DESC";
  $stmt = $db->prepare($query);
  $stmt->bindParam(':user_id', $userId);
  $stmt->execute();
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
  ?>
  
  <div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <!-- Encabezado -->
    <div class="bg-primary p-6 text-white">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold flex items-center">
                <i class="fas fa-ticket-alt mr-3"></i>
                <?php echo ($_SESSION['user_role'] === 'admin') ? 'Reservas creadas manualmente' : 'Mis Reservas'; ?>
            </h2>
            <?php if ($_SESSION['user_role'] !== 'admin'): ?>
            <a href="index.php?page=courts" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-bold py-2 px-4 rounded-lg transition duration-300 backdrop-blur-sm flex items-center">
                <i class="fa fa-plus mr-2"></i>Nueva Reserva
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mensajes de notificación -->
    <div class="p-6">
        <?php if (isset($_GET['success']) && $_GET['success'] === 'created'): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <div>
                    <p class="font-medium">¡Reserva creada exitosamente!</p>
                    <p class="text-sm text-green-600">Su reserva ha sido registrada y está pendiente de confirmación.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'cancelled'): ?>
            <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-r-lg flex items-start">
                <i class="fas fa-info-circle text-blue-500 mr-3 mt-1"></i>
                <div>
                    <p class="font-medium">Reserva cancelada exitosamente</p>
                    <p class="text-sm text-blue-600">Su reserva ha sido cancelada correctamente.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($reservations)): ?>
            <div class="text-center py-12 bg-gray-50 rounded-xl border border-gray-200">
                <img src="https://cdn-icons-png.flaticon.com/512/6598/6598519.png" alt="No hay reservas" class="w-24 h-24 mx-auto mb-4 opacity-30">
                <p class="text-gray-600 mb-4 text-lg font-medium">No tienes reservas activas</p>
                <p class="text-gray-500 mb-6 max-w-md mx-auto">Reserva una cancha para comenzar a disfrutar de nuestras instalaciones.</p>
                <a href="index.php?page=courts" class="inline-block bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition duration-300 flex items-center mx-auto w-max">
                    <i class="fas fa-plus mr-2"></i> Reservar Cancha
                </a>
            </div>
        <?php else: ?>
            <!-- Tabla de reservas mejorada -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Cancha</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Fecha</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Horario</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Precio</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Estado</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($reservations as $reservation): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-baseball-ball text-primary mr-2"></i>
                                        <span class="font-medium"><?php echo $reservation['court_name']; ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center">
                                        <i class="far fa-calendar-alt text-gray-500 mr-2"></i>
                                        <?php echo date('d/m/Y', strtotime($reservation['fecha'])); ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center">
                                        <i class="far fa-clock text-gray-500 mr-2"></i>
                                        <?php echo substr($reservation['hora_inicio'], 0, 5); ?> - <?php echo substr($reservation['hora_fin'], 0, 5); ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-medium text-gray-800">
                                        $<?php echo number_format($reservation['precio'], 2); ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <?php 
                                    switch ($reservation['estado']) {
                                        case 'pendiente':
                                            echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-200 text-yellow-800 border border-yellow-200">
                                                    <i class="fas fa-clock mr-1"></i> Pendiente
                                                  </span>';
                                            break;
                                        case 'confirmada':
                                            echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-200 text-green-800 border border-green-200">
                                                    <i class="fas fa-check-circle mr-1"></i> Confirmada
                                                  </span>';
                                            break;
                                        case 'cancelada':
                                            echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-200 text-red-800 border border-red-200">
                                                    <i class="fas fa-times-circle mr-1"></i> Cancelada
                                                  </span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td class="py-4 px-4">
                                    <?php 
                                    // Solo permitir cancelar si no está cancelada y la fecha es futura o es hoy pero la hora no ha pasado
                                    $canCancel = $reservation['estado'] !== 'cancelada' && 
                                                ($reservation['fecha'] > date('Y-m-d') || 
                                                ($reservation['fecha'] == date('Y-m-d') && $reservation['hora_inicio'] > date('H:i:s')));
                                    
                                    if ($canCancel):
                                    ?>
                                        <button type="button" onclick="openCancelModal(<?php echo $reservation['id']; ?>, '<?php echo htmlspecialchars($reservation['court_name'], ENT_QUOTES); ?>', '<?php echo date('d/m/Y', strtotime($reservation['fecha'])); ?>', '<?php echo substr($reservation['hora_inicio'], 0, 5); ?>', '<?php echo substr($reservation['hora_fin'], 0, 5); ?>')" class="text-red-600 hover:text-red-800 inline-flex items-center text-sm bg-red-50 hover:bg-red-100 px-3 py-1 rounded-lg transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Cancelar
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">No disponible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Leyenda de estados -->
            <div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h4 class="font-medium text-gray-700 mb-2">Estados de reserva:</h4>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-yellow-400 mr-2"></span>
                        <span class="text-sm text-gray-600">Pendiente: Esperando confirmación</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                        <span class="text-sm text-gray-600">Confirmada: Lista para usar</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                        <span class="text-sm text-gray-600">Cancelada: No disponible</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
  <?php
}
?>

<!-- Modal de Cancelación de Reserva -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden max-w-md w-full mx-4 transform transition-all">
        <!-- Encabezado del modal -->
        <div class="bg-red-600 p-6 text-white">
            <h3 class="text-xl font-bold text-center">Cancelar Reserva</h3>
            <p class="text-center text-white text-opacity-80 mt-2">¿Está seguro que desea cancelar esta reserva?</p>
        </div>
        
        <!-- Contenido del modal -->
        <div class="p-6">
            <div class="bg-red-50 rounded-xl p-6 mb-6 border border-red-100">
                <h4 class="font-semibold text-lg mb-4 flex items-center text-gray-800">
                    <i class="fas fa-info-circle text-red-500 mr-2"></i>
                    Detalles de la Reserva
                </h4>
                
                <div class="grid grid-cols-1 gap-4">
                    <div class="flex items-start">
                        <div class="bg-red-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-baseball-ball text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Cancha</p>
                            <p id="modal-court" class="font-medium text-gray-800"></p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-red-100 p-2 rounded-lg mr-3">
                            <i class="far fa-calendar-alt text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Fecha</p>
                            <p id="modal-date" class="font-medium text-gray-800"></p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-red-100 p-2 rounded-lg mr-3">
                            <i class="far fa-clock text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Horario</p>
                            <p id="modal-time" class="font-medium text-gray-800"></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-red-100">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                        <p class="text-sm">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <form id="cancelForm" action="api/reservations/cancel.php" method="post">
                <input type="hidden" id="reservation_id" name="reservation_id" value="">
                
                <div class="flex justify-between">
                    <button type="button" onclick="closeCancelModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-times mr-2"></i> Volver
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-check mr-2"></i> Confirmar Cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Función para abrir el modal de cancelación
    function openCancelModal(reservationId, courtName, date, startTime, endTime) {
        // Establecer los valores en el modal
        document.getElementById('reservation_id').value = reservationId;
        document.getElementById('modal-court').textContent = courtName;
        document.getElementById('modal-date').textContent = date;
        document.getElementById('modal-time').textContent = startTime + ' - ' + endTime;
        
        // Mostrar el modal
        document.getElementById('cancelModal').classList.remove('hidden');
    }
    
    // Función para cerrar el modal
    function closeCancelModal() {
        document.getElementById('cancelModal').classList.add('hidden');
    }
    
    // Configurar el formulario para redirigir después de enviar
    document.getElementById('cancelForm').addEventListener('submit', function() {
        setTimeout(function() {
            window.location.href = 'index.php?page=reservations&success=cancelled';
        }, 100);
    });
</script>

