
<?php
header('Content-Type: application/json');

try {
    // Get current user for audit logging
    $user = Auth::getCurrentUser();
    
    if ($user) {
        $db = Database::getInstance();
        
        // Log the logout action
        $audit_stmt = $db->prepare("
            INSERT INTO AuditLog (usuario_id, rol, accion, objeto, resumen, ip_origen, user_agent)
            VALUES (?, ?, 'LOGOUT', 'Auth', ?, ?, ?)
        ");
        $audit_stmt->execute([
            $user['usuario_id'],
            $user['rol'],
            "User logged out: " . $user['username'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
