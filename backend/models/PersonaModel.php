
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
    public function searchPublicPersonas($search_term = null, $refugio_id = null, $limit = 20, $offset = 0) {
        try {
            // Call stored procedure for search
            $stmt = $this->db->prepare("CALL sp_buscar_personas_publico(?, ?, ?, ?)");
            $stmt->execute([$search_term, $refugio_id, $limit, $offset]);
            
            $data = $stmt->fetchAll();
            $total = 0;
            
            if (!empty($data)) {
                $total = (int)$data[0]['total_registros'];
                // Remove the total_registros field from each record
                foreach ($data as &$row) {
                    unset($row['total_registros']);
                }
            }
            
            return [
                'data' => $data,
                'total' => $total
            ];
            
        } catch (PDOException $e) {
            error_log("Error in searchPublicPersonas: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0
            ];
        }
    }
    
    /**
     * Get public personas from view
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPublicPersonas($limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT *, COUNT(*) OVER() as total_registros 
                FROM vw_public_personas 
                ORDER BY fecha_ingreso DESC, hora_ingreso DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            
            $data = $stmt->fetchAll();
            $total = 0;
            
            if (!empty($data)) {
                $total = (int)$data[0]['total_registros'];
                // Remove the total_registros field from each record
                foreach ($data as &$row) {
                    unset($row['total_registros']);
                }
            }
            
            return [
                'data' => $data,
                'total' => $total
            ];
            
        } catch (PDOException $e) {
            error_log("Error in getPublicPersonas: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0
            ];
        }
    }
    
    /**
     * Get total count of public personas
     * @return int
     */
    public function getPublicPersonasCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM vw_public_personas");
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error in getPublicPersonasCount: " . $e->getMessage());
            return 0;
        }
    }
}
?>
