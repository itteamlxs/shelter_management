
<?php
$user = Auth::requireAuth('Refugio');

try {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }
    
    $file = $_FILES['file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File upload error']);
        exit;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        http_response_code(400);
        echo json_encode(['error' => 'File too large (max 5MB)']);
        exit;
    }
    
    $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types) && !str_ends_with($file['name'], '.csv')) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only CSV files allowed']);
        exit;
    }
    
    // Read and validate CSV
    $csv_data = [];
    $handle = fopen($file['tmp_name'], 'r');
    
    if (!$handle) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not read file']);
        exit;
    }
    
    // Expected CSV headers in exact order
    $expected_headers = [
        'nombre_preferido', 'edad_rango', 'genero', 'idioma_principal',
        'grupo_jefe', 'grupo_id_externo', 'relacion_con_jefe',
        'condicion_medica', 'medicamentos', 'alergias', 'asistencia_especial',
        'localidad', 'situacion', 'tiene_mascotas', 'mascotas_detalle',
        'fecha_ingreso', 'hora_ingreso', 'area_asignada', 'estatus'
    ];
    
    $headers = fgetcsv($handle);
    
    if (!$headers || $headers !== $expected_headers) {
        fclose($handle);
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid CSV headers',
            'expected' => $expected_headers,
            'received' => $headers
        ]);
        exit;
    }
    
    $row_number = 1;
    $errors = [];
    $valid_rows = 0;
    
    while (($row = fgetcsv($handle)) !== false) {
        $row_number++;
        
        if (count($row) !== count($expected_headers)) {
            $errors[] = "Row $row_number: Column count mismatch";
            continue;
        }
        
        $row_data = array_combine($expected_headers, $row);
        
        // Validate required fields
        if (empty($row_data['nombre_preferido']) || empty($row_data['edad_rango']) || 
            empty($row_data['genero']) || empty($row_data['localidad']) || 
            empty($row_data['situacion']) || empty($row_data['area_asignada'])) {
            $errors[] = "Row $row_number: Missing required fields";
            continue;
        }
        
        // Validate enums
        $valid_edad = ['Niño/a', 'Adolescente', 'Adulto', 'Adulto mayor'];
        $valid_genero = ['F', 'M', 'Otro', 'Prefiere no decir'];
        $valid_situacion = ['Vivienda perdida', 'Temporalmente desplazado', 'Evacuación preventiva'];
        $valid_estatus = ['Alojado', 'Dado de alta', 'Trasladado a otro refugio'];
        
        if (!in_array($row_data['edad_rango'], $valid_edad)) {
            $errors[] = "Row $row_number: Invalid edad_rango";
            continue;
        }
        
        if (!in_array($row_data['genero'], $valid_genero)) {
            $errors[] = "Row $row_number: Invalid genero";
            continue;
        }
        
        if (!in_array($row_data['situacion'], $valid_situacion)) {
            $errors[] = "Row $row_number: Invalid situacion";
            continue;
        }
        
        if (!empty($row_data['estatus']) && !in_array($row_data['estatus'], $valid_estatus)) {
            $errors[] = "Row $row_number: Invalid estatus";
            continue;
        }
        
        // Validate dates
        if (!empty($row_data['fecha_ingreso']) && !strtotime($row_data['fecha_ingreso'])) {
            $errors[] = "Row $row_number: Invalid fecha_ingreso format";
            continue;
        }
        
        if (!empty($row_data['hora_ingreso']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $row_data['hora_ingreso'])) {
            $errors[] = "Row $row_number: Invalid hora_ingreso format";
            continue;
        }
        
        $csv_data[] = $row_data;
        $valid_rows++;
    }
    
    fclose($handle);
    
    if (count($errors) > 0 && $valid_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'CSV validation failed',
            'errors' => $errors,
            'valid_rows' => $valid_rows
        ]);
        exit;
    }
    
    // Save to BulkUploads table
    $db = Database::getInstance();
    
    $upload_stmt = $db->prepare("
        INSERT INTO BulkUploads (refugio_id, usuario_id, filename, original_filename, file_size, total_filas, estado, mensaje)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $filename = uniqid('csv_') . '.csv';
    $estado = count($errors) > 0 ? 'Fallido' : 'Validado';
    $mensaje = count($errors) > 0 ? 
        'Validation errors found: ' . implode('; ', array_slice($errors, 0, 5)) :
        'CSV validated successfully';
    
    $upload_stmt->execute([
        $user['refugio_id'],
        $user['usuario_id'],
        $filename,
        $file['name'],
        $file['size'],
        count($csv_data),
        $estado,
        $mensaje
    ]);
    
    $upload_id = $db->lastInsertId();
    
    // If validation passed, process the CSV data
    if ($estado === 'Validado') {
        $processed = 0;
        $failed = 0;
        
        foreach ($csv_data as $row) {
            try {
                $stmt = $db->prepare("CALL sp_crear_persona_completa(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $row['nombre_preferido'],
                    $row['edad_rango'],
                    $row['genero'],
                    $row['idioma_principal'] ?: null,
                    $user['refugio_id'],
                    $row['fecha_ingreso'] ?: date('Y-m-d'),
                    $row['hora_ingreso'] ?: date('H:i:s'),
                    $row['area_asignada'],
                    null, // observaciones
                    // Salud
                    $row['condicion_medica'] ?: null,
                    $row['medicamentos'] ?: null,
                    $row['alergias'] ?: null,
                    $row['asistencia_especial'] ?: null,
                    false, // requiere_atencion_urgente
                    // Procedencia
                    $row['localidad'],
                    null, // municipio
                    null, // departamento
                    $row['situacion'],
                    strtolower($row['tiene_mascotas']) === 'true' || $row['tiene_mascotas'] === '1',
                    $row['mascotas_detalle'] ?: null,
                    // Usuario
                    $user['usuario_id'],
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                $processed++;
                
            } catch (Exception $e) {
                error_log("CSV row processing error: " . $e->getMessage());
                $failed++;
            }
        }
        
        // Update upload status
        $update_stmt = $db->prepare("
            UPDATE BulkUploads 
            SET estado = 'Procesado', 
                filas_procesadas = ?, 
                filas_exitosas = ?, 
                filas_fallidas = ?,
                mensaje = ?,
                procesado_en = NOW()
            WHERE upload_id = ?
        ");
        
        $final_message = "Processed: $processed, Failed: $failed";
        $update_stmt->execute([$processed + $failed, $processed, $failed, $final_message, $upload_id]);
    }
    
    echo json_encode([
        'success' => true,
        'upload_id' => $upload_id,
        'validation_errors' => $errors,
        'valid_rows' => $valid_rows,
        'estado' => $estado,
        'message' => $mensaje
    ]);
    
} catch (Exception $e) {
    error_log("CSV upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error processing CSV upload'
    ]);
}
