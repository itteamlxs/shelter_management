<?php
require_once 'config/database.php';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refugios Disponibles - Sistema de Refugios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Sistema de Refugios</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Búsqueda</a>
                <a class="nav-link" href="shelter.php">Refugios</a>
                <a class="nav-link" href="#admin">Panel Admin</a>
            </div>
        </div>
    </nav>

    <header class="bg-primary text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="h2">Refugios Disponibles</h1>
                    <p class="mb-0">Listado de refugios habilitados para emergencias</p>
                </div>
            </div>
        </div>
    </header>

    <section class="py-4">
        <div class="container">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h4 class="text-primary"><?= $stats['total_personas'] ?? 0 ?></h4>
                            <p class="card-text">Total Personas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h4 class="text-success"><?= $stats['total_alojados'] ?? 0 ?></h4>
                            <p class="card-text">Alojados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h4 class="text-info"><?= $stats['total_dados_alta'] ?? 0 ?></h4>
                            <p class="card-text">Dados de Alta</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h4 class="text-warning"><?= $stats['total_trasladados'] ?? 0 ?></h4>
                            <p class="card-text">Trasladados</p>
                        </div>
                    </div>
                </div>
            </div>

            <h3>Listado de Refugios</h3>
            <div class="row g-3">
                <?php foreach ($refugios as $refugio): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($refugio['nombre_refugio']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($refugio['ubicacion']) ?></p>
                                <p><strong>Capacidad:</strong> <?= $refugio['capacidad_ocupada'] ?>/<?= $refugio['capacidad_maxima'] ?></p>
                                <p><strong>Estado:</strong> 
                                    <span class="badge <?= $refugio['estado'] == 'Disponible' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= htmlspecialchars($refugio['estado']) ?>
                                    </span>
                                </p>
                                <div class="d-flex gap-2">
                                    <a href="export.php?refugio=<?= $refugio['refugio_id'] ?>&format=csv" class="btn btn-sm btn-outline-primary">CSV</a>
                                    <?php if ($refugio['lat'] && $refugio['lng']): ?>
                                        <a href="https://maps.google.com/?q=<?= $refugio['lat'] ?>,<?= $refugio['lng'] ?>" target="_blank" class="btn btn-sm btn-outline-success">Mapa</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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