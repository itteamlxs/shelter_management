
<?php
/**
 * Refugio Model
 * Handles all shelter-related database operations
 * Uses only views and stored procedures as per requirements
 */

require_once __DIR__ . '/../config/database.php';

class RefugioModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all public refugios from vw_public_refugios view
     * @return array
     */
    public function getPublicRefugios() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM vw_public_refugios");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getPublicRefugios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get count of available shelters
     * @return int
     */
    public function getAvailableSheltersCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM vw_public_refugios WHERE estado = 'Disponible'");
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error in getAvailableSheltersCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get public statistics from vw_public_estadisticas view
     * @return array
     */
    public function getPublicStatistics() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM vw_public_estadisticas");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getPublicStatistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search public refugios with pagination
     * @param string|null $search_term
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchPublicRefugios($search_term = null, $limit = 20, $offset = 0) {
        try {
            $sql = "
                SELECT *, COUNT(*) OVER() as total_registros 
                FROM vw_public_refugios 
            ";
            
            $params = [];
            if ($search_term) {
                $sql .= " WHERE nombre_refugio LIKE ? OR ubicacion LIKE ?";
                $search_pattern = '%' . $search_term . '%';
                $params = [$search_pattern, $search_pattern];
            }
            
            $sql .= " ORDER BY nombre_refugio LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
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
            error_log("Error in searchPublicRefugios: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0
            ];
        }
    }
    
    /**
     * Get refugio by ID from public view
     * @param int $refugio_id
     * @return array|null
     */
    public function getPublicRefugioById($refugio_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM vw_public_refugios WHERE refugio_id = ?");
            $stmt->execute([$refugio_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getPublicRefugioById: " . $e->getMessage());
            return null;
        }
    }
}
