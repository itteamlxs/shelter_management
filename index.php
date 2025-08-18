
<?php
/**
 * Sistema de Refugios - Main Index
 * Phase 0: Basic connection test and shelter count display
 */

// Error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/backend/models/RefugioModel.php';

try {
    // Initialize the model
    $refugioModel = new RefugioModel();
    
    // Get shelter statistics
    $availableShelters = $refugioModel->getAvailableSheltersCount();
    $statistics = $refugioModel->getPublicStatistics();
    $refugios = $refugioModel->getPublicRefugios();
    
    $connectionStatus = "✅ Conexión exitosa a shelter_database_system";
    $errorMessage = null;
    
} catch (Exception $e) {
    $connectionStatus = "❌ Error de conexión";
    $errorMessage = $e->getMessage();
    $availableShelters = 0;
    $statistics = [];
    $refugios = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Refugios - Inicio</title>
    
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Theme CSS -->
    <link href="assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <!-- Header -->
                <div class="text-center mb-4">
                    <h1 class="display-4 text-primary">Sistema de Refugios</h1>
                    <p class="lead text-muted">Plataforma de Gestión de Refugios - Fase 0</p>
                </div>
                
                <!-- Connection Status -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Estado de Conexión</h5>
                        <p class="card-text"><?php echo $connectionStatus; ?></p>
                        
                        <?php if ($errorMessage): ?>
                            <div class="alert alert-danger" role="alert">
                                <strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Refugios Disponibles</h5>
                                <h2 class="text-success"><?php echo $availableShelters; ?></h2>
                                <p class="text-muted">Total de refugios con capacidad</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total de Refugios</h5>
                                <h2 class="text-primary"><?php echo count($refugios); ?></h2>
                                <p class="text-muted">Refugios registrados en el sistema</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics from vw_public_estadisticas -->
                <?php if (!empty($statistics)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Estadísticas Públicas</h5>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-info"><?php echo $statistics['total_personas'] ?? 0; ?></h4>
                                <small class="text-muted">Total Personas</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-success"><?php echo $statistics['total_alojados'] ?? 0; ?></h4>
                                <small class="text-muted">Alojados</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning"><?php echo $statistics['total_dados_alta'] ?? 0; ?></h4>
                                <small class="text-muted">Dados de Alta</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-secondary"><?php echo $statistics['total_trasladados'] ?? 0; ?></h4>
                                <small class="text-muted">Trasladados</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Technical Information -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Información Técnica</h5>
                        <ul class="list-unstyled">
                            <li><strong>Base de Datos:</strong> shelter_database_system</li>
                            <li><strong>Vista Consultada:</strong> vw_public_refugios</li>
                            <li><strong>Fase de Desarrollo:</strong> 0 (Configuración inicial)</li>
                            <li><strong>Próxima Fase:</strong> Landing page pública</li>
                        </ul>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
