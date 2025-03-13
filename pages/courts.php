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

// Obtener todas las canchas activas
$query = "SELECT * FROM courts WHERE estado = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$courts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener la fecha seleccionada o usar la fecha actual
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Convertir la fecha seleccionada a formato legible
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');
$formattedDate = date('d/m/Y', strtotime($selectedDate));
$dayName = strftime('%A', strtotime($selectedDate));
$dayName = ucfirst($dayName);

// Obtener las próximas fechas para el selector visual
$nextDates = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i day"));
    $dayOfWeek = date('D', strtotime($date));
    $dayNum = date('d', strtotime($date));
    $isSelected = ($date == $selectedDate);
    
    $nextDates[] = [
        'date' => $date,
        'day_of_week' => $dayOfWeek,
        'day_num' => $dayNum,
        'is_selected' => $isSelected
    ];
}
?>

<div class="bg-white rounded-lg shadow-md p-6">
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
    <h2 class="text-2xl font-bold mb-4 md:mb-0 flex items-center">
        <i class="fas fa-calendar-alt text-primary mr-3"></i>
        Disponibilidad de Canchas
    </h2>
    <div class="text-gray-500">
        <i class="far fa-clock mr-1"></i> Actualizado: <?php echo date('H:i'); ?>
    </div>
  </div>
  
  <!-- Selector de fecha visual -->
  <div class="mb-6">
    <form id="dateForm" action="" method="get">
        <input type="hidden" name="page" value="courts">
        <input type="hidden" id="dateInput" name="date" value="<?php echo $selectedDate; ?>">
        
        <div class="bg-gray-100 p-4 rounded-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-lg"><?php echo $dayName . ' ' . $formattedDate; ?></h3>
                <button type="button" id="calendarToggle" class="text-primary hover:text-primary-dark">
                    <i class="fas fa-calendar-alt"></i> Cambiar fecha
                </button>
            </div>
            
            <!-- Selector visual de próximos días -->
            <div class="grid grid-cols-7 gap-2">
                <?php foreach ($nextDates as $day): ?>
                <div 
                    class="date-option cursor-pointer text-center py-2 px-1 rounded-lg transition-all duration-200 <?php echo $day['is_selected'] ? 'bg-primary text-white' : 'bg-white hover:bg-gray-200'; ?>" 
                    data-date="<?php echo $day['date']; ?>"
                >
                    <div class="text-xs font-medium"><?php echo $day['day_of_week']; ?></div>
                    <div class="text-lg font-bold"><?php echo $day['day_num']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Selector de fecha avanzado (oculto por defecto) -->
            <div id="advancedDateSelector" class="hidden mt-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <label for="date" class="block text-gray-700 font-medium mb-2">Seleccionar Fecha</label>
                        <input type="date" id="date" value="<?php echo $selectedDate; ?>" min="<?php echo date('Y-m-d'); ?>" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <button type="button" id="applyDate" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Aplicar
                    </button>
                </div>
            </div>
        </div>
    </form>
  </div>
  
  <!-- Filtro de superficie (nuevo) -->
  <div class="mb-6">
    <div class="flex flex-wrap gap-2">
        <button type="button" class="surface-filter active bg-primary text-white py-1 px-3 rounded-full text-sm" data-surface="all">Todas</button>
        <button type="button" class="surface-filter bg-white hover:bg-gray-100 text-gray-700 py-1 px-3 rounded-full text-sm border" data-surface="Césped">Césped</button>
        <button type="button" class="surface-filter bg-white hover:bg-gray-100 text-gray-700 py-1 px-3 rounded-full text-sm border" data-surface="Cemento">Cemento</button>
        <button type="button" class="surface-filter bg-white hover:bg-gray-100 text-gray-700 py-1 px-3 rounded-full text-sm border" data-surface="Arcilla">Arcilla</button>
        <button type="button" class="surface-filter bg-white hover:bg-gray-100 text-gray-700 py-1 px-3 rounded-full text-sm border" data-surface="Sintético">Sintético</button>
    </div>
  </div>
  
  <div id="courts-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($courts as $court): ?>
          <div class="court-card border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300" data-surface="<?php echo $court['superficie']; ?>">
              <?php if (!empty($court['imagen']) && file_exists('uploads/courts/' . $court['imagen'])): ?>
                  <div class="relative overflow-hidden h-48">
                      <img src="uploads/courts/<?php echo $court['imagen']; ?>" alt="<?php echo $court['nombre']; ?>" class="w-full h-48 object-cover transition-all duration-500 hover:scale-110">
                      <!-- Etiqueta de superficie -->
                      <div class="absolute top-2 right-2 bg-black bg-opacity-70 text-white text-xs py-1 px-2 rounded">
                          <?php echo $court['superficie']; ?>
                      </div>
                  </div>
              <?php else: ?>
                  <div class="w-full h-48 bg-gray-200 flex items-center justify-center relative">
                      <i class="fa-solid fa-baseball text-gray-400 text-4xl"></i>
                      <!-- Etiqueta de superficie -->
                      <div class="absolute top-2 right-2 bg-black bg-opacity-70 text-white text-xs py-1 px-2 rounded">
                          <?php echo $court['superficie']; ?>
                      </div>
                  </div>
              <?php endif; ?>
              
              <div class="bg-gray-100 p-4">
                  <h3 class="text-xl font-semibold"><?php echo $court['nombre']; ?></h3>
                  <div class="flex items-center text-gray-600 mt-2">
                      <i class="far fa-clock text-primary mr-2"></i>
                      <span><?php echo substr($court['disponibilidad_inicio'], 0, 5); ?> - <?php echo substr($court['disponibilidad_fin'], 0, 5); ?></span>
                  </div>
              </div>
              
              <div class="p-4">
                  <h4 class="font-medium mb-3 flex items-center">
                      <i class="far fa-calendar-check text-primary mr-2"></i>
                      <span>Horarios para <?php echo $formattedDate; ?></span>
                  </h4>
                  
                  <div class="court-slots" data-court-id="<?php echo $court['id']; ?>" data-date="<?php echo $selectedDate; ?>">
                  <?php
                  // Obtener las reservas existentes para esta cancha en la fecha seleccionada
                  $query = "SELECT hora_inicio, hora_fin FROM reservations 
                            WHERE court_id = :court_id AND fecha = :fecha AND estado != 'cancelada'";
                  $stmt = $db->prepare($query);
                  $stmt->bindParam(':court_id', $court['id']);
                  $stmt->bindParam(':fecha', $selectedDate);
                  $stmt->execute();
                  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                  
                  // Crear array con las horas reservadas
                  $reservedHours = [];
                  foreach ($reservations as $reservation) {
                      $start = strtotime($reservation['hora_inicio']);
                      $end = strtotime($reservation['hora_fin']);
                      
                      for ($time = $start; $time < $end; $time += 3600) {
                          $reservedHours[date('H:i', $time)] = true;
                      }
                  }
                  
                  // Generar slots de 1 hora desde la hora de inicio hasta la hora de fin
                  $startTime = strtotime($court['disponibilidad_inicio']);
                  $endTime = strtotime($court['disponibilidad_fin']);
                  
                  // Obtener el intervalo de tiempo configurado (en minutos)
                  $query = "SELECT setting_value FROM settings WHERE setting_key = 'time_interval'";
                  $stmt = $db->prepare($query);
                  $stmt->execute();
                  $timeInterval = $stmt->fetchColumn();

                  // Si no hay configuración, usar 60 minutos por defecto
                  if (!$timeInterval) {
                      $timeInterval = 60;
                  }

                  // Convertir minutos a segundos para los cálculos
                  $timeIntervalSeconds = $timeInterval * 60;
                  $availableCount = 0;
                  $unavailableCount = 0;

                  // Generar slots de tiempo según el intervalo configurado
                  echo '<div class="grid grid-cols-2 gap-2">';
                  
                  for ($time = $startTime; $time < $endTime; $time += $timeIntervalSeconds) {
                      $timeSlot = date('H:i', $time);
                      $endTimeSlot = date('H:i', $time + $timeIntervalSeconds);
                      $isReserved = isset($reservedHours[$timeSlot]);
                      $isPast = ($selectedDate == date('Y-m-d') && $timeSlot < date('H:i'));
                      
                      // Calcular el precio del turno
                      $slotPrice = PriceCalculator::calculatePrice(
                          $selectedDate, 
                          $timeSlot.':00', 
                          $endTimeSlot.':00', 
                          $db
                      );
                      
                      if ($isReserved || $isPast || $slotPrice === null) {
                          $unavailableCount++;
                          echo '<div class="slot-item bg-gray-100 rounded-lg overflow-hidden shadow-sm border border-gray-200" data-start="' . $timeSlot . '" data-end="' . $endTimeSlot . '" data-status="unavailable">';
                          echo '<div class="p-3">';
                          echo '<div class="flex justify-between items-center">';
                          echo '<span class="font-medium text-gray-500">' . $timeSlot . ' - ' . $endTimeSlot . '</span>';
                          echo '<span class="status-badge bg-gray-200 text-gray-500 text-xs px-2 py-1 rounded-full">No disponible</span>';
                          echo '</div>';
                          echo '</div>';
                          echo '</div>';
                      } else {
                          $availableCount++;
                          echo '<a href="index.php?page=reservations&action=new&court_id=' . $court['id'] . '&date=' . $selectedDate . '&time=' . $timeSlot . '&duration=' . $timeInterval . '" ';
                          echo 'class="slot-item block bg-white rounded-lg overflow-hidden shadow-sm border border-secondary hover:border-secondary-dark transition-all duration-200 hover:shadow-md" data-start="' . $timeSlot . '" data-end="' . $endTimeSlot . '" data-status="available" data-price="' . $slotPrice . '">';
                          echo '<div class="p-3">';
                          echo '<div class="flex justify-between items-center">';
                          echo '<span class="font-medium text-gray-700">' . $timeSlot . ' - ' . $endTimeSlot . '</span>';
                          echo '<span class="status-badge bg-secondary text-white text-xs px-2 py-1 rounded-full">$' . number_format($slotPrice, 2) . '</span>';
                          echo '</div>';
                          echo '<div class="mt-2 text-xs text-gray-500 flex items-center">';
                          echo '<i class="far fa-clock mr-1"></i> Duración: ' . $timeInterval . ' min';
                          echo '</div>';
                          echo '</div>';
                          echo '</a>';
                      }
                  }
                  
                  echo '</div>';
                  
                  // Mostrar resumen de disponibilidad
                  echo '<div class="court-summary mt-4 flex justify-between text-sm bg-gray-50 rounded-lg p-3" data-court-id="' . $court['id'] . '">';
                  echo '<div class="flex items-center">';
                  echo '<div class="w-3 h-3 rounded-full bg-secondary mr-2"></div>';
                  echo '<span><span class="available-count font-medium text-green-600">' . $availableCount . '</span> turnos disponibles</span>';
                  echo '</div>';
                  echo '<div class="flex items-center">';
                  echo '<div class="w-3 h-3 rounded-full bg-gray-200 mr-2"></div>';
                  echo '<span><span class="unavailable-count font-medium text-gray-500">' . $unavailableCount . '</span> no disponibles</span>';
                  echo '</div>';
                  echo '</div>';
                  
                  // Añadir indicador de última actualización con posibilidad de actualizar manualmente
                  echo '<div class="court-last-update mt-2 text-xs text-gray-400 text-right cursor-pointer hover:text-primary transition-colors duration-200" data-court-id="' . $court['id'] . '">';
                  echo '<i class="fas fa-sync-alt mr-1"></i> Actualizado: <span>' . date('H:i:s') . '</span>';
                  echo '</div>';
                  ?>
                  </div>
              </div>
          </div>
      <?php endforeach; ?>
  </div>
  
  <!-- Mensaje si no hay canchas -->
  <div id="no-courts-message" class="hidden text-center py-8">
      <div class="text-gray-500">
          <i class="fas fa-search text-4xl mb-3"></i>
          <p class="text-lg">No se encontraron canchas con los filtros seleccionados.</p>
      </div>
  </div>
</div>

<style>
    /* Estilos para los slots de tiempo */
    .slot-item {
        transition: all 0.2s ease-in-out;
    }
    
    .slot-item[data-status="available"]:hover {
        transform: translateY(-2px);
    }
    
    /* Estilos para el indicador de actualización */
    .court-last-update:hover i {
        transform: rotate(180deg);
        transition: transform 0.5s ease;
    }
</style>

<script src="js/realtime-reservations.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Selector de fecha visual
        const dateOptions = document.querySelectorAll('.date-option');
        const dateInput = document.getElementById('dateInput');
        const dateForm = document.getElementById('dateForm');
        
        dateOptions.forEach(option => {
            option.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                dateInput.value = date;
                dateForm.submit();
            });
        });
        
        // Toggle para calendario avanzado
        const calendarToggle = document.getElementById('calendarToggle');
        const advancedDateSelector = document.getElementById('advancedDateSelector');
        
        calendarToggle.addEventListener('click', function() {
            advancedDateSelector.classList.toggle('hidden');
        });
        
        // Botón aplicar fecha
        const applyDateBtn = document.getElementById('applyDate');
        const datePicker = document.getElementById('date');
        
        applyDateBtn.addEventListener('click', function() {
            dateInput.value = datePicker.value;
            dateForm.submit();
        });
        
        // Filtro de superficie
        const surfaceFilters = document.querySelectorAll('.surface-filter');
        const courtCards = document.querySelectorAll('.court-card');
        const noCourtMessage = document.getElementById('no-courts-message');
        
        surfaceFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                // Eliminar clase activa de todos los filtros
                surfaceFilters.forEach(f => f.classList.remove('active', 'bg-primary', 'text-white'));
                surfaceFilters.forEach(f => f.classList.add('bg-white', 'text-gray-700'));
                
                // Agregar clase activa al filtro seleccionado
                this.classList.add('active', 'bg-primary', 'text-white');
                this.classList.remove('bg-white', 'text-gray-700');
                
                const selectedSurface = this.getAttribute('data-surface');
                let visibleCount = 0;
                
                // Filtrar canchas
                courtCards.forEach(card => {
                    const cardSurface = card.getAttribute('data-surface');
                    if (selectedSurface === 'all' || cardSurface === selectedSurface) {
                        card.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        card.classList.add('hidden');
                    }
                });
                
                // Mostrar mensaje si no hay canchas
                if (visibleCount === 0) {
                    noCourtMessage.classList.remove('hidden');
                } else {
                    noCourtMessage.classList.add('hidden');
                }
            });
        });
        
        // Inicializar sistema de actualizaciones en tiempo real para cada cancha
        const courtElements = document.querySelectorAll('.court-card[data-surface]');
        courtElements.forEach(court => {
            const courtId = court.querySelector('a')?.href.split('court_id=')[1]?.split('&')[0];
            if (courtId) {
                // Inicializar sistema de actualizaciones en tiempo real
                if (typeof RealtimeReservations !== 'undefined') {
                    RealtimeReservations.init(courtId, '<?php echo $selectedDate; ?>');
                }
            }
        });
    });
</script>

