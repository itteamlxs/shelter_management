
# Sistema de Refugios - Plan de Pruebas

## 📋 Información General

**Objetivo:** Documentar pruebas manuales y automáticas para cada fase del desarrollo  
**Entorno de Pruebas:** XAMPP local + shelter_database_system  
**Metodología:** Testing incremental por fase  

---

## ✅ Phase 0: Configuración Inicial

### Pruebas de Configuración

#### Test 1: Conexión a Base de Datos
**Objetivo:** Verificar conexión exitosa a shelter_database_system

**Pasos:**
1. Configurar .env con credenciales de BD
2. Ejecutar `php test_db.php`
3. Verificar mensaje "✅ Conexión exitosa"
4. Confirmar listado de tablas del schema

**Criterios de Aceptación:**
- [x] Conexión PDO establecida sin errores
- [x] Listado de tablas muestra estructura completa
- [x] No hay warnings de PHP

#### Test 2: Estructura de Proyecto
**Objetivo:** Validar organización de archivos

**Pasos:**
1. Verificar carpetas: `frontend/`, `backend/`, `assets/`, `config/`
2. Confirmar archivos principales: `index.php`, `ROADMAP.md`, `README.md`
3. Validar carga de autoloader de Composer

**Criterios de Aceptación:**
- [x] Estructura de carpetas correcta
- [x] Archivos principales existentes
- [x] Composer autoload funcional

#### Test 3: Consulta a Vistas Públicas
**Objetivo:** Verificar acceso a vistas del sistema existente

**Pasos:**
1. Acceder a `http://localhost/index.php`
2. Verificar carga de estadísticas desde vw_public_estadisticas
3. Verificar conteo desde vw_public_refugios
4. Confirmar datos mostrados sin errores

**Criterios de Aceptación:**
- [x] index.php carga sin errores
- [x] Estadísticas mostradas correctamente
- [x] Conteo de refugios preciso
- [x] CSS Bootstrap aplicado

#### Test 4: Tema CSS Personalizable
**Objetivo:** Verificar funcionamiento del sistema de temas

**Pasos:**
1. Modificar variables CSS en `assets/css/theme.css`
2. Cambiar `--primary-color` de #2563eb a #dc2626
3. Recargar página y verificar cambios
4. Restaurar color original

**Criterios de Aceptación:**
- [x] Variables CSS aplicadas correctamente
- [x] Cambios reflejados en UI
- [x] Sin conflictos con Bootstrap

---

## 🌐 Phase 1: Landing Page Pública (Planificado)

### Pruebas Funcionales

#### Test 1.1: Hero Section
**Objetivo:** Verificar sección principal de landing

**Pasos:**
1. Acceder a landing page
2. Verificar título y subtítulo
3. Comprobar CTAs principales funcionan
4. Test responsivo (móvil/tablet/desktop)

**Criterios de Aceptación:**
- [ ] Hero visible en todas las resoluciones
- [ ] CTAs llevan a secciones correctas
- [ ] Texto alineado y legible

#### Test 1.2: Buscador de Personas
**Objetivo:** Validar búsqueda en tiempo real

**Pasos:**
1. Escribir nombre en buscador
2. Verificar debounce de 300ms
3. Comprobar resultados desde vw_public_personas
4. Probar filtros por refugio
5. Verificar paginación

**Criterios de Aceptación:**
- [ ] Búsqueda responde después de 300ms
- [ ] Resultados precisos y formateados
- [ ] Paginación funcional
- [ ] Filtros aplicados correctamente

#### Test 1.3: Catálogo de Refugios
**Objetivo:** Verificar listado y filtrado de refugios

**Pasos:**
1. Verificar grid de refugios desde vw_public_refugios
2. Probar filtros dinámicos
3. Verificar indicadores de capacidad
4. Comprobar links a descarga CSV

**Criterios de Aceptación:**
- [ ] Grid responsive
- [ ] Filtros dinámicos operativos
- [ ] Indicadores precisos
- [ ] Descargas funcionan

#### Test 1.4: Descargas CSV
**Objetivo:** Validar exportación de datos

**Pasos:**
1. Seleccionar refugio
2. Hacer clic en "Descargar CSV"
3. Verificar archivo descargado
4. Validar formato y contenido

**Criterios de Aceptación:**
- [ ] Archivo CSV válido
- [ ] Datos correctos del refugio
- [ ] Headers apropiados
- [ ] Sin datos sensibles

### Pruebas de Performance

#### Test 1.5: Tiempo de Respuesta
**Objetivo:** Verificar performance aceptable

**Pasos:**
1. Medir tiempo de carga inicial
2. Medir tiempo de búsqueda
3. Medir tiempo de descarga CSV
4. Probar con múltiples usuarios simultáneos

**Criterios de Aceptación:**
- [ ] Carga inicial < 2 segundos
- [ ] Búsqueda < 1 segundo
- [ ] Descarga CSV < 5 segundos
- [ ] Estable con 10 usuarios simultáneos

### Pruebas de Seguridad

#### Test 1.6: Validación de Inputs
**Objetivo:** Verificar sanitización de entradas

**Pasos:**
1. Probar inyección SQL en buscador
2. Intentar XSS en campos de búsqueda
3. Verificar rate limiting en búsquedas
4. Probar caracteres especiales

**Criterios de Aceptación:**
- [ ] Prepared statements evitan SQL injection
- [ ] Escape correcto previene XSS
- [ ] Rate limiting activo
- [ ] Caracteres especiales manejados

---

## 🔐 Phase 2: Panel Privado (Planificado)

### Pruebas de Autenticación

#### Test 2.1: Sistema de Login
**Objetivo:** Verificar autenticación segura

**Pasos:**
1. Probar login con credenciales válidas
2. Probar login con credenciales inválidas
3. Verificar timeout de sesión
4. Probar diferentes roles (Admin, Refugio, Auditor)

**Criterios de Aceptación:**
- [ ] Login exitoso con credenciales válidas
- [ ] Rechazo de credenciales inválidas
- [ ] Sesiones expiran correctamente
- [ ] Roles aplicados apropiadamente

#### Test 2.2: Protección CSRF
**Objetivo:** Verificar tokens CSRF en formularios

**Pasos:**
1. Inspeccionar formularios por tokens CSRF
2. Intentar submit sin token válido
3. Verificar regeneración de tokens
4. Probar ataques CSRF simulados

**Criterios de Aceptación:**
- [ ] Todos los formularios tienen tokens
- [ ] Submits sin token son rechazados
- [ ] Tokens se regeneran correctamente
- [ ] CSRF bloqueado efectivamente

### Pruebas de Panel Refugio

#### Test 2.3: Registro Manual de Personas
**Objetivo:** Verificar formulario de registro

**Pasos:**
1. Llenar formulario de nueva persona
2. Verificar validación frontend
3. Confirmar llamada a sp_registrar_ingreso
4. Verificar actualización de capacidad

**Criterios de Aceptación:**
- [ ] Formulario valida correctamente
- [ ] Stored procedure ejecutado
- [ ] Capacidad actualizada
- [ ] Registro en AuditLog

#### Test 2.4: Dashboard de Refugio
**Objetivo:** Verificar métricas y listados

**Pasos:**
1. Acceder a dashboard de refugio
2. Verificar métricas desde vw_refugio_personas
3. Comprobar lista de personas alojadas
4. Verificar indicadores de capacidad

**Criterios de Aceptación:**
- [ ] Métricas precisas
- [ ] Lista filtrada por refugio
- [ ] Indicadores actualizados
- [ ] Interfaz responsive

### Pruebas de Panel Administrador

#### Test 2.5: Gestión de Refugios
**Objetivo:** Verificar CRUD de refugios

**Pasos:**
1. Crear nuevo refugio
2. Editar refugio existente
3. Verificar validaciones
4. Confirmar stored procedures

**Criterios de Aceptación:**
- [ ] Creación exitosa
- [ ] Edición funcional
- [ ] Validaciones activas
- [ ] SPs ejecutados correctamente

---

## 📤 Phase 3: Importación CSV (Planificado)

### Pruebas de Upload

#### Test 3.1: Validación de Formato
**Objetivo:** Verificar validación de archivos CSV

**Pasos:**
1. Subir CSV con formato correcto
2. Subir CSV con cabeceras incorrectas
3. Subir archivo no-CSV
4. Subir CSV con datos inválidos

**Criterios de Aceptación:**
- [ ] CSV válido aceptado
- [ ] CSV inválido rechazado
- [ ] Archivos no-CSV rechazados
- [ ] Errores reportados claramente

#### Test 3.2: Procesamiento por Lotes
**Objetivo:** Verificar procesamiento background

**Pasos:**
1. Subir CSV con 1000 filas
2. Verificar estado en BulkUploads
3. Monitorear progreso
4. Verificar inserción final

**Criterios de Aceptación:**
- [ ] Archivo procesado en background
- [ ] Estados actualizados correctamente
- [ ] Todas las filas válidas insertadas
- [ ] Errores registrados

### Pruebas de Validación

#### Test 3.3: Validación de Datos
**Objetivo:** Verificar validaciones de negocio

**Pasos:**
1. CSV con capacidad de refugio excedida
2. CSV con relaciones de grupo inválidas
3. CSV con campos obligatorios vacíos
4. CSV con fechas inválidas

**Criterios de Aceptación:**
- [ ] Capacidad validada
- [ ] Relaciones de grupo verificadas
- [ ] Campos obligatorios validados
- [ ] Fechas parseadas correctamente

---

## 🗺️ Phase 4: Geolocalización (Planificado)

### Pruebas de Mapas

#### Test 4.1: Mapa Público
**Objetivo:** Verificar visualización de refugios

**Pasos:**
1. Cargar mapa en landing page
2. Verificar markers de refugios
3. Probar clustering
4. Verificar popups informativos

**Criterios de Aceptación:**
- [ ] Mapa carga correctamente
- [ ] Markers en posiciones correctas
- [ ] Clustering funcional
- [ ] Popups con información precisa

#### Test 4.2: Geolocalización
**Objetivo:** Verificar actualización de coordenadas

**Pasos:**
1. Hacer clic en "Obtener ubicación"
2. Permitir acceso a geolocalización
3. Verificar coordenadas obtenidas
4. Confirmar actualización en BD

**Criterios de Aceptación:**
- [ ] Permiso solicitado correctamente
- [ ] Coordenadas precisas
- [ ] BD actualizada via SP
- [ ] Reverse geocoding funcional

---

## 📊 Phase 5: Reportes (Planificado)

### Pruebas de Analíticas

#### Test 5.1: Dashboard Analítico
**Objetivo:** Verificar gráficos y métricas

**Pasos:**
1. Acceder a dashboard analítico
2. Verificar gráficos de ocupación
3. Comprobar análisis demográfico
4. Validar exportaciones avanzadas

**Criterios de Aceptación:**
- [ ] Gráficos renderizan correctamente
- [ ] Datos precisos
- [ ] Exportaciones funcionales
- [ ] Performance aceptable

---

## 🔒 Phase 6: Seguridad (Planificado)

### Pruebas de Penetración

#### Test 6.1: Headers de Seguridad
**Objetivo:** Verificar headers implementados

**Pasos:**
1. Inspeccionar headers HTTP
2. Verificar CSP
3. Comprobar HSTS
4. Validar X-Frame-Options

**Criterios de Aceptación:**
- [ ] Todos los headers presentes
- [ ] CSP configurado correctamente
- [ ] HSTS activo
- [ ] X-Frame-Options protege contra clickjacking

#### Test 6.2: Auditoría Completa
**Objetivo:** Verificar logging de auditoría

**Pasos:**
1. Realizar operaciones críticas
2. Verificar entradas en AuditLog
3. Comprobar integridad de logs
4. Validar detección de anomalías

**Criterios de Aceptación:**
- [ ] Todas las operaciones loggeadas
- [ ] Logs íntegros y detallados
- [ ] Anomalías detectadas
- [ ] Reportes de auditoría generados

---

## 🚀 Phase 7: Producción (Planificado)

### Pruebas de Performance

#### Test 7.1: Carga y Estrés
**Objetivo:** Verificar performance bajo carga

**Pasos:**
1. Simular 100 usuarios concurrentes
2. Medir tiempos de respuesta
3. Verificar uso de memoria
4. Comprobar estabilidad

**Criterios de Aceptación:**
- [ ] Sistema estable con 100 usuarios
- [ ] Tiempos de respuesta < 2s
- [ ] Uso de memoria controlado
- [ ] Sin memory leaks

### Pruebas de Deployment

#### Test 7.2: Configuración de Producción
**Objetivo:** Verificar configuración para producción

**Pasos:**
1. Configurar variables de entorno
2. Verificar SSL/TLS
3. Comprobar permisos de archivos
4. Validar backups automáticos

**Criterios de Aceptación:**
- [ ] Variables configuradas
- [ ] SSL funcionando
- [ ] Permisos seguros
- [ ] Backups operativos

---

## 📝 Metodología de Testing

### Registro de Resultados
- [ ] Cada test documentado con resultado (PASS/FAIL)
- [ ] Screenshots para tests de UI
- [ ] Logs de errores adjuntados
- [ ] Tiempo de ejecución registrado

### Criterios de Aprobación
- **Phase 0:** 100% de tests críticos PASS
- **Phases siguientes:** 95% de tests funcionales PASS
- **Performance:** Todos los benchmarks cumplidos
- **Seguridad:** 100% de tests de seguridad PASS

### Herramientas Sugeridas
- **Manual Testing:** Navegadores múltiples (Chrome, Firefox, Safari)
- **Performance:** Apache Bench (ab), Lighthouse
- **Seguridad:** OWASP ZAP, manual testing
- **Database:** phpMyAdmin para verificaciones

---

*Plan de Pruebas versión 1.0 - Phase 0 documentado*
