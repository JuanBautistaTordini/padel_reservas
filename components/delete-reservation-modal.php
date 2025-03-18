<!-- Modal para cancelar reserva-->
<div id="deleteReservationModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded-lg shadow-lg overflow-y-auto transform transition-all duration-300 scale-95 opacity-0">
        <div class="modal-content p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <p class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-trash-alt text-red-500 mr-2"></i> Confirmar cancelación
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
                        <span>¿Está seguro que desea cancelar la siguiente reserva de turno? Esta acción no se puede deshacer.</span>
                    </p>
                </div>
                
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="font-medium text-gray-800" id="reservation_to_delete_info"></p>
                </div>
            </div>
            
            <form action="api/admin/reservations/update.php" method="post">
                <input type="hidden" id="reservation_id" name="reservation_id">
                <input type="hidden" name="status" value="cancelada">
                
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