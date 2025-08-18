
<?php
/**
 * Main Entry Point - Sistema de Refugios
 * Phase 1: Landing page with dynamic data loading
 */

// Load configuration
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/models/RefugioModel.php';

// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check if this is an API request
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '/backend/api/') !== false) {
    // Route to API
    if (strpos($request_uri, '/backend/api/public.php') !== false) {
        include __DIR__ . '/backend/api/public.php';
        exit;
    }
}

// Test database connection and get basic stats
$db_status = 'disconnected';
$shelter_count = 0;
$error_message = '';

try {
    $refugioModel = new RefugioModel();
    $shelter_count = $refugioModel->getAvailableSheltersCount();
    $statistics = $refugioModel->getPublicStatistics();
    $db_status = 'connected';
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Database connection error: " . $error_message);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Refugios - Gestión de Personas Albergadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-home me-2"></i>Sistema de Refugios</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.html">Portal Completo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#status">Estado del Sistema</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold text-primary mb-3">Sistema de Refugios</h1>
                    <p class="lead mb-4">Plataforma para gestión segura de personas albergadas durante desastres</p>
                    <p class="mb-4">Esta plataforma permite registrar, gestionar y publicar información no sensible sobre personas albergadas en refugios durante emergencias.</p>
                    <div class="d-flex gap-3">
                        <a href="frontend/index.html" class="btn btn-primary btn-lg">Acceder al Portal</a>
                        <a href="#status" class="btn btn-outline-primary btn-lg">Ver Estado</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="text-center">
                        <i class="fas fa-shield-alt display-1 text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Status Section -->
    <section id="status" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Estado del Sistema</h2>
            <div class="row g-4">
                <!-- Database Status -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-database display-4 <?php echo $db_status === 'connected' ? 'text-success' : 'text-danger'; ?> mb-3"></i>
                            <h4 class="card-title">Base de Datos</h4>
                            <span class="badge <?php echo $db_status === 'connected' ? 'bg-success' : 'bg-danger'; ?> fs-6">
                                <?php echo $db_status === 'connected' ? 'Conectada' : 'Desconectada'; ?>
                            </span>
                            <?php if ($error_message): ?>
                                <p class="text-danger mt-2 small">Error: <?php echo htmlspecialchars($error_message); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Shelter Count -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-home display-4 text-info mb-3"></i>
                            <h4 class="card-title">Refugios Disponibles</h4>
                            <h2 class="text-info"><?php echo number_format($shelter_count); ?></h2>
                            <p class="text-muted">Refugios activos en el sistema</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($db_status === 'connected' && !empty($statistics)): ?>
                <div class="row g-4 mt-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-users display-5 text-primary mb-2"></i>
                                <h5><?php echo number_format($statistics['total_personas'] ?? 0); ?></h5>
                                <small class="text-muted">Total Personas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-bed display-5 text-success mb-2"></i>
                                <h5><?php echo number_format($statistics['total_alojados'] ?? 0); ?></h5>
                                <small class="text-muted">Alojados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle display-5 text-info mb-2"></i>
                                <h5><?php echo number_format($statistics['total_dados_alta'] ?? 0); ?></h5>
                                <small class="text-muted">Dados de Alta</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-exchange-alt display-5 text-warning mb-2"></i>
                                <h5><?php echo number_format($statistics['total_trasladados'] ?? 0); ?></h5>
                                <small class="text-muted">Trasladados</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- API Status -->
            <div class="row mt-5">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-api me-2"></i>Estado de APIs</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <i class="fas fa-globe text-success fs-3"></i>
                                    <p class="mt-2 mb-0"><strong>API Pública</strong></p>
                                    <small class="text-success">Operacional</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-search text-success fs-3"></i>
                                    <p class="mt-2 mb-0"><strong>Búsquedas</strong></p>
                                    <small class="text-success">Activas</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-chart-bar text-success fs-3"></i>
                                    <p class="mt-2 mb-0"><strong>Estadísticas</strong></p>
                                    <small class="text-success">Disponibles</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Phase Information -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h3 class="mb-4">Fase 1 - Completada</h3>
                    <p class="lead">Portal público funcional con búsquedas dinámicas, estadísticas en tiempo real y API REST completa.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="frontend/index.html" class="btn btn-primary">Portal Completo</a>
                        <a href="backend/api/public.php/statistics" class="btn btn-outline-primary" target="_blank">API Estadísticas</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
