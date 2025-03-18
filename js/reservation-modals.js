document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del modal
    const deleteReservationModal = document.getElementById('deleteReservationModal');
    const modalCloseButtons = document.querySelectorAll('#deleteReservationModal .modal-close');
    const reservationToDeleteInfo = document.getElementById('reservation_to_delete_info');
    const reservationIdInput = document.getElementById('reservation_id');
    
    // Función para abrir el modal de cancelación
    window.openDeleteReservationModal = function(reservationId, reservationInfo) {
        if (deleteReservationModal && reservationToDeleteInfo && reservationIdInput) {
            // Establecer la información de la reserva y el ID
            reservationToDeleteInfo.textContent = reservationInfo;
            reservationIdInput.value = reservationId;
            
            // Mostrar el modal
            deleteReservationModal.classList.remove('hidden');
            
            // Añadir clase para animación de entrada
            setTimeout(() => {
                const modalContainer = deleteReservationModal.querySelector('.modal-container');
                if (modalContainer) {
                    modalContainer.classList.add('scale-100', 'opacity-100');
                }
            }, 10);
        }
    };
    
    // Cerrar el modal cuando se hace clic en los botones de cierre
    if (modalCloseButtons) {
        modalCloseButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Añadir clase para animación de salida
                const modalContainer = deleteReservationModal.querySelector('.modal-container');
                if (modalContainer) {
                    modalContainer.classList.remove('scale-100', 'opacity-100');
                    modalContainer.classList.add('scale-95', 'opacity-0');
                }
                
                // Ocultar el modal después de la animación
                setTimeout(() => {
                    deleteReservationModal.classList.add('hidden');
                }, 300);
            });
        });
    }
    
    // Cerrar el modal cuando se hace clic fuera del contenido
    if (deleteReservationModal) {
        deleteReservationModal.addEventListener('click', function(e) {
            if (e.target === deleteReservationModal) {
                // Añadir clase para animación de salida
                const modalContainer = deleteReservationModal.querySelector('.modal-container');
                if (modalContainer) {
                    modalContainer.classList.remove('scale-100', 'opacity-100');
                    modalContainer.classList.add('scale-95', 'opacity-0');
                }
                
                // Ocultar el modal después de la animación
                setTimeout(() => {
                    deleteReservationModal.classList.add('hidden');
                }, 300);
            }
        });
    }
});