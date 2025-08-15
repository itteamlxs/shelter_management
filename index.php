<?php
require_once 'config/database.php';

$search = $_GET['search'] ?? '';
$refugio_filter = $_GET['refugio'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $stmt = $pdo->query('SELECT * FROM vw_public_estadisticas');
    $stats = $stmt->fetch();
} catch (Exception $e) {
    $stats = ['total_personas' => 0, 'total_alojados' => 0, 'total_dados_alta' => 0, 'total_trasladados' => 0];
}

try {
    $stmt = $pdo->query('SELECT * FROM vw_public_refugios ORDER BY nombre_refugio');
    $refugios = $stmt->fetchAll();
} catch (Exception $e) {
    $refugios = [];
}

$count_query = 'SELECT COUNT(*) FROM vw_admin_personas_full WHERE 1=1';
$count_params = [];

if (!empty($search)) {
    $count_query .= ' AND (nombre_preferido LIKE ? OR nombre_refugio LIKE ?)';
    $count_params[] = '%' . $search . '%';
    $count_params[] = '%' . $search . '%';
}

if (!empty($refugio_filter)) {
    $count_query .= ' AND refugio_id = ?';
    $count_params[] = $refugio_filter;
}

if (!empty($status_filter)) {
    $count_query .= ' AND estatus = ?';
    $count_params[] = $status_filter;
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

$query = 'SELECT * FROM vw_admin_personas_full WHERE 1=1';
$params = [];

if (!empty($search)) {
    $query .= ' AND (nombre_preferido LIKE ? OR nombre_refugio LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($refugio_filter)) {
    $query .= ' AND refugio_id = ?';
    $params[] = $refugio_filter;
}

if (!empty($status_filter)) {
    $query .= ' AND estatus = ?';
    $params[] = $status_filter;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Sistema de Refugios</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="shelter.php">Refugios</a>
                <a class="nav-link" href="#admin">Panel Admin</a>
            </div>
        </div>
    </nav>

    <header class="bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-4">Gestión de Refugios</h1>
                    <p class="lead">Información pública de personas albergadas durante emergencias</p>
                </div>
            </div>
        </div>
    </header>

    <section class="py-4">
        <div class="container">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h3 class="text-primary"><?= $stats['total_personas'] ?? 0 ?></h3>
                            <p class="card-text">Total Personas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h3 class="text-success"><?= $stats['total_alojados'] ?? 0 ?></h3>
                            <p class="card-text">Alojados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h3 class="text-info"><?= $stats['total_dados_alta'] ?? 0 ?></h3>
                            <p class="card-text">Dados de Alta</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h3 class="text-warning"><?= $stats['total_trasladados'] ?? 0 ?></h3>
                            <p class="card-text">Trasladados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-4">
        <div class="container">
            <h3>Buscar Personas</h3>
            <p class="text-muted">Total: <?= $total_personas ?> personas</p>
            
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Buscar por nombre o refugio" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="refugio" class="form-select">
                        <option value="">Todos los refugios</option>
                        <?php foreach ($refugios as $refugio): ?>
                            <option value="<?= $refugio['refugio_id'] ?>" <?= $refugio_filter == $refugio['refugio_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($refugio['nombre_refugio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="Alojado" <?= $status_filter == 'Alojado' ? 'selected' : '' ?>>Alojado</option>
                        <option value="Dado de alta" <?= $status_filter == 'Dado de alta' ? 'selected' : '' ?>>Dado de alta</option>
                        <option value="Trasladado a otro refugio" <?= $status_filter == 'Trasladado a otro refugio' ? 'selected' : '' ?>>Trasladado</option>
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
                                    <td><?= htmlspecialchars($persona['nombre_preferido']) ?></td>
                                    <td><?= htmlspecialchars($persona['edad_rango']) ?></td>
                                    <td><?= htmlspecialchars($persona['genero']) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = match($persona['estatus']) {
                                            'Alojado' => 'bg-success',
                                            'Dado de alta' => 'bg-info',
                                            'Trasladado a otro refugio' => 'bg-warning',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($persona['estatus']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($persona['fecha_ingreso']) ?> <?= htmlspecialchars($persona['hora_ingreso']) ?></td>
                                    <td><?= htmlspecialchars($persona['nombre_refugio']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Paginación">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Anterior</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Siguiente</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>Sistema de Gestión de Refugios</h6>
                    <p class="text-muted">Plataforma segura para gestión de personas albergadas durante emergencias</p>
                </div>
                <div class="col-md-6">
                    <h6>Contacto</h6>
                    <p class="text-muted">Para más información contacte a las autoridades locales</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>