<?php
require_once 'config/database.php';

$search = $_GET['search'] ?? '';
$refugio_filter = $_GET['refugio'] ?? '';

// Estadísticas
$stmt = $pdo->query('SELECT * FROM vw_public_estadisticas');
$stats = $stmt->fetch();

// Personas
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

$query .= ' ORDER BY fecha_ingreso DESC LIMIT 50';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$personas = $stmt->fetchAll();

// Refugios
$stmt = $pdo->query('SELECT * FROM vw_public_refugios ORDER BY nombre_refugio');
$refugios = $stmt->fetchAll();
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
        <h3>Buscar Personas</h3>
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
                        <th>Estatus</th>
                        <th>Fecha Ingreso</th>
                        <th>Refugio</th>
                        <th>Ubicación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($personas as $persona): ?>
                        <tr>
                            <td><?= htmlspecialchars($persona['nombre']) ?></td>
                            <td>
                                <span class="badge bg-success"><?= htmlspecialchars($persona['estatus']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($persona['fecha_ingreso']) ?> <?= htmlspecialchars($persona['hora_ingreso']) ?></td>
                            <td><?= htmlspecialchars($persona['refugio']) ?></td>
                            <td><?= htmlspecialchars($persona['direccion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
                            <a href="export.php?refugio=<?= $refugio['refugio_id'] ?>&format=csv" class="btn btn-sm btn-outline-primary">Descargar CSV</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>