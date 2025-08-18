/**
 * Dashboard JavaScript
 * Sistema de Refugios - Enhanced with Phase 3 features
 */

let currentUser = null;
let currentSection = 'dashboard';

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
});

/**
 * Check authentication and load initial data
 */
async function checkAuthentication() {
    try {
        const response = await fetch('/backend/api/auth.php/me');
        const data = await response.json();

        if (data.success && data.user) {
            currentUser = data.user;
            setupUserInterface();
            loadDashboardData();
        } else {
            window.location.href = '/login.html';
        }
    } catch (error) {
        console.error('Authentication check failed:', error);
        window.location.href = '/login.html';
    }
}

/**
 * Setup user interface based on role
 */
function setupUserInterface() {
    const userInfo = document.getElementById('userInfo');
    userInfo.textContent = `${currentUser.username} (${currentUser.rol})`;

    // Show/hide navigation items based on role
    if (currentUser.rol === 'Administrador') {
        document.getElementById('adminNav').style.display = 'block';
        document.getElementById('uploadsNav').style.display = 'block';
        document.getElementById('uploadLink').style.display = 'block';
        document.getElementById('uploadSidebarLink').style.display = 'block';
    } else if (currentUser.rol === 'Refugio') {
        document.getElementById('personasNav').style.display = 'block';
        document.getElementById('uploadsNav').style.display = 'block';
        document.getElementById('uploadLink').style.display = 'block';
        document.getElementById('uploadSidebarLink').style.display = 'block';
    } else if (currentUser.rol === 'Auditor') {
        document.getElementById('uploadsNav').style.display = 'block';
    }
}

/**
 * Load dashboard data
 */
async function loadDashboardData() {
    try {
        const response = await fetch('/backend/api/private.php/dashboard');
        const data = await response.json();

        if (data.success) {
            displayDashboardStats(data.data.stats);
            displayRecentUploads(data.data.recent_uploads || []);

            // Role-specific data
            if (currentUser.rol === 'Refugio' && data.data.refugio) {
                displayRefugioInfo(data.data.refugio);
            }
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

/**
 * Display dashboard statistics
 */
function displayDashboardStats(stats) {
    const statsCards = document.getElementById('statsCards');
    let html = '';

    if (currentUser.rol === 'Administrador') {
        html = `
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Refugios</h5>
                        <h2 class="card-text">${stats.total_refugios || 0}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Personas</h5>
                        <h2 class="card-text">${stats.total_personas || 0}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Personas Activas</h5>
                        <h2 class="card-text">${stats.personas_activas || 0}</h2>
                    </div>
                </div>
            </div>
        `;
    } else if (currentUser.rol === 'Refugio') {
        html = `
            <div class="col-md-6">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Personas Activas</h5>
                        <h2 class="card-text">${stats.personas_activas || 0}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Ingresos Hoy</h5>
                        <h2 class="card-text">${stats.ingresos_hoy || 0}</h2>
                    </div>
                </div>
            </div>
        `;
    } else if (currentUser.rol === 'Auditor') {
        html = `
            <div class="col-md-6">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Refugios</h5>
                        <h2 class="card-text">${stats.total_refugios || 0}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Personas</h5>
                        <h2 class="card-text">${stats.total_personas || 0}</h2>
                    </div>
                </div>
            </div>
        `;
    }

    statsCards.innerHTML = html;
}

/**
 * Display recent uploads
 */
function displayRecentUploads(uploads) {
    const container = document.getElementById('recentUploads');

    if (!uploads || uploads.length === 0) {
        container.innerHTML = '<p class="text-muted">No hay subidas recientes.</p>';
        return;
    }

    const html = uploads.map(upload => `
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <div>
                <div class="fw-bold">${upload.archivo_nombre}</div>
                <small class="text-muted">${upload.refugio_nombre || 'N/A'}</small>
            </div>
            <div class="text-end">
                <span class="badge bg-${getStatusBadgeClass(upload.estado)}">
                    ${getStatusText(upload.estado)}
                </span>
                <br>
                <small class="text-muted">${formatDate(upload.fecha_subida)}</small>
            </div>
        </div>
    `).join('');

    container.innerHTML = html;
}

/**
 * Display refugio info for refugio users
 */
function displayRefugioInfo(refugio) {
    const recentActivity = document.getElementById('recentActivity');

    const html = `
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">${refugio.nombre}</h6>
                <p class="card-text">
                    <strong>Dirección:</strong> ${refugio.direccion}<br>
                    <strong>Capacidad:</strong> ${refugio.capacidad_maxima}<br>
                    <strong>Teléfono:</strong> ${refugio.telefono || 'N/A'}<br>
                    <strong>Email:</strong> ${refugio.email || 'N/A'}
                </p>
            </div>
        </div>
    `;

    recentActivity.innerHTML = html;
}

/**
 * Show specific section
 */
function showSection(section) {
    // Hide all sections
    document.querySelectorAll('[id$="Section"]').forEach(el => el.style.display = 'none');

    // Show selected section
    document.getElementById(section + 'Section').style.display = 'block';

    // Update navigation
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    event.target.classList.add('active');

    currentSection = section;

    // Load section-specific data
    switch (section) {
        case 'personas':
            loadPersonas();
            break;
        case 'admin':
            loadAdminData();
            break;
        case 'uploads':
            loadUploadsHistory();
            break;
    }
}

/**
 * Load personas data
 */
async function loadPersonas() {
    if (currentUser.rol !== 'Refugio' && currentUser.rol !== 'Administrador') {
        return;
    }

    try {
        const search = document.getElementById('personasSearch')?.value || '';
        let url = '/backend/api/private.php/refugio/personas';

        if (currentUser.rol === 'Administrador') {
            const refugioId = currentUser.refugio_id || 1; // Default for admin
            url += `?refugio_id=${refugioId}`;
        }

        if (search) {
            url += (url.includes('?') ? '&' : '?') + `search=${encodeURIComponent(search)}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            displayPersonasTable(data.data || []);
        } else {
            document.getElementById('personasTable').innerHTML = 
                '<p class="text-muted">Error cargando personas.</p>';
        }
    } catch (error) {
        console.error('Error loading personas:', error);
        document.getElementById('personasTable').innerHTML = 
            '<p class="text-muted">Error de conexión.</p>';
    }
}

/**
 * Display personas table
 */
function displayPersonasTable(personas) {
    const container = document.getElementById('personasTable');

    if (personas.length === 0) {
        container.innerHTML = '<p class="text-muted">No hay personas registradas.</p>';
        return;
    }

    const html = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Teléfono</th>
                        <th>Fecha Ingreso</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    ${personas.map(persona => `
                        <tr>
                            <td>${persona.nombres} ${persona.apellidos}</td>
                            <td>${persona.documento_identidad}</td>
                            <td>${persona.telefono || 'N/A'}</td>
                            <td>${formatDate(persona.fecha_ingreso)}</td>
                            <td>
                                <span class="badge bg-${persona.fecha_salida ? 'secondary' : 'success'}">
                                    ${persona.fecha_salida ? 'Inactivo' : 'Activo'}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Search personas
 */
function searchPersonas() {
    loadPersonas();
}

/**
 * Load admin data
 */
async function loadAdminData() {
    if (currentUser.rol !== 'Administrador') {
        return;
    }

    try {
        const response = await fetch('/backend/api/private.php/admin/users');
        const data = await response.json();

        if (data.success) {
            displayUsersTable(data.data || []);
        }
    } catch (error) {
        console.error('Error loading admin data:', error);
    }
}

/**
 * Display users table
 */
function displayUsersTable(users) {
    const container = document.getElementById('usersTable');

    const html = `
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    ${users.map(user => `
                        <tr>
                            <td>${user.username}</td>
                            <td>${user.email || 'N/A'}</td>
                            <td>${user.rol}</td>
                            <td>
                                <span class="badge bg-${user.activo ? 'success' : 'danger'}">
                                    ${user.activo ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Load uploads history
 */
async function loadUploadsHistory() {
    try {
        const response = await fetch('/backend/api/private.php/upload/history');
        const data = await response.json();

        if (data.success) {
            displayUploadsTable(data.data || []);
        }
    } catch (error) {
        console.error('Error loading uploads history:', error);
    }
}

/**
 * Display uploads table
 */
function displayUploadsTable(uploads) {
    const container = document.getElementById('uploadsTable');

    if (uploads.length === 0) {
        container.innerHTML = '<p class="text-muted">No hay historial de subidas.</p>';
        return;
    }

    const html = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Refugio</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Resultados</th>
                    </tr>
                </thead>
                <tbody>
                    ${uploads.map(upload => `
                        <tr>
                            <td>${upload.archivo_nombre}</td>
                            <td>${upload.refugio_nombre || 'N/A'}</td>
                            <td>${upload.username || 'N/A'}</td>
                            <td>
                                <span class="badge bg-${getStatusBadgeClass(upload.estado)}">
                                    ${getStatusText(upload.estado)}
                                </span>
                            </td>
                            <td>${formatDate(upload.fecha_subida)}</td>
                            <td>
                                ${upload.registros_procesados ? `
                                    <small>
                                        ${upload.registros_exitosos}/${upload.registros_procesados} exitosos
                                        ${upload.registros_error > 0 ? `<br>${upload.registros_error} errores` : ''}
                                    </small>
                                ` : '-'}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Utility functions
 */
function getStatusBadgeClass(status) {
    switch (status) {
        case 'COMPLETED': return 'success';
        case 'ERROR': return 'danger';
        case 'PROCESSING': return 'warning';
        default: return 'secondary';
    }
}

function getStatusText(status) {
    switch (status) {
        case 'COMPLETED': return 'Completado';
        case 'ERROR': return 'Error';
        case 'PROCESSING': return 'Procesando';
        default: return status;
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Logout function
 */
async function logout() {
    try {
        await fetch('/backend/api/auth.php/logout', { method: 'POST' });
        window.location.href = '/login.html';
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = '/login.html';
    }
}