<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header('Location: index.php?page=unauthorized');
  exit;
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Verificar si hay una acción de edición
$editMode = false;
$courtToEdit = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $editMode = true;
  $courtId = $_GET['id'];
  
  $query = "SELECT * FROM courts WHERE id = :id";
  $stmt = $db->prepare($query);
  $stmt->bindParam(':id', $courtId);
  $stmt->execute();
  $courtToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$courtToEdit) {
      $editMode = false;
  }
}

// Obtener todas las canchas
$query = "SELECT * FROM courts ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$courts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white rounded-lg shadow-md p-6">
  <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold">Gestión de Canchas</h2>
      <a href="index.php?page=admin" class="text-primary hover:underline">
          <i class="fas fa-arrow-left mr-1"></i> Volver al Dashboard
      </a>
  </div>
  
  <?php if (isset($_GET['success'])): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          <?php 
          $success = $_GET['success'];
          switch ($success) {
              case 'created':
                  echo 'Cancha creada exitosamente.';
                  break;
              case 'updated':
                  echo 'Cancha actualizada exitosamente.';
                  break;
              case 'deleted':
                  echo 'Cancha eliminada exitosamente.';
                  break;
              default:
                  echo 'Operación completada exitosamente.';
          }
          ?>
      </div>
  <?php endif; ?>
  
  <?php if (isset($_GET['error'])): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          <?php 
          $error = $_GET['error'];
          switch ($error) {
              case 'empty_fields':
                  echo 'Por favor complete todos los campos.';
                  break;
              case 'invalid_time':
                  echo 'El horario de disponibilidad es inválido.';
                  break;
              case 'has_reservations':
                  echo 'No se puede eliminar la cancha porque tiene reservas asociadas.';
                  break;
              case 'image_upload':
                  echo 'Error al subir la imagen. Verifique el formato y tamaño.';
                  break;
              default:
                  echo 'Ha ocurrido un error. Por favor intente nuevamente.';
          }
          ?>
      </div>
  <?php endif; ?>
  
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="md:col-span-2">
          <h3 class="text-xl font-semibold mb-4">Lista de Canchas</h3>
          
          <div class="overflow-x-auto">
              <table class="min-w-full bg-white">
                  <thead>
                      <tr>
                          <th class="py-3 px-4 bg-gray-100 text-left">Imagen</th>
                          <th class="py-3 px-4 bg-gray-100 text-left">Nombre</th>
                          <th class="py-3 px-4 bg-gray-100 text-left">Superficie</th>
                          <th class="py-3 px-4 bg-gray-100 text-left">Disponibilidad</th>
                          <th class="py-3 px-4 bg-gray-100 text-left">Estado</th>
                          <th class="py-3 px-4 bg-gray-100 text-left">Acciones</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach ($courts as $court): ?>
                          <tr class="border-b">
                              <td class="py-3 px-4">
                                  <?php if (!empty($court['imagen'])): ?>
                                      <img src="uploads/courts/<?php echo $court['imagen']; ?>" alt="<?php echo $court['nombre']; ?>" class="w-16 h-16 object-cover rounded">
                                  <?php else: ?>
                                      <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                          <i class="fa-solid fa-baseball text-gray-400 text-xl"></i>
                                      </div>
                                  <?php endif; ?>
                              </td>
                              <td class="py-3 px-4"><?php echo $court['nombre']; ?></td>
                              <td class="py-3 px-4"><?php echo $court['superficie']; ?></td>
                              <td class="py-3 px-4"><?php echo substr($court['disponibilidad_inicio'], 0, 5); ?> - <?php echo substr($court['disponibilidad_fin'], 0, 5); ?></td>
                              <td class="py-3 px-4">
                                  <?php if ($court['estado']): ?>
                                      <span class="bg-green-200 text-green-800 py-1 px-2 rounded text-sm">Activa</span>
                                  <?php else: ?>
                                      <span class="bg-red-200 text-red-800 py-1 px-2 rounded text-sm">Inactiva</span>
                                  <?php endif; ?>
                              </td>
                              <td class="py-3 px-4">
                                  <a href="index.php?page=admin-courts&action=edit&id=<?php echo $court['id']; ?>" class="text-primary hover:underline mr-3">
                                      Editar
                                  </a>
                                  <form action="api/admin/courts/delete.php" method="post" class="inline" onsubmit="return confirm('¿Está seguro que desea eliminar esta cancha?');">
                                      <input type="hidden" name="court_id" value="<?php echo $court['id']; ?>">
                                      <button type="submit" class="text-red-600 hover:text-red-800">
                                          Eliminar
                                      </button>
                                  </form>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      </div>
      
      <div>
          <h3 class="text-xl font-semibold mb-4">
              <?php echo $editMode ? 'Editar Cancha' : 'Agregar Nueva Cancha'; ?>
          </h3>
          
          <form action="api/admin/courts/<?php echo $editMode ? 'update.php' : 'create.php'; ?>" method="post" enctype="multipart/form-data" class="bg-gray-50 p-4 rounded-lg">
              <?php if ($editMode): ?>
                  <input type="hidden" name="court_id" value="<?php echo $courtToEdit['id']; ?>">
              <?php endif; ?>
              
              <div class="mb-4">
                  <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre</label>
                  <input type="text" id="nombre" name="nombre" value="<?php echo $editMode ? $courtToEdit['nombre'] : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
              </div>
              
              <div class="mb-4">
                  <label for="superficie" class="block text-gray-700 font-medium mb-2">Superficie</label>
                  <select id="superficie" name="superficie" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                      <option value="">Seleccionar superficie</option>
                      <option value="Césped" <?php echo ($editMode && $courtToEdit['superficie'] === 'Césped') ? 'selected' : ''; ?>>Césped</option>
                      <option value="Cemento" <?php echo ($editMode && $courtToEdit['superficie'] === 'Cemento') ? 'selected' : ''; ?>>Cemento</option>
                      <option value="Arcilla" <?php echo ($editMode && $courtToEdit['superficie'] === 'Arcilla') ? 'selected' : ''; ?>>Arcilla</option>
                      <option value="Sintético" <?php echo ($editMode && $courtToEdit['superficie'] === 'Sintético') ? 'selected' : ''; ?>>Sintético</option>
                  </select>
              </div>
              
              <div class="mb-4">
                  <label for="disponibilidad_inicio" class="block text-gray-700 font-medium mb-2">Hora de Inicio</label>
                  <input type="time" id="disponibilidad_inicio" name="disponibilidad_inicio" value="<?php echo $editMode ? substr($courtToEdit['disponibilidad_inicio'], 0, 5) : '08:00'; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
              </div>
              
              <div class="mb-4">
                  <label for="disponibilidad_fin" class="block text-gray-700 font-medium mb-2">Hora de Fin</label>
                  <input type="time" id="disponibilidad_fin" name="disponibilidad_fin" value="<?php echo $editMode ? substr($courtToEdit['disponibilidad_fin'], 0, 5) : '22:00'; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
              </div>
              
              <div class="mb-4">
                  <label for="estado" class="block text-gray-700 font-medium mb-2">Estado</label>
                  <select id="estado" name="estado" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                      <option value="1" <?php echo ($editMode && $courtToEdit['estado'] == 1) ? 'selected' : ''; ?>>Activa</option>
                      <option value="0" <?php echo ($editMode && $courtToEdit['estado'] == 0) ? 'selected' : ''; ?>>Inactiva</option>
                  </select>
              </div>
              
              <div class="mb-4">
                  <label for="imagen" class="block text-gray-700 font-medium mb-2">Imagen de la Cancha</label>
                  <?php if ($editMode && !empty($courtToEdit['imagen'])): ?>
                      <div class="mb-2">
                          <img src="uploads/courts/<?php echo $courtToEdit['imagen']; ?>" alt="<?php echo $courtToEdit['nombre']; ?>" class="w-full h-40 object-cover rounded mb-2">
                          <div class="text-sm text-gray-500">Imagen actual</div>
                      </div>
                  <?php endif; ?>
                  <input type="file" id="imagen" name="imagen" accept="image/*" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  <p class="text-sm text-gray-500 mt-1">Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 2MB.</p>
              </div>
              
              <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                  <p class="text-yellow-800">
                      <strong>Nota:</strong> Los precios de las canchas ahora se configuran por horario en la sección de 
                      <a href="index.php?page=admin-settings" class="text-blue-600 hover:underline">Configuración</a>.
                  </p>
              </div>
              
              <div class="flex justify-between">
                  <?php if ($editMode): ?>
                      <a href="index.php?page=admin-courts" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                          Cancelar
                      </a>
                  <?php endif; ?>
                  
                  <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition duration-300 <?php echo $editMode ? '' : 'w-full'; ?>">
                      <?php echo $editMode ? 'Actualizar Cancha' : 'Agregar Cancha'; ?>
                  </button>
              </div>
          </form>
      </div>
  </div>
</div>

