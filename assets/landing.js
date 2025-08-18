
class LandingApp {
    constructor() {
        this.personasPage = 1;
        this.refugiosPage = 1;
        this.debounceTimer = null;
        this.init();
    }
    
    init() {
        this.loadStatistics();
        this.loadPersonas();
        this.loadRefugios();
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Person search with debounce
        document.getElementById('search-personas').addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.personasPage = 1;
                this.loadPersonas();
            }, 300);
        });
        
        // Refugio filter
        document.getElementById('filter-refugio').addEventListener('change', () => {
            this.personasPage = 1;
            this.loadPersonas();
        });
        
        // Refugio search with debounce
        document.getElementById('search-refugios').addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.refugiosPage = 1;
                this.loadRefugios();
            }, 300);
        });
    }
    
    async loadStatistics() {
        try {
            const response = await fetch('/public/statistics');
            const data = await response.json();
            
            if (data.success) {
                const stats = data.data;
                document.getElementById('total-personas').textContent = stats.total_personas || 0;
                document.getElementById('total-alojados').textContent = stats.total_alojados || 0;
                document.getElementById('total-dados-alta').textContent = stats.total_dados_alta || 0;
                document.getElementById('total-refugios').textContent = stats.total_refugios || 0;
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }
    
    async loadPersonas() {
        const search = document.getElementById('search-personas').value;
        const refugio = document.getElementById('filter-refugio').value;
        
        const params = new URLSearchParams({
            page: this.personasPage,
            per_page: 10
        });
        
        if (search) params.append('search', search);
        if (refugio) params.append('refugio', refugio);
        
        try {
            const response = await fetch(`/public/personas?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderPersonas(data.data);
                this.renderPersonasPagination(data.meta);
            }
        } catch (error) {
            console.error('Error loading personas:', error);
            document.getElementById('personas-results').innerHTML = 
                '<div class="alert alert-danger">Error al cargar los datos</div>';
        }
    }
    
    renderPersonas(personas) {
        const container = document.getElementById('personas-results');
        
        if (personas.length === 0) {
            container.innerHTML = '<div class="alert alert-warning">No se encontraron resultados</div>';
            return;
        }
        
        let html = `
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Refugio</th>
                        <th>Ubicación</th>
                        <th>Fecha Ingreso</th>
                        <th>Hora Ingreso</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        personas.forEach(persona => {
            html += `
                <tr>
                    <td>${this.escapeHtml(persona.nombre)}</td>
                    <td>${this.escapeHtml(persona.refugio)}</td>
                    <td>${this.escapeHtml(persona.direccion)}</td>
                    <td>${persona.fecha_ingreso}</td>
                    <td>${persona.hora_ingreso}</td>
                    <td><span class="badge bg-success">${persona.estatus}</span></td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    }
    
    renderPersonasPagination(meta) {
        const container = document.getElementById('personas-pagination');
        
        if (meta.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Previous button
        if (meta.current_page > 1) {
            html += `<button onclick="app.goToPersonasPage(${meta.current_page - 1})">Anterior</button>`;
        }
        
        // Page numbers
        for (let i = Math.max(1, meta.current_page - 2); 
             i <= Math.min(meta.total_pages, meta.current_page + 2); i++) {
            const active = i === meta.current_page ? 'active' : '';
            html += `<button class="${active}" onclick="app.goToPersonasPage(${i})">${i}</button>`;
        }
        
        // Next button
        if (meta.current_page < meta.total_pages) {
            html += `<button onclick="app.goToPersonasPage(${meta.current_page + 1})">Siguiente</button>`;
        }
        
        container.innerHTML = html;
    }
    
    goToPersonasPage(page) {
        this.personasPage = page;
        this.loadPersonas();
    }
    
    async loadRefugios() {
        const search = document.getElementById('search-refugios').value;
        
        const params = new URLSearchParams({
            page: this.refugiosPage,
            per_page: 12
        });
        
        if (search) params.append('search', search);
        
        try {
            const response = await fetch(`/public/refugios?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderRefugios(data.data);
                this.renderRefugiosPagination(data.meta);
                this.populateRefugioFilter(data.all_refugios || []);
            }
        } catch (error) {
            console.error('Error loading refugios:', error);
            document.getElementById('refugios-grid').innerHTML = 
                '<div class="alert alert-danger">Error al cargar los refugios</div>';
        }
    }
    
    renderRefugios(refugios) {
        const container = document.getElementById('refugios-grid');
        
        if (refugios.length === 0) {
            container.innerHTML = '<div class="alert alert-warning">No se encontraron refugios</div>';
            return;
        }
        
        let html = '';
        refugios.forEach(refugio => {
            const ocupacion = refugio.capacidad_maxima > 0 ? 
                ((refugio.capacidad_ocupada / refugio.capacidad_maxima) * 100).toFixed(1) : 0;
            
            html += `
                <div class="card">
                    <h5>${this.escapeHtml(refugio.nombre_refugio)}</h5>
                    <p class="text-muted">${this.escapeHtml(refugio.ubicacion)}</p>
                    
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="fw-bold text-primary">${refugio.personas_alojadas}</div>
                            <small>Alojados</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-success">${refugio.personas_dadas_alta}</div>
                            <small>Dados de Alta</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">${ocupacion}%</div>
                            <small>Ocupación</small>
                        </div>
                    </div>
                    
                    <div class="progress mb-3">
                        <div class="progress-bar ${ocupacion >= 90 ? 'bg-danger' : ocupacion >= 70 ? 'bg-warning' : 'bg-success'}" 
                             style="width: ${ocupacion}%"></div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="/public/refugios/${refugio.refugio_id}/download?format=csv" 
                           class="btn btn-sm btn-outline-primary">Descargar CSV</a>
                        <a href="/public/refugios/${refugio.refugio_id}/download?format=pdf" 
                           class="btn btn-sm btn-outline-secondary">Descargar PDF</a>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    renderRefugiosPagination(meta) {
        const container = document.getElementById('refugios-pagination');
        
        if (meta.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '';
        
        if (meta.current_page > 1) {
            html += `<button onclick="app.goToRefugiosPage(${meta.current_page - 1})">Anterior</button>`;
        }
        
        for (let i = Math.max(1, meta.current_page - 2); 
             i <= Math.min(meta.total_pages, meta.current_page + 2); i++) {
            const active = i === meta.current_page ? 'active' : '';
            html += `<button class="${active}" onclick="app.goToRefugiosPage(${i})">${i}</button>`;
        }
        
        if (meta.current_page < meta.total_pages) {
            html += `<button onclick="app.goToRefugiosPage(${meta.current_page + 1})">Siguiente</button>`;
        }
        
        container.innerHTML = html;
    }
    
    goToRefugiosPage(page) {
        this.refugiosPage = page;
        this.loadRefugios();
    }
    
    populateRefugioFilter(refugios) {
        const select = document.getElementById('filter-refugio');
        const currentValue = select.value;
        
        select.innerHTML = '<option value="">Todos los refugios</option>';
        
        refugios.forEach(refugio => {
            const option = document.createElement('option');
            option.value = refugio.refugio_id;
            option.textContent = refugio.nombre_refugio;
            if (option.value === currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize app
const app = new LandingApp();
