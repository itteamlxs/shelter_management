
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Refugios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/theme.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
            margin-bottom: 0;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 10px;
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Iniciar Sesión</h2>
                <p>Sistema de Gestión de Refugios</p>
            </div>
            
            <form id="loginForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" id="username" class="form-control" required 
                           placeholder="Ingrese su usuario">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" class="form-control" required 
                           placeholder="Ingrese su contraseña">
                </div>
                
                <div id="loginError" class="alert alert-danger d-none"></div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">
                        <span id="loginText">Ingresar</span>
                        <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                    </button>
                </div>
            </form>
            
            <div class="back-link">
                <a href="/">← Volver al inicio</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('loginError');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            // Show loading state
            submitBtn.disabled = true;
            loginText.textContent = 'Ingresando...';
            loginSpinner.classList.remove('d-none');
            errorDiv.classList.add('d-none');
            
            try {
                const response = await fetch('/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Store token and redirect
                    localStorage.setItem('authToken', data.token);
                    window.location.href = '/panel';
                } else {
                    errorDiv.textContent = data.error || 'Error de autenticación';
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Login error:', error);
                errorDiv.textContent = 'Error de conexión. Inténtelo nuevamente.';
                errorDiv.classList.remove('d-none');
            } finally {
                // Reset loading state
                submitBtn.disabled = false;
                loginText.textContent = 'Ingresar';
                loginSpinner.classList.add('d-none');
            }
        });
        
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Check if already logged in
        if (localStorage.getItem('authToken')) {
            window.location.href = '/panel';
        }
    </script>
</body>
</html>
