
<?php
try {
    $db = Database::getInstance();
    
    $stmt = $db->prepare("SELECT * FROM vw_public_estadisticas LIMIT 1");
    $stmt->execute();
    $stats = $stmt->fetch();
    
    if (!$stats) {
        $stats = [
            'total_personas' => 0,
            'total_alojados' => 0,
            'total_dados_alta' => 0,
            'total_trasladados' => 0,
            'total_refugios' => 0,
            'promedio_ocupacion' => 0,
            'capacidad_total_sistema' => 0,
            'ocupacion_total_sistema' => 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Statistics error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving statistics'
    ]);
}
