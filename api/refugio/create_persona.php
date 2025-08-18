
<?php
$user = Auth::requireAuth('Refugio');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['nombre_preferido', 'edad_rango', 'genero', 'localidad', 'situacion', 'area_asignada'];
    
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field $field is required"]);
            exit;
        }
    }
    
    // Validate enums
    $valid_edad = ['Niño/a', 'Adolescente', 'Adulto', 'Adulto mayor'];
    $valid_genero = ['F', 'M', 'Otro', 'Prefiere no decir'];
    $valid_situacion = ['Vivienda perdida', 'Temporalmente desplazado', 'Evacuación preventiva'];
    
    if (!in_array($input['edad_rango'], $valid_edad)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid edad_rango']);
        exit;
    }
    
    if (!in_array($input['genero'], $valid_genero)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid genero']);
        exit;
    }
    
    if (!in_array($input['situacion'], $valid_situacion)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid situacion']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Call stored procedure to create complete person
    $stmt = $db->prepare("CALL sp_crear_persona_completa(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $input['nombre_preferido'],
        $input['edad_rango'],
        $input['genero'],
        $input['idioma_principal'] ?? null,
        $user['refugio_id'],
        date('Y-m-d'), // fecha_ingreso
        date('H:i:s'), // hora_ingreso
        $input['area_asignada'],
        $input['observaciones'] ?? null,
        // Salud
        $input['condicion_medica'] ?? null,
        $input['medicamentos'] ?? null,
        $input['alergias'] ?? null,
        $input['asistencia_especial'] ?? null,
        isset($input['requiere_atencion_urgente']) ? (bool)$input['requiere_atencion_urgente'] : false,
        // Procedencia
        $input['localidad'],
        $input['municipio'] ?? null,
        $input['departamento'] ?? null,
        $input['situacion'],
        isset($input['tiene_mascotas']) ? (bool)$input['tiene_mascotas'] : false,
        $input['mascotas_detalle'] ?? null,
        // Usuario
        $user['usuario_id'],
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    $result = $stmt->fetch();
    
    if ($result && isset($result['persona_id'])) {
        echo json_encode([
            'success' => true,
            'persona_id' => $result['persona_id'],
            'message' => 'Persona registered successfully'
        ]);
    } else {
        throw new Exception('Failed to create persona');
    }
    
} catch (Exception $e) {
    error_log("Create persona error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
