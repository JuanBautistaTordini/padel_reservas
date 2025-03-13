<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header('Location: index.php?page=unauthorized');
  exit;
}

require_once 'config/timezone.php'; // Incluir configuración de zona horaria
require_once 'config/database.php';
require_once 'utils/price_calculator.php';
$database = new Database();
$db = $database->getConnection();

// Determinar si estamos en modo de creación manual
$createMode = isset($_GET['action']) && $_GET['action'] === 'create';

// Filtros
$courtFilter = isset($_GET['court_id']) ? $_GET['court_id'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Obtener todas las canchas para el filtro y el formulario
$query = "SELECT id, nombre FROM courts WHERE estado = 1 ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$courts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los clientes para el formulario de creación manual
$query = "SELECT id, nombre, email FROM users WHERE rol = 'cliente' ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construir la consulta con filtros
$query = "SELECT r.*, u.nombre as user_name, u.email as user_email, c.nombre as court_name 
      FROM reservations r 
      JOIN users u ON r.user_id = u.id 
      JOIN courts c ON r.court_id = c.id 
      WHERE 1=1";

$params = [];

if (!empty($courtFilter)) {
  $query .= " AND r.court_id = :court_id";
  $params[':court_id'] = $courtFilter;
}

if (!empty($dateFilter)) {
  $query .= " AND r.fecha = :fecha";
  $params[':fecha'] = $dateFilter;
}

if (!empty($statusFilter)) {
  $query .= " AND r.estado = :estado";
  $params[':estado'] = $statusFilter;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
  $stmt->bindValue($key, $value);
}
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white rounded-xl shadow-lg overflow-hidden">
  <!-- Header con título y botón de volver -->
  <div class="bg-primary to-secondary p-6 text-white">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold flex items-center">
        <i class="fas fa-calendar-check mr-3"></i>
        Gestión de Reservas
      </h2>
      <div class="flex space-x-3">
        <?php if (!$createMode): ?>
          <a href="index.php?page=admin-reservations&action=create" class="bg-white text-primary hover:bg-gray-100 font-bold py-2 px-4 rounded-lg transition duration-300 flex items-center">
            <i class="fas fa-plus mr-2"></i>Crear Reserva
          </a>
        <?php endif; ?>
        <a href="index.php?page=admin" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-bold py-2 px-4 rounded-lg transition duration-300 backdrop-blur-sm flex items-center">
          <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
      </div>
    </div>
  </div>

  <!-- Mensajes de notificación -->
  <div class="p-6 border-b border-gray-200">
    <?php if (isset($_GET['success'])): ?>
      <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r-lg flex items-start animate-fade-in">
        <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
        <div>
          <p class="font-medium">
            <?php 
            $success = $_GET['success'];
            switch ($success) {
                case 'created':
                    echo '¡Reserva creada exitosamente!';
                    break;
                case 'updated':
                    echo '¡Reserva actualizada exitosamente!';
                    break;
                case 'deleted':
                    echo '¡Reserva cancelada exitosamente!';
                    break;
                default:
                    echo 'Operación completada exitosamente.';
            }
            ?>
          </p>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
      <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r-lg flex items-start animate-fade-in">
        <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
        <div>
          <p class="font-medium">
            <?php 
            $error = $_GET['error'];
            switch ($error) {
                case 'empty_fields':
                    echo 'Por favor complete todos los campos.';
                    break;
                case 'already_reserved':
                    echo 'El horario seleccionado ya está reservado.';
                    break;
                case 'not_found':
                    echo 'La reserva no existe.';
                    break;
                default:
                    echo 'Ha ocurrido un error. Por favor intente nuevamente.';
            }
            ?>
          </p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($createMode): ?>
    <!-- Formulario de creación manual de reservas -->
    <div class="p-6">
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold flex items-center text-gray-800">
            <i class="fas fa-plus-circle text-primary mr-2"></i>
            Crear Reserva Manual
          </h3>
        </div>
        
        <div class="p-6">
          <form action="api/admin/reservations/create.php" method="post" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                <div class="relative">
                  <select id="client_id" name="client_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent appearance-none" required>
                    <option value="">Seleccionar cliente</option>
                    <?php foreach ($clients as $client): ?>
                      <option value="<?php echo $client['id']; ?>">
                        <?php echo $client['nombre']; ?> (<?php echo $client['email']; ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <i class="fas fa-chevron-down text-xs"></i>
                  </div>
                </div>
              </div>
              
              <div>
                <label for="court_id" class="block text-sm font-medium text-gray-700 mb-1">Cancha</label>
                <div class="relative">
                  <select id="court_id" name="court_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent appearance-none" required>
                    <option value="">Seleccionar cancha</option>
                    <?php foreach ($courts as $court): ?>
                      <option value="<?php echo $court['id']; ?>">
                        <?php echo $court['nombre']; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <i class="fas fa-chevron-down text-xs"></i>
                  </div>
                </div>
              </div>
              
              <div>
                <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="far fa-calendar-alt text-gray-400"></i>
                  </div>
                  <input type="date" id="fecha" name="fecha" min="<?php echo date('Y-m-d'); ?>" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required onchange="updatePriceEstimate()">
                </div>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="hora_inicio" class="block text-sm font-medium text-gray-700 mb-1">Hora Inicio</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <i class="far fa-clock text-gray-400"></i>
                    </div>
                    <input type="time" id="hora_inicio" name="hora_inicio" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required onchange="updatePriceEstimate()">
                  </div>
                </div>
                
                <div>
                  <label for="hora_fin" class="block text-sm font-medium text-gray-700 mb-1">Hora Fin</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <i class="far fa-clock text-gray-400"></i>
                    </div>
                    <input type="time" id="hora_fin" name="hora_fin" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required onchange="updatePriceEstimate()">
                  </div>
                </div>
              </div>
              
              <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <div class="relative">
                  <select id="estado" name="estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent appearance-none" required>
                    <option value="confirmada" selected>Confirmada</option>
                    <option value="pendiente">Pendiente</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <i class="fas fa-chevron-down text-xs"></i>
                  </div>
                </div>
              </div>

              <div>
                <label for="precio" class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-dollar-sign text-gray-400"></i>
                  </div>
                  <input type="number" id="precio" name="precio" value="0.00" min="0" step="0.01" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>
                <p class="text-sm text-gray-500 mt-1" id="price_note">El precio se calculará automáticamente según el horario seleccionado.</p>
              </div>
            </div>
            
            <div class="flex justify-between pt-4 border-t border-gray-200">
              <a href="index.php?page=admin-reservations" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                <i class="fas fa-times mr-2"></i> Cancelar
              </a>
              <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center">
                <i class="fas fa-save mr-2"></i> Crear Reserva
              </button>
            </div>
          </form>
        </div>
      </div>

      <script>
        function updatePriceEstimate() {
          const fecha = document.getElementById('fecha').value;
          const horaInicio = document.getElementById('hora_inicio').value;
          const horaFin = document.getElementById('hora_fin').value;
          
          if (fecha && horaInicio && horaFin) {
            // Realizar una solicitud AJAX para obtener el precio estimado
            fetch(`api/admin/reservations/get_price.php?fecha=${fecha}&hora_inicio=${horaInicio}&hora_fin=${horaFin}`)
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  document.getElementById('precio').value = data.price;
                  document.getElementById('price_note').textContent = 'Precio calculado según el horario seleccionado.';
                  document.getElementById('price_note').className = 'text-sm text-green-600 mt-1';
                } else {
                  document.getElementById('precio').value = '0.00';
                  document.getElementById('price_note').textContent = data.message || 'Error al calcular el precio. Intente nuevamente.';
                  document.getElementById('price_note').className = 'text-sm text-red-600 mt-1';
                }
              })
              .catch(error => {
                console.error('Error:', error);
                document.getElementById('price_note').textContent = 'Error al calcular el precio. Intente nuevamente.';
                document.getElementById('price_note').className = 'text-sm text-red-600 mt-1';
              });
          }
        }
      </script>
    </div>
  <?php else: ?>
    <!-- Filtros mejorados -->
    <div class="p-6 border-b border-gray-200 bg-gray-50">
      <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-800">
        <i class="fas fa-filter text-primary mr-2"></i>
        Filtros
      </h3>
      <form action="" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="hidden" name="page" value="admin-reservations">
        
        <div>
          <label for="court_id" class="block text-sm font-medium text-gray-700 mb-1">Cancha</label>
          <div class="relative">
            <select id="court_id" name="court_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent appearance-none">
              <option value="">Todas las canchas</option>
              <?php foreach ($courts as $court): ?>
                <option value="<?php echo $court['id']; ?>" <?php echo ($courtFilter == $court['id']) ? 'selected' : ''; ?>>
                  <?php echo $court['nombre']; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
              <i class="fas fa-chevron-down text-xs"></i>
            </div>
          </div>
        </div>
        
        <div>
          <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="far fa-calendar-alt text-gray-400"></i>
            </div>
            <input type="date" id="date" name="date" value="<?php echo $dateFilter; ?>" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
          </div>
        </div>
        
        <div>
          <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
          <div class="relative">
            <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent appearance-none">
              <option value="">Todos los estados</option>
              <option value="pendiente" <?php echo ($statusFilter === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
              <option value="confirmada" <?php echo ($statusFilter === 'confirmada') ? 'selected' : ''; ?>>Confirmada</option>
              <option value="cancelada" <?php echo ($statusFilter === 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
              <i class="fas fa-chevron-down text-xs"></i>
            </div>
          </div>
        </div>
        
        <div class="flex items-end space-x-2">
          <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
            <i class="fas fa-search mr-2"></i> Filtrar
          </button>
          <a href="index.php?page=admin-reservations" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
            <i class="fas fa-redo-alt mr-2"></i> Limpiar
          </a>
        </div>
      </form>
    </div>
    
    <!-- Tabla de reservas mejorada -->
    <div class="p-6">
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cancha</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creada</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (empty($reservations)): ?>
                <tr>
                  <td colspan="9" class="px-6 py-12 text-center">
                    <div class="text-gray-400 mb-4">
                      <i class="fas fa-calendar-times text-5xl"></i>
                    </div>
                    <p class="text-gray-600 text-lg font-medium">No se encontraron reservas</p>
                    <p class="text-gray-500 mt-1">Intente con otros filtros o cree una nueva reserva</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($reservations as $reservation): ?>
                  <tr class="hover:bg-gray-50 transition-colors">

                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-gray-100 rounded-full flex items-center justify-center">
                          <i class="fas fa-user text-gray-500"></i>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900"><?php echo $reservation['user_name']; ?></div>
                          <div class="text-sm text-gray-500"><?php echo $reservation['user_email']; ?></div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900 flex items-center">
                        <i class="fas fa-baseball-ball text-primary mr-2"></i>
                        <?php echo $reservation['court_name']; ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900 flex items-center">
                        <i class="far fa-calendar-alt text-gray-500 mr-2"></i>
                        <?php echo date('d/m/Y', strtotime($reservation['fecha'])); ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900 flex items-center">
                        <i class="far fa-clock text-gray-500 mr-2"></i>
                        <?php echo substr($reservation['hora_inicio'], 0, 5); ?> - <?php echo substr($reservation['hora_fin'], 0, 5); ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        $<?php echo number_format($reservation['precio'], 2); ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <?php 
                      switch ($reservation['estado']) {
                          case 'pendiente':
                              echo '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-200 text-yellow-800 border border-yellow-200">
                                      <i class="fas fa-clock mr-1"></i> Pendiente
                                    </span>';
                              break;
                          case 'confirmada':
                              echo '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-200 text-green-800 border border-green-200">
                                      <i class="fas fa-check-circle mr-1"></i> Confirmada
                                    </span>';
                              break;
                          case 'cancelada':
                              echo '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-200 text-red-800 border border-red-200">
                                      <i class="fas fa-times-circle mr-1"></i> Cancelada
                                    </span>';
                              break;
                      }
                      ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <?php if ($reservation['estado'] !== 'cancelada'): ?>
                        <div class="flex flex-col space-y-2">
                          <?php if ($reservation['estado'] !== 'confirmada'): ?>
                            <form action="api/admin/reservations/update.php" method="post" class="inline">
                              <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                              <input type="hidden" name="status" value="confirmada">
                              <button type="submit" class="text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 rounded-lg px-3 py-1 transition-colors flex items-center w-full">
                                <i class="fas fa-check-circle mr-1"></i> Confirmar
                              </button>
                            </form>
                          <?php endif; ?>
                          
                          <form action="api/admin/reservations/update.php" method="post" class="inline">
                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                            <input type="hidden" name="status" value="cancelada">
                            <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-lg px-3 py-1 transition-colors flex items-center w-full" onclick="return confirm('¿Está seguro que desea cancelar esta reserva?');">
                              <i class="fas fa-times-circle mr-1"></i> Cancelar
                            </button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span class="text-gray-400 text-sm flex items-center">
                          <i class="fas fa-ban mr-1"></i> No disponible
                        </span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Leyenda de estados -->
      <div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
        <h4 class="font-medium text-gray-700 mb-2 flex items-center">
          <i class="fas fa-info-circle text-primary mr-2"></i> Estados de reserva:
        </h4>
        <div class="flex flex-wrap gap-6">
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
    </div>
  <?php endif; ?>
</div>

<style>
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
  }
</style>

