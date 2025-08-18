
/**
 * Upload Page JavaScript
 * Sistema de Refugios - Phase 3
 */

let currentUser = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
    loadRefugios();
    loadUploadHistory();
    setupEventListeners();
});

/**
 * Check if user is authenticated
 */
async function checkAuthentication() {
    try {
        const response = await fetch('/backend/api/auth.php/me');
        const data = await response.json();
        
        if (data.success && data.user) {
            currentUser = data.user;
            
            // Hide refugio selector for Refugio role users
            if (data.user.rol === 'Refugio') {
                const refugioSelect = document.getElementById('refugio_id');
                refugioSelect.value = data.user.refugio_id;
                refugioSelect.disabled = true;
                refugioSelect.closest('.mb-3').style.display = 'none';
            }
        } else {
            window.location.href = '/login.html';
        }
    } catch (error) {
        console.error('Authentication check failed:', error);
        window.location.href = '/login.html';
    }
}

/**
 * Load CSRF token
 */
async function loadCSRFToken() {
    try {
        const response = await fetch('/backend/api/auth.php/csrf-token');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('csrf_token').value = data.token;
        }
    } catch (error) {
        console.error('Error loading CSRF token:', error);
    }
}

/**
 * Load refugios for selection
 */
async function loadRefugios() {
    if (currentUser && currentUser.rol === 'Refugio') {
        return; // Skip for refugio users
    }
    
    try {
        const response = await fetch('/backend/api/public.php/refugios');
        const data = await response.json();
        
        if (data.success && data.data) {
            const select = document.getElementById('refugio_id');
            select.innerHTML = '<option value="">Seleccionar refugio...</option>';
            
            data.data.forEach(refugio => {
                const option = document.createElement('option');
                option.value = refugio.id;
                option.textContent = refugio.nombre;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading refugios:', error);
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    const form = document.getElementById('uploadForm');
    const fileInput = document.getElementById('csv_file');
    
    form.addEventListener('submit', handleUpload);
    
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            validateFile(file);
        }
    });
    
    // Load CSRF token when form is first interacted with
    form.addEventListener('focus', loadCSRFToken, { once: true });
}

/**
 * Validate uploaded file
 */
function validateFile(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['text/csv', 'application/csv'];
    
    if (file.size > maxSize) {
        showError('El archivo es demasiado grande. Máximo 5MB.');
        document.getElementById('csv_file').value = '';
        return false;
    }
    
    if (!file.name.toLowerCase().endsWith('.csv')) {
        showError('Solo se permiten archivos CSV.');
        document.getElementById('csv_file').value = '';
        return false;
    }
    
    return true;
}

/**
 * Handle form upload
 */
async function handleUpload(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const uploadBtn = document.getElementById('uploadBtn');
    const spinner = document.getElementById('uploadSpinner');
    const progressContainer = document.getElementById('progressContainer');
    const resultsContainer = document.getElementById('resultsContainer');
    
    // Validate form
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    // Show progress
    uploadBtn.disabled = true;
    spinner.classList.remove('d-none');
    progressContainer.classList.remove('d-none');
    resultsContainer.classList.add('d-none');
    
    updateProgress(0, 'Subiendo archivo...');
    
    try {
        const response = await fetch('/backend/api/upload.php', {
            method: 'POST',
            body: formData
        });
        
        updateProgress(50, 'Procesando datos...');
        
        const data = await response.json();
        
        updateProgress(100, 'Completado');
        
        if (data.success) {
            showSuccess(data.message, data.data);
            form.reset();
            loadUploadHistory(); // Refresh history
        } else {
            showError(data.error, data.details);
        }
        
    } catch (error) {
        console.error('Upload error:', error);
        showError('Error de conexión. Intente nuevamente.');
    } finally {
        uploadBtn.disabled = false;
        spinner.classList.add('d-none');
        
        // Hide progress after a delay
        setTimeout(() => {
            progressContainer.classList.add('d-none');
        }, 2000);
    }
}

/**
 * Update progress bar
 */
function updateProgress(percent, text) {
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    progressBar.style.width = percent + '%';
    progressBar.setAttribute('aria-valuenow', percent);
    progressText.textContent = text;
}

/**
 * Show success message
 */
function showSuccess(message, data) {
    const resultsContainer = document.getElementById('resultsContainer');
    const resultsAlert = document.getElementById('resultsAlert');
    const resultsContent = document.getElementById('resultsContent');
    
    resultsAlert.className = 'alert alert-success';
    
    let html = `<strong>✓ ${message}</strong>`;
    
    if (data && data.stats) {
        html += `
            <div class="mt-2">
                <div class="row text-center">
                    <div class="col">
                        <div class="fw-bold text-primary">${data.stats.processed}</div>
                        <small>Procesados</small>
                    </div>
                    <div class="col">
                        <div class="fw-bold text-success">${data.stats.successful}</div>
                        <small>Exitosos</small>
                    </div>
                    <div class="col">
                        <div class="fw-bold text-danger">${data.stats.errors}</div>
                        <small>Errores</small>
                    </div>
                </div>
            </div>
        `;
        
        if (data.stats.error_details && data.stats.error_details.length > 0) {
            html += `
                <div class="mt-3">
                    <h6>Detalles de errores:</h6>
                    <ul class="list-unstyled">
                        ${data.stats.error_details.slice(0, 10).map(error => `<li><small>${error}</small></li>`).join('')}
                    </ul>
                    ${data.stats.error_details.length > 10 ? `<small class="text-muted">... y ${data.stats.error_details.length - 10} más</small>` : ''}
                </div>
            `;
        }
    }
    
    resultsContent.innerHTML = html;
    resultsContainer.classList.remove('d-none');
}

/**
 * Show error message
 */
function showError(message, details) {
    const resultsContainer = document.getElementById('resultsContainer');
    const resultsAlert = document.getElementById('resultsAlert');
    const resultsContent = document.getElementById('resultsContent');
    
    resultsAlert.className = 'alert alert-danger';
    
    let html = `<strong>✗ ${message}</strong>`;
    
    if (details) {
        if (Array.isArray(details)) {
            html += `
                <div class="mt-2">
                    <ul class="list-unstyled mb-0">
                        ${details.slice(0, 10).map(detail => `<li><small>${detail}</small></li>`).join('')}
                    </ul>
                    ${details.length > 10 ? `<small class="text-muted">... y ${details.length - 10} más</small>` : ''}
                </div>
            `;
        } else {
            html += `<div class="mt-2"><small>${details}</small></div>`;
        }
    }
    
    resultsContent.innerHTML = html;
    resultsContainer.classList.remove('d-none');
}

/**
 * Load upload history
 */
async function loadUploadHistory() {
    try {
        const response = await fetch('/backend/api/private.php/upload/history');
        const data = await response.json();
        
        if (data.success && data.data) {
            displayUploadHistory(data.data);
        } else {
            document.getElementById('uploadHistory').innerHTML = 
                '<p class="text-muted">No hay historial de subidas disponible.</p>';
        }
    } catch (error) {
        console.error('Error loading upload history:', error);
        document.getElementById('uploadHistory').innerHTML = 
            '<p class="text-muted">Error cargando historial.</p>';
    }
}

/**
 * Display upload history
 */
function displayUploadHistory(uploads) {
    const container = document.getElementById('uploadHistory');
    
    if (uploads.length === 0) {
        container.innerHTML = '<p class="text-muted">No hay subidas registradas.</p>';
        return;
    }
    
    const html = `
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Refugio</th>
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
 * Get status badge class
 */
function getStatusBadgeClass(status) {
    switch (status) {
        case 'COMPLETED': return 'success';
        case 'ERROR': return 'danger';
        case 'PROCESSING': return 'warning';
        default: return 'secondary';
    }
}

/**
 * Get status text
 */
function getStatusText(status) {
    switch (status) {
        case 'COMPLETED': return 'Completado';
        case 'ERROR': return 'Error';
        case 'PROCESSING': return 'Procesando';
        default: return status;
    }
}

/**
 * Format date
 */
function formatDate(dateString) {
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
 * Download CSV template
 */
async function downloadTemplate() {
    try {
        const response = await fetch('/backend/api/upload.php/template');
        const data = await response.json();
        
        if (data.success) {
            // Create CSV content
            const csvContent = [
                data.headers.join(','),
                data.sample.map(field => `"${field}"`).join(',')
            ].join('\n');
            
            // Download file
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'plantilla_refugios.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    } catch (error) {
        console.error('Error downloading template:', error);
        alert('Error al descargar la plantilla.');
    }
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
