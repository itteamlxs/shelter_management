
class PanelApp {
    constructor() {
        this.token = localStorage.getItem('authToken');
        this.user = null;
        this.init();
    }
    
    init() {
        if (this.token) {
            this.validateToken();
        } else {
            window.location.href = '/login';
            return;
        }
        
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Panel-specific event listeners will be added here
    }
    
    async validateToken() {
        try {
            // Parse token to check expiration
            const userData = this.parseJWT(this.token);
            if (!userData || userData.exp < Math.floor(Date.now() / 1000)) {
                this.logout();
                return;
            }
            
            // Test token with a simple API call
            const response = await this.fetch('/refugio/personas');
            if (response.ok) {
                this.user = userData;
                this.showPanel();
            } else {
                this.logout();
            }
        } catch (error) {
            this.logout();
        }
    }
    
    parseJWT(token) {
        try {
            const payload = token.split('.')[1];
            return JSON.parse(atob(payload));
        } catch (error) {
            return null;
        }
    }
    
    logout() {
        localStorage.removeItem('authToken');
        this.token = null;
        this.user = null;
        window.location.href = '/login';
    }
    
    showPanel() {
        document.getElementById('mainPanel').classList.remove('d-none');
        document.getElementById('userInfo').textContent = 
            `${this.user.nombre_mostrado} (${this.user.rol})`;
        
        // Show appropriate dashboard
        this.hideAllDashboards();
        
        switch (this.user.rol) {
            case 'Administrador':
                document.getElementById('adminDashboard').classList.remove('d-none');
                this.loadAdminData();
                break;
            case 'Refugio':
                document.getElementById('refugioDashboard').classList.remove('d-none');
                this.loadRefugioData();
                break;
            case 'Auditor':
                document.getElementById('auditorDashboard').classList.remove('d-none');
                this.loadAuditorData();
                break;
        }
    }
    
    hideAllDashboards() {
        document.getElementById('adminDashboard').classList.add('d-none');
        document.getElementById('refugioDashboard').classList.add('d-none');
        document.getElementById('auditorDashboard').classList.add('d-none');
    }
    
    async fetch(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            }
        };
        
        return fetch(url, { ...defaultOptions, ...options });
    }
    
    async loadAdminData() {
        // Load users and refugios for admin
        console.log('Loading admin data...');
    }
    
    async loadRefugioData() {
        // Load personas for this refugio
        try {
            const response = await this.fetch('/refugio/personas');
            const data = await response.json();
            
            if (data.success) {
                this.renderRefugioPersonas(data.data);
                this.renderRefugioStats(data.stats);
            }
        } catch (error) {
            console.error('Error loading refugio data:', error);
        }
    }
    
    async loadAuditorData() {
        // Load audit logs
        try {
            const response = await this.fetch('/auditor/logs');
            const data = await response.json();
            
            if (data.success) {
                this.renderAuditLogs(data.data);
            }
        } catch (error) {
            console.error('Error loading audit data:', error);
        }
    }
    
    renderRefugioPersonas(personas) {
        const container = document.getElementById('refugioPersonasList');
        
        if (!personas || personas.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay personas registradas</div>';
            return;
        }
        
        let html = `
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Género</th>
                        <th>Fecha Ingreso</th>
                        <th>Área</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        personas.forEach(persona => {
            html += `
                <tr>
                    <td>${this.escapeHtml(persona.nombre_preferido)}</td>
                    <td>${persona.edad_rango}</td>
                    <td>${persona.genero}</td>
                    <td>${persona.fecha_ingreso}</td>
                    <td>${this.escapeHtml(persona.area_asignada)}</td>
                    <td><span class="badge bg-success">${persona.estatus}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="panel.editPersona(${persona.persona_id})">
                            Editar
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    }
    
    renderRefugioStats(stats) {
        const container = document.getElementById('refugioStats');
        
        if (!stats) {
            container.innerHTML = '<div class="text-muted">Cargando estadísticas...</div>';
            return;
        }
        
        container.innerHTML = `
            <div class="row text-center">
                <div class="col-6">
                    <div class="fw-bold text-primary fs-4">${stats.alojados || 0}</div>
                    <small>Alojados</small>
                </div>
                <div class="col-6">
                    <div class="fw-bold text-success fs-4">${stats.dados_alta || 0}</div>
                    <small>Dados de Alta</small>
                </div>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="fw-bold">${stats.capacidad_ocupada || 0}</div>
                    <small>Ocupación</small>
                </div>
                <div class="col-6">
                    <div class="fw-bold">${stats.capacidad_maxima || 0}</div>
                    <small>Capacidad</small>
                </div>
            </div>
        `;
    }
    
    renderAuditLogs(logs) {
        const container = document.getElementById('auditLogsList');
        
        if (!logs || logs.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay registros de auditoría</div>';
            return;
        }
        
        let html = `
            <table class="table table-sm">
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
        `;
        
        logs.forEach(log => {
            html += `
                <tr>
                    <td>${new Date(log.creado_en).toLocaleString()}</td>
                    <td>${this.escapeHtml(log.username || 'Sistema')}</td>
                    <td><span class="badge bg-info">${log.accion}</span></td>
                    <td>${log.objeto}</td>
                    <td>${this.escapeHtml(log.resumen || '')}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    }
    
    showCreatePersonaModal() {
        const modal = this.createModal('createPersonaModal', 'Registrar Nueva Persona', `
            <form id="createPersonaForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nombre Preferido *</label>
                            <input type="text" name="nombre_preferido" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Edad *</label>
                            <select name="edad_rango" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Niño/a">Niño/a</option>
                                <option value="Adolescente">Adolescente</option>
                                <option value="Adulto">Adulto</option>
                                <option value="Adulto mayor">Adulto mayor</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Género *</label>
                            <select name="genero" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="F">Femenino</option>
                                <option value="M">Masculino</option>
                                <option value="Otro">Otro</option>
                                <option value="Prefiere no decir">Prefiere no decir</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Idioma Principal</label>
                            <input type="text" name="idioma_principal" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Localidad *</label>
                            <input type="text" name="localidad" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Situación *</label>
                            <select name="situacion" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Vivienda perdida">Vivienda perdida</option>
                                <option value="Temporalmente desplazado">Temporalmente desplazado</option>
                                <option value="Evacuación preventiva">Evacuación preventiva</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Área Asignada *</label>
                            <input type="text" name="area_asignada" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tiene Mascotas</label>
                            <select name="tiene_mascotas" class="form-control">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"></textarea>
                </div>
            </form>
        `, [
            { text: 'Cancelar', class: 'btn-secondary', action: 'close' },
            { text: 'Registrar', class: 'btn-primary', action: 'submit', form: 'createPersonaForm' }
        ]);
        
        document.getElementById('createPersonaForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createPersona(new FormData(e.target));
        });
    }
    
    async createPersona(formData) {
        try {
            const data = Object.fromEntries(formData);
            
            const response = await this.fetch('/refugio/personas', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('createPersonaModal')).hide();
                this.loadRefugioData(); // Refresh data
                this.showAlert('Persona registrada exitosamente', 'success');
            } else {
                this.showAlert(result.error || 'Error al registrar persona', 'danger');
            }
        } catch (error) {
            this.showAlert('Error de conexión', 'danger');
        }
    }
    
    async updateLocation() {
        if (!navigator.geolocation) {
            this.showAlert('Geolocalización no disponible', 'warning');
            return;
        }
        
        navigator.geolocation.getCurrentPosition(async (position) => {
            try {
                const response = await this.fetch('/refugio/geolocate', {
                    method: 'POST',
                    body: JSON.stringify({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showAlert('Ubicación actualizada exitosamente', 'success');
                } else {
                    this.showAlert('Error al actualizar ubicación', 'danger');
                }
            } catch (error) {
                this.showAlert('Error de conexión', 'danger');
            }
        }, () => {
            this.showAlert('No se pudo obtener la ubicación', 'warning');
        });
    }
    
    createModal(id, title, body, buttons = []) {
        const modalHtml = `
            <div class="modal fade" id="${id}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${body}
                        </div>
                        <div class="modal-footer">
                            ${buttons.map(btn => `
                                <button type="${btn.action === 'submit' ? 'submit' : 'button'}" 
                                        class="btn ${btn.class}"
                                        ${btn.action === 'close' ? 'data-bs-dismiss="modal"' : ''}
                                        ${btn.form ? `form="${btn.form}"` : ''}>
                                    ${btn.text}
                                </button>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('dynamicModals').innerHTML = modalHtml;
        const modal = new bootstrap.Modal(document.getElementById(id));
        modal.show();
        
        return modal;
    }
    
    showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert at the top of the main panel
        const mainPanel = document.getElementById('mainPanel');
        const firstChild = mainPanel.firstElementChild;
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = alertHtml;
        mainPanel.insertBefore(tempDiv.firstChild, firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            const alert = mainPanel.querySelector('.alert');
            if (alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize panel
const panel = new PanelApp();
