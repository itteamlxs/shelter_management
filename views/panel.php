
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Sistema de Refugios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/theme.css">
</head>
<body>
    <!-- Main Panel -->
    <div id="mainPanel">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="/">Sistema de Refugios</a>
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        <span id="userInfo"></span>
                    </span>
                    <button class="btn btn-outline-danger btn-sm" onclick="panel.logout()">Cerrar Sesión</button>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="container-fluid py-4">
            <!-- Admin Dashboard -->
            <div id="adminDashboard" class="d-none">
                <h2>Panel de Administración</h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Gestión de Usuarios</h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary mb-3" onclick="panel.showCreateUserModal()">
                                    Crear Nuevo Usuario
                                </button>
                                <div id="usersList"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Gestión de Refugios</h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary mb-3" onclick="panel.showCreateRefugioModal()">
                                    Crear Nuevo Refugio
                                </button>
                                <div id="refugiosList"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refugio Dashboard -->
            <div id="refugioDashboard" class="d-none">
                <h2>Panel de Refugio</h2>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5>Personas Alojadas</h5>
                                <div>
                                    <button class="btn btn-success btn-sm me-2" onclick="panel.showCreatePersonaModal()">
                                        Registrar Persona
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="panel.showUploadCSVModal()">
                                        Subir CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <input type="text" id="searchRefugioPersonas" class="form-control" 
                                           placeholder="Buscar personas...">
                                </div>
                                <div id="refugioPersonasList"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Estadísticas del Refugio</h5>
                            </div>
                            <div class="card-body">
                                <div id="refugioStats"></div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Configuración</h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-outline-primary w-100 mb-2" onclick="panel.updateLocation()">
                                    Actualizar Ubicación
                                </button>
                                <button class="btn btn-outline-secondary w-100" onclick="panel.showProfileModal()">
                                    Editar Perfil
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auditor Dashboard -->
            <div id="auditorDashboard" class="d-none">
                <h2>Panel de Auditoría</h2>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Registro de Actividades</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="text" id="searchAuditLogs" class="form-control" 
                                   placeholder="Buscar en logs...">
                        </div>
                        <div id="auditLogsList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals will be dynamically created here -->
    <div id="dynamicModals"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/panel.js"></script>
</body>
</html>
