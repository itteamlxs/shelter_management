
<?php

require_once __DIR__ . '/../config/database.php';

class UserModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function authenticate($username, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT usuario_id, username, password_hash, rol, refugio_id, nombre_mostrado, activo
                FROM Usuarios 
                WHERE username = ? AND activo = TRUE
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->updateLastLogin($user['usuario_id']);
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE Usuarios SET ultimo_login = NOW() WHERE usuario_id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT usuario_id, username, rol, refugio_id, nombre_mostrado, activo, ultimo_login
                FROM Usuarios 
                WHERE usuario_id = ? AND activo = TRUE
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    public function changePassword($userId, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE Usuarios SET password_hash = ? WHERE usuario_id = ?");
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }
    
    public function createUser($username, $password, $rol, $refugioId, $nombreMostrado) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                INSERT INTO Usuarios (username, password_hash, rol, refugio_id, nombre_mostrado)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$username, $hashedPassword, $rol, $refugioId, $nombreMostrado]);
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllUsers() {
        try {
            $stmt = $this->db->query("
                SELECT u.usuario_id, u.username, u.rol, u.refugio_id, u.nombre_mostrado, 
                       u.activo, u.ultimo_login, r.nombre_refugio
                FROM Usuarios u
                LEFT JOIN Refugios r ON u.refugio_id = r.refugio_id
                ORDER BY u.creado_en DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    public function logActivity($userId, $action, $object, $objectId = null, $summary = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO AuditLog (usuario_id, accion, objeto, objeto_id, resumen, ip_origen, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            return $stmt->execute([
                $userId, $action, $object, $objectId, $summary, $ipAddress, $userAgent
            ]);
        } catch (PDOException $e) {
            error_log("Log activity error: " . $e->getMessage());
            return false;
        }
    }
}
