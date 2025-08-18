<?php
/**
 * Persona Model
 * Handles all person-related database operations
 * Uses only views and stored procedures as per requirements
 */

require_once __DIR__ . '/../config/database.php';

class PersonaModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Search public personas with pagination
     * @param string|null $search_term
     * @param int|null $refugio_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchPublicPersonas($search = null, $refugioId = null, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("CALL sp_buscar_personas_publico(?, ?, ?, ?)");
            $stmt->execute([$search, $refugioId, $limit, $offset]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM vw_public_personas 
                WHERE (? IS NULL OR ? = '' OR nombre LIKE CONCAT('%', ?, '%') OR refugio LIKE CONCAT('%', ?, '%'))
                AND (? IS NULL OR refugio_id = ?)
            ");
            $stmt->execute([$search, $search, $search, $search, $refugioId, $refugioId]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            return [
                'data' => $data,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
        } catch (PDOException $e) {
            error_log("Search personas error: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'limit' => $limit,
                'offset' => $offset
            ];
        }
    }

    /**
     * Get personas associated with a specific refugio, with optional search and pagination.
     *
     * @param int $refugioId The ID of the refugio.
     * @param string|null $search Optional search term to filter by preferred name.
     * @param int $limit Maximum number of results to return.
     * @param int $offset Number of results to skip.
     * @return array An array containing persona data, total count, pagination info, and success status.
     */
    public function getPersonasByRefugio($refugioId, $search = null, $limit = 20, $offset = 0) {
        try {
            $whereClause = '';
            $params = [$refugioId];

            if ($search) {
                $whereClause = ' AND nombre_preferido LIKE ?';
                $params[] = '%' . $search . '%';
            }

            $stmt = $this->db->prepare("
                SELECT * FROM vw_refugio_personas 
                WHERE refugio_id = ? $whereClause
                ORDER BY fecha_ingreso DESC, hora_ingreso DESC
                LIMIT ? OFFSET ?
            ");

            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);

            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $this->getPersonasCountByRefugio($refugioId, $search),
                'limit' => $limit,
                'offset' => $offset,
                'success' => true
            ];
        } catch (PDOException $e) {
            error_log("Get personas by refugio error: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'limit' => $limit,
                'offset' => $offset,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Private helper method to get the count of personas for a specific refugio.
     *
     * @param int $refugioId The ID of the refugio.
     * @param string|null $search Optional search term.
     * @return int The count of personas.
     */
    private function getPersonasCountByRefugio($refugioId, $search = null) {
        try {
            $whereClause = '';
            $params = [$refugioId];

            if ($search) {
                $whereClause = ' AND nombre_preferido LIKE ?';
                $params[] = '%' . $search . '%';
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM vw_refugio_personas 
                WHERE refugio_id = ? $whereClause
            ");
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            error_log("Get personas count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Creates a new persona record and associated health and origin information.
     *
     * @param array $data Associative array containing persona details.
     * @return int|null The ID of the newly created persona, or null on failure.
     * @throws Exception If a database error occurs during the transaction.
     */
    public function createPersona($data) {
        try {
            $this->db->beginTransaction();

            // Insert persona
            $stmt = $this->db->prepare("
                INSERT INTO Personas (nombre_preferido, edad_rango, genero, idioma_principal)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['nombre_preferido'],
                $data['edad_rango'],
                $data['genero'],
                $data['idioma_principal'] ?? null
            ]);

            $personaId = $this->db->lastInsertId();

            // Insert procedencia if provided
            if (!empty($data['localidad'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO Procedencia (persona_id, localidad, situacion, tiene_mascotas, mascotas_detalle)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $personaId,
                    $data['localidad'],
                    $data['situacion'] ?? 'Temporalmente desplazado',
                    isset($data['tiene_mascotas']) ? (bool)$data['tiene_mascotas'] : false,
                    $data['mascotas_detalle'] ?? null
                ]);
            }

            // Insert salud if provided
            if (!empty($data['condicion_medica']) || !empty($data['medicamentos']) || !empty($data['alergias'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO Salud (persona_id, condicion_medica, medicamentos, alergias, asistencia_especial)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $personaId,
                    $data['condicion_medica'] ?? null,
                    $data['medicamentos'] ?? null,
                    $data['alergias'] ?? null,
                    $data['asistencia_especial'] ?? null
                ]);
            }

            $this->db->commit();
            return $personaId;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Create persona error: " . $e->getMessage());
            throw new Exception("Error al crear persona: " . $e->getMessage());
        }
    }

    /**
     * Registers a person's entry into a refugio.
     *
     * @param int $personaId The ID of the person.
     * @param int $refugioId The ID of the refugio.
     * @param string $fechaIngreso The date of entry.
     * @param string $horaIngreso The time of entry.
     * @param string $areaAsignada The assigned area.
     * @param string $estatus The status of the person (default 'Alojado').
     * @param string|null $observaciones Additional observations.
     * @param int|null $usuarioId The ID of the user performing the action.
     * @return bool True on success, false on failure.
     * @throws Exception If a database error occurs.
     */
    public function registerInRefugio($personaId, $refugioId, $fechaIngreso, $horaIngreso, $areaAsignada, $estatus = 'Alojado', $observaciones = null, $usuarioId = null) {
        try {
            $stmt = $this->db->prepare("CALL sp_registrar_ingreso(?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $personaId,
                $refugioId,
                $fechaIngreso,
                $horaIngreso,
                $areaAsignada,
                $estatus,
                $observaciones,
                $usuarioId
            ]);

            return $result;
        } catch (PDOException $e) {
            error_log("Register in refugio error: " . $e->getMessage());
            throw new Exception("Error al registrar en refugio: " . $e->getMessage());
        }
    }
}
?>