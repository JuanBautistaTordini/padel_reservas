<div class="bg-white rounded-lg shadow-md p-6 mb-8 transition-all duration-300 hover:shadow-lg">
  <div class="text-center mb-12 animate-fade-in">
      <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4 relative inline-block">
          Bienvenido a ReservaCanchas
          <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-green-500 to-green-700 transform scale-x-0 group-hover:scale-x-100 transition-transform"></div>
      </h1>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          El sistema más fácil para reservar tu cancha deportiva favorita en cualquier momento.
      </p>
  </div>
  
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
      <div class="bg-gray-50 p-6 rounded-lg text-center transform transition-all duration-300 hover:scale-105 hover:shadow-md">
          <div class="text-primary text-4xl mb-4 bg-green-200 p-4 rounded-full w-20 h-20 flex items-center justify-center mx-auto">
              <i class="fas fa-calendar-alt"></i>
          </div>
          <h3 class="text-xl font-semibold mb-2">Reserva Online</h3>
          <p class="text-gray-600">Reserva tu cancha favorita en cualquier momento, desde cualquier dispositivo.</p>
      </div>
      
      <div class="bg-gray-50 p-6 rounded-lg text-center transform transition-all duration-300 hover:scale-105 hover:shadow-md">
          <div class="text-primary text-4xl mb-4 bg-green-200 p-4 rounded-full w-20 h-20 flex items-center justify-center mx-auto">
              <i class="fas fa-search"></i>
          </div>
          <h3 class="text-xl font-semibold mb-2">Disponibilidad en Tiempo Real</h3>
          <p class="text-gray-600">Consulta la disponibilidad de las canchas en tiempo real.</p>
      </div>
      
      <div class="bg-gray-50 p-6 rounded-lg text-center transform transition-all duration-300 hover:scale-105 hover:shadow-md">
          <div class="text-primary text-4xl mb-4 bg-green-200 p-4 rounded-full w-20 h-20 flex items-center justify-center mx-auto">
              <i class="fas fa-user-circle"></i>
          </div>
          <h3 class="text-xl font-semibold mb-2">Gestión de Reservas</h3>
          <p class="text-gray-600">Administra tus reservas fácilmente desde tu cuenta personal.</p>
      </div>
  </div>
  
  <div class="text-center">
      <?php if (!isset($_SESSION['user_id'])): ?>
          <a href="index.php?page=register" class="inline-block bg-primary hover:bg-primary-dark text-white font-bold py-3 px-8 rounded-lg mr-4 transition duration-300 transform hover:scale-105 hover:shadow-md">
              <i class="fas fa-user-plus mr-2"></i> Regístrate Ahora
          </a>
          <a href="index.php?page=login" class="inline-block bg-white border-2 border-primary text-primary hover:bg-gray-50 font-bold py-3 px-8 rounded-lg transition duration-300 transform hover:scale-105 hover:shadow-md">
              <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
          </a>
      <?php else: ?>
          <a href="index.php?page=courts" class="inline-block bg-primary hover:bg-primary-dark text-white font-bold py-3 px-8 rounded-lg transition duration-300 transform hover:scale-105 hover:shadow-md">
              <i class="fas fa-search mr-2"></i> Ver Canchas Disponibles
          </a>
      <?php endif; ?>
  </div>
</div>

<div class="mt-12">
  <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center relative inline-block after:content-[''] after:block after:absolute after:w-1/3 after:h-1 after:bg-primary after:bottom-0 after:left-1/3">Nuestras Canchas</h2>
  
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <?php
      require_once 'config/database.php';
      $database = new Database();
      $db = $database->getConnection();
      
      $query = "SELECT * FROM courts WHERE estado = 1 LIMIT 3";
      $stmt = $db->prepare($query);
      $stmt->execute();
      
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          echo '<div class="bg-white rounded-lg shadow-md overflow-hidden transform transition-all duration-300 hover:shadow-lg hover:scale-105">';
          
          // Mostrar imagen de la cancha o una imagen por defecto
          if (!empty($row['imagen']) && file_exists('uploads/courts/' . $row['imagen'])) {
              echo '<div class="relative overflow-hidden h-48">';
              echo '<img src="uploads/courts/' . $row['imagen'] . '" alt="' . $row['nombre'] . '" class="w-full h-48 object-cover transition-all duration-500 hover:scale-110">';
              echo '</div>';
          } else {
              echo '<div class="w-full h-48 bg-gray-200 flex items-center justify-center">';
              echo '<i class="fa-solid fa-baseball text-gray-400 text-6xl"></i>';
              echo '</div>';
          }
          
          echo '<div class="p-6">';
          echo '<h3 class="text-xl font-semibold mb-2">' . $row['nombre'] . '</h3>';
          echo '<div class="flex items-center mb-4 text-gray-600">';
          echo '<i class="fas fa-layer-group mr-2"></i><span>Superficie: ' . $row['superficie'] . '</span>';
          echo '</div>';
          echo '<div class="flex items-center mb-4 text-gray-600">';
          echo '<i class="far fa-clock mr-2"></i><span>Horario: ' . substr($row['disponibilidad_inicio'], 0, 5) . ' - ' . substr($row['disponibilidad_fin'], 0, 5) . '</span>';
          echo '</div>';
          
          // Obtener el precio mínimo para esta cancha
          $query = "SELECT MIN(price) as min_price FROM time_prices";
          $stmt2 = $db->prepare($query);
          $stmt2->execute();
          $minPrice = $stmt2->fetch(PDO::FETCH_ASSOC)['min_price'];
          
          if ($minPrice) {
              echo '<div class="flex items-center mb-4 text-gray-600">';
              echo '<i class="fas fa-tag mr-2"></i><span>Desde: $' . number_format($minPrice, 2) . ' por turno</span>';
              echo '</div>';
          }
          
          echo '<a href="' . (isset($_SESSION['user_id']) ? 'index.php?page=courts' : 'index.php?page=login&redirect=courts') . '" class="inline-block w-full bg-secondary hover:bg-secondary-dark text-white font-bold py-2 px-4 rounded transition duration-300 text-center">';
          echo '<i class="far fa-calendar-check mr-2"></i>Ver Disponibilidad';
          echo '</a>';
          echo '</div>';
          echo '</div>';
      }
      ?>
  </div>
  
  <!-- Información sobre precios por horario -->
  <div class="mt-12 bg-gradient-to-r from-gray-50 to-gray-100 p-8 rounded-lg shadow-sm">
      <h3 class="text-2xl font-semibold mb-6 text-center text-gray-800">Precios por Horario</h3>
      <p class="text-gray-600 mb-6 text-center max-w-3xl mx-auto">
          Nuestros precios varían según el día y la hora para ofrecerte la mejor experiencia en cada momento.
      </p>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
          <div class="bg-white p-6 rounded-lg shadow-sm transform transition-all duration-300 hover:shadow-md hover:scale-105">
              <div class="flex items-center mb-4">
                  <div class="bg-yellow-100 p-3 rounded-full mr-4">
                      <i class="fas fa-sun text-yellow-500 text-xl"></i>
                  </div>
                  <h4 class="font-semibold text-lg text-primary">Horario Mañana</h4>
              </div>
              <p class="text-gray-600">Disfruta de nuestras canchas en las primeras horas del día con tarifas especiales.</p>
          </div>
          
          <div class="bg-white p-6 rounded-lg shadow-sm transform transition-all duration-300 hover:shadow-md hover:scale-105">
              <div class="flex items-center mb-4">
                  <div class="bg-blue-100 p-3 rounded-full mr-4">
                      <i class="fas fa-cloud-sun text-blue-500 text-xl"></i>
                  </div>
                  <h4 class="font-semibold text-lg text-primary">Horario Tarde</h4>
              </div>
              <p class="text-gray-600">Aprovecha la tarde para practicar tu deporte favorito con precios accesibles.</p>
          </div>
          
          <div class="bg-white p-6 rounded-lg shadow-sm transform transition-all duration-300 hover:shadow-md hover:scale-105">
              <div class="flex items-center mb-4">
                  <div class="bg-indigo-100 p-3 rounded-full mr-4">
                      <i class="fas fa-moon text-indigo-500 text-xl"></i>
                  </div>
                  <h4 class="font-semibold text-lg text-primary">Horario Noche</h4>
              </div>
              <p class="text-gray-600">Reserva en horario nocturno y disfruta de nuestras instalaciones iluminadas.</p>
          </div>
      </div>
      
      <div class="text-center mt-8">
          <a href="<?php echo isset($_SESSION['user_id']) ? 'index.php?page=courts' : 'index.php?page=login&redirect=courts'; ?>" class="inline-block bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105 hover:shadow-md">
              <i class="fas fa-calendar-alt mr-2"></i> Ver Precios y Disponibilidad
          </a>
      </div>
  </div>
  
  <!-- Sección de testimonios -->
  <div class="mt-12 mb-8">
      <h3 class="text-2xl font-semibold mb-8 text-center relative inline-block after:content-[''] after:block after:absolute after:w-1/3 after:h-1 after:bg-primary after:bottom-0 after:left-1/3">Lo que opinan nuestros clientes</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="bg-white p-6 rounded-lg shadow-md">
              <div class="flex items-center mb-4">
                  <div class="text-yellow-400 mr-2">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                  </div>
                  <span class="text-gray-600">5.0</span>
              </div>
              <p class="text-gray-600 italic mb-4">"Excelente sistema para reservar canchas. Muy intuitivo y fácil de usar. Las instalaciones están siempre en perfecto estado."</p>
              <div class="flex items-center">
                  <div class="bg-gray-200 rounded-full w-10 h-10 flex items-center justify-center mr-3">
                      <i class="fas fa-user text-gray-500"></i>
                  </div>
                  <div>
                      <h4 class="font-medium">Juan Pérez</h4>
                      <p class="text-sm text-gray-500">Cliente frecuente</p>
                  </div>
              </div>
          </div>
          
          <div class="bg-white p-6 rounded-lg shadow-md">
              <div class="flex items-center mb-4">
                  <div class="text-yellow-400 mr-2">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                  </div>
                  <span class="text-gray-600">5.0</span>
              </div>
              <p class="text-gray-600 italic mb-4">"Me encanta la facilidad para ver la disponibilidad en tiempo real. Los precios son justos y el sistema funciona perfectamente."</p>
              <div class="flex items-center">
                  <div class="bg-gray-200 rounded-full w-10 h-10 flex items-center justify-center mr-3">
                      <i class="fas fa-user text-gray-500"></i>
                  </div>
                  <div>
                      <h4 class="font-medium">María López</h4>
                      <p class="text-sm text-gray-500">Jugadora amateur</p>
                  </div>
              </div>
          </div>
          
          <div class="bg-white p-6 rounded-lg shadow-md">
              <div class="flex items-center mb-4">
                  <div class="text-yellow-400 mr-2">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="far fa-star"></i>
                  </div>
                  <span class="text-gray-600">4.0</span>
              </div>
              <p class="text-gray-600 italic mb-4">"Muy buen servicio, la reserva online funciona perfectamente. Las canchas están siempre limpias y bien mantenidas."</p>
              <div class="flex items-center">
                  <div class="bg-gray-200 rounded-full w-10 h-10 flex items-center justify-center mr-3">
                      <i class="fas fa-user text-gray-500"></i>
                  </div>
                  <div>
                      <h4 class="font-medium">Carlos Rodríguez</h4>
                      <p class="text-sm text-gray-500">Entrenador</p>
                  </div>
              </div>
          </div>
      </div>
  </div>
</div>

<style>
/* Animaciones */
.animate-fade-in {
    animation: fadeIn 0.8s ease-in forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

