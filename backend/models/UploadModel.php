
<?php
/**
 * Upload Model
 * Sistema de Refugios - CSV Upload Processing
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/UserModel.php';

class UploadModel {
    private $db;
    private $userModel;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->userModel = new UserModel();
    }
    
    /**
     * Process CSV upload
     */
    public function processCSVUpload($filePath, $fileName, $userId, $refugioId) {
        try {
            // Create upload record
            $uploadId = $this->createUploadRecord($fileName, $userId, $refugioId);
            
            // Validate CSV structure
            $validation = $this->validateCSVStructure($filePath);
            if (!$validation['valid']) {
                $this->updateUploadStatus($uploadId, 'ERROR', $validation['error']);
                return [
                    'success' => false,
                    'error' => 'Estructura de CSV inválida',
                    'details' => $validation['error']
                ];
            }
            
            // Process CSV data
            $result = $this->processCSVData($filePath, $uploadId, $refugioId, $userId);
            
            if ($result['success']) {
                $this->updateUploadStatus($uploadId, 'COMPLETED', null, $result['stats']);
                
                // Log successful upload
                $this->userModel->logActivity(
                    $userId,
                    'CSV_UPLOAD',
                    'BulkUploads',
                    $uploadId,
                    "CSV upload completed: {$result['stats']['processed']} records processed"
                );
                
                return [
                    'success' => true,
                    'data' => [
                        'upload_id' => $uploadId,
                        'stats' => $result['stats']
                    ]
                ];
            } else {
                $this->updateUploadStatus($uploadId, 'ERROR', $result['error']);
                return [
                    'success' => false,
                    'error' => $result['error'],
                    'details' => $result['details'] ?? null
                ];
            }
            
        } catch (Exception $e) {
            error_log("CSV Upload Error: " . $e->getMessage());
            if (isset($uploadId)) {
                $this->updateUploadStatus($uploadId, 'ERROR', $e->getMessage());
            }
            return [
                'success' => false,
                'error' => 'Error procesando archivo CSV'
            ];
        }
    }
    
    /**
     * Create upload record
     */
    private function createUploadRecord($fileName, $userId, $refugioId) {
        $stmt = $this->db->prepare("
            INSERT INTO BulkUploads (archivo_nombre, usuario_id, refugio_id, estado, fecha_subida)
            VALUES (?, ?, ?, 'PROCESSING', NOW())
        ");
        $stmt->execute([$fileName, $userId, $refugioId]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Update upload status
     */
    private function updateUploadStatus($uploadId, $status, $error = null, $stats = null) {
        $stmt = $this->db->prepare("
            UPDATE BulkUploads 
            SET estado = ?, error_mensaje = ?, registros_procesados = ?, 
                registros_exitosos = ?, registros_error = ?, fecha_procesado = NOW()
            WHERE id = ?
        ");
        
        $processed = $stats['processed'] ?? null;
        $successful = $stats['successful'] ?? null;
        $errors = $stats['errors'] ?? null;
        
        $stmt->execute([$status, $error, $processed, $successful, $errors, $uploadId]);
    }
    
    /**
     * Validate CSV structure
     */
    private function validateCSVStructure($filePath) {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['valid' => false, 'error' => 'No se pudo abrir el archivo'];
        }
        
        // Read header
        $header = fgetcsv($handle, 1000, ',');
        fclose($handle);
        
        if (!$header) {
            return ['valid' => false, 'error' => 'Archivo CSV vacío'];
        }
        
        // Required columns
        $requiredColumns = [
            'nombres',
            'apellidos',
            'documento_identidad',
            'tipo_documento',
            'fecha_nacimiento',
            'telefono'
        ];
        
        $headerLower = array_map('strtolower', array_map('trim', $header));
        
        foreach ($requiredColumns as $required) {
            if (!in_array(strtolower($required), $headerLower)) {
                return [
                    'valid' => false,
                    'error' => "Columna requerida faltante: $required"
                ];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Process CSV data
     */
    private function processCSVData($filePath, $uploadId, $refugioId, $userId) {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'No se pudo abrir el archivo'];
        }
        
        // Read header and create column map
        $header = fgetcsv($handle, 1000, ',');
        $columnMap = [];
        foreach ($header as $index => $column) {
            $columnMap[strtolower(trim($column))] = $index;
        }
        
        $stats = [
            'processed' => 0,
            'successful' => 0,
            'errors' => 0,
            'error_details' => []
        ];
        
        $this->db->beginTransaction();
        
        try {
            $row = 1; // Header is row 1
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $row++;
                $stats['processed']++;
                
                try {
                    // Extract data using column map
                    $personData = [
                        'nombres' => trim($data[$columnMap['nombres']] ?? ''),
                        'apellidos' => trim($data[$columnMap['apellidos']] ?? ''),
                        'documento_identidad' => trim($data[$columnMap['documento_identidad']] ?? ''),
                        'tipo_documento' => trim($data[$columnMap['tipo_documento']] ?? ''),
                        'fecha_nacimiento' => trim($data[$columnMap['fecha_nacimiento']] ?? ''),
                        'telefono' => trim($data[$columnMap['telefono']] ?? ''),
                        'necesidades_especiales' => trim($data[$columnMap['necesidades_especiales']] ?? ''),
                        'notas' => trim($data[$columnMap['notas']] ?? '')
                    ];
                    
                    // Validate required fields
                    $validation = $this->validatePersonData($personData, $row);
                    if (!$validation['valid']) {
                        $stats['errors']++;
                        $stats['error_details'][] = "Fila $row: " . $validation['error'];
                        continue;
                    }
                    
                    // Check if person already exists
                    $existingPersonId = $this->findExistingPerson($personData['documento_identidad']);
                    
                    if ($existingPersonId) {
                        // Update existing person
                        $personId = $this->updateExistingPerson($existingPersonId, $personData);
                    } else {
                        // Create new person
                        $personId = $this->createNewPerson($personData);
                    }
                    
                    // Register in shelter
                    $this->registerPersonInShelter($personId, $refugioId, $userId);
                    
                    $stats['successful']++;
                    
                } catch (Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = "Fila $row: Error procesando datos - " . $e->getMessage();
                    error_log("CSV Row Error (row $row): " . $e->getMessage());
                }
            }
            
            $this->db->commit();
            
            fclose($handle);
            
            return [
                'success' => true,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            fclose($handle);
            
            return [
                'success' => false,
                'error' => 'Error procesando datos CSV',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate person data
     */
    private function validatePersonData($data, $row) {
        if (empty($data['nombres'])) {
            return ['valid' => false, 'error' => 'Nombres es requerido'];
        }
        
        if (empty($data['apellidos'])) {
            return ['valid' => false, 'error' => 'Apellidos es requerido'];
        }
        
        if (empty($data['documento_identidad'])) {
            return ['valid' => false, 'error' => 'Documento de identidad es requerido'];
        }
        
        if (empty($data['tipo_documento'])) {
            return ['valid' => false, 'error' => 'Tipo de documento es requerido'];
        }
        
        // Validate document type
        $validDocTypes = ['DNI', 'Pasaporte', 'Carnet_Extranjeria', 'Otro'];
        if (!in_array($data['tipo_documento'], $validDocTypes)) {
            return ['valid' => false, 'error' => 'Tipo de documento inválido'];
        }
        
        // Validate date format
        if (!empty($data['fecha_nacimiento'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
            if (!$date || $date->format('Y-m-d') !== $data['fecha_nacimiento']) {
                return ['valid' => false, 'error' => 'Formato de fecha inválido (use YYYY-MM-DD)'];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Find existing person by document
     */
    private function findExistingPerson($documento) {
        $stmt = $this->db->prepare("SELECT id FROM Personas WHERE documento_identidad = ?");
        $stmt->execute([$documento]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }
    
    /**
     * Create new person
     */
    private function createNewPerson($data) {
        $stmt = $this->db->prepare("
            INSERT INTO Personas (
                nombres, apellidos, documento_identidad, tipo_documento, 
                fecha_nacimiento, telefono, necesidades_especiales, notas
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['nombres'],
            $data['apellidos'],
            $data['documento_identidad'],
            $data['tipo_documento'],
            $data['fecha_nacimiento'] ?: null,
            $data['telefono'] ?: null,
            $data['necesidades_especiales'] ?: null,
            $data['notas'] ?: null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update existing person
     */
    private function updateExistingPerson($personId, $data) {
        $stmt = $this->db->prepare("
            UPDATE Personas 
            SET nombres = ?, apellidos = ?, tipo_documento = ?, 
                fecha_nacimiento = ?, telefono = ?, necesidades_especiales = ?, 
                notas = ?, fecha_actualizacion = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['nombres'],
            $data['apellidos'],
            $data['tipo_documento'],
            $data['fecha_nacimiento'] ?: null,
            $data['telefono'] ?: null,
            $data['necesidades_especiales'] ?: null,
            $data['notas'] ?: null,
            $personId
        ]);
        
        return $personId;
    }
    
    /**
     * Register person in shelter
     */
    private function registerPersonInShelter($personId, $refugioId, $userId) {
        // Check if already registered in this shelter
        $stmt = $this->db->prepare("
            SELECT id FROM RegistroRefugio 
            WHERE persona_id = ? AND refugio_id = ? AND fecha_salida IS NULL
        ");
        $stmt->execute([$personId, $refugioId]);
        
        if (!$stmt->fetch()) {
            // Register in shelter
            $stmt = $this->db->prepare("
                INSERT INTO RegistroRefugio (persona_id, refugio_id, fecha_ingreso, usuario_registro)
                VALUES (?, ?, NOW(), ?)
            ");
            $stmt->execute([$personId, $refugioId, $userId]);
        }
    }
    
    /**
     * Get upload history
     */
    public function getUploadHistory($userId = null, $refugioId = null, $limit = 50, $offset = 0) {
        $where = [];
        $params = [];
        
        if ($userId) {
            $where[] = "bu.usuario_id = ?";
            $params[] = $userId;
        }
        
        if ($refugioId) {
            $where[] = "bu.refugio_id = ?";
            $params[] = $refugioId;
        }
        
        $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
        
        $stmt = $this->db->prepare("
            SELECT bu.*, u.username, r.nombre as refugio_nombre
            FROM BulkUploads bu
            LEFT JOIN Usuarios u ON bu.usuario_id = u.id
            LEFT JOIN Refugios r ON bu.refugio_id = r.id
            $whereClause
            ORDER BY bu.fecha_subida DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get CSV template
     */
    public function generateCSVTemplate() {
        $headers = [
            'nombres',
            'apellidos',
            'documento_identidad',
            'tipo_documento',
            'fecha_nacimiento',
            'telefono',
            'necesidades_especiales',
            'notas'
        ];
        
        $sampleData = [
            'Juan Carlos',
            'Pérez García',
            '12345678',
            'DNI',
            '1985-03-15',
            '+51987654321',
            'Diabetes',
            'Requiere medicación diaria'
        ];
        
        return [
            'headers' => $headers,
            'sample' => $sampleData
        ];
    }
}
?>
