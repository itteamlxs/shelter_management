# Sistema de Refugios - Roadmap de Desarrollo

## Información General

**Proyecto:** Plataforma de Gestión de Refugios durante Desastres  
**Base de Datos:** shelter_database_system (existente, no modificar)  
**Tecnologías:** PHP vanilla + HTML + Bootstrap + MySQL  
**Metodología:** Desarrollo por fases iterativas  

---

## 📋 Phase 0: Configuración Inicial ✅

**Objetivo:** Establecer estructura base y conexión a BD existente

### Entregables Completados:
- [x] Estructura de carpetas del proyecto
- [x] Configuración de base de datos (backend/config/database.php)
- [x] Modelo base para refugios (RefugioModel.php)
- [x] index.php funcional que consulta vw_public_refugios
- [x] Tema CSS personalizable (assets/css/theme.css)
- [x] Documentación inicial (ROADMAP.md, README.md)

### Alcance Técnico:
- Conexión PDO a shelter_database_system
- Consulta a vistas públicas (vw_public_refugios, vw_public_estadisticas)
- Bootstrap 5 + tema personalizable
- Manejo básico de errores

---

## 🌐 Phase 1: Landing Page Pública (MVP) ✅

**Duración Estimada:** 3-4 semanas  
**Objetivo:** Portal público funcional con información básica

### Entregables:

#### Frontend Público:
- [x] **Hero Section:** Título, propósito, CTAs principales
- [x] **Estadísticas Globales:** Tarjetas con métricas desde vw_public_estadisticas
- [x] **Buscador de Personas:** 
  - Consulta vw_public_personas con debounce (300ms)
  - Filtros: nombre, refugio
  - Paginación y ordenamiento
- [x] **Catálogo de Refugios:**
  - Grid responsive desde vw_public_refugios
  - Filtrado dinámico
  - Indicadores de capacidad
- [x] **Funciones de Descarga:**
  - CSV de personas por refugio
  - PDF básico (opcional en Phase 1)

#### Backend API:
- [x] **Endpoints Públicos:**
  - `GET /api/public/personas` (búsqueda paginada)
  - `GET /api/public/refugios` (listado con filtros)
  - `GET /api/public/statistics` (métricas agregadas)
  - `GET /api/public/download/csv/{refugio_id}`

#### Técnico:
- [x] Rate limiting básico
- [x] Validación y sanitización de inputs
- [x] Logging de errores
- [x] Headers de seguridad básicos

### Criterios de Aceptación:
- Landing page responsive (móvil + desktop)
- Búsqueda funcional con resultados en tiempo real
- Descarga CSV operativa
- Tiempo de respuesta < 2 segundos
- Compatible con XAMPP local

---

## 🔐 Phase 2: Sistema de Autenticación y Panel Privado ✅

**Duración Estimada:** 4-5 semanas  
**Objetivo:** Panel administrativo con roles y gestión básica

### Entregables:

#### Sistema de Autenticación:
- [ ] **Login/Logout:**
  - Autenticación contra tabla Usuarios
  - Sesiones seguras (PHP sessions)
  - Validación de roles (Administrador, Refugio, Auditor)
- [ ] **Gestión de Sesiones:**
  - Timeout automático
  - Protección CSRF
  - Rate limiting en login

#### Panel Refugio:
- [ ] **Dashboard:**
  - Métricas del refugio desde vw_refugio_personas
  - Lista de personas alojadas
  - Indicadores de capacidad
- [ ] **Registro Manual:**
  - Formulario para nueva persona
  - Llamada a sp_registrar_ingreso
  - Validación frontend + backend
- [ ] **Gestión Básica:**
  - Actualizar estatus de personas
  - Ver historial de registros

#### Panel Administrador:
- [ ] **Gestión de Refugios:**
  - Crear/editar refugios (via stored procedures)
  - Monitorear capacidades
- [ ] **Gestión de Usuarios:**
  - Crear usuarios de refugio
  - Asignar permisos

#### Panel Auditor:
- [ ] **Consulta de Logs:**
  - Vista de AuditLog con filtros
  - Reportes básicos de actividad

### Criterios de Aceptación:
- Login seguro con validación de roles
- Formularios funcionales que llaman stored procedures
- Auditoría automática en operaciones críticas
- Interfaz intuitiva y responsive

---

## 📤 Phase 3: Importación Masiva CSV

**Duración Estimada:** 3-4 semanas  
**Objetivo:** Carga masiva de datos con validación robusta

### Entregables:

#### Sistema de Upload:
- [ ] **Interfaz de Carga:**
  - Drag & drop para archivos CSV
  - Vista previa de primeras 10 filas
  - Validación de formato en tiempo real
- [ ] **Procesamiento Background:**
  - Worker PHP para procesar archivos grandes
  - Validación por lotes (dry-run)
  - Reportes de errores detallados

#### Validaciones:
- [ ] **Formato CSV:**
  - Cabeceras exactas en orden correcto
  - Tipos de datos y enumerados
  - Longitud de campos
- [ ] **Lógica de Negocio:**
  - Capacidad de refugios
  - Relaciones de grupos familiares
  - Campos obligatorios

#### Monitoreo:
- [ ] **Estado de Procesamiento:**
  - Tabla BulkUploads para tracking
  - Estados: Pendiente, Validado, Fallido, Procesado
  - Notificaciones de progreso

### Criterios de Aceptación:
- Procesamiento de archivos hasta 5,000 filas
- Validación completa antes de inserción
- Reportes de errores comprensibles
- Rollback automático en caso de fallos

---

## 🗺️ Phase 4: Geolocalización y Mapas

**Duración Estimada:** 2-3 semanas  
**Objetivo:** Funcionalidades de ubicación y visualización

### Entregables:

#### Mapas Públicos:
- [ ] **Mapa Interactivo:**
  - Google Maps o OpenStreetMap
  - Markers por refugio
  - Clustering para múltiples refugios
  - Popups con información básica

#### Geolocalización:
- [ ] **Actualización de Coordenadas:**
  - Botón "Obtener ubicación actual"
  - API de geolocalización del navegador
  - Reverse geocoding para direcciones
  - Actualización via stored procedures

#### Integración:
- [ ] **Landing Page:**
  - Mapa embebido con todos los refugios
  - Filtros geográficos
- [ ] **Panel Refugio:**
  - Actualizar ubicación del refugio
  - Validación de coordenadas

---

## 📊 Phase 5: Reportes y Analíticas Avanzadas

**Duración Estimada:** 3-4 semanas  
**Objetivo:** Sistema completo de reportes y métricas

### Entregables:

#### Reportes Automáticos:
- [ ] **Dashboard Analítico:**
  - Gráficos de ocupación temporal
  - Análisis demográfico
  - Tendencias de altas/traslados
- [ ] **Exportaciones Avanzadas:**
  - PDF con gráficos
  - Excel con múltiples hojas
  - Reportes programados

#### Vistas Especializadas:
- [ ] **Analíticas desde vw_analisis_demografico**
- [ ] **Reportes de salud desde vw_resumen_salud**
- [ ] **Estadísticas temporales desde vw_estadisticas_diarias**

---

## 🔒 Phase 6: Hardening y Cumplimiento

**Duración Estimada:** 2-3 semanas  
**Objetivo:** Seguridad avanzada y cumplimiento normativo

### Entregables:

#### Seguridad:
- [ ] **Headers de Seguridad:**
  - CSP (Content Security Policy)
  - HSTS, X-Frame-Options
  - Validación de inputs robusta
- [ ] **Auditoría Completa:**
  - Logs detallados en AuditLog
  - Monitoreo de accesos
  - Detección de patrones sospechosos

#### Cumplimiento:
- [ ] **Documentación:**
  - Políticas de privacidad
  - Procedimientos de backup
  - Plan de recuperación ante desastres
- [ ] **Testing:**
  - Tests unitarios críticos
  - Tests de integración
  - Pruebas de penetración básicas

---

## 🚀 Phase 7: Optimización y Producción

**Duración Estimada:** 2 semanas  
**Objetivo:** Preparación para entornos de producción

### Entregables:

#### Performance:
- [ ] **Optimización de Consultas:**
  - Índices adicionales si necesarios
  - Cache de consultas frecuentes
  - Compresión de respuestas
- [ ] **Monitoreo:**
  - Métricas de performance
  - Alertas automáticas
  - Dashboard de salud del sistema

#### Deployment:
- [ ] **Configuración de Producción:**
  - Variables de entorno
  - Configuración de Apache/Nginx
  - SSL/TLS
- [ ] **Documentación Final:**
  - Manual de instalación
  - Guía de mantenimiento
  - Troubleshooting

---

## 📅 Timeline Estimado

| Phase | Duración | Acumulado | Entregable Principal |
|-------|----------|-----------|---------------------|
| 0 | 1 semana | 1 semana | Estructura base ✅ |
| 1 | 4 semanas | 5 semanas | Landing pública MVP |
| 2 | 5 semanas | 10 semanas | Panel administrativo |
| 3 | 4 semanas | 14 semanas | Importación CSV |
| 4 | 3 semanas | 17 semanas | Mapas y geolocalización |
| 5 | 4 semanas | 21 semanas | Reportes avanzados |
| 6 | 3 semanas | 24 semanas | Seguridad y cumplimiento |
| 7 | 2 semanas | 26 semanas | Optimización final |

**Total Estimado:** ~6 meses de desarrollo

---

## 🎯 Criterios de Éxito por Phase

### MVP (Phase 1):
- [x] Landing page completamente funcional
- [x] Búsqueda de personas operativa
- [x] Descarga de datos básica
- [x] Compatible con XAMPP

### Producción (Phase 7):
- [ ] Sistema completo desplegado
- [ ] Documentación completa
- [ ] Pruebas de seguridad aprobadas
- [ ] Performance optimizado

---

## 📝 Notas Importantes

1. **Base de Datos:** Nunca modificar esquema existente
2. **Tecnologías:** Solo PHP vanilla, sin frameworks
3. **Vistas y SPs:** Toda interacción DB via procedimientos almacenados
4. **Testing:** Pruebas locales en XAMPP entre cada phase
5. **Commits:** Atómicos y con mensajes descriptivos
6. **Documentación:** Actualizar README.md en cada phase

---

*Roadmap versión 1.0 - Phase 0 completado*