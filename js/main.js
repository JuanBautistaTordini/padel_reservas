document.addEventListener("DOMContentLoaded", () => {
  // Función para mostrar/ocultar mensajes de alerta después de un tiempo
  const alerts = document.querySelectorAll(".bg-green-100, .bg-red-100")
  if (alerts.length > 0) {
    setTimeout(() => {
      alerts.forEach((alert) => {
        alert.style.transition = "opacity 0.5s ease"
        alert.style.opacity = "0"
        setTimeout(() => {
          alert.style.display = "none"
        }, 500)
      })
    }, 5000)
  }
  
  // Inicializar el polling de disponibilidad en la página de reservas
  if (window.location.href.includes('pages=courts')) {
    // Verificar si existe el selector de cancha y fecha
    const courtSelector = document.getElementById('court-selector');
    const dateSelector = document.getElementById('date-selector');
    
    if (courtSelector && dateSelector) {
      // Inicializar el poller con opciones personalizadas
      const availabilityPoller = new AvailabilityPoller({
        courtSelector: '#court-selector',
        dateSelector: '#date-selector',
        interval: 30000, // 30 segundos
        onUpdate: function(data) {
          console.log('Disponibilidad actualizada:', data);
          updateAvailabilityUI(data);
        },
        onError: function(error) {
          console.error('Error en el polling:', error);
        }
      });
      
      // Iniciar el polling
      availabilityPoller.start();
      
      // Detener el polling cuando el usuario cambie de página
      window.addEventListener('beforeunload', function() {
        availabilityPoller.stop();
      });
      
      // También actualizar cuando el usuario cambie la cancha o la fecha
      courtSelector.addEventListener('change', function() {
        availabilityPoller.forceUpdate();
      });
      
      dateSelector.addEventListener('change', function() {
        availabilityPoller.forceUpdate();
      });
    }
  }

  // Validación de formularios
  const registerForm = document.querySelector('form[action="api/auth/register.php"]')
  if (registerForm) {
    registerForm.addEventListener("submit", (e) => {
      const password = document.getElementById("password").value
      if (password.length < 6) {
        e.preventDefault()
        alert("La contraseña debe tener al menos 6 caracteres.")
      }
    })
  }

  // Validación de formulario de creación/edición de cancha
  const courtForm = document.querySelector('form[action*="api/admin/courts/"]')
  if (courtForm) {
    courtForm.addEventListener("submit", (e) => {
      const inicio = document.getElementById("disponibilidad_inicio").value
      const fin = document.getElementById("disponibilidad_fin").value

      if (inicio >= fin) {
        e.preventDefault()
        alert("La hora de fin debe ser posterior a la hora de inicio.")
      }
    })
  }
})

