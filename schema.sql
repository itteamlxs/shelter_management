-- =============================================
-- SISTEMA DE GESTIÓN DE REFUGIOS - SCRIPT COMPLETO
-- Base de datos con seguridad, auditoría y cumplimiento
-- Versión: 1.0.0
-- Fecha: 2025-08-12
-- =============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS shelter_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shelter_management;

-- =============================================
-- TABLAS PRINCIPALES
-- =============================================

-- Tabla de Refugios
CREATE TABLE Refugios (
    refugio_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_refugio VARCHAR(150) NOT NULL,
    ubicacion VARCHAR(200) NOT NULL,
    lat DECIMAL(9,6) NULL,
    lng DECIMAL(9,6) NULL,
    fecha_apertura DATE NOT NULL,
    capacidad_maxima INT NOT NULL,
    capacidad_ocupada INT NOT NULL DEFAULT 0,
    estado ENUM('Disponible','Completo') 
        AS (CASE WHEN capacidad_ocupada >= capacidad_maxima THEN 'Completo' ELSE 'Disponible' END) STORED,
    activo BOOLEAN NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_nombre_refugio (nombre_refugio),
    INDEX idx_refugios_estado (estado),
    INDEX idx_refugios_activo (activo),
    INDEX idx_refugios_coords (lat, lng)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Usuarios
CREATE TABLE Usuarios (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('Administrador','Refugio','Auditor') NOT NULL,
    refugio_id INT NULL,
    nombre_mostrado VARCHAR(150) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login DATETIME NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE SET NULL,
    INDEX idx_usuarios_rol (rol),
    INDEX idx_usuarios_activo (activo),
    INDEX idx_usuarios_refugio (refugio_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Personas
CREATE TABLE Personas (
    persona_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_preferido VARCHAR(150) NOT NULL,
    edad_rango ENUM('Niño/a','Adolescente','Adulto','Adulto mayor') NOT NULL,
    genero ENUM('F','M','Otro','Prefiere no decir') NOT NULL,
    idioma_principal VARCHAR(80) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_personas_nombre (nombre_preferido),
    INDEX idx_personas_edad (edad_rango),
    INDEX idx_personas_genero (genero)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Grupos
CREATE TABLE Grupos (
    grupo_id INT AUTO_INCREMENT PRIMARY KEY,
    jefe_grupo_id INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jefe_grupo_id) REFERENCES Personas(persona_id) ON DELETE RESTRICT,
    INDEX idx_grupos_jefe (jefe_grupo_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Miembros de Grupo
CREATE TABLE GrupoMiembros (
    grupo_id INT NOT NULL,
    persona_id INT NOT NULL,
    relacion ENUM('Jefe','Esposo/a','Hijo/a','Otro') NOT NULL,
    PRIMARY KEY (grupo_id, persona_id),
    FOREIGN KEY (grupo_id) REFERENCES Grupos(grupo_id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE,
    INDEX idx_grupo_miembros_persona (persona_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Información de Salud
CREATE TABLE Salud (
    salud_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE,
    condicion_medica TEXT,
    medicamentos TEXT,
    alergias TEXT,
    asistencia_especial TEXT,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE,
    INDEX idx_salud_persona (persona_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Procedencia
CREATE TABLE Procedencia (
    procedencia_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE,
    localidad VARCHAR(120) NOT NULL,
    situacion ENUM('Vivienda perdida','Temporalmente desplazado') NOT NULL,
    tiene_mascotas BOOLEAN NOT NULL DEFAULT FALSE,
    mascotas_detalle TEXT,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE,
    INDEX idx_procedencia_persona (persona_id),
    INDEX idx_procedencia_localidad (localidad)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Registro de Refugio
CREATE TABLE RegistroRefugio (
    registro_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL,
    refugio_id INT NOT NULL,
    fecha_ingreso DATE NOT NULL,
    hora_ingreso TIME NOT NULL,
    area_asignada VARCHAR(80) NOT NULL,
    estatus ENUM('Alojado','Dado de alta','Trasladado a otro refugio') NOT NULL,
    observaciones TEXT,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE,
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE CASCADE,
    INDEX idx_registro_estatus (estatus),
    INDEX idx_registro_fecha (fecha_ingreso),
    INDEX idx_registro_persona (persona_id),
    INDEX idx_registro_refugio (refugio_id),
    INDEX idx_registro_compuesto (refugio_id, estatus, fecha_ingreso)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Auditoría
CREATE TABLE AuditLog (
    log_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    rol VARCHAR(50) NULL,
    accion VARCHAR(50) NOT NULL,
    objeto VARCHAR(100) NOT NULL,
    objeto_id VARCHAR(100) NULL,
    resumen TEXT,
    payload_hash VARCHAR(128) NULL,
    ip_origen VARCHAR(45) NULL,
    user_agent TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE SET NULL,
    INDEX idx_audit_usuario (usuario_id),
    INDEX idx_audit_accion (accion),
    INDEX idx_audit_objeto (objeto),
    INDEX idx_audit_fecha (creado_en),
    INDEX idx_audit_ip (ip_origen)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Cargas Masivas
CREATE TABLE BulkUploads (
    upload_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    refugio_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    estado ENUM('Pendiente','Validado','Fallido','Procesado') NOT NULL DEFAULT 'Pendiente',
    mensaje TEXT NULL,
    total_filas INT NULL,
    filas_procesadas INT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    procesado_en DATETIME NULL,
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE CASCADE,
    INDEX idx_bulk_refugio (refugio_id),
    INDEX idx_bulk_estado (estado),
    INDEX idx_bulk_fecha (creado_en)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =============================================
-- TABLAS DE AUTENTICACIÓN Y SEGURIDAD
-- =============================================

-- Tabla de Refresh Tokens
CREATE TABLE RefreshTokens (
    token_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_hash VARCHAR(128) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked BOOLEAN NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE CASCADE,
    INDEX idx_refresh_token_hash (token_hash),
    INDEX idx_refresh_expires (expires_at),
    INDEX idx_refresh_usuario (usuario_id),
    INDEX idx_refresh_revoked (revoked)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Intentos de Login
CREATE TABLE LoginAttempts (
    attempt_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    success BOOLEAN NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_username (username),
    INDEX idx_login_ip (ip_address),
    INDEX idx_login_time (attempted_at),
    INDEX idx_login_success (success)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de Configuración del Sistema
CREATE TABLE SystemConfig (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    config_type ENUM('string','integer','boolean','json') NOT NULL DEFAULT 'string',
    description TEXT,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key),
    INDEX idx_config_type (config_type)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =============================================
-- VISTAS DE SEGURIDAD
-- =============================================

-- Vista pública para personas (sin datos sensibles)
CREATE VIEW vw_public_personas AS
SELECT 
    p.persona_id,
    p.nombre_preferido AS nombre,
    p.edad_rango,
    p.genero,
    rr.estatus,
    rr.fecha_ingreso,
    rr.hora_ingreso,
    r.nombre_refugio AS refugio,
    r.ubicacion AS direccion,
    r.refugio_id
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id
WHERE rr.estatus = 'Alojado' AND r.activo = 1;

-- Vista pública para estadísticas agregadas
CREATE VIEW vw_public_estadisticas AS
SELECT 
    COUNT(DISTINCT p.persona_id) AS total_personas,
    SUM(CASE WHEN rr.estatus='Alojado' THEN 1 ELSE 0 END) AS total_alojados,
    SUM(CASE WHEN rr.estatus='Dado de alta' THEN 1 ELSE 0 END) AS total_dados_alta,
    SUM(CASE WHEN rr.estatus='Trasladado a otro refugio' THEN 1 ELSE 0 END) AS total_trasladados,
    COUNT(DISTINCT r.refugio_id) AS total_refugios,
    COALESCE(AVG(r.capacidad_ocupada / r.capacidad_maxima * 100), 0) AS ocupacion_promedio
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id
WHERE r.activo = 1;

-- Vista pública para refugios
CREATE VIEW vw_public_refugios AS
SELECT 
    r.refugio_id,
    r.nombre_refugio,
    r.ubicacion,
    r.lat,
    r.lng,
    r.fecha_apertura,
    r.capacidad_maxima,
    r.capacidad_ocupada,
    r.estado,
    COUNT(rr.registro_id) as total_registros,
    SUM(CASE WHEN rr.estatus = 'Alojado' THEN 1 ELSE 0 END) as personas_actuales,
    SUM(CASE WHEN rr.estatus = 'Dado de alta' THEN 1 ELSE 0 END) as personas_dadas_alta,
    SUM(CASE WHEN rr.estatus = 'Trasladado a otro refugio' THEN 1 ELSE 0 END) as personas_trasladadas
FROM Refugios r
LEFT JOIN RegistroRefugio rr ON r.refugio_id = rr.refugio_id
WHERE r.activo = 1
GROUP BY r.refugio_id, r.nombre_refugio, r.ubicacion, r.lat, r.lng, 
         r.fecha_apertura, r.capacidad_maxima, r.capacidad_ocupada, r.estado;

-- Vista para refugio específico (usada por rol Refugio)
CREATE VIEW vw_refugio_personas AS
SELECT 
    p.persona_id,
    p.nombre_preferido,
    p.edad_rango,
    p.genero,
    p.idioma_principal,
    rr.registro_id,
    rr.refugio_id,
    rr.fecha_ingreso,
    rr.hora_ingreso,
    rr.area_asignada,
    rr.estatus,
    rr.observaciones,
    rr.actualizado_en,
    s.condicion_medica,
    s.medicamentos,
    s.alergias,
    s.asistencia_especial,
    pr.localidad,
    pr.situacion,
    pr.tiene_mascotas,
    pr.mascotas_detalle,
    g.grupo_id,
    gm.relacion
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id
LEFT JOIN Salud s ON p.persona_id = s.persona_id
LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id
LEFT JOIN GrupoMiembros gm ON p.persona_id = gm.persona_id
LEFT JOIN Grupos g ON gm.grupo_id = g.grupo_id
WHERE r.activo = 1;

-- Vista administrativa completa
CREATE VIEW vw_admin_personas_full AS
SELECT 
    p.*,
    rr.registro_id,
    rr.refugio_id,
    rr.fecha_ingreso,
    rr.hora_ingreso,
    rr.area_asignada,
    rr.estatus,
    rr.observaciones,
    rr.creado_en as fecha_registro,
    rr.actualizado_en as fecha_actualizacion,
    s.condicion_medica,
    s.medicamentos,
    s.alergias,
    s.asistencia_especial,
    pr.localidad,
    pr.situacion,
    pr.tiene_mascotas,
    pr.mascotas_detalle,
    r.nombre_refugio,
    r.ubicacion as refugio_ubicacion,
    g.grupo_id,
    gm.relacion
FROM Personas p
LEFT JOIN RegistroRefugio rr ON p.persona_id = rr.persona_id
LEFT JOIN Salud s ON p.persona_id = s.persona_id
LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id
LEFT JOIN Refugios r ON rr.refugio_id = r.refugio_id
LEFT JOIN GrupoMiembros gm ON p.persona_id = gm.persona_id
LEFT JOIN Grupos g ON gm.grupo_id = g.grupo_id
WHERE r.activo = 1 OR r.activo IS NULL;

-- Vista para auditoría
CREATE VIEW vw_auditor_activity AS
SELECT 
    al.log_id,
    al.usuario_id,
    u.username,
    u.nombre_mostrado,
    al.rol,
    al.accion,
    al.objeto,
    al.objeto_id,
    al.resumen,
    al.ip_origen,
    al.user_agent,
    al.creado_en,
    r.nombre_refugio
FROM AuditLog al
LEFT JOIN Usuarios u ON al.usuario_id = u.usuario_id
LEFT JOIN Refugios r ON u.refugio_id = r.refugio_id
ORDER BY al.creado_en DESC;

-- =============================================
-- PROCEDIMIENTOS ALMACENADOS
-- =============================================

DELIMITER //

-- Procedimiento para registrar ingreso de persona
CREATE PROCEDURE sp_registrar_ingreso(
    IN p_persona_id INT,
    IN p_refugio_id INT,
    IN p_fecha_ingreso DATE,
    IN p_hora_ingreso TIME,
    IN p_area VARCHAR(80),
    IN p_estatus ENUM('Alojado','Dado de alta','Trasladado a otro refugio'),
    IN p_observaciones TEXT,
    IN p_usuario_id INT,
    IN p_ip_origen VARCHAR(45)
)
BEGIN
    DECLARE v_cap_actual INT;
    DECLARE v_cap_max INT;
    DECLARE v_registro_id BIGINT;
    DECLARE v_error_msg VARCHAR(255);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        RESIGNAL SET MESSAGE_TEXT = v_error_msg;
    END;

    START TRANSACTION;
    
    -- Verificar capacidad del refugio
    SELECT capacidad_ocupada, capacidad_maxima 
    INTO v_cap_actual, v_cap_max
    FROM Refugios 
    WHERE refugio_id = p_refugio_id AND activo = 1
    FOR UPDATE;

    IF v_cap_actual IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio no encontrado o inactivo';
    END IF;

    IF p_estatus = 'Alojado' AND v_cap_actual >= v_cap_max THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio sin capacidad disponible';
    END IF;

    -- Insertar registro
    INSERT INTO RegistroRefugio(
        persona_id, refugio_id, fecha_ingreso, hora_ingreso, 
        area_asignada, estatus, observaciones
    ) VALUES (
        p_persona_id, p_refugio_id, p_fecha_ingreso, p_hora_ingreso, 
        p_area, p_estatus, p_observaciones
    );
    
    SET v_registro_id = LAST_INSERT_ID();

    -- Actualizar capacidad si es alojamiento
    IF p_estatus = 'Alojado' THEN
        UPDATE Refugios 
        SET capacidad_ocupada = capacidad_ocupada + 1 
        WHERE refugio_id = p_refugio_id;
    END IF;
    
    -- Registrar auditoría
    INSERT INTO AuditLog(
        usuario_id, rol, accion, objeto, objeto_id, 
        resumen, ip_origen, creado_en
    ) VALUES (
        p_usuario_id, 
        (SELECT rol FROM Usuarios WHERE usuario_id = p_usuario_id),
        'INSERT', 
        'RegistroRefugio', 
        v_registro_id, 
        CONCAT('Registro de ingreso - Persona ID: ', p_persona_id, ', Refugio ID: ', p_refugio_id),
        p_ip_origen,
        NOW()
    );

    COMMIT;
    SELECT v_registro_id as registro_id;
END //

-- Procedimiento para crear persona completa
CREATE PROCEDURE sp_crear_persona_completa(
    IN p_nombre_preferido VARCHAR(150),
    IN p_edad_rango ENUM('Niño/a','Adolescente','Adulto','Adulto mayor'),
    IN p_genero ENUM('F','M','Otro','Prefiere no decir'),
    IN p_idioma_principal VARCHAR(80),
    IN p_condicion_medica TEXT,
    IN p_medicamentos TEXT,
    IN p_alergias TEXT,
    IN p_asistencia_especial TEXT,
    IN p_localidad VARCHAR(120),
    IN p_situacion ENUM('Vivienda perdida','Temporalmente desplazado'),
    IN p_tiene_mascotas BOOLEAN,
    IN p_mascotas_detalle TEXT,
    IN p_usuario_id INT,
    IN p_ip_origen VARCHAR(45)
)
BEGIN
    DECLARE v_persona_id INT;
    DECLARE v_error_msg VARCHAR(255);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        RESIGNAL SET MESSAGE_TEXT = v_error_msg;
    END;

    START TRANSACTION;
    
    -- Crear persona
    INSERT INTO Personas(nombre_preferido, edad_rango, genero, idioma_principal)
    VALUES (p_nombre_preferido, p_edad_rango, p_genero, p_idioma_principal);
    
    SET v_persona_id = LAST_INSERT_ID();
    
    -- Crear registro de salud si hay datos
    IF p_condicion_medica IS NOT NULL OR p_medicamentos IS NOT NULL OR 
       p_alergias IS NOT NULL OR p_asistencia_especial IS NOT NULL THEN
        INSERT INTO Salud(persona_id, condicion_medica, medicamentos, alergias, asistencia_especial)
        VALUES (v_persona_id, p_condicion_medica, p_medicamentos, p_alergias, p_asistencia_especial);
    END IF;
    
    -- Crear registro de procedencia
    INSERT INTO Procedencia(persona_id, localidad, situacion, tiene_mascotas, mascotas_detalle)
    VALUES (v_persona_id, p_localidad, p_situacion, p_tiene_mascotas, p_mascotas_detalle);
    
    -- Auditoría
    INSERT INTO AuditLog(
        usuario_id, rol, accion, objeto, objeto_id, 
        resumen, ip_origen, creado_en
    ) VALUES (
        p_usuario_id,
        (SELECT rol FROM Usuarios WHERE usuario_id = p_usuario_id),
        'INSERT',
        'Personas',
        v_persona_id,
        CONCAT('Persona creada: ', p_nombre_preferido),
        p_ip_origen,
        NOW()
    );
    
    COMMIT;
    SELECT v_persona_id as persona_id;
END //

-- Procedimiento para actualizar estatus
CREATE PROCEDURE sp_actualizar_estatus_registro(
    IN p_registro_id BIGINT,
    IN p_nuevo_estatus ENUM('Alojado','Dado de alta','Trasladado a otro refugio'),
    IN p_observaciones TEXT,
    IN p_usuario_id INT,
    IN p_ip_origen VARCHAR(45)
)
BEGIN
    DECLARE v_estatus_anterior ENUM('Alojado','Dado de alta','Trasladado a otro refugio');
    DECLARE v_refugio_id INT;
    DECLARE v_persona_id INT;
    DECLARE v_error_msg VARCHAR(255);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        RESIGNAL SET MESSAGE_TEXT = v_error_msg;
    END;

    START TRANSACTION;
    
    -- Obtener estado actual
    SELECT estatus, refugio_id, persona_id 
    INTO v_estatus_anterior, v_refugio_id, v_persona_id
    FROM RegistroRefugio 
    WHERE registro_id = p_registro_id
    FOR UPDATE;
    
    IF v_estatus_anterior IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Registro no encontrado';
    END IF;
    
    -- Actualizar registro
    UPDATE RegistroRefugio 
    SET estatus = p_nuevo_estatus, 
        observaciones = COALESCE(p_observaciones, observaciones),
        actualizado_en = NOW()
    WHERE registro_id = p_registro_id;
    
    -- Ajustar capacidad del refugio
    IF v_estatus_anterior = 'Alojado' AND p_nuevo_estatus != 'Alojado' THEN
        -- Liberar espacio
        UPDATE Refugios 
        SET capacidad_ocupada = GREATEST(0, capacidad_ocupada - 1)
        WHERE refugio_id = v_refugio_id;
    ELSEIF v_estatus_anterior != 'Alojado' AND p_nuevo_estatus = 'Alojado' THEN
        -- Ocupar espacio (verificar capacidad)
        UPDATE Refugios 
        SET capacidad_ocupada = capacidad_ocupada + 1 
        WHERE refugio_id = v_refugio_id 
        AND capacidad_ocupada < capacidad_maxima;
        
        -- Verificar si se pudo actualizar
        IF ROW_COUNT() = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio sin capacidad disponible';
        END IF;
    END IF;
    
    -- Auditoría
    INSERT INTO AuditLog(
        usuario_id, rol, accion, objeto, objeto_id, 
        resumen, ip_origen, creado_en
    ) VALUES (
        p_usuario_id,
        (SELECT rol FROM Usuarios WHERE usuario_id = p_usuario_id),
        'UPDATE',
        'RegistroRefugio',
        p_registro_id,
        CONCAT('Cambio de estatus de ', v_estatus_anterior, ' a ', p_nuevo_estatus, ' - Persona ID: ', v_persona_id),
        p_ip_origen,
        NOW()
    );
    
    COMMIT;
END //

-- Procedimiento para crear refugio
CREATE PROCEDURE sp_crear_refugio(
    IN p_nombre_refugio VARCHAR(150),
    IN p_ubicacion VARCHAR(200),
    IN p_lat DECIMAL(9,6),
    IN p_lng DECIMAL(9,6),
    IN p_fecha_apertura DATE,
    IN p_capacidad_maxima INT,
    IN p_usuario_id INT,
    IN p_ip_origen VARCHAR(45)
)
BEGIN
    DECLARE v_refugio_id INT;
    DECLARE v_error_msg VARCHAR(255);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        RESIGNAL SET MESSAGE_TEXT = v_error_msg;
    END;

    START TRANSACTION;
    
    -- Validar capacidad mínima
    IF p_capacidad_maxima < 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La capacidad máxima debe ser mayor a 0';
    END IF;
    
    INSERT INTO Refugios(
        nombre_refugio, ubicacion, lat, lng, 
        fecha_apertura, capacidad_maxima
    ) VALUES (
        p_nombre_refugio, p_ubicacion, p_lat, p_lng, 
        p_fecha_apertura, p_capacidad_maxima
    );
    
    SET v_refugio_id = LAST_INSERT_ID();
    
    -- Auditoría
    INSERT INTO AuditLog(
        usuario_id, rol, accion, objeto, objeto_id, 
        resumen, ip_origen, creado_en
    ) VALUES (
        p_usuario_id,
        (SELECT rol FROM Usuarios WHERE usuario_id = p_usuario_id),
        'INSERT',
        'Refugios',
        v_refugio_id,
        CONCAT('Refugio creado: ', p_nombre_refugio),
        p_ip_origen,
        NOW()
    );
    
    COMMIT;
    SELECT v_refugio_id as refugio_id;
END //

DELIMITER ;

-- =============================================
-- CONFIGURACIONES INICIALES
-- =============================================

-- Insertar configuraciones por defecto
INSERT INTO SystemConfig (config_key, config_value, config_type, description) VALUES
('app_name', 'Sistema de Gestión de Refugios', 'string', 'Nombre de la aplicación'),
('app_version', '1.0.0', 'string', 'Versión de la aplicación'),
('max_upload_size', '5242880', 'integer', 'Tamaño máximo de archivo en bytes (5MB)'),
('csv_max_rows', '5000', 'integer', 'Máximo número de filas por CSV'),
('enable_registration', 'false', 'boolean', 'Permitir auto-registro de usuarios'),
('maintenance_mode', 'false', 'boolean', 'Modo de mantenimiento'),
('contact_email', 'admin@refugios.gob', 'string', 'Email de contacto del sistema'),
('privacy_policy_url', '', 'string', 'URL de la política de privacidad'),
('terms_of_service_url', '', 'string', 'URL de términos de servicio'),
('jwt_expiry_minutes', '60', 'integer', 'Tiempo de expiración del JWT en minutos'),
('refresh_token_days', '7', 'integer', 'Tiempo de expiración del refresh token en días'),
('max_login_attempts', '5', 'integer', 'Máximo número de intentos de login'),
('login_cooldown_minutes', '15', 'integer', 'Tiempo de bloqueo tras intentos fallidos');

-- =============================================
-- DATOS DE PRUEBA (SOLO PARA DESARROLLO)
-- =============================================

-- Crear usuario administrador por defecto
-- Password: admin123 (CAMBIAR EN PRODUCCIÓN)
INSERT INTO Usuarios (username, password_hash, rol, nombre_mostrado, activo) VALUES
('admin', '$2y$12$LQv3c1yqBWVHxkd0LQ4cc.93i3ijpvm91Zw3hb8/g4fh.z1mFQqSm', 'Administrador', 'Administrador del Sistema', 1);

-- Crear refugios de ejemplo
INSERT INTO Refugios (nombre_refugio, ubicacion, lat, lng, fecha_apertura, capacidad_maxima) VALUES
('Refugio Central Norte', 'Av. Principal 123, Zona Norte', 19.432608, -99.133209, '2024-01-15', 100),
('Refugio Escuela Sur', 'Calle Secundaria 456, Zona Sur', 19.362608, -99.143209, '2024-01-16', 150),
('Refugio Deportivo Este', 'Complejo Deportivo 789, Zona Este', 19.422608, -99.123209, '2024-01-17', 200);

-- Crear usuarios de refugio de ejemplo
-- Password: refugio123 (CAMBIAR EN PRODUCCIÓN)
INSERT INTO Usuarios (username, password_hash, rol, refugio_id, nombre_mostrado, activo) VALUES
('refugio_norte', '$2y$12$LQv3c1yqBWVHxkd0LQ4cc.93i3ijpvm91Zw3hb8/g4fh.z1mFQqSm', 'Refugio', 1, 'Operador Refugio Norte', 1),
('refugio_sur', '$2y$12$LQv3c1yqBWVHxkd0LQ4cc.93i3ijpvm91Zw3hb8/g4fh.z1mFQqSm', 'Refugio', 2, 'Operador Refugio Sur', 1),
('auditor_general', '$2y$12$LQv3c1yqBWVHxkd0LQ4cc.93i3ijpvm91Zw3hb8/g4fh.z1mFQqSm', 'Auditor', NULL, 'Auditor General', 1);

-- Crear personas de ejemplo
INSERT INTO Personas (nombre_preferido, edad_rango, genero, idioma_principal) VALUES
('María García López', 'Adulto', 'F', 'Español'),
('Carlos López Martínez', 'Adulto', 'M', 'Español'),
('Ana Rodríguez Silva', 'Niño/a', 'F', 'Español'),
('Roberto Silva Hernández', 'Adulto mayor', 'M', 'Español'),
('Carmen Jiménez Torres', 'Adulto', 'F', 'Español'),
('Luis Morales Castro', 'Adolescente', 'M', 'Español'),
('Elena Vargas Ruiz', 'Adulto mayor', 'F', 'Español'),
('Pedro Sandoval Ramos', 'Adulto', 'M', 'Español'),
('Sofia Mendez Cruz', 'Niño/a', 'F', 'Español'),
('Miguel Fernández Ortiz', 'Adulto', 'M', 'Español');

-- Crear registros de procedencia
INSERT INTO Procedencia (persona_id, localidad, situacion, tiene_mascotas, mascotas_detalle) VALUES
(1, 'Colonia Centro', 'Vivienda perdida', 0, NULL),
(2, 'Barrio Industrial', 'Temporalmente desplazado', 1, '1 perro pequeño, raza mestiza'),
(3, 'Colonia Centro', 'Vivienda perdida', 0, NULL),
(4, 'Zona Comercial', 'Vivienda perdida', 1, '2 gatos domésticos'),
(5, 'Fraccionamiento Las Flores', 'Temporalmente desplazado', 0, NULL),
(6, 'Barrio Industrial', 'Temporalmente desplazado', 0, NULL),
(7, 'Colonia Residencial', 'Vivienda perdida', 1, '1 perro grande, labrador'),
(8, 'Zona Rural Norte', 'Vivienda perdida', 1, '3 gallinas, 1 gato'),
(9, 'Colonia Popular', 'Temporalmente desplazado', 0, NULL),
(10, 'Barrio Histórico', 'Vivienda perdida', 0, NULL);

-- Crear algunos registros de salud
INSERT INTO Salud (persona_id, condicion_medica, medicamentos, alergias, asistencia_especial) VALUES
(1, 'Diabetes tipo 2', 'Metformina 500mg cada 12 horas', 'Penicilina', 'Dieta especial baja en azúcar'),
(4, 'Hipertensión arterial', 'Losartán 50mg diario', 'Ninguna conocida', 'Control de presión arterial diario'),
(7, 'Artritis reumatoide', 'Ibuprofeno 400mg cada 8 horas', 'Aspirina', 'Ayuda para movilidad'),
(2, 'Asma bronquial', 'Salbutamol inhalador según necesidad', 'Polen, ácaros', 'Ambiente libre de humo'),
(8, 'Gastritis crónica', 'Omeprazol 20mg diario', 'Ninguna conocida', 'Dieta blanda');

-- Crear grupos familiares
INSERT INTO Grupos (jefe_grupo_id) VALUES (1), (2), (4), (8);

INSERT INTO GrupoMiembros (grupo_id, persona_id, relacion) VALUES
-- Grupo 1: María (jefe) con Ana (hija)
(1, 1, 'Jefe'),
(1, 3, 'Hijo/a'),
-- Grupo 2: Carlos (jefe) con Luis (hijo)
(2, 2, 'Jefe'),
(2, 6, 'Hijo/a'),
-- Grupo 3: Roberto (jefe) con Elena (esposa)
(3, 4, 'Jefe'),
(3, 7, 'Esposo/a'),
-- Grupo 4: Pedro (jefe) con Sofia (hija)
(4, 8, 'Jefe'),
(4, 9, 'Hijo/a');

-- Registrar personas en refugios usando los procedimientos almacenados
-- Refugio Norte (ID: 1)
CALL sp_registrar_ingreso(1, 1, '2024-08-10', '10:30:00', 'Área A-001', 'Alojado', 'Familia con menor - madre diabética', 1, '127.0.0.1');
CALL sp_registrar_ingreso(3, 1, '2024-08-10', '10:30:00', 'Área A-001', 'Alojado', 'Menor acompañando a madre', 1, '127.0.0.1');
CALL sp_registrar_ingreso(2, 1, '2024-08-11', '11:15:00', 'Área A-002', 'Alojado', 'Con mascota - persona con asma', 1, '127.0.0.1');
CALL sp_registrar_ingreso(6, 1, '2024-08-11', '11:15:00', 'Área A-002', 'Alojado', 'Adolescente acompañando a padre', 1, '127.0.0.1');
CALL sp_registrar_ingreso(5, 1, '2024-08-12', '09:45:00', 'Área A-003', 'Alojado', 'Persona sola, desplazamiento temporal', 1, '127.0.0.1');

-- Refugio Sur (ID: 2)
CALL sp_registrar_ingreso(4, 2, '2024-08-11', '14:20:00', 'Área B-001', 'Alojado', 'Adulto mayor hipertenso con mascotas', 2, '127.0.0.1');
CALL sp_registrar_ingreso(7, 2, '2024-08-11', '14:20:00', 'Área B-001', 'Alojado', 'Adulta mayor con artritis, esposa del anterior', 2, '127.0.0.1');
CALL sp_registrar_ingreso(8, 2, '2024-08-12', '16:30:00', 'Área B-002', 'Alojado', 'Con gastritis, trae animales de granja', 2, '127.0.0.1');
CALL sp_registrar_ingreso(9, 2, '2024-08-12', '16:30:00', 'Área B-002', 'Alojado', 'Menor acompañando a padre', 2, '127.0.0.1');

-- Refugio Este (ID: 3)
CALL sp_registrar_ingreso(10, 3, '2024-08-12', '18:00:00', 'Área C-001', 'Alojado', 'Persona sola, vivienda completamente perdida', 3, '127.0.0.1');

-- Simular algunas altas y traslados para tener datos históricos
CALL sp_actualizar_estatus_registro(
    (SELECT registro_id FROM RegistroRefugio WHERE persona_id = 5 ORDER BY registro_id DESC LIMIT 1),
    'Dado de alta',
    'Encontró alojamiento temporal con familiares',
    1,
    '127.0.0.1'
);

-- =============================================
-- USUARIOS Y PERMISOS DE SEGURIDAD
-- =============================================

-- Crear usuarios específicos con privilegios mínimos
-- NOTA: Ejecutar esto después de crear las vistas y procedimientos

-- Usuario para operaciones públicas (solo lectura de vistas públicas)
CREATE USER IF NOT EXISTS 'app_public'@'localhost' IDENTIFIED BY 'secure_public_2024!';
GRANT SELECT ON shelter_management.vw_public_personas TO 'app_public'@'localhost';
GRANT SELECT ON shelter_management.vw_public_estadisticas TO 'app_public'@'localhost';
GRANT SELECT ON shelter_management.vw_public_refugios TO 'app_public'@'localhost';

-- Usuario para operaciones de refugio
CREATE USER IF NOT EXISTS 'app_refugio'@'localhost' IDENTIFIED BY 'secure_refugio_2024!';
GRANT SELECT ON shelter_management.vw_refugio_personas TO 'app_refugio'@'localhost';
GRANT SELECT ON shelter_management.vw_public_refugios TO 'app_refugio'@'localhost';
GRANT EXECUTE ON PROCEDURE shelter_management.sp_registrar_ingreso TO 'app_refugio'@'localhost';
GRANT EXECUTE ON PROCEDURE shelter_management.sp_crear_persona_completa TO 'app_refugio'@'localhost';
GRANT EXECUTE ON PROCEDURE shelter_management.sp_actualizar_estatus_registro TO 'app_refugio'@'localhost';

-- Usuario para operaciones administrativas
CREATE USER IF NOT EXISTS 'app_admin'@'localhost' IDENTIFIED BY 'secure_admin_2024!';
GRANT SELECT ON shelter_management.vw_admin_personas_full TO 'app_admin'@'localhost';
GRANT SELECT ON shelter_management.vw_refugio_personas TO 'app_admin'@'localhost';
GRANT SELECT ON shelter_management.vw_public_refugios TO 'app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE ON shelter_management.Refugios TO 'app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE ON shelter_management.Usuarios TO 'app_admin'@'localhost';
GRANT EXECUTE ON PROCEDURE shelter_management.sp_crear_refugio TO 'app_admin'@'localhost';
GRANT EXECUTE ON PROCEDURE shelter_management.sp_registrar_ingreso TO 'app_admin'@'localhost';
GRANT EXECUTE ON PROCEDURE shelter_management.sp_crear_persona_completa TO 'app_admin'@'localhost';
GRANT EXECUTE ON PROCEDURE shelter_management.sp_actualizar_estatus_registro TO 'app_admin'@'localhost';

-- Usuario para auditoría
CREATE USER IF NOT EXISTS 'app_auditor'@'localhost' IDENTIFIED BY 'secure_auditor_2024!';
GRANT SELECT ON shelter_management.vw_auditor_activity TO 'app_auditor'@'localhost';
GRANT SELECT ON shelter_management.vw_admin_personas_full TO 'app_auditor'@'localhost';
GRANT SELECT ON shelter_management.vw_public_refugios TO 'app_auditor'@'localhost';
GRANT SELECT ON shelter_management.AuditLog TO 'app_auditor'@'localhost';

-- Permisos para autenticación (todos los usuarios de aplicación necesitan esto)
GRANT SELECT ON shelter_management.Usuarios TO 'app_public'@'localhost';
GRANT SELECT ON shelter_management.Usuarios TO 'app_refugio'@'localhost';
GRANT SELECT ON shelter_management.Usuarios TO 'app_admin'@'localhost';
GRANT SELECT ON shelter_management.Usuarios TO 'app_auditor'@'localhost';

GRANT SELECT, INSERT, UPDATE, DELETE ON shelter_management.RefreshTokens TO 'app_public'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON shelter_management.RefreshTokens TO 'app_refugio'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON shelter_management.RefreshTokens TO 'app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON shelter_management.RefreshTokens TO 'app_auditor'@'localhost';

GRANT SELECT, INSERT ON shelter_management.LoginAttempts TO 'app_public'@'localhost';
GRANT SELECT, INSERT ON shelter_management.LoginAttempts TO 'app_refugio'@'localhost';
GRANT SELECT, INSERT ON shelter_management.LoginAttempts TO 'app_admin'@'localhost';
GRANT SELECT, INSERT ON shelter_management.LoginAttempts TO 'app_auditor'@'localhost';

GRANT SELECT, INSERT ON shelter_management.AuditLog TO 'app_refugio'@'localhost';
GRANT SELECT, INSERT ON shelter_management.AuditLog TO 'app_admin'@'localhost';

GRANT SELECT ON shelter_management.SystemConfig TO 'app_public'@'localhost';
GRANT SELECT ON shelter_management.SystemConfig TO 'app_refugio'@'localhost';
GRANT SELECT ON shelter_management.SystemConfig TO 'app_admin'@'localhost';
GRANT SELECT ON shelter_management.SystemConfig TO 'app_auditor'@'localhost';

FLUSH PRIVILEGES;

-- =============================================
-- EVENTOS AUTOMÁTICOS DE LIMPIEZA
-- =============================================

-- Habilitar el programador de eventos
SET GLOBAL event_scheduler = ON;

-- Event para limpiar tokens expirados (ejecutar diariamente a las 2:00 AM)
DELIMITER //
CREATE EVENT IF NOT EXISTS cleanup_expired_tokens
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 2 HOUR)
DO
BEGIN
    -- Limpiar refresh tokens expirados o revocados (más de 1 día)
    DELETE FROM RefreshTokens 
    WHERE expires_at < NOW() OR 
          (revoked = 1 AND creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY));
    
    -- Limpiar intentos de login antiguos (más de 24 horas)
    DELETE FROM LoginAttempts 
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    -- Limpiar logs de auditoría muy antiguos (más de 2 años)
    -- NOTA: Ajustar según política de retención de la organización
    DELETE FROM AuditLog 
    WHERE creado_en < DATE_SUB(NOW(), INTERVAL 2 YEAR);
    
    -- Log de la limpieza
    INSERT INTO AuditLog(usuario_id, rol, accion, objeto, resumen, creado_en)
    VALUES (NULL, 'SYSTEM', 'CLEANUP', 'Database', 'Limpieza automática ejecutada', NOW());
END //
DELIMITER ;

-- Event para actualizar estadísticas de capacidad (ejecutar cada hora)
DELIMITER //
CREATE EVENT IF NOT EXISTS update_refugio_capacity
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    -- Recalcular capacidad ocupada basada en registros actuales
    UPDATE Refugios r SET 
        capacidad_ocupada = (
            SELECT COUNT(*)
            FROM RegistroRefugio rr
            WHERE rr.refugio_id = r.refugio_id 
            AND rr.estatus = 'Alojado'
        )
    WHERE r.activo = 1;
    
    -- Log de la sincronización
    INSERT INTO AuditLog(usuario_id, rol, accion, objeto, resumen, creado_en)
    VALUES (NULL, 'SYSTEM', 'SYNC', 'Refugios', 'Sincronización de capacidad ejecutada', NOW());
END //
DELIMITER ;

-- =============================================
-- TRIGGERS PARA INTEGRIDAD DE DATOS
-- =============================================

-- Trigger para validar capacidad antes de insertar registro
DELIMITER //
CREATE TRIGGER tr_before_insert_registro
BEFORE INSERT ON RegistroRefugio
FOR EACH ROW
BEGIN
    DECLARE v_capacidad_actual INT;
    DECLARE v_capacidad_maxima INT;
    
    IF NEW.estatus = 'Alojado' THEN
        SELECT capacidad_ocupada, capacidad_maxima 
        INTO v_capacidad_actual, v_capacidad_maxima
        FROM Refugios 
        WHERE refugio_id = NEW.refugio_id AND activo = 1;
        
        IF v_capacidad_actual IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio no encontrado o inactivo';
        END IF;
        
        -- Nota: Esta validación es adicional, la principal está en los procedimientos
        IF v_capacidad_actual >= v_capacidad_maxima THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio sin capacidad (trigger validation)';
        END IF;
    END IF;
END //
DELIMITER ;

-- Trigger para prevenir eliminación accidental de refugios con personas
DELIMITER //
CREATE TRIGGER tr_before_delete_refugio
BEFORE DELETE ON Refugios
FOR EACH ROW
BEGIN
    DECLARE v_personas_alojadas INT;
    
    SELECT COUNT(*) INTO v_personas_alojadas
    FROM RegistroRefugio 
    WHERE refugio_id = OLD.refugio_id AND estatus = 'Alojado';
    
    IF v_personas_alojadas > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede eliminar refugio con personas alojadas';
    END IF;
END //
DELIMITER ;

-- =============================================
-- ÍNDICES ADICIONALES PARA RENDIMIENTO
-- =============================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_registro_refugio_estatus_fecha ON RegistroRefugio(refugio_id, estatus, fecha_ingreso);
CREATE INDEX idx_personas_edad_genero ON Personas(edad_rango, genero);
CREATE INDEX idx_audit_usuario_fecha ON AuditLog(usuario_id, creado_en);
CREATE INDEX idx_audit_objeto_fecha ON AuditLog(objeto, creado_en);

-- Índices para búsquedas de texto
CREATE FULLTEXT INDEX idx_personas_nombre_fulltext ON Personas(nombre_preferido);
CREATE FULLTEXT INDEX idx_refugios_nombre_fulltext ON Refugios(nombre_refugio, ubicacion);

-- =============================================
-- VERIFICACIONES FINALES
-- =============================================

-- Verificar que las vistas funcionan correctamente
SELECT 'Verificando vista pública de personas...' as verificacion;
SELECT COUNT(*) as total_personas_publicas FROM vw_public_personas;

SELECT 'Verificando vista pública de estadísticas...' as verificacion;
SELECT * FROM vw_public_estadisticas;

SELECT 'Verificando vista pública de refugios...' as verificacion;
SELECT COUNT(*) as total_refugios_publicos FROM vw_public_refugios;

-- Mostrar resumen de datos creados
SELECT 'RESUMEN DE INSTALACIÓN' as seccion;
SELECT 
    (SELECT COUNT(*) FROM Refugios WHERE activo = 1) as refugios_activos,
    (SELECT COUNT(*) FROM Usuarios WHERE activo = 1) as usuarios_activos,
    (SELECT COUNT(*) FROM Personas) as personas_registradas,
    (SELECT COUNT(*) FROM RegistroRefugio WHERE estatus = 'Alojado') as personas_alojadas,
    (SELECT COUNT(*) FROM vw_public_personas) as personas_visibles_publicamente;

-- Mostrar usuarios por rol
SELECT 'USUARIOS POR ROL' as seccion;
SELECT rol, COUNT(*) as cantidad FROM Usuarios WHERE activo = 1 GROUP BY rol;

-- Mostrar ocupación por refugio
SELECT 'OCUPACIÓN POR REFUGIO' as seccion;
SELECT 
    nombre_refugio,
    capacidad_ocupada,
    capacidad_maxima,
    ROUND((capacidad_ocupada / capacidad_maxima * 100), 2) as porcentaje_ocupacion,
    estado
FROM Refugios 
WHERE activo = 1 
ORDER BY porcentaje_ocupacion DESC;

-- =============================================
-- INFORMACIÓN DE CREDENCIALES (SOLO DESARROLLO)
-- =============================================

SELECT '
=== CREDENCIALES DE ACCESO (SOLO DESARROLLO) ===

USUARIOS WEB:
- admin / admin123 (Administrador)
- refugio_norte / refugio123 (Operador Refugio Norte)
- refugio_sur / refugio123 (Operador Refugio Sur)  
- auditor_general / refugio123 (Auditor)

USUARIOS BASE DE DATOS:
- app_public / secure_public_2024! (Solo vistas públicas)
- app_refugio / secure_refugio_2024! (Operaciones de refugio)
- app_admin / secure_admin_2024! (Operaciones administrativas)
- app_auditor / secure_auditor_2024! (Solo auditoría)

¡IMPORTANTE! Cambiar todas las contraseñas en producción.

' as informacion_credenciales;

-- =============================================
-- FIN DEL SCRIPT
-- =============================================

SELECT '
=== INSTALACIÓN COMPLETADA ===

El sistema de gestión de refugios ha sido instalado exitosamente.

PRÓXIMOS PASOS:
1. Cambiar todas las contraseñas por defecto
2. Configurar variables de entorno para JWT
3. Configurar el sistema de archivos para uploads
4. Implementar el frontend y la API
5. Configurar backups automáticos
6. Realizar pruebas de seguridad

Ver la documentación técnica para más detalles.
' as instalacion_completada;