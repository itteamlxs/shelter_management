
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Refugios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/theme.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .quick-actions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/panel">Sistema de Refugios</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3" id="userInfo">
                    Cargando...
                </span>
                <button class="btn btn-outline-light btn-sm" onclick="logout()">
                    Cerrar Sesión
                </button>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1>Panel de Control</h1>
            <p>Bienvenido al sistema de gestión de refugios</p>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container">
        <!-- User Role Specific Content -->
        <div id="adminContent" class="d-none">
            <h3>Panel de Administración</h3>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number" id="totalUsers">-</div>
                        <div>Total de Usuarios</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number" id="totalRefugios">-</div>
                        <div>Total de Refugios</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number" id="totalPersonas">-</div>
                        <div>Total de Personas</div>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h5>Acciones Rápidas</h5>
                <div class="row">
                    <div class="col-md-6">
                        <button class="btn btn-primary w-100 mb-2" onclick="createUser()">
                            Crear Usuario
                        </button>
                        <button class="btn btn-success w-100" onclick="createRefugio()">
                            Crear Refugio
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-info w-100 mb-2" onclick="viewUsers()">
                            Ver Usuarios
                        </button>
                        <button class="btn btn-warning w-100" onclick="viewAuditLogs()">
                            Ver Logs de Auditoría
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="refugioContent" class="d-none">
            <h3>Panel de Refugio</h3>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number" id="refugioPersonas">-</div>
                        <div>Personas Alojadas</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number" id="refugioCapacidad">-</div>
                        <div>Capacidad Total</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number" id="refugioOcupacion">-</div>
                        <div>% Ocupación</div>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h5>Acciones Rápidas</h5>
                <div class="row">
                    <div class="col-md-6">
                        <button class="btn btn-success w-100 mb-2" onclick="addPersona()">
                            Registrar Persona
                        </button>
                        <button class="btn btn-info w-100" onclick="uploadCSV()">
                            Subir CSV
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-primary w-100 mb-2" onclick="viewPersonas()">
                            Ver Personas
                        </button>
                        <button class="btn btn-warning w-100" onclick="updateProfile()">
                            Actualizar Perfil
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="auditorContent" class="d-none">
            <h3>Panel de Auditoría</h3>
            <div class="quick-actions">
                <h5>Acciones Disponibles</h5>
                <button class="btn btn-primary w-100" onclick="viewAuditLogs()">
                    Ver Logs de Auditoría
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentUser = null;

        // Check authentication on page load
        document.addEventListener('DOMContentLoaded', function() {
            const token = localStorage.getItem('authToken');
            if (!token) {
                window.location.href = '/login';
                return;
            }
            
            // Parse token to get user info
            try {
                const payload = JSON.parse(atob(token.split('.')[1]));
                currentUser = payload;
                
                // Check if token is expired
                if (payload.exp < Math.floor(Date.now() / 1000)) {
                    logout();
                    return;
                }
                
                displayUserInfo();
                showRoleContent();
                loadDashboardData();
            } catch (error) {
                console.error('Token parse error:', error);
                logout();
            }
        });

        function displayUserInfo() {
            if (currentUser) {
                document.getElementById('userInfo').textContent = 
                    `${currentUser.username} (${currentUser.rol})`;
            }
        }

        function showRoleContent() {
            // Hide all content
            document.getElementById('adminContent').classList.add('d-none');
            document.getElementById('refugioContent').classList.add('d-none');
            document.getElementById('auditorContent').classList.add('d-none');
            
            // Show role-specific content
            switch (currentUser.rol) {
                case 'Administrador':
                    document.getElementById('adminContent').classList.remove('d-none');
                    break;
                case 'Refugio':
                    document.getElementById('refugioContent').classList.remove('d-none');
                    break;
                case 'Auditor':
                    document.getElementById('auditorContent').classList.remove('d-none');
                    break;
            }
        }

        async function loadDashboardData() {
            const token = localStorage.getItem('authToken');
            
            try {
                // Load statistics based on role
                if (currentUser.rol === 'Administrador') {
                    // Load admin statistics
                    const response = await fetch('/public/statistics', {
                        headers: {
                            'Authorization': `Bearer ${token}`
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        document.getElementById('totalPersonas').textContent = data.total_personas || 0;
                        document.getElementById('totalRefugios').textContent = data.total_refugios || 0;
                    }
                } else if (currentUser.rol === 'Refugio') {
                    // Load refugio-specific statistics
                    const response = await fetch('/refugio/personas', {
                        headers: {
                            'Authorization': `Bearer ${token}`
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        document.getElementById('refugioPersonas').textContent = data.total || 0;
                    }
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        function logout() {
            localStorage.removeItem('authToken');
            window.location.href = '/login';
        }

        // Quick action functions
        function createUser() {
            window.location.href = '/panel'; // Redirect to full panel for complex actions
        }

        function createRefugio() {
            window.location.href = '/panel';
        }

        function viewUsers() {
            window.location.href = '/panel';
        }

        function viewAuditLogs() {
            window.location.href = '/panel';
        }

        function addPersona() {
            window.location.href = '/panel';
        }

        function uploadCSV() {
            window.location.href = '/panel';
        }

        function viewPersonas() {
            window.location.href = '/panel';
        }

        function updateProfile() {
            window.location.href = '/panel';
        }
    </script>
</body>
</html>
