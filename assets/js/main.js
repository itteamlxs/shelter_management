
/**
 * Main JavaScript file for Sistema de Refugios
 * Handles dynamic data loading and user interactions
 */

// Configuration
const API_BASE = '/backend/api/public.php';
let currentPersonasPage = 1;
let currentRefugiosPage = 1;
let searchTimeout = null;

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    loadLandingData();
    loadStatistics();
    loadPersonas();
    loadRefugios();
    setupEventListeners();
}

function setupEventListeners() {
    // Persona search with debounce
    const personaSearch = document.getElementById('persona-search');
    if (personaSearch) {
        personaSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPersonasPage = 1;
                loadPersonas(this.value);
            }, 300);
        });
    }

    // Refugio search with debounce
    const refugioSearch = document.getElementById('refugio-search');
    if (refugioSearch) {
        refugioSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentRefugiosPage = 1;
                loadRefugios(this.value);
            }, 300);
        });
    }

    // Search buttons
    document.getElementById('search-btn')?.addEventListener('click', function() {
        const searchTerm = document.getElementById('persona-search').value;
        currentPersonasPage = 1;
        loadPersonas(searchTerm);
    });

    document.getElementById('refugio-search-btn')?.addEventListener('click', function() {
        const searchTerm = document.getElementById('refugio-search').value;
        currentRefugiosPage = 1;
        loadRefugios(searchTerm);
    });
}

async function loadLandingData() {
    try {
        const response = await fetch(`${API_BASE}/landing`);
        const result = await response.json();
        
        if (result.data) {
            document.getElementById('hero-title').textContent = result.data.title;
            document.getElementById('hero-subtitle').textContent = result.data.subtitle;
            document.getElementById('hero-mission').textContent = result.data.mission;
        }
    } catch (error) {
        console.error('Error loading landing data:', error);
    }
}

async function loadStatistics() {
    try {
        showLoading();
        const response = await fetch(`${API_BASE}/statistics`);
        const result = await response.json();
        
        if (result.data) {
            const stats = result.data;
            document.getElementById('stat-total-personas').textContent = 
                (stats.total_personas || 0).toLocaleString();
            document.getElementById('stat-alojados').textContent = 
                (stats.total_alojados || 0).toLocaleString();
            document.getElementById('stat-dados-alta').textContent = 
                (stats.total_dados_alta || 0).toLocaleString();
            document.getElementById('stat-refugios').textContent = 
                (stats.total_refugios || 0).toLocaleString();
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
        showError('No se pudieron cargar las estadísticas');
    } finally {
        hideLoading();
    }
}

async function loadPersonas(searchTerm = '', page = 1) {
    try {
        showLoading();
        const params = new URLSearchParams({
            page: page,
            per_page: 20
        });
        
        if (searchTerm) {
            params.append('search', searchTerm);
        }
        
        const response = await fetch(`${API_BASE}/personas?${params}`);
        const result = await response.json();
        
        displayPersonas(result.data || []);
        displayPersonasPagination(result.meta);
        
    } catch (error) {
        console.error('Error loading personas:', error);
        showError('No se pudieron cargar las personas');
    } finally {
        hideLoading();
    }
}

function displayPersonas(personas) {
    const tbody = document.getElementById('personas-table-body');
    if (!tbody) return;
    
    if (personas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No se encontraron resultados</td></tr>';
        return;
    }
    
    tbody.innerHTML = personas.map(persona => `
        <tr>
            <td>${escapeHtml(persona.nombre)}</td>
            <td>${escapeHtml(persona.edad_rango)}</td>
            <td>
                <span class="badge ${getStatusBadgeClass(persona.estatus)}">
                    ${escapeHtml(persona.estatus)}
                </span>
            </td>
            <td>${formatDate(persona.fecha_ingreso)} ${formatTime(persona.hora_ingreso)}</td>
            <td>${escapeHtml(persona.refugio)}</td>
            <td>${escapeHtml(persona.direccion)}</td>
        </tr>
    `).join('');
}

function displayPersonasPagination(meta) {
    const pagination = document.getElementById('personas-pagination');
    if (!pagination || !meta) return;
    
    let html = '';
    
    // Previous button
    if (meta.has_prev) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadPersonas('${document.getElementById('persona-search').value}', ${meta.current_page - 1})">Anterior</a>
        </li>`;
    }
    
    // Page numbers (show max 5 pages)
    const startPage = Math.max(1, meta.current_page - 2);
    const endPage = Math.min(meta.total_pages, startPage + 4);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === meta.current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadPersonas('${document.getElementById('persona-search').value}', ${i})">${i}</a>
        </li>`;
    }
    
    // Next button
    if (meta.has_next) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadPersonas('${document.getElementById('persona-search').value}', ${meta.current_page + 1})">Siguiente</a>
        </li>`;
    }
    
    pagination.innerHTML = html;
}

async function loadRefugios(searchTerm = '', page = 1) {
    try {
        showLoading();
        const params = new URLSearchParams({
            page: page,
            per_page: 12
        });
        
        if (searchTerm) {
            params.append('search', searchTerm);
        }
        
        const response = await fetch(`${API_BASE}/refugios?${params}`);
        const result = await response.json();
        
        displayRefugios(result.data || []);
        displayRefugiosPagination(result.meta);
        
    } catch (error) {
        console.error('Error loading refugios:', error);
        showError('No se pudieron cargar los refugios');
    } finally {
        hideLoading();
    }
}

function displayRefugios(refugios) {
    const grid = document.getElementById('refugios-grid');
    if (!grid) return;
    
    if (refugios.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center">No se encontraron refugios</div>';
        return;
    }
    
    grid.innerHTML = refugios.map(refugio => `
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">${escapeHtml(refugio.nombre_refugio)}</h5>
                    <p class="card-text">
                        <i class="fas fa-map-marker-alt text-muted me-1"></i>
                        ${escapeHtml(refugio.ubicacion)}
                    </p>
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <small class="text-muted">Capacidad</small>
                            <div class="fw-bold">${refugio.capacidad_maxima}</div>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Ocupados</small>
                            <div class="fw-bold text-primary">${refugio.capacidad_ocupada}</div>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Disponible</small>
                            <div class="fw-bold text-success">${refugio.capacidad_maxima - refugio.capacidad_ocupada}</div>
                        </div>
                    </div>
                    <div class="progress mb-3">
                        <div class="progress-bar ${getCapacityProgressClass(refugio.porcentaje_ocupacion)}" 
                             style="width: ${refugio.porcentaje_ocupacion}%">
                            ${refugio.porcentaje_ocupacion}%
                        </div>
                    </div>
                    <span class="badge ${getStatusBadgeClass(refugio.estado)} mb-2">${refugio.estado}</span>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-grid">
                        <button class="btn btn-outline-primary btn-sm" onclick="downloadRefugioData(${refugio.refugio_id})">
                            <i class="fas fa-download me-1"></i>Descargar CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function displayRefugiosPagination(meta) {
    const pagination = document.getElementById('refugios-pagination');
    if (!pagination || !meta) return;
    
    let html = '';
    
    // Previous button
    if (meta.has_prev) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadRefugios('${document.getElementById('refugio-search').value}', ${meta.current_page - 1})">Anterior</a>
        </li>`;
    }
    
    // Page numbers
    const startPage = Math.max(1, meta.current_page - 2);
    const endPage = Math.min(meta.total_pages, startPage + 4);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === meta.current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadRefugios('${document.getElementById('refugio-search').value}', ${i})">${i}</a>
        </li>`;
    }
    
    // Next button
    if (meta.has_next) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadRefugios('${document.getElementById('refugio-search').value}', ${meta.current_page + 1})">Siguiente</a>
        </li>`;
    }
    
    pagination.innerHTML = html;
}

function downloadRefugioData(refugioId) {
    // This would be implemented in Phase 2
    alert('Funcionalidad de descarga será implementada en la Fase 2');
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES');
}

function formatTime(timeString) {
    if (!timeString) return '';
    return timeString.substring(0, 5); // HH:MM
}

function getStatusBadgeClass(status) {
    switch(status) {
        case 'Alojado':
            return 'bg-success';
        case 'Dado de alta':
            return 'bg-info';
        case 'Trasladado a otro refugio':
            return 'bg-warning';
        case 'Disponible':
            return 'bg-success';
        case 'Completo':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function getCapacityProgressClass(percentage) {
    if (percentage >= 90) return 'bg-danger';
    if (percentage >= 75) return 'bg-warning';
    return 'bg-success';
}

function showLoading() {
    document.getElementById('loading-spinner')?.classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('loading-spinner')?.classList.add('d-none');
}

function showError(message) {
    // Simple error display - could be enhanced with toast notifications
    console.error(message);
}
