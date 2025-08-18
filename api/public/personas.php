
<?php
try {
    $search = $_GET['search'] ?? '';
    $refugio = $_GET['refugio'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
    $offset = ($page - 1) * $per_page;
    
    $db = Database::getInstance();
    
    // Build search conditions
    $where_conditions = ["1=1"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(nombre LIKE ? OR refugio LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($refugio)) {
        // Get refugio_id from name or id
        if (is_numeric($refugio)) {
            $where_conditions[] = "refugio_id = ?";
            $params[] = $refugio;
        } else {
            $where_conditions[] = "refugio LIKE ?";
            $params[] = "%$refugio%";
        }
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM vw_public_personas WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // Get paginated results
    $sql = "SELECT * FROM vw_public_personas WHERE $where_clause ORDER BY fecha_ingreso DESC, hora_ingreso DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $personas = $stmt->fetchAll();
    
    // Calculate pagination meta
    $total_pages = ceil($total / $per_page);
    
    echo json_encode([
        'success' => true,
        'data' => $personas,
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
    error_log("Personas search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error searching personas'
    ]);
}
