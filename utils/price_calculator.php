<?php
class PriceCalculator {
    /**
     * Calcula el precio de un turno según la fecha, hora de inicio y fin
     * 
     * @param string $fecha Fecha en formato Y-m-d
     * @param string $horaInicio Hora de inicio en formato H:i:s
     * @param string $horaFin Hora de fin en formato H:i:s
     * @param PDO $db Conexión a la base de datos
     * @return float Precio del turno
     */
    public static function calculatePrice($fecha, $horaInicio, $horaFin, $db) {
        // Obtener el día de la semana (1 = lunes, 7 = domingo)
        $dayOfWeek = date('N', strtotime($fecha));
        $dayName = '';
        
        // Convertir número a nombre del día
        switch ($dayOfWeek) {
            case 1: $dayName = 'monday'; break;
            case 2: $dayName = 'tuesday'; break;
            case 3: $dayName = 'wednesday'; break;
            case 4: $dayName = 'thursday'; break;
            case 5: $dayName = 'friday'; break;
            case 6: $dayName = 'saturday'; break;
            case 7: $dayName = 'sunday'; break;
        }
        
        // Primero buscamos un precio específico para este día y rango horario
        $query = "SELECT price FROM time_prices 
                  WHERE day_of_week = :day_name 
                  AND :hora_inicio >= start_time 
                  AND :hora_fin <= end_time
                  ORDER BY id DESC LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':day_name', $dayName);
        $stmt->bindParam(':hora_inicio', $horaInicio);
        $stmt->bindParam(':hora_fin', $horaFin);
        $stmt->execute();
        
        $price = $stmt->fetchColumn();
        
        // Si no encontramos un precio específico para este día, buscamos uno general (all)
        if (!$price) {
            $query = "SELECT price FROM time_prices 
                      WHERE day_of_week = 'all' 
                      AND :hora_inicio >= start_time 
                      AND :hora_fin <= end_time
                      ORDER BY id DESC LIMIT 1";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':hora_inicio', $horaInicio);
            $stmt->bindParam(':hora_fin', $horaFin);
            $stmt->execute();
            
            $price = $stmt->fetchColumn();
        }
        
        // Si aún no encontramos un precio, devolvemos null para indicar que no está disponible
        if (!$price) {
            return null; // Indica que no hay precio configurado para este horario
        }
        
        return (float)$price;
    }
}
?>

