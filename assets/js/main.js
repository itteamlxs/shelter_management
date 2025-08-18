
// API Base URL
const API_BASE = '/backend/api/public.php';

// Utility functions
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// API calls
async function apiCall(endpoint, options = {}) {
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error(`API call failed for ${endpoint}:`, error);
        throw error;
    }
}

// Load statistics
async function loadStatistics() {
    try {
        const data = await apiCall('/statistics');
        
        if (data.success && data.data) {
            const stats = data.data;
            document.getElementById('total-personas').textContent = stats.total_personas || '0';
            document.getElementById('total-alojados').textContent = stats.total_alojados || '0';
            document.getElementById('total-refugios').textContent = stats.total_refugios || '0';
            document.getElementById('total-dados-alta').textContent = stats.total_dados_alta || '0';
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
        // Set default values on error
        document.getElementById('total-personas').textContent = 'Error';
        document.getElementById('total-alojados').textContent = 'Error';
        document.getElementById('total-refugios').textContent = 'Error';
        document.getElementById('total-dados-alta').textContent = 'Error';
    }
}

// Load refugios for filter
async function loadRefugiosFilter() {
    try {
        const data = await apiCall('/refugios');
        
        if (data.success && data.data) {
            const select = document.getElementById('filter-refugio');
            data.data.forEach(refugio => {
                const option = document.createElement('option');
                option.value = refugio.refugio_id;
                option.textContent = refugio.nombre_refugio;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading refugios for filter:', error);
    }
}

// Load refugios cards
async function loadRefugios() {
    try {
        const data = await apiCall('/refugios');
        
        if (data.success && data.data) {
            const container = document.getElementById('refugios-container');
            container.innerHTML = '';
            
            data.data.forEach(refugio => {
                const col = document.createElement('div');
                col.className = 'col-md-6 col-lg-4 mb-4';
                
                const occupancyPercentage = refugio.porcentaje_ocupacion || 0;
                const statusClass = occupancyPercentage >= 90 ? 'danger' : 
                                  occupancyPercentage >= 70 ? 'warning' : 'success';
                
                col.innerHTML = `
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">${refugio.nombre_refugio}</h5>
                            <p class="card-text">
                                <i class="bi bi-geo-alt text-muted"></i>
                                ${refugio.ubicacion}
                            </p>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Ocupación</span>
                                    <span>${refugio.capacidad_ocupada}/${refugio.capacidad_maxima}</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-${statusClass}" 
                                         style="width: ${occupancyPercentage}%"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-${statusClass}">${refugio.estado}</span>
                                <small class="text-muted">
                                    Apertura: ${new Date(refugio.fecha_apertura).toLocaleDateString()}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
                
                container.appendChild(col);
            });
        }
    } catch (error) {
        console.error('Error loading refugios:', error);
        document.getElementById('refugios-container').innerHTML = 
            '<div class="col-12"><div class="alert alert-danger">Error al cargar los refugios</div></div>';
    }
}

// Search personas
async function searchPersonas() {
    const searchTerm = document.getElementById('search-personas').value;
    const refugioId = document.getElementById('filter-refugio').value;
    
    try {
        let endpoint = '/personas?';
        const params = new URLSearchParams();
        
        if (searchTerm) params.append('search', searchTerm);
        if (refugioId) params.append('refugio_id', refugioId);
        params.append('limit', '20');
        params.append('offset', '0');
        
        const data = await apiCall(`/personas?${params.toString()}`);
        
        if (data.success && data.data) {
            displayPersonasResults(data.data);
        }
    } catch (error) {
        console.error('Error searching personas:', error);
        document.getElementById('personas-results').innerHTML = 
            '<div class="alert alert-danger">Error al buscar personas</div>';
    }
}

// Display personas results
function displayPersonasResults(personas) {
    const container = document.getElementById('personas-results');
    
    if (personas.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No se encontraron personas con los criterios especificados</div>';
        return;
    }
    
    let html = `
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Resultados de la búsqueda (${personas.length})</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Edad</th>
                                <th>Género</th>
                                <th>Estatus</th>
                                <th>Refugio</th>
                                <th>Fecha Ingreso</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    personas.forEach(persona => {
        const statusClass = persona.estatus === 'Alojado' ? 'success' : 
                           persona.estatus === 'Dado de alta' ? 'info' : 'warning';
        
        html += `
            <tr>
                <td><strong>${persona.nombre}</strong></td>
                <td>${persona.edad_rango}</td>
                <td>${persona.genero}</td>
                <td><span class="badge bg-${statusClass}">${persona.estatus}</span></td>
                <td>${persona.refugio}</td>
                <td>${new Date(persona.fecha_ingreso).toLocaleDateString()}</td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data
    loadStatistics();
    loadRefugiosFilter();
    loadRefugios();
    
    // Search functionality with debounce
    const debouncedSearch = debounce(searchPersonas, 300);
    document.getElementById('search-personas').addEventListener('input', debouncedSearch);
    document.getElementById('filter-refugio').addEventListener('change', searchPersonas);
    
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
