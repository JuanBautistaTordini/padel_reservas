/**
 * Funciones para actualizar la interfaz de usuario con los datos de disponibilidad
 */

/**
 * Actualiza la interfaz de usuario con los datos de disponibilidad recibidos
 * @param {Object} data - Datos de disponibilidad recibidos del servidor
 */
function updateAvailabilityUI(data) {
    if (!data || !data.reservations) {
        console.error('Datos de disponibilidad inválidos');
        return;
    }

    // Obtener todos los slots de tiempo disponibles en la página
    const timeSlots = document.querySelectorAll('.time-slot');
    
    if (timeSlots.length === 0) {
        console.warn('No se encontraron slots de tiempo en la página');
        return;
    }

    // Crear un mapa de las reservas por hora de inicio para búsqueda rápida
    const reservationMap = {};
    data.reservations.forEach(reservation => {
        // Usar solo la hora y minutos (HH:MM) como clave
        const startTime = reservation.hora_inicio.substring(0, 5);
        reservationMap[startTime] = reservation;
    });

    // Actualizar cada slot de tiempo
    timeSlots.forEach(slot => {
        // Obtener la hora del slot desde el atributo data-time
        const slotTime = slot.getAttribute('data-time');
        
        if (!slotTime) return;
        
        // Verificar si esta hora está reservada
        if (reservationMap[slotTime]) {
            // Marcar como no disponible
            slot.classList.add('reserved');
            slot.classList.remove('available');
            
            // Actualizar texto o estado visual
            const statusElement = slot.querySelector('.slot-status');
            if (statusElement) {
                statusElement.textContent = 'Reservado';
                statusElement.classList.add('text-red-600');
                statusElement.classList.remove('text-green-600');
            }
            
            // Deshabilitar el botón de reserva si existe
            const reserveButton = slot.querySelector('.reserve-button');
            if (reserveButton) {
                reserveButton.disabled = true;
                reserveButton.classList.add('opacity-50', 'cursor-not-allowed');
                reserveButton.classList.remove('hover:bg-secondary');
            }
        } else {
            // Marcar como disponible
            slot.classList.add('available');
            slot.classList.remove('reserved');
            
            // Actualizar texto o estado visual
            const statusElement = slot.querySelector('.slot-status');
            if (statusElement) {
                statusElement.textContent = 'Disponible';
                statusElement.classList.add('text-green-600');
                statusElement.classList.remove('text-red-600');
            }
            
            // Habilitar el botón de reserva si existe
            const reserveButton = slot.querySelector('.reserve-button');
            if (reserveButton) {
                reserveButton.disabled = false;
                reserveButton.classList.remove('opacity-50', 'cursor-not-allowed');
                reserveButton.classList.add('hover:bg-secondary');
            }
        }
    });

    // Mostrar notificación de actualización
    showUpdateNotification();
}

/**
 * Muestra una notificación temporal indicando que los datos se han actualizado
 */
function showUpdateNotification() {
    // Verificar si ya existe una notificación
    let notification = document.getElementById('availability-notification');
    
    // Si no existe, crearla
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'availability-notification';
        notification.className = 'fixed bottom-4 right-4 bg-primary text-white px-4 py-2 rounded-lg shadow-lg transform transition-transform duration-300 translate-y-20 opacity-0';
        notification.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Disponibilidad actualizada';
        document.body.appendChild(notification);
    }

    // Mostrar la notificación
    setTimeout(() => {
        notification.classList.remove('translate-y-20', 'opacity-0');
    }, 100);

    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.classList.add('translate-y-20', 'opacity-0');
    }, 3000);
}