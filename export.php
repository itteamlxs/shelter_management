<?php
require_once 'config/database.php';

$refugio_id = $_GET['refugio'] ?? null;
$format = $_GET['format'] ?? 'csv';

if (!$refugio_id) {
    http_response_code(400);
    die('ID de refugio requerido');
}

$stmt = $pdo->prepare('SELECT * FROM vw_public_personas WHERE refugio_id = ?');
$stmt->execute([$refugio_id]);
$personas = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT nombre_refugio FROM vw_public_refugios WHERE refugio_id = ?');
$stmt->execute([$refugio_id]);
$refugio = $stmt->fetch();

if (!$refugio) {
    http_response_code(404);
    die('Refugio no encontrado');
}

$filename = 'refugio_' . $refugio_id . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fputcsv($output, ['Nombre', 'Edad', 'Género', 'Estatus', 'Fecha Ingreso', 'Hora Ingreso', 'Refugio', 'Ubicación']);

foreach ($personas as $persona) {
    fputcsv($output, [
        $persona['nombre'],
        $persona['edad_rango'],
        $persona['genero'],
        $persona['estatus'],
        $persona['fecha_ingreso'],
        $persona['hora_ingreso'],
        $persona['refugio'],
        $persona['direccion']
    ]);
}

fclose($output);
?>