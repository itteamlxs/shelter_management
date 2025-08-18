-- Sistema de Refugios - Database Schema
-- Warning: This creates the complete database structure with sample data

DROP DATABASE IF EXISTS shelter_database_system;
CREATE DATABASE shelter_database_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shelter_database_system;

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
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE(nombre_refugio)
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
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE SET NULL
);

-- Personas
CREATE TABLE Personas (
    persona_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_preferido VARCHAR(150) NOT NULL,
    edad_rango ENUM('Niño/a','Adolescente','Adulto','Adulto mayor') NOT NULL,
    genero ENUM('F','M','Otro','Prefiere no decir') NOT NULL,
    idioma_principal VARCHAR(80) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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

-- Salud
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
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE CASCADE
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
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE SET NULL
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
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE CASCADE
);

-- Índices para optimización
CREATE INDEX idx_refugios_estado ON Refugios(estado);
CREATE INDEX idx_usuarios_rol ON Usuarios(rol);
CREATE INDEX idx_personas_nombre ON Personas(nombre_preferido);
CREATE INDEX idx_registro_estatus ON RegistroRefugio(estatus);
CREATE INDEX idx_registro_fecha ON RegistroRefugio(fecha_ingreso);

-- VISTAS PÚBLICAS
CREATE VIEW vw_public_refugios AS
SELECT 
    refugio_id,
    nombre_refugio,
    ubicacion,
    lat,
    lng,
    fecha_apertura,
    capacidad_maxima,
    capacidad_ocupada,
    estado,
    ROUND((capacidad_ocupada / capacidad_maxima) * 100, 1) as porcentaje_ocupacion
FROM Refugios
WHERE capacidad_maxima > 0;

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
    r.ubicacion AS direccion
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id
WHERE rr.estatus = 'Alojado';

CREATE VIEW vw_public_estadisticas AS
SELECT 
    COUNT(DISTINCT r.refugio_id) as total_refugios,
    COUNT(DISTINCT p.persona_id) AS total_personas,
    SUM(CASE WHEN rr.estatus='Alojado' THEN 1 ELSE 0 END) AS total_alojados,
    SUM(CASE WHEN rr.estatus='Dado de alta' THEN 1 ELSE 0 END) AS total_dados_alta,
    SUM(CASE WHEN rr.estatus='Trasladado a otro refugio' THEN 1 ELSE 0 END) AS total_trasladados,
    SUM(r.capacidad_maxima) as capacidad_total_sistema,
    SUM(r.capacidad_ocupada) as ocupacion_total_sistema
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
JOIN Refugios r ON rr.refugio_id = r.refugio_id;

-- VISTAS PRIVADAS
CREATE VIEW vw_refugio_personas AS
SELECT 
    p.persona_id,
    p.nombre_preferido,
    p.edad_rango,
    p.genero,
    p.idioma_principal,
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
FROM RegistroRefugio rr
JOIN Personas p ON rr.persona_id = p.persona_id
LEFT JOIN Salud s ON p.persona_id = s.persona_id
LEFT JOIN Procedencia pr ON p.persona_id = pr.persona_id;

-- STORED PROCEDURES
DELIMITER //

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
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT capacidad_ocupada, capacidad_maxima INTO v_cap_actual, v_cap_max
    FROM Refugios WHERE refugio_id = p_refugio_id FOR UPDATE;

    IF p_estatus = 'Alojado' AND v_cap_actual >= v_cap_max THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refugio sin capacidad disponible';
    ELSE
        INSERT INTO RegistroRefugio(persona_id, refugio_id, fecha_ingreso, hora_ingreso, area_asignada, estatus, observaciones)
        VALUES (p_persona_id, p_refugio_id, p_fecha_ingreso, p_hora_ingreso, p_area, p_estatus, p_observaciones);

        IF p_estatus = 'Alojado' THEN
            UPDATE Refugios SET capacidad_ocupada = capacidad_ocupada + 1 WHERE refugio_id = p_refugio_id;
        END IF;

        INSERT INTO AuditLog(usuario_id, rol, accion, objeto, objeto_id, resumen)
        VALUES (p_usuario_id, NULL, 'INSERT', 'RegistroRefugio', LAST_INSERT_ID(), 
                CONCAT('Ingreso de persona ', p_persona_id, ' en refugio ', p_refugio_id));
    END IF;

    COMMIT;
END //

CREATE PROCEDURE sp_buscar_personas_publico(
    IN p_search VARCHAR(255),
    IN p_refugio_id INT,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT 
        persona_id,
        nombre,
        edad_rango,
        genero,
        estatus,
        fecha_ingreso,
        hora_ingreso,
        refugio,
        direccion
    FROM vw_public_personas
    WHERE 
        (p_search IS NULL OR p_search = '' OR 
         nombre LIKE CONCAT('%', p_search, '%') OR 
         refugio LIKE CONCAT('%', p_search, '%'))
        AND (p_refugio_id IS NULL OR refugio_id = p_refugio_id)
    ORDER BY fecha_ingreso DESC, hora_ingreso DESC
    LIMIT p_limit OFFSET p_offset;
END //

CREATE PROCEDURE sp_estadisticas_refugio(
    IN p_refugio_id INT
)
BEGIN
    SELECT 
        r.nombre_refugio,
        r.capacidad_maxima,
        r.capacidad_ocupada,
        r.estado,
        COUNT(rr.registro_id) as total_registros,
        SUM(CASE WHEN rr.estatus = 'Alojado' THEN 1 ELSE 0 END) as actualmente_alojados,
        SUM(CASE WHEN rr.estatus = 'Dado de alta' THEN 1 ELSE 0 END) as dados_alta,
        SUM(CASE WHEN rr.estatus = 'Trasladado a otro refugio' THEN 1 ELSE 0 END) as trasladados
    FROM Refugios r
    LEFT JOIN RegistroRefugio rr ON r.refugio_id = rr.refugio_id
    WHERE r.refugio_id = p_refugio_id
    GROUP BY r.refugio_id;
END //

DELIMITER ;

-- DATOS DE PRUEBA
INSERT INTO Refugios (nombre_refugio, ubicacion, lat, lng, fecha_apertura, capacidad_maxima, capacidad_ocupada) VALUES
('Refugio Central Norte', 'Av. Libertadores 1234, Centro', 19.432608, -99.133209, '2024-01-15', 150, 87),
('Albergue Comunitario Sur', 'Calle 5 de Mayo 567, Zona Sur', 19.362464, -99.175529, '2024-01-20', 200, 156),
('Centro de Acogida Este', 'Blvd. Oriente 890, Delegación Este', 19.427847, -99.072472, '2024-02-01', 100, 45),
('Refugio Temporal Oeste', 'Av. Poniente 321, Zona Industrial', 19.404936, -99.194830, '2024-02-10', 75, 73);

INSERT INTO Usuarios (username, password_hash, rol, refugio_id, nombre_mostrado) VALUES
('admin', '$2y$10$8K/B.6H8xvKY9cMQG1FWu.oJ4w4lJl0x3yK8qBzMvV.vNx6kZy2I2', 'Administrador', NULL, 'Administrador del Sistema'),
('refugio1', '$2y$10$8K/B.6H8xvKY9cMQG1FWu.oJ4w4lJl0x3yK8qBzMvV.vNx6kZy2I2', 'Refugio', 1, 'Operador Refugio Norte'),
('refugio2', '$2y$10$8K/B.6H8xvKY9cMQG1FWu.oJ4w4lJl0x3yK8qBzMvV.vNx6kZy2I2', 'Refugio', 2, 'Operador Refugio Sur'),
('auditor1', '$2y$10$8K/B.6H8xvKY9cMQG1FWu.oJ4w4lJl0x3yK8qBzMvV.vNx6kZy2I2', 'Auditor', NULL, 'Auditor General');

INSERT INTO Personas (nombre_preferido, edad_rango, genero, idioma_principal) VALUES
('María González', 'Adulto', 'F', 'Español'),
('José Martínez', 'Adulto mayor', 'M', 'Español'),
('Ana Rodríguez', 'Adolescente', 'F', 'Español'),
('Carlos López', 'Adulto', 'M', 'Español'),
('Laura Hernández', 'Adulto', 'F', 'Español'),
('Miguel Sánchez', 'Niño/a', 'M', 'Español'),
('Carmen Jiménez', 'Adulto mayor', 'F', 'Español'),
('Roberto Morales', 'Adulto', 'M', 'Español');

INSERT INTO Procedencia (persona_id, localidad, situacion, tiene_mascotas, mascotas_detalle) VALUES
(1, 'Colonia Centro', 'Vivienda perdida', FALSE, NULL),
(2, 'Barrio San Juan', 'Temporalmente desplazado', TRUE, '1 perro pequeño'),
(3, 'Colonia Norte', 'Vivienda perdida', FALSE, NULL),
(4, 'Zona Industrial', 'Temporalmente desplazado', FALSE, NULL),
(5, 'Colonia Sur', 'Vivienda perdida', TRUE, '2 gatos'),
(6, 'Barrio San Juan', 'Temporalmente desplazado', FALSE, NULL),
(7, 'Colonia Centro', 'Vivienda perdida', FALSE, NULL),
(8, 'Zona Este', 'Temporalmente desplazado', FALSE, NULL);

INSERT INTO RegistroRefugio (persona_id, refugio_id, fecha_ingreso, hora_ingreso, area_asignada, estatus, observaciones) VALUES
(1, 1, '2024-01-16', '09:30:00', 'Sector A', 'Alojado', 'Familia con necesidades especiales'),
(2, 1, '2024-01-17', '14:15:00', 'Sector B', 'Alojado', 'Adulto mayor requiere asistencia'),
(3, 2, '2024-01-21', '11:00:00', 'Área Familiar', 'Alojado', NULL),
(4, 2, '2024-01-22', '16:45:00', 'Sector C', 'Alojado', NULL),
(5, 3, '2024-02-02', '08:20:00', 'Zona Norte', 'Alojado', 'Con mascotas'),
(6, 2, '2024-01-23', '10:30:00', 'Área Familiar', 'Alojado', 'Menor acompañado'),
(7, 1, '2024-01-18', '13:00:00', 'Sector A', 'Dado de alta', 'Reubicación exitosa'),
(8, 4, '2024-02-11', '15:30:00', 'Área Temporal', 'Alojado', NULL);