<?php
require_once 'config/database.php';

$search = $_GET['search'] ?? '';
$refugio_filter = $_GET['refugio'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Estadísticas
try {
    $stmt = $pdo->query('SELECT * FROM vw_public_estadisticas');
    $stats = $stmt->fetch();
} catch (Exception $e) {
    $stats = ['total_personas' => 0, 'total_alojados' => 0, 'total_dados_alta' => 0, 'total_trasladados' => 0];
}

// Refugios
try {
    $stmt = $pdo->query('SELECT * FROM vw_public_refugios ORDER BY nombre_refugio');
    $refugios = $stmt->fetchAll();
} catch (Exception $e) {
    $refugios = [];
}

// Contar total de personas para paginación
$count_query = 'SELECT COUNT(*) FROM vw_public_personas WHERE 1=1';
$count_params = [];

if (!empty($search)) {
    $count_query .= ' AND (nombre LIKE ? OR refugio LIKE ?)';
    $count_params[] = '%' . $search . '%';
    $count_params[] = '%' . $search . '%';
}

if (!empty($refugio_filter)) {
    $count_query .= ' AND refugio_id = ?';
    $count_params[] = $refugio_filter;
}

try {
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($count_params);
    $total_personas = $stmt->fetchColumn();
    $total_pages = ceil($total_personas / $limit);
} catch (Exception $e) {
    $total_personas = 0;
    $total_pages = 0;
}

// Personas con paginación
$query = 'SELECT * FROM vw_public_personas WHERE 1=1';
$params = [];

if (!empty($search)) {
    $query .= ' AND (nombre LIKE ? OR refugio LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($refugio_filter)) {
    $query .= ' AND refugio_id = ?';
    $params[] = $refugio_filter;
}

$query .= ' ORDER BY fecha_ingreso DESC LIMIT ? OFFSET ?';
$params[] = $limit;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $personas = $stmt->fetchAll();
} catch (Exception $e) {
    $personas = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Refugios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <span class="navbar-brand">Sistema de Refugios</span>
            <a href="#admin" class="btn btn-outline-light btn-sm">Panel Admin</a>
        </div>
    </nav>

    <div class="bg-primary text-white py-5">
        <div class="container text-center">
            <h1>Gestión de Refugios</h1>
            <p class="lead">Información pública de personas albergadas durante emergencias</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-primary"><?= $stats['total_personas'] ?? 0 ?></h2>
                        <p>Total Personas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-success"><?= $stats['total_alojados'] ?? 0 ?></h2>
                        <p>Alojados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-info"><?= $stats['total_dados_alta'] ?? 0 ?></h2>
                        <p>Dados de Alta</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-warning"><?= $stats['total_trasladados'] ?? 0 ?></h2>
                        <p>Trasladados</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <h3>Refugios Disponibles</h3>
        <div class="row">
            <?php foreach ($refugios as $refugio): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($refugio['nombre_refugio']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($refugio['ubicacion']) ?></p>
                            <p><strong>Capacidad:</strong> <?= $refugio['capacidad_ocupada'] ?>/<?= $refugio['capacidad_maxima'] ?></p>
                            <p><strong>Estado:</strong> 
                                <span class="badge <?= $refugio['estado'] == 'Disponible' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= htmlspecialchars($refugio['estado']) ?>
                                </span>
                            </p>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="export.php?refugio=<?= $refugio['refugio_id'] ?>&format=csv" class="btn btn-sm btn-outline-primary">Descargar CSV</a>
                                <?php if ($refugio['lat'] && $refugio['lng']): ?>
                                    <a href="https://maps.google.com/?q=<?= $refugio['lat'] ?>,<?= $refugio['lng'] ?>" target="_blank" class="btn btn-sm btn-outline-success">Ver en Mapa</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="container my-5">
        <h3>Buscar Personas (<?= $total_personas ?> total)</h3>
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Buscar por nombre o refugio" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <select name="refugio" class="form-select">
                    <option value="">Todos los refugios</option>
                    <?php foreach ($refugios as $refugio): ?>
                        <option value="<?= $refugio['refugio_id'] ?>" <?= $refugio_filter == $refugio['refugio_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($refugio['nombre_refugio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Género</th>
                        <th>Estatus</th>
                        <th>Fecha Ingreso</th>
                        <th>Refugio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($personas)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No se encontraron personas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($personas as $persona): ?>
                            <tr>
                                <td><?= htmlspecialchars($persona['nombre']) ?></td>
                                <td><?= htmlspecialchars($persona['edad_rango']) ?></td>
                                <td><?= htmlspecialchars($persona['genero']) ?></td>
                                <td>
                                    <span class="badge bg-success"><?= htmlspecialchars($persona['estatus']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($persona['fecha_ingreso']) ?> <?= htmlspecialchars($persona['hora_ingreso']) ?></td>
                                <td><?= htmlspecialchars($persona['refugio']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación de personas">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Anterior</a>
                    </li>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>