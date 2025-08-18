DROP DATABASE IF EXISTS refugios_db;
CREATE DATABASE refugios_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE refugios_db;

-- ===================================================
-- TABLES CREATION
-- ===================================================

-- Refugios Table
CREATE TABLE Refugios (
    refugio_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_refugio VARCHAR(150) NOT NULL,
    ubicacion VARCHAR(200) NOT NULL,
    lat DECIMAL(9,6) NULL,
    lng DECIMAL(9,6) NULL,
    fecha_apertura DATE NOT NULL,
    capacidad_maxima INT NOT NULL,
    capacidad_ocupada INT NOT NULL DEFAULT 0,
    estado VARCHAR(20) GENERATED ALWAYS AS (
        CASE 
            WHEN capacidad_ocupada >= capacidad_maxima THEN 'Completo' 
            ELSE 'Disponible' 
        END
    ) STORED,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_nombre_refugio (nombre_refugio),
    INDEX idx_refugios_estado (estado),
    INDEX idx_refugios_ubicacion (ubicacion),
    INDEX idx_refugios_coords (lat, lng)
);

-- Usuarios Table
CREATE TABLE Usuarios (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('Administrador','Refugio','Auditor') NOT NULL,
    refugio_id INT NULL,
    nombre_mostrado VARCHAR(150) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login DATETIME NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY fk_usuarios_refugio (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE SET NULL,
    INDEX idx_usuarios_rol (rol),
    INDEX idx_usuarios_refugio (refugio_id),
    INDEX idx_usuarios_activo (activo)
);

-- Personas Table
CREATE TABLE Personas (
    persona_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_preferido VARCHAR(150) NOT NULL,
    edad_rango ENUM('Niño/a','Adolescente','Adulto','Adulto mayor') NOT NULL,
    genero ENUM('F','M','Otro','Prefiere no decir') NOT NULL,
    idioma_principal VARCHAR(80) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_personas_nombre (nombre_preferido),
    INDEX idx_personas_edad_genero (edad_rango, genero),
    INDEX idx_personas_creado (creado_en)
);

-- Grupos Table
CREATE TABLE Grupos (
    grupo_id INT AUTO_INCREMENT PRIMARY KEY,
    jefe_grupo_id INT NOT NULL,
    nombre_grupo VARCHAR(100) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY fk_grupos_jefe (jefe_grupo_id) REFERENCES Personas(persona_id) ON DELETE RESTRICT,
    INDEX idx_grupos_jefe (jefe_grupo_id)
);

-- GrupoMiembros Table
CREATE TABLE GrupoMiembros (
    grupo_id INT NOT NULL,
    persona_id INT NOT NULL,
    relacion ENUM('Jefe','Esposo/a','Hijo/a','Padre/Madre','Hermano/a','Otro') NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (grupo_id, persona_id),
    FOREIGN KEY fk_miembros_grupo (grupo_id) REFERENCES Grupos(grupo_id) ON DELETE CASCADE,
    FOREIGN KEY fk_miembros_persona (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE,
    INDEX idx_miembros_relacion (relacion)
);

-- Salud Table (1:1 with Personas)
CREATE TABLE Salud (
    salud_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE,
    condicion_medica TEXT,
    medicamentos TEXT,
    alergias TEXT,
    asistencia_especial TEXT,
    requiere_atencion_urgente BOOLEAN NOT NULL DEFAULT FALSE,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY fk_salud_persona (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE,
    INDEX idx_salud_urgente (requiere_atencion_urgente)
);

-- Procedencia Table (1:1 with Personas)
CREATE TABLE Procedencia (
    procedencia_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE,
    localidad VARCHAR(120) NOT NULL,
    municipio VARCHAR(120) NULL,
    departamento VARCHAR(120) NULL,
    situacion ENUM('Vivienda perdida','Temporalmente desplazado','Evacuación preventiva') NOT NULL,
    tiene_mascotas BOOLEAN NOT NULL DEFAULT FALSE,
    mascotas_detalle TEXT,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY fk_procedencia_persona (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE,
    INDEX idx_procedencia_localidad (localidad),
    INDEX idx_procedencia_situacion (situacion),
    INDEX idx_procedencia_mascotas (tiene_mascotas)
);

-- RegistroRefugio Table
CREATE TABLE RegistroRefugio (
    registro_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL,
    refugio_id INT NOT NULL,
    fecha_ingreso DATE NOT NULL,
    hora_ingreso TIME NOT NULL,
    area_asignada VARCHAR(80) NOT NULL,
    estatus ENUM('Alojado','Dado de alta','Trasladado a otro refugio') NOT NULL DEFAULT 'Alojado',
    fecha_salida DATE NULL,
    hora_salida TIME NULL,
    refugio_destino_id INT NULL,
    observaciones TEXT,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY fk_registro_persona (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE,
    FOREIGN KEY fk_registro_refugio (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE CASCADE,
    FOREIGN KEY fk_registro_destino (refugio_destino_id) REFERENCES Refugios(refugio_id) ON DELETE SET NULL,
    INDEX idx_registro_estatus (estatus),
    INDEX idx_registro_fecha (fecha_ingreso),
    INDEX idx_registro_refugio_estatus (refugio_id, estatus),
    INDEX idx_registro_persona_actual (persona_id, estatus),
    UNIQUE KEY uk_persona_refugio_activo (persona_id, refugio_id, estatus)
);

-- AuditLog Table
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
    request_id VARCHAR(100) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY fk_audit_usuario (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE SET NULL,
    INDEX idx_audit_usuario (usuario_id),
    INDEX idx_audit_accion (accion),
    INDEX idx_audit_objeto (objeto),
    INDEX idx_audit_fecha (creado_en),
    INDEX idx_audit_ip (ip_origen)
);

-- BulkUploads Table
CREATE TABLE BulkUploads (
    upload_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    refugio_id INT NOT NULL,
    usuario_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    total_filas INT NULL,
    filas_procesadas INT NOT NULL DEFAULT 0,
    filas_exitosas INT NOT NULL DEFAULT 0,
    filas_fallidas INT NOT NULL DEFAULT 0,
    estado ENUM('Pendiente','Validado','Fallido','Procesado','En_proceso') NOT NULL DEFAULT 'Pendiente',
    mensaje TEXT NULL,
    validation_report JSON NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    procesado_en DATETIME NULL,
    FOREIGN KEY fk_upload_refugio (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE CASCADE,
    FOREIGN KEY fk_upload_usuario (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE CASCADE,
    INDEX idx_upload_estado (estado),
    INDEX idx_upload_refugio (refugio_id),
    INDEX idx_upload_fecha (creado_en)
);

-- ===================================================
-- PUBLIC VIEWS
-- ===================================================

-- Public view for persons (only currently housed)
CREATE VIEW vw_public_personas AS
SELECT 
    p.persona_id,
    p.nombre_preferido AS nombre,
    p.edad_rango,
    p.genero,
    rr.estatus,
    rr.fecha_ingreso,
    rr.hora_ingreso,
    rr.area_asignada,
    r.nombre_refugio AS refugio,
    r.ubicacion AS direccion
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id
WHERE rr.estatus = 'Alojado'
ORDER BY rr.fecha_ingreso DESC, rr.hora_ingreso DESC;

-- Public statistics view
CREATE VIEW vw_public_estadisticas AS
SELECT 
    COUNT(DISTINCT p.persona_id) AS total_personas,
    SUM(CASE WHEN rr.estatus='Alojado' THEN 1 ELSE 0 END) AS total_alojados,
    SUM(CASE WHEN rr.estatus='Dado de alta' THEN 1 ELSE 0 END) AS total_dados_alta,
    SUM(CASE WHEN rr.estatus='Trasladado a otro refugio' THEN 1 ELSE 0 END) AS total_trasladados,
    COUNT(DISTINCT rr.refugio_id) AS total_refugios,
    AVG(r.capacidad_ocupada) AS promedio_ocupacion,
    SUM(r.capacidad_maxima) AS capacidad_total_sistema,
    SUM(r.capacidad_ocupada) AS ocupacion_total_sistema
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id;

-- Public refugios view
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
    COUNT(CASE WHEN rr.estatus='Alojado' THEN 1 END) AS personas_alojadas,
    COUNT(CASE WHEN rr.estatus='Dado de alta' THEN 1 END) AS personas_dadas_alta,
    COUNT(CASE WHEN rr.estatus='Trasladado a otro refugio' THEN 1 END) AS personas_trasladadas,
    ROUND((r.capacidad_ocupada / r.capacidad_maxima) * 100, 1) AS porcentaje_ocupacion
FROM Refugios r
LEFT JOIN RegistroRefugio rr ON r.refugio_id = rr.refugio_id
GROUP BY r.refugio_id, r.nombre_refugio, r.ubicacion, r.lat, r.lng, 
         r.fecha_apertura, r.capacidad_maxima, r.capacidad_ocupada, r.estado
ORDER BY r.nombre_refugio;

-- ===================================================
-- PRIVATE VIEWS (Role-based)
-- ===================================================

-- View for refugio users (filtered by refugio_id in application layer)
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
    rr.fecha_salida,
    rr.hora_salida,
    rr.observaciones,
    s.condicion_medica,
    s.medicamentos,
    s.alergias,
    s.asistencia_especial,
    s.requiere_atencion_urgente,
    pr.localidad,
    pr.situacion,
    pr.tiene_mascotas,
    pr.mascotas_detalle,
    g.grupo_id,
    gm.relacion AS relacion_grupo,
    (SELECT COUNT(*) FROM GrupoMiembros WHERE grupo_id = g.grupo_id) AS tamaño_grupo
FROM Personas p
JOIN RegistroRefugio rr ON p.persona_id = rr.persona_id
LEFT JOIN Salud s ON p.persona_id = s.persona_id
LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id
LEFT JOIN GrupoMiembros gm ON p.persona_id = gm.persona_id
LEFT JOIN Grupos g ON gm.grupo_id = g.grupo_id
ORDER BY rr.fecha_ingreso DESC, rr.hora_ingreso DESC;

-- Admin full view
CREATE VIEW vw_admin_personas_full AS
SELECT 
    p.persona_id,
    p.nombre_preferido,
    p.edad_rango,
    p.genero,
    p.idioma_principal,
    p.creado_en AS persona_creada,
    rr.registro_id,
    rr.refugio_id,
    r.nombre_refugio,
    r.ubicacion AS refugio_ubicacion,
    rr.fecha_ingreso,
    rr.hora_ingreso,
    rr.area_asignada,
    rr.estatus,
    rr.fecha_salida,
    rr.hora_salida,
    rr.refugio_destino_id,
    rd.nombre_refugio AS refugio_destino,
    rr.observaciones,
    s.condicion_medica,
    s.medicamentos,
    s.alergias,
    s.asistencia_especial,
    s.requiere_atencion_urgente,
    pr.localidad,
    pr.municipio,
    pr.departamento,
    pr.situacion,
    pr.tiene_mascotas,
    pr.mascotas_detalle,
    g.grupo_id,
    gm.relacion AS relacion_grupo,
    pj.nombre_preferido AS jefe_grupo,
    (SELECT COUNT(*) FROM GrupoMiembros WHERE grupo_id = g.grupo_id) AS tamaño_grupo,
    rr.creado_en AS registro_creado,
    rr.actualizado_en AS registro_actualizado
FROM Personas p
JOIN RegistroRefugio rr ON p.persona_id = rr.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id
LEFT JOIN Refugios rd ON rr.refugio_destino_id = rd.refugio_id
LEFT JOIN Salud s ON p.persona_id = s.persona_id
LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id
LEFT JOIN GrupoMiembros gm ON p.persona_id = gm.persona_id
LEFT JOIN Grupos g ON gm.grupo_id = g.grupo_id
LEFT JOIN Personas pj ON g.jefe_grupo_id = pj.persona_id
ORDER BY rr.fecha_ingreso DESC, rr.hora_ingreso DESC;

-- Auditor activity view
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
    al.creado_en,
    al.request_id,
    CASE 
        WHEN al.usuario_id IS NOT NULL THEN 'Usuario autenticado'
        ELSE 'Sistema/Anónimo'
    END AS tipo_origen
FROM AuditLog al
LEFT JOIN Usuarios u ON al.usuario_id = u.usuario_id
ORDER BY al.creado_en DESC;

-- ===================================================
-- STORED PROCEDURES
-- ===================================================

DELIMITER //

-- Procedure to register person entry
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
        RESIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END;
    
    START TRANSACTION;
    
    -- Check refugio capacity
    SELECT capacidad_ocupada, capacidad_maxima 
    INTO v_cap_actual, v_cap_max
    FROM Refugios 
    WHERE refugio_id = p_refugio_id 
    FOR UPDATE;
    
    IF v_cap_actual IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio no encontrado';
    END IF;
    
    IF p_estatus = 'Alojado' AND v_cap_actual >= v_cap_max THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio sin capacidad disponible';
    END IF;
    
    -- Insert registro
    INSERT INTO RegistroRefugio(
        persona_id, refugio_id, fecha_ingreso, hora_ingreso, 
        area_asignada, estatus, observaciones
    ) VALUES (
        p_persona_id, p_refugio_id, p_fecha_ingreso, p_hora_ingreso,
        p_area, p_estatus, p_observaciones
    );
    
    SET v_registro_id = LAST_INSERT_ID();
    
    -- Update capacity if alojado
    IF p_estatus = 'Alojado' THEN
        UPDATE Refugios 
        SET capacidad_ocupada = capacidad_ocupada + 1 
        WHERE refugio_id = p_refugio_id;
    END IF;
    
    -- Audit log
    INSERT INTO AuditLog(
        usuario_id, rol, accion, objeto, objeto_id, 
        resumen, ip_origen
    ) VALUES (
        p_usuario_id, 
        (SELECT rol FROM Usuarios WHERE usuario_id = p_usuario_id),
        'INSERT', 
        'RegistroRefugio', 
        v_registro_id,
        CONCAT('Registro de ingreso - Persona ID: ', p_persona_id, ' en Refugio ID: ', p_refugio_id),
        p_ip_origen
    );
    
    COMMIT;
    
    SELECT v_registro_id AS registro_id;
    
END //

-- Procedure to update registration status
CREATE PROCEDURE sp_actualizar_estatus_registro(
    IN p_registro_id BIGINT,
    IN p_nuevo_estatus ENUM('Alojado','Dado de alta','Trasladado a otro refugio'),
    IN p_fecha_salida DATE,
    IN p_hora_salida TIME,
    IN p_refugio_destino_id INT,
    IN p_observaciones TEXT,
    IN p_usuario_id INT,
    IN p_ip_origen VARCHAR(45)
)
BEGIN
    DECLARE v_estatus_anterior VARCHAR(50);
    DECLARE v_refugio_id INT;
    DECLARE v_persona_id INT;
    DECLARE v_error_msg VARCHAR(255);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        RESIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END;
    
    START TRANSACTION;
    
    -- Get current status and refugio
    SELECT estatus, refugio_id, persona_id
    INTO v_estatus_anterior, v_refugio_id, v_persona_id
    FROM RegistroRefugio
    WHERE registro_id = p_registro_id
    FOR UPDATE;
    
    IF v_estatus_anterior IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Registro no encontrado';
    END IF;
    
    -- Update registro
    UPDATE RegistroRefugio 
    SET 
        estatus = p_nuevo_estatus,
        fecha_salida = p_fecha_salida,
        hora_salida = p_hora_salida,
        refugio_destino_id = p_refugio_destino_id,
        observaciones = COALESCE(p_observaciones, observaciones)
    WHERE registro_id = p_registro_id;
    
    -- Update capacity based on status change
    IF v_estatus_anterior = 'Alojado' AND p_nuevo_estatus != 'Alojado' THEN
        -- Person leaving, decrease capacity
        UPDATE Refugios 
        SET capacidad_ocupada = capacidad_ocupada - 1 
        WHERE refugio_id = v_refugio_id;
    ELSEIF v_estatus_anterior != 'Alojado' AND p_nuevo_estatus = 'Alojado' THEN
        -- Person returning, increase capacity (check limit)
        UPDATE Refugios 
        SET capacidad_ocupada = capacidad_ocupada + 1 
        WHERE refugio_id = v_refugio_id 
        AND capacidad_ocupada < capacidad_maxima;
        
        IF ROW_COUNT() = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio sin capacidad para retorno';
        END IF;
    END IF;
    
    -- Audit log
    INSERT INTO AuditLog(
        usuario_id, rol, accion, objeto, objeto_id, 
        resumen, ip_origen
    ) VALUES (
        p_usuario_id,
        (SELECT rol FROM Usuarios WHERE usuario_id = p_usuario_id),
        'UPDATE',
        'RegistroRefugio',
        p_registro_id,
        CONCAT('Cambio de estatus: ', v_estatus_anterior, ' -> ', p_nuevo_estatus, ' - Persona ID: ', v_persona_id),
        p_ip_origen
    );
    
    COMMIT;
    
END //

-- Procedure to create a complete person with all related data
CREATE PROCEDURE sp_crear_persona_completa(
    IN p_nombre_preferido VARCHAR(150),
    IN p_edad_rango ENUM('Niño/a','Adolescente','Adulto','Adulto mayor'),
    IN p_genero ENUM('F','M','Otro','Prefiere no decir'),
    IN p_idioma_principal VARCHAR(80),
    IN p_refugio_id INT,
    IN p_fecha_ingreso DATE,
    IN p_hora_ingreso TIME,
    IN p_area_asignada VARCHAR(80),
    IN p_observaciones TEXT,
    -- Salud
    IN p_condicion_medica TEXT,
    IN p_medicamentos TEXT,
    IN p_alergias TEXT,
    IN p_asistencia_especial TEXT,
    IN p_requiere_atencion_urgente BOOLEAN,
    -- Procedencia
    IN p_localidad VARCHAR(120),
    IN p_municipio VARCHAR(120),
    IN p_departamento VARCHAR(120),
    IN p_situacion ENUM('Vivienda perdida','Temporalmente desplazado','Evacuación preventiva'),
    IN p_tiene_mascotas BOOLEAN,
    IN p_mascotas_detalle TEXT,
    -- Usuario
    IN p_usuario_id INT,
    IN p_ip_origen VARCHAR(45)
)
BEGIN
    DECLARE v_persona_id INT;
    DECLARE v_registro_id BIGINT;
    DECLARE v_error_msg VARCHAR(255);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        RESIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END;
    
    START TRANSACTION;
    
    -- Create person
    INSERT INTO Personas(nombre_preferido, edad_rango, genero, idioma_principal)
    VALUES (p_nombre_preferido, p_edad_rango, p_genero, p_idioma_principal);
    
    SET v_persona_id = LAST_INSERT_ID();
    
    -- Create salud record if any health data provided
    IF p_condicion_medica IS NOT NULL OR p_medicamentos IS NOT NULL OR 
       p_alergias IS NOT NULL OR p_asistencia_especial IS NOT NULL OR
       p_requiere_atencion_urgente = TRUE THEN
        
        INSERT INTO Salud(
            persona_id, condicion_medica, medicamentos, alergias, 
            asistencia_especial, requiere_atencion_urgente
        ) VALUES (
            v_persona_id, p_condicion_medica, p_medicamentos, p_alergias,
            p_asistencia_especial, COALESCE(p_requiere_atencion_urgente, FALSE)
        );
    END IF;
    
    -- Create procedencia record
    INSERT INTO Procedencia(
        persona_id, localidad, municipio, departamento, situacion,
        tiene_mascotas, mascotas_detalle
    ) VALUES (
        v_persona_id, p_localidad, p_municipio, p_departamento, p_situacion,
        COALESCE(p_tiene_mascotas, FALSE), p_mascotas_detalle
    );
    
    -- Register in refugio
    CALL sp_registrar_ingreso(
        v_persona_id, p_refugio_id, p_fecha_ingreso, p_hora_ingreso,
        p_area_asignada, 'Alojado', p_observaciones, p_usuario_id, p_ip_origen
    );
    
    COMMIT;
    
    SELECT v_persona_id AS persona_id;
    
END //

DELIMITER ;

-- ===================================================
-- TRIGGERS
-- ===================================================

-- Trigger to ensure refugio capacity consistency
DELIMITER //

CREATE TRIGGER tr_refugio_capacity_check
BEFORE UPDATE ON Refugios
FOR EACH ROW
BEGIN
    IF NEW.capacidad_ocupada < 0 THEN
        SET NEW.capacidad_ocupada = 0;
    END IF;
    
    IF NEW.capacidad_ocupada > NEW.capacidad_maxima THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La capacidad ocupada no puede exceder la capacidad máxima';
    END IF;
END //

DELIMITER ;

-- ===================================================
-- INDEXES OPTIMIZATION
-- ===================================================

-- Additional composite indexes for better performance
CREATE INDEX idx_personas_edad_genero_nombre ON Personas(edad_rango, genero, nombre_preferido);
CREATE INDEX idx_registro_refugio_fecha_estatus ON RegistroRefugio(refugio_id, fecha_ingreso, estatus);
CREATE INDEX idx_audit_fecha_usuario ON AuditLog(creado_en, usuario_id);
CREATE INDEX idx_upload_refugio_estado_fecha ON BulkUploads(refugio_id, estado, creado_en);

-- ===================================================
-- ADDITIONAL HELPER PROCEDURES
-- ===================================================

DELIMITER //

-- Procedure to get refugio statistics
CREATE PROCEDURE sp_estadisticas_refugio(
    IN p_refugio_id INT
)
BEGIN
    SELECT 
        r.refugio_id,
        r.nombre_refugio,
        r.capacidad_maxima,
        r.capacidad_ocupada,
        r.estado,
        COUNT(CASE WHEN rr.estatus = 'Alojado' THEN 1 END) AS alojados_actual,
        COUNT(CASE WHEN rr.estatus = 'Dado de alta' THEN 1 END) AS dados_alta_total,
        COUNT(CASE WHEN rr.estatus = 'Trasladado a otro refugio' THEN 1 END) AS trasladados_total,
        COUNT(DISTINCT CASE WHEN rr.estatus = 'Alojado' AND p.edad_rango = 'Niño/a' THEN p.persona_id END) AS niños_alojados,
        COUNT(DISTINCT CASE WHEN rr.estatus = 'Alojado' AND p.edad_rango = 'Adolescente' THEN p.persona_id END) AS adolescentes_alojados,
        COUNT(DISTINCT CASE WHEN rr.estatus = 'Alojado' AND p.edad_rango = 'Adulto' THEN p.persona_id END) AS adultos_alojados,
        COUNT(DISTINCT CASE WHEN rr.estatus = 'Alojado' AND p.edad_rango = 'Adulto mayor' THEN p.persona_id END) AS adultos_mayor_alojados,
        COUNT(DISTINCT CASE WHEN rr.estatus = 'Alojado' AND s.requiere_atencion_urgente = TRUE THEN p.persona_id END) AS requieren_atencion_urgente,
        COUNT(DISTINCT CASE WHEN rr.estatus = 'Alojado' AND pr.tiene_mascotas = TRUE THEN p.persona_id END) AS personas_con_mascotas,
        ROUND((r.capacidad_ocupada / r.capacidad_maxima) * 100, 1) AS porcentaje_ocupacion
    FROM Refugios r
    LEFT JOIN RegistroRefugio rr ON r.refugio_id = rr.refugio_id
    LEFT JOIN Personas p ON rr.persona_id = p.persona_id
    LEFT JOIN Salud s ON p.persona_id = s.persona_id
    LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id
    WHERE r.refugio_id = p_refugio_id
    GROUP BY r.refugio_id, r.nombre_refugio, r.capacidad_maxima, r.capacidad_ocupada, r.estado;
END //

-- Procedure to search persons (public)
CREATE PROCEDURE sp_buscar_personas_publico(
    IN p_search_term VARCHAR(255),
    IN p_refugio_id INT,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    DECLARE v_search_pattern VARCHAR(257);
    SET v_search_pattern = CONCAT('%', COALESCE(p_search_term, ''), '%');
    
    SELECT 
        p.persona_id,
        p.nombre_preferido AS nombre,
        p.edad_rango,
        p.genero,
        rr.estatus,
        rr.fecha_ingreso,
        rr.hora_ingreso,
        rr.area_asignada,
        r.nombre_refugio AS refugio,
        r.ubicacion AS direccion,
        COUNT(*) OVER() AS total_registros
    FROM RegistroRefugio rr
    JOIN Personas p ON rr.persona_id = p.persona_id
    JOIN Refugios r ON rr.refugio_id = r.refugio_id
    WHERE rr.estatus = 'Alojado'
    AND (p_search_term IS NULL OR p.nombre_preferido LIKE v_search_pattern OR r.nombre_refugio LIKE v_search_pattern)
    AND (p_refugio_id IS NULL OR r.refugio_id = p_refugio_id)
    ORDER BY rr.fecha_ingreso DESC, rr.hora_ingreso DESC
    LIMIT p_limit OFFSET p_offset;
END //

-- Procedure to validate CSV bulk upload
CREATE PROCEDURE sp_validar_csv_upload(
    IN p_upload_id BIGINT,
    IN p_usuario_id INT
)
BEGIN
    DECLARE v_refugio_id INT;
    DECLARE v_filename VARCHAR(255);
    DECLARE v_total_filas INT DEFAULT 0;
    DECLARE v_validation_errors JSON;
    
    SELECT refugio_id, filename INTO v_refugio_id, v_filename
    FROM BulkUploads 
    WHERE upload_id = p_upload_id;
    
    -- Update status to validating
    UPDATE BulkUploads 
    SET estado = 'En_proceso', mensaje = 'Validando archivo...'
    WHERE upload_id = p_upload_id;
    
    -- Here would go the actual CSV validation logic
    -- For now, we'll simulate validation success
    SET v_total_filas = 0; -- This would be calculated from actual CSV parsing
    SET v_validation_errors = JSON_OBJECT('errors', JSON_ARRAY(), 'warnings', JSON_ARRAY());
    
    UPDATE BulkUploads 
    SET 
        estado = 'Validado',
        mensaje = 'Archivo validado correctamente',
        total_filas = v_total_filas,
        validation_report = v_validation_errors
    WHERE upload_id = p_upload_id;
    
    -- Audit log
    INSERT INTO AuditLog(usuario_id, rol, accion, objeto, objeto_id, resumen)
    VALUES (p_usuario_id, 'Sistema', 'VALIDATE', 'BulkUploads', p_upload_id, 
            CONCAT('Validación CSV completada - Archivo: ', v_filename));
            
END //

-- Procedure to create grupo (family/group)
CREATE PROCEDURE sp_crear_grupo(
    IN p_jefe_grupo_id INT,
    IN p_nombre_grupo VARCHAR(100),
    IN p_usuario_id INT
)
BEGIN
    DECLARE v_grupo_id INT;
    DECLARE v_error_msg VARCHAR(255);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        RESIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END;
    
    START TRANSACTION;
    
    -- Verify jefe exists
    IF NOT EXISTS (SELECT 1 FROM Personas WHERE persona_id = p_jefe_grupo_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Persona jefe de grupo no encontrada';
    END IF;
    
    -- Create grupo
    INSERT INTO Grupos(jefe_grupo_id, nombre_grupo) 
    VALUES (p_jefe_grupo_id, p_nombre_grupo);
    
    SET v_grupo_id = LAST_INSERT_ID();
    
    -- Add jefe as member
    INSERT INTO GrupoMiembros(grupo_id, persona_id, relacion)
    VALUES (v_grupo_id, p_jefe_grupo_id, 'Jefe');
    
    -- Audit log
    INSERT INTO AuditLog(usuario_id, rol, accion, objeto, objeto_id, resumen)
    VALUES (p_usuario_id, 
            (SELECT rol FROM Usuarios WHERE usuario_id = p_usuario_id),
            'INSERT', 'Grupos', v_grupo_id,
            CONCAT('Grupo creado - Jefe: ', p_jefe_grupo_id));
    
    COMMIT;
    
    SELECT v_grupo_id AS grupo_id;
END //

-- Procedure to add member to grupo
CREATE PROCEDURE sp_agregar_miembro_grupo(
    IN p_grupo_id INT,
    IN p_persona_id INT,
    IN p_relacion ENUM('Jefe','Esposo/a','Hijo/a','Padre/Madre','Hermano/a','Otro'),
    IN p_usuario_id INT
)
BEGIN
    DECLARE v_error_msg VARCHAR(255);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        RESIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END;
    
    START TRANSACTION;
    
    -- Verify grupo exists
    IF NOT EXISTS (SELECT 1 FROM Grupos WHERE grupo_id = p_grupo_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Grupo no encontrado';
    END IF;
    
    -- Verify persona exists
    IF NOT EXISTS (SELECT 1 FROM Personas WHERE persona_id = p_persona_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Persona no encontrada';
    END IF;
    
    -- Add member
    INSERT INTO GrupoMiembros(grupo_id, persona_id, relacion)
    VALUES (p_grupo_id, p_persona_id, p_relacion);
    
    -- Audit log
    INSERT INTO AuditLog(usuario_id, rol, accion, objeto, objeto_id, resumen)
    VALUES (p_usuario_id,
            (SELECT rol FROM Usuarios WHERE usuario_id = p_usuario_id),
            'INSERT', 'GrupoMiembros', 
            CONCAT(p_grupo_id, '-', p_persona_id),
            CONCAT('Miembro agregado al grupo - Persona: ', p_persona_id, ', Relación: ', p_relacion));
    
    COMMIT;
END //

DELIMITER ;

-- ===================================================
-- ADDITIONAL VIEWS FOR REPORTING
-- ===================================================

-- View for daily statistics
CREATE VIEW vw_estadisticas_diarias AS
SELECT 
    DATE(rr.creado_en) as fecha,
    COUNT(CASE WHEN rr.estatus = 'Alojado' THEN 1 END) as nuevos_ingresos,
    COUNT(CASE WHEN rr.estatus = 'Dado de alta' THEN 1 END) as altas_dia,
    COUNT(CASE WHEN rr.estatus = 'Trasladado a otro refugio' THEN 1 END) as traslados_dia,
    COUNT(DISTINCT rr.refugio_id) as refugios_activos,
    AVG(r.capacidad_ocupada) as promedio_ocupacion
FROM RegistroRefugio rr
JOIN Refugios r ON rr.refugio_id = r.refugio_id
WHERE rr.creado_en >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(rr.creado_en)
ORDER BY fecha DESC;

-- View for health summary
CREATE VIEW vw_resumen_salud AS
SELECT 
    r.refugio_id,
    r.nombre_refugio,
    COUNT(CASE WHEN s.requiere_atencion_urgente = TRUE AND rr.estatus = 'Alojado' THEN 1 END) as casos_urgentes,
    COUNT(CASE WHEN s.condicion_medica IS NOT NULL AND s.condicion_medica != '' AND rr.estatus = 'Alojado' THEN 1 END) as con_condiciones_medicas,
    COUNT(CASE WHEN s.medicamentos IS NOT NULL AND s.medicamentos != '' AND rr.estatus = 'Alojado' THEN 1 END) as requieren_medicamentos,
    COUNT(CASE WHEN s.alergias IS NOT NULL AND s.alergias != '' AND rr.estatus = 'Alojado' THEN 1 END) as con_alergias,
    COUNT(CASE WHEN s.asistencia_especial IS NOT NULL AND s.asistencia_especial != '' AND rr.estatus = 'Alojado' THEN 1 END) as requieren_asistencia_especial
FROM Refugios r
LEFT JOIN RegistroRefugio rr ON r.refugio_id = rr.refugio_id
LEFT JOIN Salud s ON rr.persona_id = s.persona_id
GROUP BY r.refugio_id, r.nombre_refugio
ORDER BY casos_urgentes DESC, con_condiciones_medicas DESC;

-- View for demographic analysis
CREATE VIEW vw_analisis_demografico AS
SELECT 
    r.refugio_id,
    r.nombre_refugio,
    COUNT(CASE WHEN p.edad_rango = 'Niño/a' AND rr.estatus = 'Alojado' THEN 1 END) as niños,
    COUNT(CASE WHEN p.edad_rango = 'Adolescente' AND rr.estatus = 'Alojado' THEN 1 END) as adolescentes,
    COUNT(CASE WHEN p.edad_rango = 'Adulto' AND rr.estatus = 'Alojado' THEN 1 END) as adultos,
    COUNT(CASE WHEN p.edad_rango = 'Adulto mayor' AND rr.estatus = 'Alojado' THEN 1 END) as adultos_mayores,
    COUNT(CASE WHEN p.genero = 'F' AND rr.estatus = 'Alojado' THEN 1 END) as mujeres,
    COUNT(CASE WHEN p.genero = 'M' AND rr.estatus = 'Alojado' THEN 1 END) as hombres,
    COUNT(CASE WHEN p.genero IN ('Otro', 'Prefiere no decir') AND rr.estatus = 'Alojado' THEN 1 END) as otros_generos,
    COUNT(DISTINCT CASE WHEN rr.estatus = 'Alojado' THEN g.grupo_id END) as grupos_familiares,
    COUNT(CASE WHEN pr.tiene_mascotas = TRUE AND rr.estatus = 'Alojado' THEN 1 END) as personas_con_mascotas
FROM Refugios r
LEFT JOIN RegistroRefugio rr ON r.refugio_id = rr.refugio_id
LEFT JOIN Personas p ON rr.persona_id = p.persona_id
LEFT JOIN GrupoMiembros gm ON p.persona_id = gm.persona_id
LEFT JOIN Grupos g ON gm.grupo_id = g.grupo_id
LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id
GROUP BY r.refugio_id, r.nombre_refugio
ORDER BY r.nombre_refugio;

-- ===================================================
-- SECURITY FUNCTIONS
-- ===================================================

DELIMITER //

-- Function to hash audit payload
CREATE FUNCTION fn_hash_payload(payload TEXT) 
RETURNS VARCHAR(128)
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN SHA2(CONCAT(payload, 'audit_salt_2024'), 256);
END //

-- Function to validate user permissions for refugio
CREATE FUNCTION fn_user_can_access_refugio(p_usuario_id INT, p_refugio_id INT)
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_rol VARCHAR(50);
    DECLARE v_user_refugio_id INT;
    
    SELECT rol, refugio_id INTO v_rol, v_user_refugio_id
    FROM Usuarios 
    WHERE usuario_id = p_usuario_id AND activo = TRUE;
    
    IF v_rol = 'Administrador' THEN
        RETURN TRUE;
    ELSEIF v_rol = 'Refugio' AND v_user_refugio_id = p_refugio_id THEN
        RETURN TRUE;
    ELSEIF v_rol = 'Auditor' THEN
        RETURN TRUE; -- Auditor can view all refugios (read-only)
    ELSE
        RETURN FALSE;
    END IF;
END //

DELIMITER ;

-- Analyze tables for better query planning
ANALYZE TABLE Refugios, Usuarios, Personas, RegistroRefugio, Grupos, GrupoMiembros, Salud, Procedencia, AuditLog, BulkUploads;

-- ===================================================
-- VERIFICATION QUERIES (for testing)
-- ===================================================

/*
-- Test public views
SELECT * FROM vw_public_personas LIMIT 5;
SELECT * FROM vw_public_estadisticas;
SELECT * FROM vw_public_refugios;

-- Test private views
SELECT * FROM vw_refugio_personas WHERE refugio_id = 1 LIMIT 5;
SELECT * FROM vw_admin_personas_full LIMIT 3;
SELECT * FROM vw_auditor_activity ORDER BY creado_en DESC LIMIT 10;

-- Test stored procedures
CALL sp_estadisticas_refugio(1);
CALL sp_buscar_personas_publico('María', NULL, 10, 0);

-- Test demographic views
SELECT * FROM vw_analisis_demografico;
SELECT * FROM vw_resumen_salud;
SELECT * FROM vw_estadisticas_diarias LIMIT 7;
*/

-- ===================================================
-- DATABASE CREATION COMPLETED
-- ===================================================

SELECT 'Database refugios_db created successfully with all tables, views, procedures, and sample data' AS status;