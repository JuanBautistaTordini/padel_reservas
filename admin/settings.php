<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header('Location: index.php?page=unauthorized');
  exit;
}
require_once 'config/timezone.php'; // Incluir configuración de zona horaria
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
// Obtener la configuración actual
$query = "SELECT * FROM settings WHERE setting_key = 'time_interval'";
$stmt = $db->prepare($query);
$stmt->execute();
$timeInterval = $stmt->fetch(PDO::FETCH_ASSOC);
// Si no existe, establecer valor por defecto
if (!$timeInterval) {
  $timeInterval = [
      'setting_key' => 'time_interval',
      'setting_value' => '60',
      'description' => 'Intervalo de tiempo entre turnos en minutos'
  ];
}
// Obtener los precios por turno
$query = "SELECT * FROM time_prices ORDER BY day_of_week, start_time";
$stmt = $db->prepare($query);
$stmt->execute();
$timePrices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
// Total de precios configurados
$query = "SELECT COUNT(*) as total FROM time_prices";
$stmt = $db->prepare($query);
$stmt->execute();
$totalPrices = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Precio promedio
$query = "SELECT AVG(price) as avg_price FROM time_prices";
$stmt = $db->prepare($query);
$stmt->execute();
$avgPrice = $stmt->fetch(PDO::FETCH_ASSOC)['avg_price'];
?>

<div class="bg-white rounded-lg shadow-md p-6">
  <div class="flex justify-between items-center mb-6">
      <div>
          <h2 class="text-2xl font-bold text-gray-800">Configuración del Sistema</h2>
          <p class="text-gray-500 text-sm mt-1">Gestiona los parámetros y precios del sistema de reservas</p>
      </div>
      <a href="index.php?page=admin" class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center text-sm">
          <i class="fas fa-arrow-left mr-2"></i> Volver al Dashboard
      </a>
  </div>
  
  <?php if (isset($_GET['success'])): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 animate-fade-in">
          <div class="flex">
              <div class="flex-shrink-0">
                  <i class="fas fa-check-circle text-green-500"></i>
              </div>
              <div class="ml-3">
                  <p class="text-sm font-medium">
                      <?php 
                      $success = $_GET['success'];
                      switch ($success) {
                          case 'updated':
                              echo 'Configuración actualizada exitosamente.';
                              break;
                          case 'price_added':
                              echo 'Precio por turno agregado exitosamente.';
                              break;
                          case 'price_updated':
                              echo 'Precio por turno actualizado exitosamente.';
                              break;
                          case 'price_deleted':
                              echo 'Precio por turno eliminado exitosamente.';
                              break;
                          case 'bulk_price_added':
                              echo 'Precios por turno agregados exitosamente para todos los días seleccionados.';
                              break;
                          case 'partial_bulk_price_added':
                              echo 'Algunos precios por turno fueron agregados exitosamente. Algunos días fueron omitidos debido a superposiciones.';
                              break;
                          default:
                              echo 'Operación completada exitosamente.';
                      }
                      ?>
                  </p>
              </div>
          </div>
      </div>
  <?php endif; ?>
  
  <?php if (isset($_GET['error'])): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 animate-fade-in">
          <div class="flex">
              <div class="flex-shrink-0">
                  <i class="fas fa-exclamation-circle text-red-500"></i>
              </div>
              <div class="ml-3">
                  <p class="text-sm font-medium">
                      <?php 
                      $error = $_GET['error'];
                      switch ($error) {
                          case 'empty_fields':
                              echo 'Por favor complete todos los campos.';
                              break;
                          case 'invalid_value':
                              echo 'El valor ingresado no es válido.';
                              break;
                          case 'invalid_time_range':
                              echo 'El rango de tiempo no es válido. La hora de fin debe ser posterior a la hora de inicio.';
                              break;
                          case 'overlapping_time_range':
                              echo 'El rango de tiempo se superpone con otro rango existente para el mismo día.';
                              break;
                          case 'some_days_overlapping':
                              echo 'Algunos días no pudieron ser configurados debido a superposiciones con rangos existentes.';
                              break;
                          case 'all_days_overlapping':
                              echo 'No se pudo configurar ningún día debido a superposiciones con rangos existentes.';
                              break;
                          default:
                              echo 'Ha ocurrido un error. Por favor intente nuevamente.';
                      }
                      ?>
                  </p>
              </div>
          </div>
      </div>
  <?php endif; ?>
  
  <!-- Tarjetas de resumen -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-100">
          <div class="flex items-center mb-2">
              <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-3">
                  <i class="fas fa-clock"></i>
              </div>
              <h3 class="text-lg font-semibold text-gray-800">Intervalo de Tiempo</h3>
          </div>
          <p class="text-gray-600 mb-4">Duración actual de cada turno: <span class="font-semibold text-primary"><?php echo $timeInterval['setting_value']; ?> minutos</span></p>
      </div>
      
      <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-100">
          <div class="flex items-center mb-2">
              <div class="p-3 rounded-full bg-green-200 text-green-500 mr-3">
                  <i class="fas fa-tag"></i>
              </div>
              <h3 class="text-lg font-semibold text-gray-800">Precios Configurados</h3>
          </div>
          <p class="text-gray-600 mb-4">Total de configuraciones: <span class="font-semibold text-primary"><?php echo $totalPrices; ?></span></p>
      </div>
      
      <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-100">
          <div class="flex items-center mb-2">
              <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-3">
                  <i class="fas fa-dollar-sign"></i>
              </div>
              <h3 class="text-lg font-semibold text-gray-800">Precio Promedio</h3>
          </div>
          <p class="text-gray-600 mb-4">Valor promedio: <span class="font-semibold text-primary">$<?php echo number_format($avgPrice, 2); ?></span></p>
      </div>
  </div>
  
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Configuración de Intervalo de Tiempo -->
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <div class="flex items-center mb-4">
              <div class="p-2 rounded-full bg-blue-100 text-blue-500 mr-3">
                  <i class="fas fa-hourglass-half"></i>
              </div>
              <h3 class="text-lg font-semibold text-gray-800">Intervalo de Tiempo entre Turnos</h3>
          </div>
          
          <form action="api/admin/settings/update.php" method="post" class="space-y-4">
              <input type="hidden" name="setting_key" value="time_interval">
              
              <div>
                  <label for="time_interval" class="block text-gray-700 font-medium mb-2">Duración del Turno (minutos)</label>
                  <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <i class="far fa-clock text-gray-400"></i>
                      </div>
                      <input type="number" id="time_interval" name="setting_value" value="<?php echo $timeInterval['setting_value']; ?>" min="30" max="240" step="15" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                  </div>
                  <p class="text-sm text-gray-500 mt-1">Establece la duración de cada turno en minutos (mínimo 30, máximo 240).</p>
              </div>
              
              <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                  <div class="text-blue-500 mr-3">
                      <i class="fas fa-info-circle"></i>
                  </div>
                  <p class="text-sm text-blue-600">El tiempo recomendado es 60 minutos (1 hora). Este valor afecta a todas las canchas del sistema.</p>
              </div>
              
              <div>
                  <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                      <i class="fas fa-save mr-2"></i> Guardar Configuración
                  </button>
              </div>
          </form>
      </div>
      
      <!-- Información de Ayuda con Toggle -->
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <div class="flex justify-between items-center mb-4">
              <div class="flex items-center">
                  <div class="p-2 rounded-full bg-blue-100 text-blue-500 mr-3">
                      <i class="fas fa-info-circle"></i>
                  </div>
                  <h3 class="text-lg font-semibold text-gray-800">Información</h3>
              </div>
              <button id="toggleInfoBtn" class="text-blue-600 hover:text-blue-800 focus:outline-none bg-blue-50 p-2 rounded-lg transition-colors">
                  <i class="fas fa-chevron-up" id="toggleIcon"></i>
              </button>
          </div>
          
          <div id="infoContent" class="transition-all duration-300 overflow-hidden space-y-4">
              <div class="p-4 bg-gray-50 rounded-lg">
                  <h4 class="font-medium text-gray-800 mb-2">Intervalo de Tiempo</h4>
                  <p class="text-gray-600 mb-2">El intervalo de tiempo define la duración de cada turno de reserva en el sistema.</p>
                  <ul class="list-disc list-inside text-gray-600 space-y-1 ml-2">
                      <li>Valor predeterminado: 60 minutos (1 hora)</li>
                      <li>Valores recomendados: 30, 60, 90 o 120 minutos</li>
                      <li>Este valor afecta a todas las canchas del sistema</li>
                  </ul>
              </div>
              
              <div class="p-4 bg-gray-50 rounded-lg">
                  <h4 class="font-medium text-gray-800 mb-2">Precios por Turno</h4>
                  <p class="text-gray-600 mb-2">Los precios por turno permiten establecer diferentes tarifas según el día y la hora.</p>
                  <ul class="list-disc list-inside text-gray-600 space-y-1 ml-2">
                      <li>Puede definir precios específicos para cada día de la semana</li>
                      <li>Los precios marcados como "Todos los días" se aplican cuando no hay un precio específico para ese día</li>
                      <li>Los rangos de tiempo no deben superponerse para el mismo día</li>
                  </ul>
              </div>
              
              <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                  <p class="text-yellow-800">
                      <strong>Nota:</strong> Cambiar el intervalo de tiempo puede afectar a las reservas existentes. Se recomienda hacer cambios cuando no haya reservas activas en el sistema.
                  </p>
              </div>
          </div>
      </div>
  </div>
  
  <!-- Precios por Turno -->
  <div class="mt-8">
      <div class="flex items-center mb-6">
          <div class="p-2 rounded-full bg-green-100 text-green-500 mr-3">
              <i class="fas fa-dollar-sign"></i>
          </div>
          <h3 class="text-xl font-semibold text-gray-800">Precios por Turno</h3>
      </div>
      
      <!-- Pestañas para elegir modo de configuración -->
      <div class="mb-6">
          <div class="flex border-b border-gray-200">
              <button id="tab-single" class="px-4 py-2 font-medium text-sm text-primary border-b-2 border-primary active-tab focus:outline-none">
                  Configuración Individual
              </button>
              <button id="tab-bulk" class="px-4 py-2 font-medium text-sm text-gray-500 hover:text-primary focus:outline-none">
                  Configuración Múltiple
              </button>
          </div>
      </div>
      
      <!-- Formulario para agregar nuevo precio por turno (individual) -->
      <div id="form-single" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
          <h4 class="font-medium mb-4 flex items-center">
              <i class="fas fa-plus-circle text-primary mr-2"></i> Agregar Nuevo Precio por Turno
          </h4>
          
          <form action="api/admin/settings/add_time_price.php" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                  <label for="day_of_week" class="block text-gray-700 font-medium mb-2">Día de la Semana</label>
                  <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <i class="far fa-calendar-alt text-gray-400"></i>
                      </div>
                      <select id="day_of_week" name="day_of_week" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary appearance-none" required>
                          <option value="all">Todos los días</option>
                          <option value="monday">Lunes</option>
                          <option value="tuesday">Martes</option>
                          <option value="wednesday">Miércoles</option>
                          <option value="thursday">Jueves</option>
                          <option value="friday">Viernes</option>
                          <option value="saturday">Sábado</option>
                          <option value="sunday">Domingo</option>
                      </select>
                      <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                          <i class="fas fa-chevron-down text-gray-400"></i>
                      </div>
                  </div>
              </div>
              
              <div>
                  <label for="price" class="block text-gray-700 font-medium mb-2">Precio</label>
                  <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <i class="fas fa-dollar-sign text-gray-400"></i>
                      </div>
                      <input type="number" id="price" name="price" min="0" step="0.01" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                  </div>
              </div>
              
              <div>
                  <label for="start_time" class="block text-gray-700 font-medium mb-2">Hora de Inicio</label>
                  <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <i class="far fa-clock text-gray-400"></i>
                      </div>
                      <input type="time" id="start_time" name="start_time" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                  </div>
              </div>
              
              <div>
                  <label for="end_time" class="block text-gray-700 font-medium mb-2">Hora de Fin</label>
                  <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <i class="far fa-clock text-gray-400"></i>
                      </div>
                      <input type="time" id="end_time" name="end_time" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                  </div>
              </div>
              
              <div class="md:col-span-2">
                  <label for="description" class="block text-gray-700 font-medium mb-2">Descripción</label>
                  <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <i class="fas fa-align-left text-gray-400"></i>
                      </div>
                      <input type="text" id="description" name="description" placeholder="Ej: Tarifa mañana" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  </div>
              </div>
              
              <div class="md:col-span-2">
                  <button type="submit" class="bg-secondary hover:bg-secondary-dark text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                      <i class="fas fa-plus mr-2"></i> Agregar Precio
                  </button>
              </div>
          </form>
      </div>
      
      <!-- Formulario para agregar precios por turno en múltiples días -->
      <div id="form-bulk" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6 hidden">
          <h4 class="font-medium mb-4 flex items-center">
              <i class="fas fa-layer-group text-primary mr-2"></i> Configuración Masiva de Precios
          </h4>
          
          <form action="api/admin/settings/add_bulk_time_price.php" method="post" class="space-y-4">
              <div class="bg-blue-50 p-4 rounded-lg mb-4">
                  <p class="text-sm text-blue-600 flex items-center">
                      <i class="fas fa-info-circle mr-2"></i> Selecciona múltiples días para aplicar la misma configuración de precios.
                  </p>
              </div>
          
              <div>
                  <label class="block text-gray-700 font-medium mb-2">Seleccionar Días</label>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                      <div class="flex items-center p-2 border rounded-lg hover:bg-gray-50">
                          <input type="checkbox" id="day_monday" name="days_of_week[]" value="monday" class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                          <label for="day_monday" class="text-sm text-gray-700">Lunes</label>
                      </div>
                      <div class="flex items-center p-2 border rounded-lg hover:bg-gray-50">
                          <input type="checkbox" id="day_tuesday" name="days_of_week[]" value="tuesday" class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                          <label for="day_tuesday" class="text-sm text-gray-700">Martes</label>
                      </div>
                      <div class="flex items-center p-2 border rounded-lg hover:bg-gray-50">
                          <input type="checkbox" id="day_wednesday" name="days_of_week[]" value="wednesday" class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                          <label for="day_wednesday" class="text-sm text-gray-700">Miércoles</label>
                      </div>
                      <div class="flex items-center p-2 border rounded-lg hover:bg-gray-50">
                          <input type="checkbox" id="day_thursday" name="days_of_week[]" value="thursday" class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                          <label for="day_thursday" class="text-sm text-gray-700">Jueves</label>
                      </div>
                      <div class="flex items-center p-2 border rounded-lg hover:bg-gray-50">
                          <input type="checkbox" id="day_friday" name="days_of_week[]" value="friday" class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                          <label for="day_friday" class="text-sm text-gray-700">Viernes</label>
                      </div>
                      <div class="flex items-center p-2 border rounded-lg hover:bg-gray-50">
                          <input type="checkbox" id="day_saturday" name="days_of_week[]" value="saturday" class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                          <label for="day_saturday" class="text-sm text-gray-700">Sábado</label>
                      </div>
                      <div class="flex items-center p-2 border rounded-lg hover:bg-gray-50">
                          <input type="checkbox" id="day_sunday" name="days_of_week[]" value="sunday" class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                          <label for="day_sunday" class="text-sm text-gray-700">Domingo</label>
                      </div>
                      <div class="flex items-center p-2 border rounded-lg hover:bg-gray-50">
                          <input type="checkbox" id="day_all" name="days_of_week[]" value="all" class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                          <label for="day_all" class="text-sm text-gray-700">Todos los días</label>
                      </div>
                  </div>
                  <div class="mt-2 flex space-x-4">
                      <button type="button" id="btn-weekdays" class="text-sm text-primary hover:underline focus:outline-none flex items-center">
                          <i class="fas fa-briefcase mr-1"></i> Días laborables
                      </button>
                      <button type="button" id="btn-weekend" class="text-sm text-primary hover:underline focus:outline-none flex items-center">
                          <i class="fas fa-umbrella-beach mr-1"></i> Fin de semana
                      </button>
                      <button type="button" id="btn-select-all" class="text-sm text-primary hover:underline focus:outline-none flex items-center">
                          <i class="fas fa-check-square mr-1"></i> Seleccionar todos
                      </button>
                      <button type="button" id="btn-clear-all" class="text-sm text-red-600 hover:underline focus:outline-none flex items-center">
                          <i class="fas fa-times-circle mr-1"></i> Limpiar
                      </button>
                  </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                      <label for="bulk_price" class="block text-gray-700 font-medium mb-2">Precio</label>
                      <div class="relative">
                          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                              <i class="fas fa-dollar-sign text-gray-400"></i>
                          </div>
                          <input type="number" id="bulk_price" name="price" min="0" step="0.01" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                      </div>
                  </div>
                  
                  <div class="grid grid-cols-2 gap-4">
                      <div>
                          <label for="bulk_start_time" class="block text-gray-700 font-medium mb-2">Hora de Inicio</label>
                          <div class="relative">
                              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                  <i class="far fa-clock text-gray-400"></i>
                              </div>
                              <input type="time" id="bulk_start_time" name="start_time" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                          </div>
                      </div>
                      
                      <div>
                          <label for="bulk_end_time" class="block text-gray-700 font-medium mb-2">Hora de Fin</label>
                          <div class="relative">
                              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                  <i class="far fa-clock text-gray-400"></i>
                              </div>
                              <input type="time" id="bulk_end_time" name="end_time" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                          </div>
                      </div>
                  </div>
              </div>
              
              <div>
                  <label for="bulk_description" class="block text-gray-700 font-medium mb-2">Descripción</label>
                  <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <i class="fas fa-align-left text-gray-400"></i>
                      </div>
                      <input type="text" id="bulk_description" name="description" placeholder="Ej: Tarifa estándar" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  </div>
              </div>
              
              <div>
                  <button type="submit" class="w-full bg-secondary hover:bg-secondary-dark text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                      <i class="fas fa-save mr-2"></i> Configurar Precios
                  </button>
              </div>
          </form>
      </div>
      
      <!-- Tabla de precios por turno -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
              <h4 class="font-medium text-gray-800 flex items-center">
                  <i class="fas fa-list text-primary mr-2"></i> Lista de Precios Configurados
              </h4>
              <div class="relative">
                  <input type="text" id="priceSearchInput" placeholder="Buscar..." class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary text-sm">
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                      <i class="fas fa-search text-gray-400"></i>
                  </div>
              </div>
          </div>
          
          <div class="overflow-x-auto">
              <table class="min-w-full bg-white" id="pricesTable">
                  <thead>
                      <tr class="bg-gray-50 text-left">
                          <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Día</th>
                          <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Horario</th>
                          <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Precio</th>
                          <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Descripción</th>
                          <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Acciones</th>
                      </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                      <?php if (empty($timePrices)): ?>
                          <tr>
                              <td colspan="5" class="py-8 px-4 text-center text-gray-500">
                                  <div class="flex flex-col items-center">
                                      <i class="fas fa-info-circle text-2xl mb-2"></i>
                                      <p>No hay precios por turno configurados.</p>
                                      <p class="text-sm mt-1">Utiliza el formulario para agregar nuevos precios.</p>
                                  </div>
                              </td>
                          </tr>
                      <?php else: ?>
                          <?php foreach ($timePrices as $price): ?>
                              <tr class="hover:bg-gray-50 transition-colors price-row">
                                  <td class="py-3 px-4 font-medium">
                                      <?php 
                                      $dayText = '';
                                      $dayClass = '';
                                      switch ($price['day_of_week']) {
                                          case 'all': 
                                              $dayText = 'Todos los días'; 
                                              $dayClass = 'bg-blue-100 text-blue-800';
                                              break;
                                          case 'monday': 
                                              $dayText = 'Lunes'; 
                                              $dayClass = 'bg-gray-100 text-gray-800';
                                              break;
                                          case 'tuesday': 
                                              $dayText = 'Martes'; 
                                              $dayClass = 'bg-gray-100 text-gray-800';
                                              break;
                                          case 'wednesday': 
                                              $dayText = 'Miércoles'; 
                                              $dayClass = 'bg-gray-100 text-gray-800';
                                              break;
                                          case 'thursday': 
                                              $dayText = 'Jueves'; 
                                              $dayClass = 'bg-gray-100 text-gray-800';
                                              break;
                                          case 'friday': 
                                              $dayText = 'Viernes'; 
                                              $dayClass = 'bg-gray-100 text-gray-800';
                                              break;
                                          case 'saturday': 
                                              $dayText = 'Sábado'; 
                                              $dayClass = 'bg-yellow-100 text-yellow-800';
                                              break;
                                          case 'sunday': 
                                              $dayText = 'Domingo'; 
                                              $dayClass = 'bg-yellow-100 text-yellow-800';
                                              break;
                                      }
                                      echo '<span class="px-2 py-1 rounded-full text-xs ' . $dayClass . '">' . $dayText . '</span>';
                                      ?>
                                  </td>
                                  <td class="py-3 px-4">
                                      <div class="flex items-center">
                                          <i class="far fa-clock text-gray-400 mr-2"></i>
                                          <span><?php echo substr($price['start_time'], 0, 5); ?> - <?php echo substr($price['end_time'], 0, 5); ?></span>
                                      </div>
                                  </td>
                                  <td class="py-3 px-4 font-medium text-gray-800">$<?php echo number_format($price['price'], 2); ?></td>
                                  <td class="py-3 px-4 text-gray-600"><?php echo $price['description'] ?: 'Sin descripción'; ?></td>
                                  <td class="py-3 px-4">
                                      <div class="flex space-x-2">
                                          <button 
                                              class="text-primary hover:text-primary-dark p-1 rounded-full hover:bg-primary-50 transition-colors focus:outline-none edit-price-btn"
                                              data-id="<?php echo $price['id']; ?>"
                                              data-day="<?php echo $price['day_of_week']; ?>"
                                              data-start="<?php echo substr($price['start_time'], 0, 5); ?>"
                                              data-end="<?php echo substr($price['end_time'], 0, 5); ?>"
                                              data-price="<?php echo $price['price']; ?>"
                                              data-description="<?php echo $price['description']; ?>"
                                              title="Editar"
                                          >
                                              <i class="fas fa-edit"></i>
                                          </button>
                                          <button 
                                              class="text-red-600 hover:text-red-800 p-1 rounded-full hover:bg-red-50 transition-colors focus:outline-none delete-price-btn"
                                              data-id="<?php echo $price['id']; ?>"
                                              data-info="<?php echo $dayText . ' ' . substr($price['start_time'], 0, 5) . ' - ' . substr($price['end_time'], 0, 5) . ' ($' . number_format($price['price'], 2) . ')'; ?>"
                                              title="Eliminar"
                                          >
                                              <i class="fas fa-trash"></i>
                                          </button>
                                      </div>
                                  </td>
                              </tr>
                          <?php endforeach; ?>
                      <?php endif; ?>
                  </tbody>
              </table>
          </div>
          
          <!-- Mensaje "Sin resultados" (oculto por defecto) -->
          <div id="noResultsMessage" class="hidden p-8 text-center text-gray-500">
              <i class="fas fa-search text-2xl mb-2"></i>
              <p>No se encontraron precios con la búsqueda.</p>
          </div>
      </div>
      
      <!-- Información sobre horarios no disponibles -->
      <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
          <div class="flex">
              <div class="flex-shrink-0 text-yellow-500 mr-3">
                  <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div>
                  <h4 class="font-medium text-yellow-800 mb-2">Horarios No Disponibles</h4>
                  <p class="text-yellow-700 mb-2">
                      Los horarios que no tienen un precio configurado se consideran como <strong>"No disponibles"</strong> y no se podrán reservar.
                  </p>
                  <p class="text-yellow-700">
                      Por ejemplo, si configura precios para los horarios 8:00-13:00 y 16:00-22:00, el horario 13:01-15:59 quedará automáticamente como "No disponible".
                  </p>
              </div>
          </div>
      </div>
  </div>
</div>

<!-- Modal para editar precio -->
<div id="editPriceModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded-lg shadow-lg overflow-y-auto">
        <div class="modal-content p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <p class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-edit text-primary mr-2"></i> Editar Precio por Turno
                </p>
                <div class="modal-close cursor-pointer z-50 p-1 rounded-full hover:bg-gray-200 transition-colors">
                    <svg class="fill-current text-gray-500 hover:text-gray-800" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>
            
            <form action="api/admin/settings/update_time_price.php" method="post" class="space-y-4">
                <input type="hidden" id="edit_price_id" name="price_id">
                
                <div>
                    <label for="edit_day_of_week" class="block text-gray-700 font-medium mb-2">Día de la Semana</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="far fa-calendar-alt text-gray-400"></i>
                        </div>
                        <select id="edit_day_of_week" name="day_of_week" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary appearance-none" required>
                            <option value="all">Todos los días</option>
                            <option value="monday">Lunes</option>
                            <option value="tuesday">Martes</option>
                            <option value="wednesday">Miércoles</option>
                            <option value="thursday">Jueves</option>
                            <option value="friday">Viernes</option>
                            <option value="saturday">Sábado</option>
                            <option value="sunday">Domingo</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="edit_price" class="block text-gray-700 font-medium mb-2">Precio</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-dollar-sign text-gray-400"></i>
                        </div>
                        <input type="number" id="edit_price" name="price" min="0" step="0.01" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_start_time" class="block text-gray-700 font-medium mb-2">Hora de Inicio</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="far fa-clock text-gray-400"></i>
                            </div>
                            <input type="time" id="edit_start_time" name="start_time" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                        </div>
                    </div>
                    
                    <div>
                        <label for="edit_end_time" class="block text-gray-700 font-medium mb-2">Hora de Fin</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="far fa-clock text-gray-400"></i>
                            </div>
                            <input type="time" id="edit_end_time" name="end_time" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="edit_description" class="block text-gray-700 font-medium mb-2">Descripción</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-align-left text-gray-400"></i>
                        </div>
                        <input type="text" id="edit_description" name="description" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                
                <div class="flex justify-end pt-2 space-x-3">
                    <button type="button" class="modal-close px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors focus:outline-none">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors focus:outline-none flex items-center">
                        <i class="fas fa-save mr-2"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para eliminar precio -->
<div id="deletePriceModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded-lg shadow-lg overflow-y-auto">
        <div class="modal-content p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <p class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-trash-alt text-red-500 mr-2"></i> Confirmar Eliminación
                </p>
                <div class="modal-close cursor-pointer z-50 p-1 rounded-full hover:bg-gray-200 transition-colors">
                    <svg class="fill-current text-gray-500 hover:text-gray-800" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>
            
            <div class="my-4">
                <div class="bg-red-50 p-4 rounded-lg mb-4">
                    <p class="text-red-800 flex items-start">
                        <i class="fas fa-exclamation-circle mt-1 mr-2"></i>
                        <span>¿Está seguro que desea eliminar el siguiente precio por turno? Esta acción no se puede deshacer.</span>
                    </p>
                </div>
                
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="font-medium text-gray-800" id="price_to_delete_info"></p>
                </div>
            </div>
            
            <form action="api/admin/settings/delete_time_price.php" method="post">
                <input type="hidden" id="delete_price_id" name="price_id">
                
                <div class="flex justify-end pt-2 space-x-3">
                    <button type="button" class="modal-close px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors focus:outline-none">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors focus:outline-none flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i> Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle para el panel de información
        const toggleBtn = document.getElementById('toggleInfoBtn');
        const toggleIcon = document.getElementById('toggleIcon');
        const infoContent = document.getElementById('infoContent');
        let infoContentHeight = infoContent.scrollHeight;
        let isInfoOpen = true;
        
        // Función para alternar la visibilidad del panel de información
        function toggleInfoPanel() {
            if (isInfoOpen) {
                // Cerrar el panel
                infoContent.style.maxHeight = '0';
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            } else {
                // Abrir el panel
                infoContent.style.maxHeight = infoContentHeight + 'px';
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            }
            isInfoOpen = !isInfoOpen;
        }
        
        // Inicializar el panel abierto
        infoContent.style.maxHeight = infoContentHeight + 'px';
        
        // Agregar evento al botón de toggle
        toggleBtn.addEventListener('click', toggleInfoPanel);
        
        // Manejo de pestañas para configuración individual y masiva
        const tabSingle = document.getElementById('tab-single');
        const tabBulk = document.getElementById('tab-bulk');
        const formSingle = document.getElementById('form-single');
        const formBulk = document.getElementById('form-bulk');
        
        tabSingle.addEventListener('click', function() {
            formSingle.classList.remove('hidden');
            formBulk.classList.add('hidden');
            tabSingle.classList.add('text-primary', 'border-primary');
            tabSingle.classList.remove('text-gray-500');
            tabBulk.classList.remove('text-primary', 'border-primary');
            tabBulk.classList.add('text-gray-500');
        });
        
        tabBulk.addEventListener('click', function() {
            formBulk.classList.remove('hidden');
            formSingle.classList.add('hidden');
            tabBulk.classList.add('text-primary', 'border-primary');
            tabBulk.classList.remove('text-gray-500');
            tabSingle.classList.remove('text-primary', 'border-primary');
            tabSingle.classList.add('text-gray-500');
        });
        
        // Botones de selección rápida para días
        const btnWeekdays = document.getElementById('btn-weekdays');
        const btnWeekend = document.getElementById('btn-weekend');
        const btnSelectAll = document.getElementById('btn-select-all');
        const btnClearAll = document.getElementById('btn-clear-all');
        
        btnWeekdays.addEventListener('click', function() {
            document.getElementById('day_monday').checked = true;
            document.getElementById('day_tuesday').checked = true;
            document.getElementById('day_wednesday').checked = true;
            document.getElementById('day_thursday').checked = true;
            document.getElementById('day_friday').checked = true;
            document.getElementById('day_saturday').checked = false;
            document.getElementById('day_sunday').checked = false;
            document.getElementById('day_all').checked = false;
        });
        
        btnWeekend.addEventListener('click', function() {
            document.getElementById('day_monday').checked = false;
            document.getElementById('day_tuesday').checked = false;
            document.getElementById('day_wednesday').checked = false;
            document.getElementById('day_thursday').checked = false;
            document.getElementById('day_friday').checked = false;
            document.getElementById('day_saturday').checked = true;
            document.getElementById('day_sunday').checked = true;
            document.getElementById('day_all').checked = false;
        });
        
        btnSelectAll.addEventListener('click', function() {
            document.getElementById('day_monday').checked = true;
            document.getElementById('day_tuesday').checked = true;
            document.getElementById('day_wednesday').checked = true;
            document.getElementById('day_thursday').checked = true;
            document.getElementById('day_friday').checked = true;
            document.getElementById('day_saturday').checked = true;
            document.getElementById('day_sunday').checked = true;
            document.getElementById('day_all').checked = false;
        });
        
        btnClearAll.addEventListener('click', function() {
            document.getElementById('day_monday').checked = false;
            document.getElementById('day_tuesday').checked = false;
            document.getElementById('day_wednesday').checked = false;
            document.getElementById('day_thursday').checked = false;
            document.getElementById('day_friday').checked = false;
            document.getElementById('day_saturday').checked = false;
            document.getElementById('day_sunday').checked = false;
            document.getElementById('day_all').checked = false;
        });
        
        // Buscador de precios
        const searchInput = document.getElementById('priceSearchInput');
        const priceRows = document.querySelectorAll('.price-row');
        const noResultsMessage = document.getElementById('noResultsMessage');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            
            priceRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                }
            });
            
            // Mostrar mensaje cuando no hay resultados
            if (visibleCount === 0 && searchTerm !== '') {
                noResultsMessage.classList.remove('hidden');
            } else {
                noResultsMessage.classList.add('hidden');
            }
        });
        
        // Edit price button handler - support both button and icon clicks
        document.querySelectorAll('.edit-price-btn, .fa-edit').forEach(button => {
            button.addEventListener('click', function() {
                const modal = document.getElementById('editPriceModal');
                // Get data from closest parent with data attributes or from the element itself
                const parent = this.closest('[data-id]') || this;
                const id = parent.getAttribute('data-id');
                const day = parent.getAttribute('data-day');
                const start = parent.getAttribute('data-start');
                const end = parent.getAttribute('data-end');
                const price = parent.getAttribute('data-price');
                const description = parent.getAttribute('data-description');
                
                // Fill form fields
                document.getElementById('edit_price_id').value = id;
                document.getElementById('edit_day_of_week').value = day;
                document.getElementById('edit_start_time').value = start;
                document.getElementById('edit_end_time').value = end;
                document.getElementById('edit_price').value = price;
                document.getElementById('edit_description').value = description;
                
                // Show modal
                modal.classList.remove('hidden');
            });
        });
        
        // Delete price button handler - support both button and icon clicks
        document.querySelectorAll('.delete-price-btn, .fa-trash').forEach(button => {
            button.addEventListener('click', function() {
                const modal = document.getElementById('deletePriceModal');
                // Get data from closest parent with data attributes or from the element itself
                const parent = this.closest('[data-id]') || this;
                const id = parent.getAttribute('data-id');
                const info = parent.getAttribute('data-info');
                
                // Fill form fields
                document.getElementById('delete_price_id').value = id;
                document.getElementById('price_to_delete_info').textContent = info;
                
                // Show modal
                modal.classList.remove('hidden');
            });
        });
        
        // Close modal handlers
        document.querySelectorAll('.modal-close').forEach(button => {
            button.addEventListener('click', function() {
                const modals = document.querySelectorAll('.modal-overlay');
                modals.forEach(modal => modal.classList.add('hidden'));
            });
        });
        
        // Cerrar modales al hacer clic fuera de ellos
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
        
        // Animación para mensajes de éxito/error
        const alerts = document.querySelectorAll('.animate-fade-in');
        if (alerts.length > 0) {
            setTimeout(() => {
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        alert.classList.add('hidden');
                    }, 500);
                });
            }, 5000);
        }
    });
</script>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.5s ease forwards;
    }
</style>

