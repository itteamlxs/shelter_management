-- config/setup_database.sql

-- Create Database
CREATE DATABASE IF NOT EXISTS shelter_management;

-- Use the Database
USE shelter_management;

-- Create Tables
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
CREATE INDEX idx_refugios_estado ON Refugios(estado);

CREATE TABLE Usuarios (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('Administrador','Refugio','Auditor') NOT NULL,
    refugio_id INT NULL,
    nombre_mostrado VARCHAR(150) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login DATETIME NULL,
    FOREIGN KEY (refugio_id) REFERENCES Refugios(refugio_id) ON DELETE SET NULL
);
CREATE INDEX idx_usuarios_rol ON Usuarios(rol);

CREATE TABLE Personas (
    persona_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_preferido VARCHAR(150) NOT NULL,
    edad_rango ENUM('Niño/a','Adolescente','Adulto','Adulto mayor') NOT NULL,
    genero ENUM('F','M','Otro','Prefiere no decir') NOT NULL,
    idioma_principal VARCHAR(80) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_personas_nombre ON Personas(nombre_preferido);

CREATE TABLE Grupos (
    grupo_id INT AUTO_INCREMENT PRIMARY KEY,
    jefe_grupo_id INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jefe_grupo_id) REFERENCES Personas(persona_id) ON DELETE RESTRICT
);

CREATE TABLE GrupoMiembros (
    grupo_id INT NOT NULL,
    persona_id INT NOT NULL,
    relacion ENUM('Jefe','Esposo/a','Hijo/a','Otro') NOT NULL,
    PRIMARY KEY (grupo_id, persona_id),
    FOREIGN KEY (grupo_id) REFERENCES Grupos(grupo_id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE
);

CREATE TABLE Salud (
    salud_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE,
    condicion_medica TEXT,
    medicamentos TEXT,
    alergias TEXT,
    asistencia_especial TEXT,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE
);

CREATE TABLE Procedencia (
    procedencia_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE,
    localidad VARCHAR(120) NOT NULL,
    situacion ENUM('Vivienda perdida','Temporalmente desplazado') NOT NULL,
    tiene_mascotas BOOLEAN NOT NULL DEFAULT FALSE,
    mascotas_detalle TEXT,
    FOREIGN KEY (persona_id) REFERENCES Personas(persona_id) ON DELETE CASCADE
);

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
CREATE INDEX idx_registro_estatus ON RegistroRefugio(estatus);
CREATE INDEX idx_registro_fecha ON RegistroRefugio(fecha_ingreso);

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