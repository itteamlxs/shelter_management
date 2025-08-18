
// Dashboard JavaScript
let currentUser = null;
let csrfToken = '';

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
    initializeEventListeners();
    getCsrfToken();
});

async function checkAuthentication() {
    try {
        const response = await fetch('/backend/api/auth.php/me');
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            updateUserInterface();
            loadDashboard();
        } else {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Authentication check failed:', error);
        window.location.href = 'login.html';
    }
}

async function getCsrfToken() {
    try {
        const response = await fetch('/backend/api/auth.php/csrf-token');
        const data = await response.json();
        if (data.success) {
            csrfToken = data.csrf_token;
        }
    } catch (error) {
        console.error('Error getting CSRF token:', error);
    }
}

function updateUserInterface() {
    document.getElementById('userDisplayName').textContent = currentUser.nombre_mostrado;
    document.getElementById('userRole').textContent = currentUser.rol;
    
    // Update navigation based on role
    const navMenu = document.getElementById('navigationMenu');
    let additionalMenuItems = '';
    
    switch (currentUser.rol) {
        case 'Refugio':
            additionalMenuItems = `
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="refugio-personas">
                        👥 Personas Alojadas
                    </a>
                </li>
            `;
            break;
            
        case 'Administrador':
            additionalMenuItems = `
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="admin-users">
                        👤 Gestión de Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="refugio-personas">
                        👥 Todas las Personas
                    </a>
                </li>
            `;
            break;
            
        case 'Auditor':
            additionalMenuItems = `
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="auditor-logs">
                        📋 Registro de Auditoría
                    </a>
                </li>
            `;
            break;
    }
    
    navMenu.innerHTML += additionalMenuItems;
}

function initializeEventListeners() {
    // Navigation menu clicks
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-section]')) {
            e.preventDefault();
            const section = e.target.getAttribute('data-section');
            showSection(section);
            
            // Update active nav item
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            e.target.classList.add('active');
        }
    });
    
    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // New person form
    document.getElementById('newPersonForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitNewPerson();
    });
    
    // New user form
    document.getElementById('newUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitNewUser();
    });
    
    // Role change handler for user form
    document.getElementById('newRol').addEventListener('change', function() {
        const refugioContainer = document.getElementById('refugioSelectContainer');
        if (this.value === 'Refugio') {
            refugioContainer.style.display = 'block';
            loadRefugiosForSelect();
        } else {
            refugioContainer.style.display = 'none';
        }
    });
    
    // Search personas
    let searchTimeout;
    document.getElementById('searchPersonas')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadPersonas(this.value);
        }, 300);
    });
    
    // Set default date/time for new person
    const today = new Date();
    document.getElementById('fechaIngreso').value = today.toISOString().split('T')[0];
    document.getElementById('horaIngreso').value = today.toTimeString().slice(0, 5);
}

function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('d-none');
    });
    
    // Show selected section
    const targetSection = document.getElementById(sectionName.replace('-', '') + 'Section');
    if (targetSection) {
        targetSection.classList.remove('d-none');
        
        // Load section-specific content
        switch (sectionName) {
            case 'dashboard':
                loadDashboard();
                break;
            case 'refugio-personas':
                loadPersonas();
                break;
            case 'admin-users':
                loadUsers();
                break;
            case 'auditor-logs':
                loadAuditLogs();
                break;
        }
    }
}

async function loadDashboard() {
    try {
        const response = await fetch('/backend/api/private.php/dashboard');
        const data = await response.json();
        
        if (data.success) {
            renderDashboard(data.data);
        } else {
            showError('Error cargando dashboard: ' + data.error);
        }
    } catch (error) {
        console.error('Dashboard load error:', error);
        showError('Error de conexión al cargar dashboard');
    }
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    let html = '';
    
    switch (currentUser.rol) {
        case 'Refugio':
            html = renderRefugioDashboard(data);
            break;
        case 'Administrador':
            html = renderAdminDashboard(data);
            break;
        case 'Auditor':
            html = renderAuditorDashboard(data);
            break;
        default:
            html = '<div class="alert alert-info">Dashboard no disponible para este rol</div>';
    }
    
    container.innerHTML = html;
}

function renderRefugioDashboard(data) {
    const stats = data.refugio_stats || {};
    const personas = data.recent_persons?.data || [];
    
    return `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-primary">${stats.capacidad_ocupada || 0}</h3>
                        <p class="mb-0">Personas Alojadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-warning">${stats.capacidad_maxima || 0}</h3>
                        <p class="mb-0">Capacidad Máxima</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-success">${stats.dados_alta || 0}</h3>
                        <p class="mb-0">Dados de Alta</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-info">${stats.trasladados || 0}</h3>
                        <p class="mb-0">Trasladados</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Personas Registradas Recientemente</h5>
            </div>
            <div class="card-body">
                ${personas.length > 0 ? renderPersonasTable(personas) : '<p class="text-muted">No hay registros recientes</p>'}
            </div>
        </div>
    `;
}

function renderAdminDashboard(data) {
    const stats = data.global_stats || {};
    const refugios = data.refugios_overview || [];
    
    return `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-primary">${stats.total_refugios || 0}</h3>
                        <p class="mb-0">Total Refugios</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-warning">${stats.total_personas || 0}</h3>
                        <p class="mb-0">Total Personas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-success">${stats.total_alojados || 0}</h3>
                        <p class="mb-0">Actualmente Alojados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-info">${Math.round((stats.ocupacion_total_sistema / stats.capacidad_total_sistema) * 100) || 0}%</h3>
                        <p class="mb-0">Ocupación Total</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Estado de Refugios</h5>
            </div>
            <div class="card-body">
                ${refugios.length > 0 ? renderRefugiosTable(refugios) : '<p class="text-muted">No hay refugios registrados</p>'}
            </div>
        </div>
    `;
}

function renderAuditorDashboard(data) {
    const logs = data.recent_activity || [];
    
    return `
        <div class="card">
            <div class="card-header">
                <h5>Actividad Reciente del Sistema</h5>
            </div>
            <div class="card-body">
                ${logs.length > 0 ? renderAuditLogsTable(logs) : '<p class="text-muted">No hay actividad reciente</p>'}
            </div>
        </div>
    `;
}

async function loadPersonas(search = '') {
    try {
        const refugioParam = currentUser.rol === 'Refugio' ? '' : '';
        const searchParam = search ? `&search=${encodeURIComponent(search)}` : '';
        
        const response = await fetch(`/backend/api/private.php/refugio/personas?page=1&per_page=50${searchParam}${refugioParam}`);
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('personasTableContainer');
            container.innerHTML = data.data.length > 0 ? renderPersonasTable(data.data) : '<p class="text-muted">No se encontraron personas</p>';
        } else {
            showError('Error cargando personas: ' + data.error);
        }
    } catch (error) {
        console.error('Load personas error:', error);
        showError('Error de conexión al cargar personas');
    }
}

function renderPersonasTable(personas) {
    return `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Género</th>
                        <th>Fecha Ingreso</th>
                        <th>Área</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    ${personas.map(persona => `
                        <tr>
                            <td>${escapeHtml(persona.nombre_preferido)}</td>
                            <td>${escapeHtml(persona.edad_rango)}</td>
                            <td>${escapeHtml(persona.genero)}</td>
                            <td>${escapeHtml(persona.fecha_ingreso)}</td>
                            <td>${escapeHtml(persona.area_asignada || '-')}</td>
                            <td>
                                <span class="badge bg-${getStatusColor(persona.estatus)}">
                                    ${escapeHtml(persona.estatus)}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function renderRefugiosTable(refugios) {
    return `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Ubicación</th>
                        <th>Capacidad</th>
                        <th>Ocupación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    ${refugios.map(refugio => `
                        <tr>
                            <td>${escapeHtml(refugio.nombre_refugio)}</td>
                            <td>${escapeHtml(refugio.ubicacion)}</td>
                            <td>${refugio.capacidad_maxima}</td>
                            <td>${refugio.capacidad_ocupada}/${refugio.capacidad_maxima}</td>
                            <td>
                                <span class="badge bg-${refugio.estado === 'Disponible' ? 'success' : 'warning'}">
                                    ${escapeHtml(refugio.estado)}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function renderAuditLogsTable(logs) {
    return `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Objeto</th>
                        <th>Resumen</th>
                    </tr>
                </thead>
                <tbody>
                    ${logs.map(log => `
                        <tr>
                            <td>${new Date(log.creado_en).toLocaleString()}</td>
                            <td>${escapeHtml(log.nombre_mostrado || log.username || 'Sistema')}</td>
                            <td>
                                <span class="badge bg-${getActionColor(log.accion)}">
                                    ${escapeHtml(log.accion)}
                                </span>
                            </td>
                            <td>${escapeHtml(log.objeto)}</td>
                            <td>${escapeHtml(log.resumen || '-')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function submitNewPerson() {
    const form = document.getElementById('newPersonForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.csrf_token = csrfToken;
    
    try {
        const response = await fetch('/backend/api/private.php/refugio/register-person', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Persona registrada exitosamente');
            bootstrap.Modal.getInstance(document.getElementById('newPersonModal')).hide();
            form.reset();
            loadPersonas();
            loadDashboard();
        } else {
            showError('Error al registrar persona: ' + result.error);
        }
    } catch (error) {
        console.error('Submit person error:', error);
        showError('Error de conexión al registrar persona');
    }
}

async function submitNewUser() {
    const form = document.getElementById('newUserForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.csrf_token = csrfToken;
    
    try {
        const response = await fetch('/backend/api/private.php/admin/create-user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Usuario creado exitosamente');
            bootstrap.Modal.getInstance(document.getElementById('newUserModal')).hide();
            form.reset();
            loadUsers();
        } else {
            showError('Error al crear usuario: ' + result.error);
        }
    } catch (error) {
        console.error('Submit user error:', error);
        showError('Error de conexión al crear usuario');
    }
}

async function logout() {
    try {
        await fetch('/backend/api/auth.php/logout', { method: 'POST' });
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'login.html';
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusColor(status) {
    switch (status) {
        case 'Alojado': return 'success';
        case 'Dado de alta': return 'info';
        case 'Trasladado a otro refugio': return 'warning';
        default: return 'secondary';
    }
}

function getActionColor(action) {
    switch (action) {
        case 'CREATE': return 'success';
        case 'UPDATE': return 'warning';
        case 'DELETE': return 'danger';
        case 'LOGIN': return 'info';
        case 'LOGOUT': return 'secondary';
        default: return 'primary';
    }
}

function showSuccess(message) {
    showAlert(message, 'success');
}

function showError(message) {
    showAlert(message, 'danger');
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }
    }, 5000);
}

function refreshDashboard() {
    loadDashboard();
}

// Additional functions for data loading
async function loadUsers() {
    try {
        const response = await fetch('/backend/api/private.php/admin/users');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('usersTableContainer');
            container.innerHTML = data.data.length > 0 ? renderUsersTable(data.data) : '<p class="text-muted">No hay usuarios registrados</p>';
        } else {
            showError('Error cargando usuarios: ' + data.error);
        }
    } catch (error) {
        console.error('Load users error:', error);
        showError('Error de conexión al cargar usuarios');
    }
}

function renderUsersTable(users) {
    return `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Refugio</th>
                        <th>Estado</th>
                        <th>Último Login</th>
                    </tr>
                </thead>
                <tbody>
                    ${users.map(user => `
                        <tr>
                            <td>${escapeHtml(user.username)}</td>
                            <td>${escapeHtml(user.nombre_mostrado)}</td>
                            <td>
                                <span class="badge bg-${getRoleColor(user.rol)}">
                                    ${escapeHtml(user.rol)}
                                </span>
                            </td>
                            <td>${escapeHtml(user.nombre_refugio || '-')}</td>
                            <td>
                                <span class="badge bg-${user.activo ? 'success' : 'danger'}">
                                    ${user.activo ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>${user.ultimo_login ? new Date(user.ultimo_login).toLocaleString() : 'Nunca'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function getRoleColor(role) {
    switch (role) {
        case 'Administrador': return 'danger';
        case 'Refugio': return 'primary';
        case 'Auditor': return 'info';
        default: return 'secondary';
    }
}

async function loadAuditLogs() {
    try {
        const response = await fetch('/backend/api/private.php/auditor/logs?page=1&per_page=50');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('auditLogsContainer');
            container.innerHTML = data.data.length > 0 ? renderAuditLogsTable(data.data) : '<p class="text-muted">No hay registros de auditoría</p>';
        } else {
            showError('Error cargando logs: ' + data.error);
        }
    } catch (error) {
        console.error('Load audit logs error:', error);
        showError('Error de conexión al cargar logs');
    }
}

async function loadRefugiosForSelect() {
    try {
        const response = await fetch('/backend/api/private.php/admin/refugios');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('newRefugioId');
            select.innerHTML = '<option value="">Seleccionar refugio...</option>';
            
            data.data.forEach(refugio => {
                select.innerHTML += `<option value="${refugio.refugio_id}">${escapeHtml(refugio.nombre_refugio)}</option>`;
            });
        }
    } catch (error) {
        console.error('Load refugios for select error:', error);
    }
}
