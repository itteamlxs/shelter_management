
<?php
$user = Auth::requireAuth('Auditor');

try {
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(100, max(1, (int)($_GET['per_page'] ?? 25)));
    $offset = ($page - 1) * $per_page;
    
    $db = Database::getInstance();
    
    // Build search conditions
    $where_conditions = ["1=1"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(username LIKE ? OR accion LIKE ? OR objeto LIKE ? OR resumen LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM vw_auditor_activity WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // Get paginated results
    $sql = "SELECT * FROM vw_auditor_activity WHERE $where_clause ORDER BY creado_en DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Calculate pagination meta
    $total_pages = ceil($total / $per_page);
    
    echo json_encode([
        'success' => true,
        'data' => $logs,
        'meta' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => $total_pages
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Audit logs error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving audit logs'
    ]);
}
