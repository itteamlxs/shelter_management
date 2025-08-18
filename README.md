# Sistema de Refugios - Plataforma de Gestión

**Plataforma web segura para gestión de personas albergadas en refugios durante desastres**

## 📋 Información General

- **Base de Datos:** shelter_database_system (existente, no modificar)
- **Backend:** PHP vanilla (compatible XAMPP)
- **Frontend:** HTML + Bootstrap 5 + CSS personalizable
- **Fase Actual:** 0 (Configuración inicial) ✅

## 🚀 Instalación Local (XAMPP)

### Requisitos Previos

- **XAMPP** con PHP 7.4+ y MySQL 5.7+
- **Composer** para gestión de dependencias
- **Git** para control de versiones
- Base de datos **shelter_database_system** ya creada

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   cd C:\xampp\htdocs\
   git clone <repository-url> sistema-refugios
   cd sistema-refugios
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   ```

3. **Configurar variables de entorno**
   ```bash
   # Copiar archivo de ejemplo
   cp .env.example .env

   # Editar .env con tus credenciales
   DB_HOST=localhost
   DB_NAME=shelter_database_system
   DB_USER=tu_usuario_mysql
   DB_PASS=tu_password_mysql
   DB_CHARSET=utf8mb4
   APP_ENV=development
   ```

4. **Verificar conexión a base de datos**
   ```bash
   php test_db.php
   ```

   Deberías ver: ✅ Conexión exitosa a la BD shelter_database_system

5. **Iniciar XAMPP**
   - Activar Apache y MySQL
   - Acceder a: `http://localhost/sistema-refugios`

## 📁 Estructura del Proyecto

```
sistema-refugios/
├── backend/
│   ├── config/
│   │   └── database.php          # Configuración de BD
│   └── models/
│       └── RefugioModel.php      # Modelo para refugios
├── frontend/
│   └── index.html                # Placeholder frontend
├── assets/
│   └── css/
│       └── theme.css             # Tema personalizable
├── config/
│   └── app.php                   # Configuración aplicación
├── vendor/                       # Dependencias Composer
├── .env                          # Variables de entorno
├── index.php                     # Punto de entrada principal
├── test_db.php                   # Test de conexión BD
├── ROADMAP.md                    # Plan de desarrollo por fases
├── TESTS.md                      # Documentación de pruebas
└── README.md                     # Este archivo
```

## 🎨 Personalización de Tema

El sistema incluye un archivo `assets/css/theme.css` donde puedes modificar fácilmente los colores globales:

```css
:root {
    --primary-color: #2563eb;      /* Color principal */
    --secondary-color: #64748b;    /* Color secundario */
    --success-color: #059669;      /* Color de éxito */
    --warning-color: #d97706;      /* Color de advertencia */
    --danger-color: #dc2626;       /* Color de peligro */
    /* ... más variables ... */
}
```

## 🔧 Configuración de Base de Datos

### ⚠️ IMPORTANTE: NO modificar el esquema

La base de datos `shelter_database_system` ya existe con:
- ✅ Todas las tablas creadas
- ✅ Vistas públicas y privadas
- ✅ Stored procedures
- ✅ Triggers y auditoría

### Vistas Principales Utilizadas

- **`vw_public_refugios`**: Catálogo público de refugios
- **`vw_public_estadisticas`**: Métricas agregadas públicas
- **`vw_public_personas`**: Lista pública de personas alojadas
- **`vw_refugio_personas`**: Personas por refugio (privado)
- **`vw_admin_personas_full`**: Vista completa administrativa

### Stored Procedures Principales

- **`sp_registrar_ingreso`**: Registrar nueva persona en refugio
- **`sp_actualizar_estatus_registro`**: Cambiar estatus de alojamiento
- **`sp_estadisticas_refugio`**: Obtener estadísticas de un refugio

## 📊 Estado Actual (Phase 0)

### ✅ Completado

- [x] Estructura base del proyecto
- [x] Conexión segura a base de datos existente
- [x] Modelo básico para consultar vistas públicas
- [x] index.php funcional que muestra conteo de refugios
- [x] Tema CSS personalizable con Bootstrap 5
- [x] Documentación completa (ROADMAP.md, TESTS.md)

### 🔍 Funcionalidades Disponibles

1. **Conexión a BD**: Conecta a shelter_database_system via PDO
2. **Consulta Vistas**: Obtiene datos desde vw_public_refugios y vw_public_estadisticas
3. **Interfaz Básica**: Muestra estadísticas básicas con diseño responsive
4. **Tema Personalizable**: Variables CSS para cambiar colores globalmente

## 🚧 Próximas Fases

### Phase 1: Landing Page Pública (Próximo)
- Hero section con propósito del sistema
- Buscador de personas en tiempo real
- Catálogo de refugios con filtros
- Descarga de datos en CSV

### Phase 2: Panel Administrativo
- Sistema de autenticación por roles
- Panel para refugios (registrar personas)
- Panel administrativo (gestión refugios)
- Panel auditor (logs y reportes)

### Phase 3: Importación CSV
- Carga masiva de datos
- Validación robusta de archivos
- Procesamiento en background

Ver [ROADMAP.md](ROADMAP.md) para detalles completos.

## 🧪 Testing

### Pruebas Manuales Phase 0

1. **Test de Conexión**:
   ```bash
   php test_db.php
   ```

2. **Test de Interfaz**:
   - Acceder a `http://localhost/sistema-refugios`
   - Verificar carga sin errores
   - Confirmar estadísticas mostradas

3. **Test de Tema**:
   - Modificar `--primary-color` en `assets/css/theme.css`
   - Recargar página y verificar cambios

Ver [TESTS.md](TESTS.md) para plan completo de pruebas.

## 🔒 Principios de Seguridad

### Acceso a Base de Datos
- ✅ Solo vistas y stored procedures
- ✅ Prepared statements (PDO)
- ✅ Nunca consultas directas a tablas

### Validación y Sanitización
- ✅ Escape de outputs HTML
- ✅ Validación de inputs
- ✅ Headers de seguridad básicos

### Auditoría
- ✅ Todas las operaciones críticas registradas en AuditLog
- ✅ Trazabilidad completa de cambios

## 📞 Soporte y Desarrollo

### Metodología de Trabajo
1. Desarrollo por fases incrementales
2. Commits atómicos con mensajes claros
3. Testing local en XAMPP entre fases
4. Documentación actualizada en cada fase

### Tecnologías Utilizadas
- **PHP**: Vanilla, sin frameworks (compatible XAMPP)
- **MySQL**: Via PDO, solo vistas y stored procedures
- **HTML/CSS**: Bootstrap 5 + tema personalizable
- **JavaScript**: Vanilla (mínimo necesario)

### Variables de Entorno
```bash
# Base de datos
DB_HOST=localhost
DB_NAME=shelter_database_system
DB_USER=usuario_bd
DB_PASS=password_bd
DB_CHARSET=utf8mb4

# Aplicación
APP_ENV=development
```

---

## 📄 Licencia

Proyecto desarrollado para gestión de refugios durante desastres.

---

## Current Status

✅ **Phase 0 Complete** - Basic foundation and database connectivity established
✅ **Phase 1 Complete** - Public portal with full API and search functionality
🔄 **Phase 2 Ready** - Authentication and private panels

## Quick Start

1. Clone/download this repository to your XAMPP htdocs folder
2. Start XAMPP (Apache + MySQL)
3. Import the existing database `shelter_database_system`
4. Configure `.env` file with your database credentials:
   ```
   DB_HOST=localhost
   DB_NAME=shelter_database_system  
   DB_USER=your_username
   DB_PASS=your_password
   ```
5. Navigate to `http://localhost/your-folder-name/` to see the main interface
6. Access the full portal at `http://localhost/your-folder-name/frontend/index.html`

## Testing

Run the comprehensive test suite:
```bash
php phase1_test.php
```

This will verify all Phase 1 functionality including database connectivity, API endpoints, and file structure.