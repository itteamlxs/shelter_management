-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 13, 2025 at 10:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shelter_management`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_actualizar_estatus_registro` (IN `p_registro_id` BIGINT, IN `p_nuevo_estatus` ENUM('Alojado','Dado de alta','Trasladado a otro refugio'), IN `p_observaciones` TEXT, IN `p_usuario_id` INT, IN `p_ip_origen` VARCHAR(45))   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crear_persona_completa` (IN `p_nombre_preferido` VARCHAR(150), IN `p_edad_rango` ENUM('Niño/a','Adolescente','Adulto','Adulto mayor'), IN `p_genero` ENUM('F','M','Otro','Prefiere no decir'), IN `p_idioma_principal` VARCHAR(80), IN `p_condicion_medica` TEXT, IN `p_medicamentos` TEXT, IN `p_alergias` TEXT, IN `p_asistencia_especial` TEXT, IN `p_localidad` VARCHAR(120), IN `p_situacion` ENUM('Vivienda perdida','Temporalmente desplazado'), IN `p_tiene_mascotas` BOOLEAN, IN `p_mascotas_detalle` TEXT, IN `p_usuario_id` INT, IN `p_ip_origen` VARCHAR(45))   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crear_refugio` (IN `p_nombre_refugio` VARCHAR(150), IN `p_ubicacion` VARCHAR(200), IN `p_lat` DECIMAL(9,6), IN `p_lng` DECIMAL(9,6), IN `p_fecha_apertura` DATE, IN `p_capacidad_maxima` INT, IN `p_usuario_id` INT, IN `p_ip_origen` VARCHAR(45))   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_ingreso` (IN `p_persona_id` INT, IN `p_refugio_id` INT, IN `p_fecha_ingreso` DATE, IN `p_hora_ingreso` TIME, IN `p_area` VARCHAR(80), IN `p_estatus` ENUM('Alojado','Dado de alta','Trasladado a otro refugio'), IN `p_observaciones` TEXT, IN `p_usuario_id` INT, IN `p_ip_origen` VARCHAR(45))   BEGIN
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
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `AuditLog`
--

CREATE TABLE `AuditLog` (
  `log_id` bigint(20) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `rol` varchar(50) DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `objeto` varchar(100) NOT NULL,
  `objeto_id` varchar(100) DEFAULT NULL,
  `resumen` text DEFAULT NULL,
  `payload_hash` varchar(128) DEFAULT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `AuditLog`
--

INSERT INTO `AuditLog` (`log_id`, `usuario_id`, `rol`, `accion`, `objeto`, `objeto_id`, `resumen`, `payload_hash`, `ip_origen`, `user_agent`, `creado_en`) VALUES
(1, 1, 'Administrador', 'INSERT', 'RegistroRefugio', '1', 'Registro de ingreso - Persona ID: 1, Refugio ID: 1', NULL, '127.0.0.1', NULL, '2025-08-12 16:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `BulkUploads`
--

CREATE TABLE `BulkUploads` (
  `upload_id` bigint(20) NOT NULL,
  `refugio_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `estado` enum('Pendiente','Validado','Fallido','Procesado') NOT NULL DEFAULT 'Pendiente',
  `mensaje` text DEFAULT NULL,
  `total_filas` int(11) DEFAULT NULL,
  `filas_procesadas` int(11) DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `procesado_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `GrupoMiembros`
--

CREATE TABLE `GrupoMiembros` (
  `grupo_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `relacion` enum('Jefe','Esposo/a','Hijo/a','Otro') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `GrupoMiembros`
--

INSERT INTO `GrupoMiembros` (`grupo_id`, `persona_id`, `relacion`) VALUES
(1, 1, 'Jefe'),
(1, 3, 'Hijo/a'),
(2, 2, 'Jefe'),
(2, 6, 'Hijo/a'),
(3, 4, 'Jefe'),
(3, 7, 'Esposo/a'),
(4, 8, 'Jefe'),
(4, 9, 'Hijo/a');

-- --------------------------------------------------------

--
-- Table structure for table `Grupos`
--

CREATE TABLE `Grupos` (
  `grupo_id` int(11) NOT NULL,
  `jefe_grupo_id` int(11) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Grupos`
--

INSERT INTO `Grupos` (`grupo_id`, `jefe_grupo_id`, `creado_en`) VALUES
(1, 1, '2025-08-12 16:13:17'),
(2, 2, '2025-08-12 16:13:17'),
(3, 4, '2025-08-12 16:13:17'),
(4, 8, '2025-08-12 16:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `LoginAttempts`
--

CREATE TABLE `LoginAttempts` (
  `attempt_id` bigint(20) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Personas`
--

CREATE TABLE `Personas` (
  `persona_id` int(11) NOT NULL,
  `nombre_preferido` varchar(150) NOT NULL,
  `edad_rango` enum('Niño/a','Adolescente','Adulto','Adulto mayor') NOT NULL,
  `genero` enum('F','M','Otro','Prefiere no decir') NOT NULL,
  `idioma_principal` varchar(80) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Personas`
--

INSERT INTO `Personas` (`persona_id`, `nombre_preferido`, `edad_rango`, `genero`, `idioma_principal`, `creado_en`) VALUES
(1, 'María García López', 'Adulto', 'F', 'Español', '2025-08-12 16:13:17'),
(2, 'Carlos López Martínez', 'Adulto', 'M', 'Español', '2025-08-12 16:13:17'),
(3, 'Ana Rodríguez Silva', 'Niño/a', 'F', 'Español', '2025-08-12 16:13:17'),
(4, 'Roberto Silva Hernández', 'Adulto mayor', 'M', 'Español', '2025-08-12 16:13:17'),
(5, 'Carmen Jiménez Torres', 'Adulto', 'F', 'Español', '2025-08-12 16:13:17'),
(6, 'Luis Morales Castro', 'Adolescente', 'M', 'Español', '2025-08-12 16:13:17'),
(7, 'Elena Vargas Ruiz', 'Adulto mayor', 'F', 'Español', '2025-08-12 16:13:17'),
(8, 'Pedro Sandoval Ramos', 'Adulto', 'M', 'Español', '2025-08-12 16:13:17'),
(9, 'Sofia Mendez Cruz', 'Niño/a', 'F', 'Español', '2025-08-12 16:13:17'),
(10, 'Miguel Fernández Ortiz', 'Adulto', 'M', 'Español', '2025-08-12 16:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `Procedencia`
--

CREATE TABLE `Procedencia` (
  `procedencia_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `localidad` varchar(120) NOT NULL,
  `situacion` enum('Vivienda perdida','Temporalmente desplazado') NOT NULL,
  `tiene_mascotas` tinyint(1) NOT NULL DEFAULT 0,
  `mascotas_detalle` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Procedencia`
--

INSERT INTO `Procedencia` (`procedencia_id`, `persona_id`, `localidad`, `situacion`, `tiene_mascotas`, `mascotas_detalle`) VALUES
(1, 1, 'Colonia Centro', 'Vivienda perdida', 0, NULL),
(2, 2, 'Barrio Industrial', 'Temporalmente desplazado', 1, '1 perro pequeño, raza mestiza'),
(3, 3, 'Colonia Centro', 'Vivienda perdida', 0, NULL),
(4, 4, 'Zona Comercial', 'Vivienda perdida', 1, '2 gatos domésticos'),
(5, 5, 'Fraccionamiento Las Flores', 'Temporalmente desplazado', 0, NULL),
(6, 6, 'Barrio Industrial', 'Temporalmente desplazado', 0, NULL),
(7, 7, 'Colonia Residencial', 'Vivienda perdida', 1, '1 perro grande, labrador'),
(8, 8, 'Zona Rural Norte', 'Vivienda perdida', 1, '3 gallinas, 1 gato'),
(9, 9, 'Colonia Popular', 'Temporalmente desplazado', 0, NULL),
(10, 10, 'Barrio Histórico', 'Vivienda perdida', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `RefreshTokens`
--

CREATE TABLE `RefreshTokens` (
  `token_id` bigint(20) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token_hash` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Refugios`
--

CREATE TABLE `Refugios` (
  `refugio_id` int(11) NOT NULL,
  `nombre_refugio` varchar(150) NOT NULL,
  `ubicacion` varchar(200) NOT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL,
  `fecha_apertura` date NOT NULL,
  `capacidad_maxima` int(11) NOT NULL,
  `capacidad_ocupada` int(11) NOT NULL DEFAULT 0,
  `estado` enum('Disponible','Completo') GENERATED ALWAYS AS (case when `capacidad_ocupada` >= `capacidad_maxima` then 'Completo' else 'Disponible' end) STORED,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Refugios`
--

INSERT INTO `Refugios` (`refugio_id`, `nombre_refugio`, `ubicacion`, `lat`, `lng`, `fecha_apertura`, `capacidad_maxima`, `capacidad_ocupada`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'Refugio Central Norte', 'Av. Principal 123, Zona Norte', 19.432608, -99.133209, '2024-01-15', 100, 1, 1, '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(2, 'Refugio Escuela Sur', 'Calle Secundaria 456, Zona Sur', 19.362608, -99.143209, '2024-01-16', 150, 0, 1, '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(3, 'Refugio Deportivo Este', 'Complejo Deportivo 789, Zona Este', 19.422608, -99.123209, '2024-01-17', 200, 0, 1, '2025-08-12 16:13:17', '2025-08-12 16:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `RegistroRefugio`
--

CREATE TABLE `RegistroRefugio` (
  `registro_id` bigint(20) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `refugio_id` int(11) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `hora_ingreso` time NOT NULL,
  `area_asignada` varchar(80) NOT NULL,
  `estatus` enum('Alojado','Dado de alta','Trasladado a otro refugio') NOT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `RegistroRefugio`
--

INSERT INTO `RegistroRefugio` (`registro_id`, `persona_id`, `refugio_id`, `fecha_ingreso`, `hora_ingreso`, `area_asignada`, `estatus`, `observaciones`, `creado_en`, `actualizado_en`) VALUES
(1, 1, 1, '2024-08-10', '10:30:00', 'Área A-001', 'Alojado', 'Familia con menor - madre diabética', '2025-08-12 16:13:17', '2025-08-12 16:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `Salud`
--

CREATE TABLE `Salud` (
  `salud_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `condicion_medica` text DEFAULT NULL,
  `medicamentos` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `asistencia_especial` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Salud`
--

INSERT INTO `Salud` (`salud_id`, `persona_id`, `condicion_medica`, `medicamentos`, `alergias`, `asistencia_especial`) VALUES
(1, 1, 'Diabetes tipo 2', 'Metformina 500mg cada 12 horas', 'Penicilina', 'Dieta especial baja en azúcar'),
(2, 4, 'Hipertensión arterial', 'Losartán 50mg diario', 'Ninguna conocida', 'Control de presión arterial diario'),
(3, 7, 'Artritis reumatoide', 'Ibuprofeno 400mg cada 8 horas', 'Aspirina', 'Ayuda para movilidad'),
(4, 2, 'Asma bronquial', 'Salbutamol inhalador según necesidad', 'Polen, ácaros', 'Ambiente libre de humo'),
(5, 8, 'Gastritis crónica', 'Omeprazol 20mg diario', 'Ninguna conocida', 'Dieta blanda');

-- --------------------------------------------------------

--
-- Table structure for table `SystemConfig`
--

CREATE TABLE `SystemConfig` (
  `config_id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text NOT NULL,
  `config_type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `SystemConfig`
--

INSERT INTO `SystemConfig` (`config_id`, `config_key`, `config_value`, `config_type`, `description`, `creado_en`, `actualizado_en`) VALUES
(1, 'app_name', 'Sistema de Gestión de Refugios', 'string', 'Nombre de la aplicación', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(2, 'app_version', '1.0.0', 'string', 'Versión de la aplicación', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(3, 'max_upload_size', '5242880', 'integer', 'Tamaño máximo de archivo en bytes (5MB)', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(4, 'csv_max_rows', '5000', 'integer', 'Máximo número de filas por CSV', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(5, 'enable_registration', 'false', 'boolean', 'Permitir auto-registro de usuarios', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(6, 'maintenance_mode', 'false', 'boolean', 'Modo de mantenimiento', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(7, 'contact_email', 'admin@refugios.gob', 'string', 'Email de contacto del sistema', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(8, 'privacy_policy_url', '', 'string', 'URL de la política de privacidad', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(9, 'terms_of_service_url', '', 'string', 'URL de términos de servicio', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(10, 'jwt_expiry_minutes', '60', 'integer', 'Tiempo de expiración del JWT en minutos', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(11, 'refresh_token_days', '7', 'integer', 'Tiempo de expiración del refresh token en días', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(12, 'max_login_attempts', '5', 'integer', 'Máximo número de intentos de login', '2025-08-12 16:13:17', '2025-08-12 16:13:17'),
(13, 'login_cooldown_minutes', '15', 'integer', 'Tiempo de bloqueo tras intentos fallidos', '2025-08-12 16:13:17', '2025-08-12 16:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `Usuarios`
--

CREATE TABLE `Usuarios` (
  `usuario_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('Administrador','Refugio','Auditor') NOT NULL,
  `refugio_id` int(11) DEFAULT NULL,
  `nombre_mostrado` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_login` datetime DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Usuarios`
--

INSERT INTO `Usuarios` (`usuario_id`, `username`, `password_hash`, `rol`, `refugio_id`, `nombre_mostrado`, `activo`, `creado_en`, `ultimo_login`, `actualizado_en`) VALUES
(1, 'admin', '$2y$12$LQv3c1yqBWVHxkd0LQ4cc.93i3ijpvm91Zw3hb8/g4fh.z1mFQqSm', 'Administrador', NULL, 'Administrador del Sistema', 1, '2025-08-12 16:13:17', NULL, '2025-08-12 16:13:17'),
(2, 'refugio_norte', '$2y$12$LQv3c1yqBWVHxkd0LQ4cc.93i3ijpvm91Zw3hb8/g4fh.z1mFQqSm', 'Refugio', 1, 'Operador Refugio Norte', 1, '2025-08-12 16:13:17', NULL, '2025-08-12 16:13:17'),
(3, 'refugio_sur', '$2y$12$LQv3c1yqBWVHxkd0LQ4cc.93i3ijpvm91Zw3hb8/g4fh.z1mFQqSm', 'Refugio', 2, 'Operador Refugio Sur', 1, '2025-08-12 16:13:17', NULL, '2025-08-12 16:13:17'),
(4, 'auditor_general', '$2y$12$LQv3c1yqBWVHxkd0LQ4cc.93i3ijpvm91Zw3hb8/g4fh.z1mFQqSm', 'Auditor', NULL, 'Auditor General', 1, '2025-08-12 16:13:17', NULL, '2025-08-12 16:13:17');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_admin_personas_full`
-- (See below for the actual view)
--
CREATE TABLE `vw_admin_personas_full` (
`persona_id` int(11)
,`nombre_preferido` varchar(150)
,`edad_rango` enum('Niño/a','Adolescente','Adulto','Adulto mayor')
,`genero` enum('F','M','Otro','Prefiere no decir')
,`idioma_principal` varchar(80)
,`creado_en` datetime
,`registro_id` bigint(20)
,`refugio_id` int(11)
,`fecha_ingreso` date
,`hora_ingreso` time
,`area_asignada` varchar(80)
,`estatus` enum('Alojado','Dado de alta','Trasladado a otro refugio')
,`observaciones` text
,`fecha_registro` datetime
,`fecha_actualizacion` datetime
,`condicion_medica` text
,`medicamentos` text
,`alergias` text
,`asistencia_especial` text
,`localidad` varchar(120)
,`situacion` enum('Vivienda perdida','Temporalmente desplazado')
,`tiene_mascotas` tinyint(1)
,`mascotas_detalle` text
,`nombre_refugio` varchar(150)
,`refugio_ubicacion` varchar(200)
,`grupo_id` int(11)
,`relacion` enum('Jefe','Esposo/a','Hijo/a','Otro')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_auditor_activity`
-- (See below for the actual view)
--
CREATE TABLE `vw_auditor_activity` (
`log_id` bigint(20)
,`usuario_id` int(11)
,`username` varchar(100)
,`nombre_mostrado` varchar(150)
,`rol` varchar(50)
,`accion` varchar(50)
,`objeto` varchar(100)
,`objeto_id` varchar(100)
,`resumen` text
,`ip_origen` varchar(45)
,`user_agent` text
,`creado_en` datetime
,`nombre_refugio` varchar(150)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_public_estadisticas`
-- (See below for the actual view)
--
CREATE TABLE `vw_public_estadisticas` (
`total_personas` bigint(21)
,`total_alojados` decimal(22,0)
,`total_dados_alta` decimal(22,0)
,`total_trasladados` decimal(22,0)
,`total_refugios` bigint(21)
,`ocupacion_promedio` decimal(21,8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_public_personas`
-- (See below for the actual view)
--
CREATE TABLE `vw_public_personas` (
`persona_id` int(11)
,`nombre` varchar(150)
,`edad_rango` enum('Niño/a','Adolescente','Adulto','Adulto mayor')
,`genero` enum('F','M','Otro','Prefiere no decir')
,`estatus` enum('Alojado','Dado de alta','Trasladado a otro refugio')
,`fecha_ingreso` date
,`hora_ingreso` time
,`refugio` varchar(150)
,`direccion` varchar(200)
,`refugio_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_public_refugios`
-- (See below for the actual view)
--
CREATE TABLE `vw_public_refugios` (
`refugio_id` int(11)
,`nombre_refugio` varchar(150)
,`ubicacion` varchar(200)
,`lat` decimal(9,6)
,`lng` decimal(9,6)
,`fecha_apertura` date
,`capacidad_maxima` int(11)
,`capacidad_ocupada` int(11)
,`estado` enum('Disponible','Completo')
,`total_registros` bigint(21)
,`personas_actuales` decimal(22,0)
,`personas_dadas_alta` decimal(22,0)
,`personas_trasladadas` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_refugio_personas`
-- (See below for the actual view)
--
CREATE TABLE `vw_refugio_personas` (
`persona_id` int(11)
,`nombre_preferido` varchar(150)
,`edad_rango` enum('Niño/a','Adolescente','Adulto','Adulto mayor')
,`genero` enum('F','M','Otro','Prefiere no decir')
,`idioma_principal` varchar(80)
,`registro_id` bigint(20)
,`refugio_id` int(11)
,`fecha_ingreso` date
,`hora_ingreso` time
,`area_asignada` varchar(80)
,`estatus` enum('Alojado','Dado de alta','Trasladado a otro refugio')
,`observaciones` text
,`actualizado_en` datetime
,`condicion_medica` text
,`medicamentos` text
,`alergias` text
,`asistencia_especial` text
,`localidad` varchar(120)
,`situacion` enum('Vivienda perdida','Temporalmente desplazado')
,`tiene_mascotas` tinyint(1)
,`mascotas_detalle` text
,`grupo_id` int(11)
,`relacion` enum('Jefe','Esposo/a','Hijo/a','Otro')
);

-- --------------------------------------------------------

--
-- Structure for view `vw_admin_personas_full`
--
DROP TABLE IF EXISTS `vw_admin_personas_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_admin_personas_full`  AS SELECT `p`.`persona_id` AS `persona_id`, `p`.`nombre_preferido` AS `nombre_preferido`, `p`.`edad_rango` AS `edad_rango`, `p`.`genero` AS `genero`, `p`.`idioma_principal` AS `idioma_principal`, `p`.`creado_en` AS `creado_en`, `rr`.`registro_id` AS `registro_id`, `rr`.`refugio_id` AS `refugio_id`, `rr`.`fecha_ingreso` AS `fecha_ingreso`, `rr`.`hora_ingreso` AS `hora_ingreso`, `rr`.`area_asignada` AS `area_asignada`, `rr`.`estatus` AS `estatus`, `rr`.`observaciones` AS `observaciones`, `rr`.`creado_en` AS `fecha_registro`, `rr`.`actualizado_en` AS `fecha_actualizacion`, `s`.`condicion_medica` AS `condicion_medica`, `s`.`medicamentos` AS `medicamentos`, `s`.`alergias` AS `alergias`, `s`.`asistencia_especial` AS `asistencia_especial`, `pr`.`localidad` AS `localidad`, `pr`.`situacion` AS `situacion`, `pr`.`tiene_mascotas` AS `tiene_mascotas`, `pr`.`mascotas_detalle` AS `mascotas_detalle`, `r`.`nombre_refugio` AS `nombre_refugio`, `r`.`ubicacion` AS `refugio_ubicacion`, `g`.`grupo_id` AS `grupo_id`, `gm`.`relacion` AS `relacion` FROM ((((((`Personas` `p` left join `RegistroRefugio` `rr` on(`p`.`persona_id` = `rr`.`persona_id`)) left join `Salud` `s` on(`p`.`persona_id` = `s`.`persona_id`)) left join `Procedencia` `pr` on(`p`.`persona_id` = `pr`.`persona_id`)) left join `Refugios` `r` on(`rr`.`refugio_id` = `r`.`refugio_id`)) left join `GrupoMiembros` `gm` on(`p`.`persona_id` = `gm`.`persona_id`)) left join `Grupos` `g` on(`gm`.`grupo_id` = `g`.`grupo_id`)) WHERE `r`.`activo` = 1 OR `r`.`activo` is null ;

-- --------------------------------------------------------

--
-- Structure for view `vw_auditor_activity`
--
DROP TABLE IF EXISTS `vw_auditor_activity`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_auditor_activity`  AS SELECT `al`.`log_id` AS `log_id`, `al`.`usuario_id` AS `usuario_id`, `u`.`username` AS `username`, `u`.`nombre_mostrado` AS `nombre_mostrado`, `al`.`rol` AS `rol`, `al`.`accion` AS `accion`, `al`.`objeto` AS `objeto`, `al`.`objeto_id` AS `objeto_id`, `al`.`resumen` AS `resumen`, `al`.`ip_origen` AS `ip_origen`, `al`.`user_agent` AS `user_agent`, `al`.`creado_en` AS `creado_en`, `r`.`nombre_refugio` AS `nombre_refugio` FROM ((`AuditLog` `al` left join `Usuarios` `u` on(`al`.`usuario_id` = `u`.`usuario_id`)) left join `Refugios` `r` on(`u`.`refugio_id` = `r`.`refugio_id`)) ORDER BY `al`.`creado_en` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_public_estadisticas`
--
DROP TABLE IF EXISTS `vw_public_estadisticas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_public_estadisticas`  AS SELECT count(distinct `p`.`persona_id`) AS `total_personas`, sum(case when `rr`.`estatus` = 'Alojado' then 1 else 0 end) AS `total_alojados`, sum(case when `rr`.`estatus` = 'Dado de alta' then 1 else 0 end) AS `total_dados_alta`, sum(case when `rr`.`estatus` = 'Trasladado a otro refugio' then 1 else 0 end) AS `total_trasladados`, count(distinct `r`.`refugio_id`) AS `total_refugios`, coalesce(avg(`r`.`capacidad_ocupada` / `r`.`capacidad_maxima` * 100),0) AS `ocupacion_promedio` FROM ((`RegistroRefugio` `rr` join `Personas` `p` on(`rr`.`persona_id` = `p`.`persona_id`)) join `Refugios` `r` on(`rr`.`refugio_id` = `r`.`refugio_id`)) WHERE `r`.`activo` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_public_personas`
--
DROP TABLE IF EXISTS `vw_public_personas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_public_personas`  AS SELECT `p`.`persona_id` AS `persona_id`, `p`.`nombre_preferido` AS `nombre`, `p`.`edad_rango` AS `edad_rango`, `p`.`genero` AS `genero`, `rr`.`estatus` AS `estatus`, `rr`.`fecha_ingreso` AS `fecha_ingreso`, `rr`.`hora_ingreso` AS `hora_ingreso`, `r`.`nombre_refugio` AS `refugio`, `r`.`ubicacion` AS `direccion`, `r`.`refugio_id` AS `refugio_id` FROM ((`RegistroRefugio` `rr` join `Personas` `p` on(`rr`.`persona_id` = `p`.`persona_id`)) join `Refugios` `r` on(`rr`.`refugio_id` = `r`.`refugio_id`)) WHERE `rr`.`estatus` = 'Alojado' AND `r`.`activo` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_public_refugios`
--
DROP TABLE IF EXISTS `vw_public_refugios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_public_refugios`  AS SELECT `r`.`refugio_id` AS `refugio_id`, `r`.`nombre_refugio` AS `nombre_refugio`, `r`.`ubicacion` AS `ubicacion`, `r`.`lat` AS `lat`, `r`.`lng` AS `lng`, `r`.`fecha_apertura` AS `fecha_apertura`, `r`.`capacidad_maxima` AS `capacidad_maxima`, `r`.`capacidad_ocupada` AS `capacidad_ocupada`, `r`.`estado` AS `estado`, count(`rr`.`registro_id`) AS `total_registros`, sum(case when `rr`.`estatus` = 'Alojado' then 1 else 0 end) AS `personas_actuales`, sum(case when `rr`.`estatus` = 'Dado de alta' then 1 else 0 end) AS `personas_dadas_alta`, sum(case when `rr`.`estatus` = 'Trasladado a otro refugio' then 1 else 0 end) AS `personas_trasladadas` FROM (`Refugios` `r` left join `RegistroRefugio` `rr` on(`r`.`refugio_id` = `rr`.`refugio_id`)) WHERE `r`.`activo` = 1 GROUP BY `r`.`refugio_id`, `r`.`nombre_refugio`, `r`.`ubicacion`, `r`.`lat`, `r`.`lng`, `r`.`fecha_apertura`, `r`.`capacidad_maxima`, `r`.`capacidad_ocupada`, `r`.`estado` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_refugio_personas`
--
DROP TABLE IF EXISTS `vw_refugio_personas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_refugio_personas`  AS SELECT `p`.`persona_id` AS `persona_id`, `p`.`nombre_preferido` AS `nombre_preferido`, `p`.`edad_rango` AS `edad_rango`, `p`.`genero` AS `genero`, `p`.`idioma_principal` AS `idioma_principal`, `rr`.`registro_id` AS `registro_id`, `rr`.`refugio_id` AS `refugio_id`, `rr`.`fecha_ingreso` AS `fecha_ingreso`, `rr`.`hora_ingreso` AS `hora_ingreso`, `rr`.`area_asignada` AS `area_asignada`, `rr`.`estatus` AS `estatus`, `rr`.`observaciones` AS `observaciones`, `rr`.`actualizado_en` AS `actualizado_en`, `s`.`condicion_medica` AS `condicion_medica`, `s`.`medicamentos` AS `medicamentos`, `s`.`alergias` AS `alergias`, `s`.`asistencia_especial` AS `asistencia_especial`, `pr`.`localidad` AS `localidad`, `pr`.`situacion` AS `situacion`, `pr`.`tiene_mascotas` AS `tiene_mascotas`, `pr`.`mascotas_detalle` AS `mascotas_detalle`, `g`.`grupo_id` AS `grupo_id`, `gm`.`relacion` AS `relacion` FROM ((((((`RegistroRefugio` `rr` join `Personas` `p` on(`rr`.`persona_id` = `p`.`persona_id`)) join `Refugios` `r` on(`rr`.`refugio_id` = `r`.`refugio_id`)) left join `Salud` `s` on(`p`.`persona_id` = `s`.`persona_id`)) left join `Procedencia` `pr` on(`p`.`persona_id` = `pr`.`persona_id`)) left join `GrupoMiembros` `gm` on(`p`.`persona_id` = `gm`.`persona_id`)) left join `Grupos` `g` on(`gm`.`grupo_id` = `g`.`grupo_id`)) WHERE `r`.`activo` = 1 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `AuditLog`
--
ALTER TABLE `AuditLog`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_audit_usuario` (`usuario_id`),
  ADD KEY `idx_audit_accion` (`accion`),
  ADD KEY `idx_audit_objeto` (`objeto`),
  ADD KEY `idx_audit_fecha` (`creado_en`),
  ADD KEY `idx_audit_ip` (`ip_origen`);

--
-- Indexes for table `BulkUploads`
--
ALTER TABLE `BulkUploads`
  ADD PRIMARY KEY (`upload_id`),
  ADD KEY `idx_bulk_refugio` (`refugio_id`),
  ADD KEY `idx_bulk_estado` (`estado`),
  ADD KEY `idx_bulk_fecha` (`creado_en`);

--
-- Indexes for table `GrupoMiembros`
--
ALTER TABLE `GrupoMiembros`
  ADD PRIMARY KEY (`grupo_id`,`persona_id`),
  ADD KEY `idx_grupo_miembros_persona` (`persona_id`);

--
-- Indexes for table `Grupos`
--
ALTER TABLE `Grupos`
  ADD PRIMARY KEY (`grupo_id`),
  ADD KEY `idx_grupos_jefe` (`jefe_grupo_id`);

--
-- Indexes for table `LoginAttempts`
--
ALTER TABLE `LoginAttempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_login_username` (`username`),
  ADD KEY `idx_login_ip` (`ip_address`),
  ADD KEY `idx_login_time` (`attempted_at`),
  ADD KEY `idx_login_success` (`success`);

--
-- Indexes for table `Personas`
--
ALTER TABLE `Personas`
  ADD PRIMARY KEY (`persona_id`),
  ADD KEY `idx_personas_nombre` (`nombre_preferido`),
  ADD KEY `idx_personas_edad` (`edad_rango`),
  ADD KEY `idx_personas_genero` (`genero`);

--
-- Indexes for table `Procedencia`
--
ALTER TABLE `Procedencia`
  ADD PRIMARY KEY (`procedencia_id`),
  ADD UNIQUE KEY `persona_id` (`persona_id`),
  ADD KEY `idx_procedencia_persona` (`persona_id`),
  ADD KEY `idx_procedencia_localidad` (`localidad`);

--
-- Indexes for table `RefreshTokens`
--
ALTER TABLE `RefreshTokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `idx_refresh_token_hash` (`token_hash`),
  ADD KEY `idx_refresh_expires` (`expires_at`),
  ADD KEY `idx_refresh_usuario` (`usuario_id`),
  ADD KEY `idx_refresh_revoked` (`revoked`);

--
-- Indexes for table `Refugios`
--
ALTER TABLE `Refugios`
  ADD PRIMARY KEY (`refugio_id`),
  ADD UNIQUE KEY `unique_nombre_refugio` (`nombre_refugio`),
  ADD KEY `idx_refugios_estado` (`estado`),
  ADD KEY `idx_refugios_activo` (`activo`),
  ADD KEY `idx_refugios_coords` (`lat`,`lng`);

--
-- Indexes for table `RegistroRefugio`
--
ALTER TABLE `RegistroRefugio`
  ADD PRIMARY KEY (`registro_id`),
  ADD KEY `idx_registro_estatus` (`estatus`),
  ADD KEY `idx_registro_fecha` (`fecha_ingreso`),
  ADD KEY `idx_registro_persona` (`persona_id`),
  ADD KEY `idx_registro_refugio` (`refugio_id`),
  ADD KEY `idx_registro_compuesto` (`refugio_id`,`estatus`,`fecha_ingreso`);

--
-- Indexes for table `Salud`
--
ALTER TABLE `Salud`
  ADD PRIMARY KEY (`salud_id`),
  ADD UNIQUE KEY `persona_id` (`persona_id`),
  ADD KEY `idx_salud_persona` (`persona_id`);

--
-- Indexes for table `SystemConfig`
--
ALTER TABLE `SystemConfig`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `config_key` (`config_key`),
  ADD KEY `idx_config_key` (`config_key`),
  ADD KEY `idx_config_type` (`config_type`);

--
-- Indexes for table `Usuarios`
--
ALTER TABLE `Usuarios`
  ADD PRIMARY KEY (`usuario_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_usuarios_rol` (`rol`),
  ADD KEY `idx_usuarios_activo` (`activo`),
  ADD KEY `idx_usuarios_refugio` (`refugio_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `AuditLog`
--
ALTER TABLE `AuditLog`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `BulkUploads`
--
ALTER TABLE `BulkUploads`
  MODIFY `upload_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Grupos`
--
ALTER TABLE `Grupos`
  MODIFY `grupo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `LoginAttempts`
--
ALTER TABLE `LoginAttempts`
  MODIFY `attempt_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Personas`
--
ALTER TABLE `Personas`
  MODIFY `persona_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `Procedencia`
--
ALTER TABLE `Procedencia`
  MODIFY `procedencia_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `RefreshTokens`
--
ALTER TABLE `RefreshTokens`
  MODIFY `token_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Refugios`
--
ALTER TABLE `Refugios`
  MODIFY `refugio_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `RegistroRefugio`
--
ALTER TABLE `RegistroRefugio`
  MODIFY `registro_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Salud`
--
ALTER TABLE `Salud`
  MODIFY `salud_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `SystemConfig`
--
ALTER TABLE `SystemConfig`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `Usuarios`
--
ALTER TABLE `Usuarios`
  MODIFY `usuario_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `AuditLog`
--
ALTER TABLE `AuditLog`
  ADD CONSTRAINT `AuditLog_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`usuario_id`) ON DELETE SET NULL;

--
-- Constraints for table `BulkUploads`
--
ALTER TABLE `BulkUploads`
  ADD CONSTRAINT `BulkUploads_ibfk_1` FOREIGN KEY (`refugio_id`) REFERENCES `Refugios` (`refugio_id`) ON DELETE CASCADE;

--
-- Constraints for table `GrupoMiembros`
--
ALTER TABLE `GrupoMiembros`
  ADD CONSTRAINT `GrupoMiembros_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `Grupos` (`grupo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `GrupoMiembros_ibfk_2` FOREIGN KEY (`persona_id`) REFERENCES `Personas` (`persona_id`) ON DELETE CASCADE;

--
-- Constraints for table `Grupos`
--
ALTER TABLE `Grupos`
  ADD CONSTRAINT `Grupos_ibfk_1` FOREIGN KEY (`jefe_grupo_id`) REFERENCES `Personas` (`persona_id`);

--
-- Constraints for table `Procedencia`
--
ALTER TABLE `Procedencia`
  ADD CONSTRAINT `Procedencia_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `Personas` (`persona_id`) ON DELETE CASCADE;

--
-- Constraints for table `RefreshTokens`
--
ALTER TABLE `RefreshTokens`
  ADD CONSTRAINT `RefreshTokens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`usuario_id`) ON DELETE CASCADE;

--
-- Constraints for table `RegistroRefugio`
--
ALTER TABLE `RegistroRefugio`
  ADD CONSTRAINT `RegistroRefugio_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `Personas` (`persona_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `RegistroRefugio_ibfk_2` FOREIGN KEY (`refugio_id`) REFERENCES `Refugios` (`refugio_id`) ON DELETE CASCADE;

--
-- Constraints for table `Salud`
--
ALTER TABLE `Salud`
  ADD CONSTRAINT `Salud_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `Personas` (`persona_id`) ON DELETE CASCADE;

--
-- Constraints for table `Usuarios`
--
ALTER TABLE `Usuarios`
  ADD CONSTRAINT `Usuarios_ibfk_1` FOREIGN KEY (`refugio_id`) REFERENCES `Refugios` (`refugio_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
