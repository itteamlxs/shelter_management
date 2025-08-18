
# Sistema de Refugios - Plataforma de Gestión

Plataforma web segura para registrar, gestionar y publicar información no sensible sobre personas albergadas en refugios durante desastres.

## Características

- **Portal Público**: Búsqueda de personas, estadísticas, catálogo de refugios
- **Panel Privado**: Gestión por roles (Administrador, Refugio, Auditor)
- **Importación CSV**: Carga masiva de datos con validación
- **Auditoría Completa**: Registro de todas las operaciones críticas
- **Geolocalización**: Actualización de coordenadas de refugios
- **Exportaciones**: Descarga de datos en CSV y PDF

## Arquitectura Técnica

- **Backend**: PHP 8+ con PDO
- **Frontend**: HTML5 + Bootstrap 5 + Vanilla JavaScript
- **Base de Datos**: MySQL 8+ con vistas y procedimientos almacenados
- **Autenticación**: JWT con roles
- **Seguridad**: Preparadas consultas, validación estricta, auditoría

## Instalación

### Prerrequisitos

- PHP 8.0+
- MySQL 8.0+
- Composer
- Servidor web (Apache/Nginx)

### Configuración

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd refugios-platform
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar base de datos**
- Importar el schema desde `storage/schema.sql`
- Configurar variables de entorno en `.env`

4. **Probar conexión**
```bash
php test_db.php
```

5. **Configurar servidor web**
- Apuntar DocumentRoot a la carpeta del proyecto
- Configurar URL rewriting para el router

### Variables de Entorno

Copiar `.env` y configurar:

```env
DB_HOST=localhost
DB_NAME=refugios_db
DB_USER=root
DB_PASS=password
DB_PORT=3306
JWT_SECRET=your-super-secret-key
```

## Uso

### Portal Público

Acceder a `/` para ver:
- Estadísticas globales
- Búsqueda de personas alojadas
- Catálogo de refugios
- Descargas de datos

### Panel Privado

Acceder a `/panel` con credenciales:

**Roles disponibles:**
- `Administrador`: Gestión completa del sistema
- `Refugio`: Gestión de personas en su refugio
- `Auditor`: Acceso de solo lectura a logs

### API Endpoints

#### Públicos
- `GET /public/statistics` - Estadísticas globales
- `GET /public/personas` - Lista de personas alojadas
- `GET /public/refugios` - Catálogo de refugios
- `GET /public/refugios/{id}/download` - Descarga de datos

#### Privados
- `POST /auth/login` - Autenticación
- `GET /refugio/personas` - Personas del refugio
- `POST /refugio/personas` - Registrar persona
- `POST /refugio/upload-csv` - Subir CSV
- `GET /auditor/logs` - Logs de auditoría

## Estructura del Proyecto

```
/
├── api/                 # Endpoints de la API
│   ├── public/         # Endpoints públicos
│   ├── auth/           # Autenticación
│   ├── refugio/        # Funciones de refugio
│   └── auditor/        # Funciones de auditoría
├── assets/             # CSS y JavaScript
├── config/             # Configuración
├── storage/            # Schema de base de datos
├── views/              # Plantillas HTML
├── .env                # Variables de entorno
├── composer.json       # Dependencias PHP
└── index.php           # Punto de entrada
```

## Seguridad

### Principios Implementados

- **Privilegio Mínimo**: Acceso solo a vistas y procedimientos
- **Defensa en Profundidad**: Validación frontend + backend
- **Trazabilidad**: Auditoría de todas las operaciones
- **Privacidad**: Sin datos sensibles en logs públicos

### Medidas de Protección

- Consultas preparadas para prevenir SQL injection
- Validación estricta de entrada
- Tokens JWT con expiración
- Escape de salida HTML
- Rate limiting en endpoints sensibles
- Validación de archivos CSV

## Formato CSV

El sistema acepta archivos CSV con el siguiente formato (orden exacto):

1. `nombre_preferido` - Nombre de la persona (requerido)
2. `edad_rango` - Niño/a, Adolescente, Adulto, Adulto mayor (requerido)
3. `genero` - F, M, Otro, Prefiere no decir (requerido)
4. `idioma_principal` - Idioma principal (opcional)
5. `grupo_jefe` - TRUE/FALSE si es jefe de grupo
6. `grupo_id_externo` - ID externo para agrupar familiares
7. `relacion_con_jefe` - Relación familiar
8. `condicion_medica` - Condiciones médicas
9. `medicamentos` - Medicamentos requeridos
10. `alergias` - Alergias conocidas
11. `asistencia_especial` - Necesidades especiales
12. `localidad` - Lugar de origen (requerido)
13. `situacion` - Vivienda perdida, Temporalmente desplazado, Evacuación preventiva (requerido)
14. `tiene_mascotas` - TRUE/FALSE
15. `mascotas_detalle` - Detalles de mascotas
16. `fecha_ingreso` - YYYY-MM-DD
17. `hora_ingreso` - HH:MM:SS
18. `area_asignada` - Área dentro del refugio (requerido)
19. `estatus` - Alojado, Dado de alta, Trasladado a otro refugio

## Auditoría y Logs

El sistema registra automáticamente:
- Inicios de sesión exitosos y fallidos
- Creación y modificación de personas
- Subidas de archivos CSV
- Cambios en configuración de refugios
- Acceso a datos sensibles

## Monitoreo

Métricas disponibles:
- Total de personas registradas
- Personas actualmente alojadas
- Capacidad de refugios
- Actividad de usuarios
- Errores de sistema

## Desarrollo

### Roadmap Implementado

✅ **Fase 1**: Infraestructura básica
- Conexión a base de datos
- Sistema de autenticación
- Router y estructura básica

✅ **Fase 2**: Portal público
- Landing page con estadísticas
- Búsqueda de personas
- Catálogo de refugios
- Descargas

✅ **Fase 3**: Panel privado
- Dashboard por roles
- Formularios de registro
- Carga de CSV
- Auditoría

### Próximas Mejoras

- [ ] Integración con mapas avanzados
- [ ] Notificaciones en tiempo real
- [ ] Reportes avanzados
- [ ] API móvil
- [ ] Exportación PDF mejorada

## Soporte

Para reportar problemas o solicitar funciones:
- Crear issue en el repositorio
- Contactar al equipo de desarrollo
- Revisar logs de auditoría para diagnóstico

## Licencia

Este proyecto está desarrollado para uso gubernamental en gestión de emergencias.
