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

