
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Refugios - Gestión de Personas Albergadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/theme.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">Sistema de Refugios</a>
            <div>
                <a href="/login" class="btn btn-primary">Iniciar Sesión</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Sistema de Gestión de Refugios</h1>
            <p>Plataforma segura para registrar y localizar personas albergadas durante emergencias</p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="#refugios" class="btn btn-light btn-lg">Ver Refugios</a>
                <a href="#personas" class="btn btn-outline-light btn-lg">Buscar Personas</a>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="container my-5">
        <div class="stats-grid" id="statistics">
            <div class="stat-card">
                <div class="stat-number" id="total-personas">-</div>
                <div class="stat-label">Total Personas Registradas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-alojados">-</div>
                <div class="stat-label">Actualmente Alojados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-dados-alta">-</div>
                <div class="stat-label">Dados de Alta</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-refugios">-</div>
                <div class="stat-label">Refugios Activos</div>
            </div>
        </div>
    </section>

    <!-- Person Search -->
    <section id="personas" class="container my-5">
        <div class="search-container">
            <h2 class="mb-4">Buscar Personas Alojadas</h2>
            <div class="row">
                <div class="col-md-8">
                    <input type="text" id="search-personas" class="form-control" 
                           placeholder="Buscar por nombre o refugio...">
                </div>
                <div class="col-md-4">
                    <select id="filter-refugio" class="form-control">
                        <option value="">Todos los refugios</option>
                    </select>
                </div>
            </div>
            
            <div id="personas-results" class="mt-4">
                <div class="loading">Cargando resultados...</div>
            </div>
            
            <div id="personas-pagination" class="pagination"></div>
        </div>
    </section>

    <!-- Shelters Catalog -->
    <section id="refugios" class="container my-5">
        <h2 class="mb-4">Catálogo de Refugios</h2>
        <div class="mb-3">
            <input type="text" id="search-refugios" class="form-control" 
                   placeholder="Buscar refugios por nombre o ubicación...">
        </div>
        
        <div id="refugios-grid" class="grid grid-2">
            <div class="loading">Cargando refugios...</div>
        </div>
        
        <div id="refugios-pagination" class="pagination"></div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Sistema de Refugios</h5>
                    <p>Plataforma oficial para la gestión de personas albergadas durante emergencias.</p>
                </div>
                <div class="col-md-6">
                    <h5>Contacto</h5>
                    <p>Email: info@refugios.gob<br>
                       Teléfono: (555) 123-4567</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2024 Sistema de Refugios. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/landing.js"></script>
</body>
</html>
