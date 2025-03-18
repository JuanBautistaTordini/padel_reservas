/**
 * Módulo de polling AJAX para actualizar la disponibilidad de canchas en tiempo real
 */

class AvailabilityPoller {
    constructor(options = {}) {
        // Configuración por defecto
        this.options = {
            endpoint: '/api/reservations/get_availability.php',
            interval: 30000, // 30 segundos por defecto
            courtSelector: '#court-selector',
            dateSelector: '#date-selector',
            onUpdate: null,
            onError: null,
            ...options
        };

        this.lastData = null;
        this.pollingTimer = null;
        this.isPolling = false;
    }

    /**
     * Inicia el polling de disponibilidad
     */
    start() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.checkAvailability();
        
        // Configurar el intervalo de polling
        this.pollingTimer = setInterval(() => {
            this.checkAvailability();
        }, this.options.interval);
        
        console.log(`Polling iniciado con intervalo de ${this.options.interval/1000} segundos`);
    }

    /**
     * Detiene el polling
     */
    stop() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
        this.isPolling = false;
        console.log('Polling detenido');
    }

    /**
     * Consulta la disponibilidad actual
     */
    checkAvailability() {
        // Obtener los valores actuales de cancha y fecha
        const courtElement = document.querySelector(this.options.courtSelector);
        const dateElement = document.querySelector(this.options.dateSelector);
        
        if (!courtElement || !dateElement) {
            console.warn('No se encontraron los selectores de cancha o fecha');
            return;
        }
        
        const courtId = courtElement.value;
        const date = dateElement.value;
        
        if (!courtId || !date) {
            console.warn('Cancha o fecha no seleccionadas');
            return;
        }

        // Construir la URL con los parámetros
        const url = `${this.options.endpoint}?court_id=${courtId}&date=${date}`;
        
        // Realizar la petición AJAX
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Verificar si los datos han cambiado
                const hasChanged = this.hasDataChanged(data);
                
                // Almacenar los nuevos datos
                this.lastData = data;
                
                // Si hay cambios y existe una función de callback, ejecutarla
                if (hasChanged && typeof this.options.onUpdate === 'function') {
                    this.options.onUpdate(data);
                }
            })
            .catch(error => {
                console.error('Error al obtener disponibilidad:', error);
                if (typeof this.options.onError === 'function') {
                    this.options.onError(error);
                }
            });
    }

    /**
     * Compara los datos actuales con los anteriores para detectar cambios
     */
    hasDataChanged(newData) {
        // Si no hay datos previos, consideramos que hay cambios
        if (!this.lastData) return true;
        
        // Comparar timestamps
        if (this.lastData.timestamp !== newData.timestamp) {
            // Comparar las reservas
            const oldReservations = JSON.stringify(this.lastData.reservations);
            const newReservations = JSON.stringify(newData.reservations);
            
            return oldReservations !== newReservations;
        }
        
        return false;
    }

    /**
     * Fuerza una actualización inmediata
     */
    forceUpdate() {
        this.checkAvailability();
    }
}

// Exportar la clase para uso global
window.AvailabilityPoller = AvailabilityPoller;