
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
}
