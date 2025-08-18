
<?php
$user = Auth::requireAuth('Refugio');

try {
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
    $offset = ($page - 1) * $per_page;
    
    $db = Database::getInstance();
    
    // Build search conditions
    $where_conditions = ["refugio_id = ?"];
    $params = [$user['refugio_id']];
    
    if (!empty($search)) {
        $where_conditions[] = "nombre_preferido LIKE ?";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM vw_refugio_personas WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // Get paginated results
    $sql = "SELECT * FROM vw_refugio_personas WHERE $where_clause ORDER BY fecha_ingreso DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $personas = $stmt->fetchAll();
    
    // Get refugio statistics
    $stats_stmt = $db->prepare("CALL sp_estadisticas_refugio(?)");
    $stats_stmt->execute([$user['refugio_id']]);
    $stats = $stats_stmt->fetch();
    
    // Calculate pagination meta
    $total_pages = ceil($total / $per_page);
    
    echo json_encode([
        'success' => true,
        'data' => $personas,
        'stats' => $stats,
        'meta' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => $total_pages
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Refugio personas error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving personas'
    ]);
}
