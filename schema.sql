-- =============================================
-- Sistema de Gestión de Refugios - Database Schema
-- =============================================

DROP DATABASE IF EXISTS shelter_management;
CREATE DATABASE shelter_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shelter_management;

-- =============================================
-- TABLES
-- =============================================

-- Refugios
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
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE(nombre_refugio),
    INDEX idx_refugios_estado (estado),
    INDEX idx_refugios_activo (activo)
);

-- Usuarios
CREATE TABLE Usuarios (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('Administrador','Refugio','Auditor') NOT NULL,
    refugio_id INT NULL,
    nombre_mostrado VARCHAR(150) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login DATETIME NULL,
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE SET NULL,
    INDEX idx_usuarios_rol (rol),
    INDEX idx_usuarios_activo (activo)
);

-- Personas
CREATE TABLE Personas (
    persona_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_preferido VARCHAR(150) NOT NULL,
    edad_rango ENUM('Niño/a','Adolescente','Adulto','Adulto mayor') NOT NULL,
    genero ENUM('F','M','Otro','Prefiere no decir') NOT NULL,
    idioma_principal VARCHAR(80) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_personas_nombre (nombre_preferido)
);

-- Grupos
CREATE TABLE Grupos (
    grupo_id INT AUTO_INCREMENT PRIMARY KEY,
    jefe_grupo_id INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jefe_grupo_id) REFERENCES Personas(persona_id) ON DELETE RESTRICT
);

-- GrupoMiembros
CREATE TABLE GrupoMiembros (
    grupo_id INT NOT NULL,
    persona_id INT NOT NULL,
    relacion ENUM('Jefe','Esposo/a','Hijo/a','Otro') NOT NULL,
    PRIMARY KEY (grupo_id, persona_id),
    FOREIGN KEY (grupo_id) REFERENCES Grupos(grupo_id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE
);

-- Salud (opcional por persona)
CREATE TABLE Salud (
    salud_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE,
    condicion_medica TEXT,
    medicamentos TEXT,
    alergias TEXT,
    asistencia_especial TEXT,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE
);

-- Procedencia
CREATE TABLE Procedencia (
    procedencia_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE,
    localidad VARCHAR(120) NOT NULL,
    situacion ENUM('Vivienda perdida','Temporalmente desplazado') NOT NULL,
    tiene_mascotas BOOLEAN NOT NULL DEFAULT FALSE,
    mascotas_detalle TEXT,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE
);

-- RegistroRefugio
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
    INDEX idx_registro_refugio (refugio_id)
);

-- Auditoría
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
    INDEX idx_audit_fecha (creado_en),
    INDEX idx_audit_accion (accion)
);

-- BulkUploads
CREATE TABLE BulkUploads (
    upload_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    refugio_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    estado ENUM('Pendiente','Validado','Fallido','Procesado') NOT NULL DEFAULT 'Pendiente',
    mensaje TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    procesado_en DATETIME NULL,
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE CASCADE,
    INDEX idx_bulk_estado (estado),
    INDEX idx_bulk_refugio (refugio_id)
);

-- RefreshTokens (for JWT)
CREATE TABLE RefreshTokens (
    token_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked BOOLEAN NOT NULL DEFAULT FALSE,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE CASCADE,
    INDEX idx_refresh_hash (token_hash),
    INDEX idx_refresh_usuario (usuario_id),
    INDEX idx_refresh_expiry (expires_at)
);

-- LoginAttempts (for rate limiting)
CREATE TABLE LoginAttempts (
    attempt_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    success BOOLEAN NOT NULL DEFAULT FALSE,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_username (username),
    INDEX idx_login_ip (ip_address),
    INDEX idx_login_time (attempted_at)
);

-- =============================================
-- VIEWS
-- =============================================

-- Vista pública de personas (solo alojadas)
CREATE VIEW vw_public_personas AS
SELECT 
    p.persona_id,
    p.nombre_preferido AS nombre,
    p.edad_rango,
    p.genero,
    rr.estatus,
    rr.fecha_ingreso,
    rr.hora_ingreso,
    r.refugio_id,
    r.nombre_refugio AS refugio,
    r.ubicacion AS direccion
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id
WHERE rr.estatus = 'Alojado' AND r.activo = TRUE;

-- Vista pública de estadísticas
CREATE VIEW vw_public_estadisticas AS
SELECT 
    COUNT(*) AS total_personas,
    SUM(CASE WHEN rr.estatus='Alojado' THEN 1 ELSE 0 END) AS total_alojados,
    SUM(CASE WHEN rr.estatus='Dado de alta' THEN 1 ELSE 0 END) AS total_dados_alta,
    SUM(CASE WHEN rr.estatus='Trasladado a otro refugio' THEN 1 ELSE 0 END) AS total_trasladados
FROM RegistroRefugio rr
JOIN Refugios r ON rr.refugio_id = r.refugio_id
WHERE r.activo = TRUE;

-- Vista pública de refugios
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
    COUNT(CASE WHEN rr.estatus = 'Alojado' THEN 1 END) AS alojados_actuales,
    COUNT(CASE WHEN rr.estatus = 'Dado de alta' THEN 1 END) AS dados_alta,
    COUNT(CASE WHEN rr.estatus = 'Trasladado a otro refugio' THEN 1 END) AS trasladados
FROM Refugios r
LEFT JOIN RegistroRefugio rr ON r.refugio_id = rr.refugio_id
WHERE r.activo = TRUE
GROUP BY r.refugio_id;

-- Vista privada para refugio específico
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
    s.condicion_medica,
    s.medicamentos,
    s.alergias,
    s.asistencia_especial,
    pr.localidad,
    pr.situacion,
    pr.tiene_mascotas,
    pr.mascotas_detalle
FROM Personas p
JOIN RegistroRefugio rr ON p.persona_id = rr.persona_id
LEFT JOIN Salud s ON p.persona_id = s.persona_id
LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id
WHERE r.activo = TRUE;

-- Vista para administradores (completa)
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
    rr.creado_en AS rr_creado_en,
    rr.actualizado_en AS rr_actualizado_en,
    s.salud_id,
    s.condicion_medica,
    s.medicamentos,
    s.alergias,
    s.asistencia_especial,
    pr.procedencia_id,
    pr.localidad,
    pr.situacion,
    pr.tiene_mascotas,
    pr.mascotas_detalle,
    r.nombre_refugio,
    r.ubicacion AS refugio_ubicacion
FROM Personas p
LEFT JOIN RegistroRefugio rr ON p.persona_id = rr.persona_id
LEFT JOIN Salud s ON p.persona_id = s.persona_id
LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id
LEFT JOIN Refugios r ON rr.refugio_id = r.refugio_id;

-- Vista para auditoría
CREATE VIEW vw_auditor_activity AS
SELECT 
    al.*,
    u.username,
    u.nombre_mostrado
FROM AuditLog al
LEFT JOIN Usuarios u ON al.usuario_id = u.usuario_id
ORDER BY al.creado_en DESC;

-- =============================================
-- STORED PROCEDURES
-- =============================================

DELIMITER //

-- Procedimiento para registrar ingreso
CREATE PROCEDURE sp_registrar_ingreso(
    IN p_persona_id INT,
    IN p_refugio_id INT,
    IN p_fecha_ingreso DATE,
    IN p_hora_ingreso TIME,
    IN p_area VARCHAR(80),
    IN p_estatus ENUM('Alojado','Dado de alta','Trasladado a otro refugio'),
    IN p_observaciones TEXT,
    IN p_usuario_id INT
)
BEGIN
    DECLARE v_cap_actual INT;
    DECLARE v_cap_max INT;
    DECLARE v_registro_id BIGINT;
    DECLARE exit handler for sqlexception
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    SELECT capacidad_ocupada, capacidad_maxima INTO v_cap_actual, v_cap_max
    FROM Refugios WHERE refugio_id = p_refugio_id FOR UPDATE;

    IF p_estatus = 'Alojado' AND v_cap_actual >= v_cap_max THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio sin capacidad';
    ELSE
        INSERT INTO RegistroRefugio(persona_id, refugio_id, fecha_ingreso, hora_ingreso, area_asignada, estatus, observaciones)
        VALUES (p_persona_id, p_refugio_id, p_fecha_ingreso, p_hora_ingreso, p_area, p_estatus, p_observaciones);
        
        SET v_registro_id = LAST_INSERT_ID();

        IF p_estatus = 'Alojado' THEN
            UPDATE Refugios SET capacidad_ocupada = capacidad_ocupada + 1 WHERE refugio_id = p_refugio_id;
        END IF;
        
        INSERT INTO AuditLog(usuario_id, rol, accion, objeto, objeto_id, resumen)
        SELECT p_usuario_id, rol, 'INSERT', 'RegistroRefugio', v_registro_id, 
               CONCAT('Ingreso de persona ', p_persona_id, ' en refugio ', p_refugio_id)
        FROM Usuarios WHERE usuario_id = p_usuario_id;
    END IF;

    COMMIT;
END //

-- Procedimiento para actualizar estatus
CREATE PROCEDURE sp_actualizar_estatus(
    IN p_registro_id BIGINT,
    IN p_nuevo_estatus ENUM('Alojado','Dado de alta','Trasladado a otro refugio'),
    IN p_observaciones TEXT,
    IN p_usuario_id INT
)
BEGIN
    DECLARE v_estatus_anterior VARCHAR(50);
    DECLARE v_refugio_id INT;
    DECLARE exit handler for sqlexception
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    SELECT estatus, refugio_id INTO v_estatus_anterior, v_refugio_id
    FROM RegistroRefugio WHERE registro_id = p_registro_id FOR UPDATE;

    UPDATE RegistroRefugio 
    SET estatus = p_nuevo_estatus, observaciones = p_observaciones
    WHERE registro_id = p_registro_id;

    IF v_estatus_anterior = 'Alojado' AND p_nuevo_estatus != 'Alojado' THEN
        UPDATE Refugios SET capacidad_ocupada = capacidad_ocupada - 1 WHERE refugio_id = v_refugio_id;
    ELSEIF v_estatus_anterior != 'Alojado' AND p_nuevo_estatus = 'Alojado' THEN
        UPDATE Refugios SET capacidad_ocupada = capacidad_ocupada + 1 WHERE refugio_id = v_refugio_id;
    END IF;
    
    INSERT INTO AuditLog(usuario_id, rol, accion, objeto, objeto_id, resumen)
    SELECT p_usuario_id, rol, 'UPDATE', 'RegistroRefugio', p_registro_id,
           CONCAT('Cambio de estatus de ', v_estatus_anterior, ' a ', p_nuevo_estatus)
    FROM Usuarios WHERE usuario_id = p_usuario_id;

    COMMIT;
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
    IN p_refugio_id INT,
    IN p_fecha_ingreso DATE,
    IN p_hora_ingreso TIME,
    IN p_area VARCHAR(80),
    IN p_usuario_id INT
)
BEGIN
    DECLARE v_persona_id INT;
    DECLARE exit handler for sqlexception
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    INSERT INTO Personas(nombre_preferido, edad_rango, genero, idioma_principal)
    VALUES (p_nombre_preferido, p_edad_rango, p_genero, p_idioma_principal);
    
    SET v_persona_id = LAST_INSERT_ID();
    
    IF p_condicion_medica IS NOT NULL OR p_medicamentos IS NOT NULL OR p_alergias IS NOT NULL OR p_asistencia_especial IS NOT NULL THEN
        INSERT INTO Salud(persona_id, condicion_medica, medicamentos, alergias, asistencia_especial)
        VALUES (v_persona_id, p_condicion_medica, p_medicamentos, p_alergias, p_asistencia_especial);
    END IF;
    
    INSERT INTO Procedencia(persona_id, localidad, situacion, tiene_mascotas, mascotas_detalle)
    VALUES (v_persona_id, p_localidad, p_situacion, p_tiene_mascotas, p_mascotas_detalle);
    
    CALL sp_registrar_ingreso(v_persona_id, p_refugio_id, p_fecha_ingreso, p_hora_ingreso, p_area, 'Alojado', NULL, p_usuario_id);
    
    COMMIT;
    SELECT v_persona_id as persona_id;
END //

DELIMITER ;

-- =============================================
-- SAMPLE DATA
-- =============================================

INSERT INTO Refugios (nombre_refugio, ubicacion, lat, lng, fecha_apertura, capacidad_maxima, capacidad_ocupada) VALUES
('Refugio Central', 'Av. Principal 123, Ciudad', 40.416775, -3.703790, '2024-01-15', 100, 45),
('Refugio Norte', 'Calle Norte 456, Distrito Norte', 40.426775, -3.713790, '2024-01-16', 80, 32),
('Refugio Sur', 'Av. Sur 789, Zona Sur', 40.406775, -3.693790, '2024-01-17', 60, 28);

INSERT INTO Usuarios (username, password_hash, rol, refugio_id, nombre_mostrado) VALUES
('admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/UnUHXx6rs/8L2uZby', 'Administrador', NULL, 'Administrador Sistema'),
('refugio1', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/UnUHXx6rs/8L2uZby', 'Refugio', 1, 'Operador Refugio Central'),
('refugio2', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/UnUHXx6rs/8L2uZby', 'Refugio', 2, 'Operador Refugio Norte'),
('auditor', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/UnUHXx6rs/8L2uZby', 'Auditor', NULL, 'Auditor Sistema');

INSERT INTO Personas (nombre_preferido, edad_rango, genero, idioma_principal) VALUES
('María González', 'Adulto', 'F', 'Español'),
('Juan Pérez', 'Adulto', 'M', 'Español'),
('Ana López', 'Niño/a', 'F', 'Español'),
('Carlos Ruiz', 'Adulto mayor', 'M', 'Español'),
('Sofía Martín', 'Adolescente', 'F', 'Español'),
('Pedro Sánchez', 'Adulto', 'M', 'Español'),
('Lucía García', 'Adulto', 'F', 'Español'),
('Miguel Torres', 'Niño/a', 'M', 'Español'),
('Isabel Moreno', 'Adulto mayor', 'F', 'Español'),
('Diego Jiménez', 'Adolescente', 'M', 'Español');

INSERT INTO Procedencia (persona_id, localidad, situacion, tiene_mascotas, mascotas_detalle) VALUES
(1, 'Barrio Centro', 'Vivienda perdida', TRUE, '1 perro pequeño'),
(2, 'Zona Industrial', 'Temporalmente desplazado', FALSE, NULL),
(3, 'Barrio Centro', 'Vivienda perdida', TRUE, '1 perro pequeño'),
(4, 'Colonia Norte', 'Vivienda perdida', FALSE, NULL),
(5, 'Sector Este', 'Temporalmente desplazado', TRUE, '1 gato'),
(6, 'Barrio Oeste', 'Vivienda perdida', FALSE, NULL),
(7, 'Centro Histórico', 'Temporalmente desplazado', TRUE, '2 gatos'),
(8, 'Periferia Sur', 'Vivienda perdida', FALSE, NULL),
(9, 'Barrio Antiguo', 'Temporalmente desplazado', TRUE, '1 perro grande'),
(10, 'Zona Residencial', 'Vivienda perdida', FALSE, NULL);

INSERT INTO Salud (persona_id, condicion_medica, medicamentos, alergias, asistencia_especial) VALUES
(1, 'Hipertensión', 'Losartán 50mg', 'Penicilina', NULL),
(4, 'Diabetes tipo 2', 'Metformina 500mg', NULL, 'Dieta especial'),
(9, 'Artritis', 'Ibuprofeno', 'Aspirina', 'Ayuda para caminar');

INSERT INTO RegistroRefugio (persona_id, refugio_id, fecha_ingreso, hora_ingreso, area_asignada, estatus, observaciones) VALUES
(1, 1, '2024-01-15', '14:30:00', 'Sector A-1', 'Alojado', 'Ingreso con mascota'),
(2, 1, '2024-01-15', '15:00:00', 'Sector A-2', 'Alojado', NULL),
(3, 1, '2024-01-15', '14:30:00', 'Sector A-1', 'Alojado', 'Menor acompañada por María González'),
(4, 2, '2024-01-16', '09:15:00', 'Sector B-1', 'Alojado', 'Requiere dieta especial'),
(5, 2, '2024-01-16', '10:00:00', 'Sector B-2', 'Alojado', NULL),
(6, 3, '2024-01-17', '16:45:00', 'Sector C-1', 'Alojado', NULL),
(7, 1, '2024-01-18', '11:20:00', 'Sector A-3', 'Alojado', 'Ingreso con mascotas'),
(8, 3, '2024-01-18', '13:30:00', 'Sector C-2', 'Alojado', 'Menor no acompañado'),
(9, 2, '2024-01-19', '08:45:00', 'Sector B-3', 'Alojado', 'Requiere asistencia para movilidad'),
(10, 1, '2024-01-19', '12:00:00', 'Sector A-4', 'Alojado', NULL);