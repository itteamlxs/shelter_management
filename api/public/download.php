
<?php
try {
    $refugio_id = (int)$_GET['id'] ?? 0;
    $format = $_GET['format'] ?? 'csv';
    
    if (!$refugio_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid refugio ID']);
        exit;
    }
    
    if (!in_array($format, ['csv', 'pdf'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid format. Use csv or pdf']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Get refugio info
    $refugio_stmt = $db->prepare("SELECT nombre_refugio FROM vw_public_refugios WHERE refugio_id = ?");
    $refugio_stmt->execute([$refugio_id]);
    $refugio = $refugio_stmt->fetch();
    
    if (!$refugio) {
        http_response_code(404);
        echo json_encode(['error' => 'Refugio not found']);
        exit;
    }
    
    // Get personas data
    $stmt = $db->prepare("SELECT * FROM vw_public_personas WHERE refugio_id = ? ORDER BY fecha_ingreso DESC");
    $stmt->execute([$refugio_id]);
    $personas = $stmt->fetchAll();
    
    $filename = sanitize_filename($refugio['nombre_refugio']) . '_' . date('Y-m-d');
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'Nombre',
            'Refugio',
            'Ubicación',
            'Fecha Ingreso',
            'Hora Ingreso',
            'Estatus'
        ]);
        
        // CSV Data
        foreach ($personas as $persona) {
            fputcsv($output, [
                $persona['nombre'],
                $persona['refugio'],
                $persona['direccion'],
                $persona['fecha_ingreso'],
                $persona['hora_ingreso'],
                $persona['estatus']
            ]);
        }
        
        fclose($output);
        
    } else if ($format === 'pdf') {
        // Simple PDF generation (basic implementation)
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        
        // For now, return a simple text-based PDF
        // In production, use a proper PDF library like TCPDF or DomPDF
        echo "%PDF-1.4\n";
        echo "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n";
        echo "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n";
        echo "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n";
        echo "/Contents 4 0 R\n>>\nendobj\n";
        echo "4 0 obj\n<<\n/Length " . strlen("Simple PDF content") . "\n>>\nstream\n";
        echo "BT\n/F1 12 Tf\n72 720 Td\n(Refugio: " . $refugio['nombre_refugio'] . ") Tj\nET\n";
        echo "endstream\nendobj\n";
        echo "xref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n";
        echo "0000000058 00000 n \n0000000115 00000 n \n0000000230 00000 n \n";
        echo "trailer\n<<\n/Size 5\n/Root 1 0 R\n>>\nstartxref\n300\n%%EOF";
    }
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error generating download']);
}

function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
}
