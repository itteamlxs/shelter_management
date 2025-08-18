
<?php
try {
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(50, max(1, (int)($_GET['per_page'] ?? 12)));
    $offset = ($page - 1) * $per_page;
    
    $db = Database::getInstance();
    
    // Build search conditions
    $where_conditions = ["1=1"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(nombre_refugio LIKE ? OR ubicacion LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM vw_public_refugios WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // Get paginated results
    $sql = "SELECT * FROM vw_public_refugios WHERE $where_clause ORDER BY nombre_refugio LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $refugios = $stmt->fetchAll();
    
    // Get all refugios for filter (without pagination)
    $all_stmt = $db->prepare("SELECT refugio_id, nombre_refugio FROM vw_public_refugios ORDER BY nombre_refugio");
    $all_stmt->execute();
    $all_refugios = $all_stmt->fetchAll();
    
    // Calculate pagination meta
    $total_pages = ceil($total / $per_page);
    
    echo json_encode([
        'success' => true,
        'data' => $refugios,
        'all_refugios' => $all_refugios,
        'meta' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => $total_pages,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Refugios error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving refugios'
    ]);
}
