-- ============================================================================
-- BASE DE DATOS INTEGRAL PARA GESTIÓN COMUNITARIA (MULTI-COMUNIDAD)
-- ============================================================================

DROP DATABASE IF EXISTS sistema_comunitario_integral;
CREATE DATABASE sistema_comunitario_integral CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_comunitario_integral;

-- 0. TABLA DE CONFIGURACIÓN GENERAL DEL SISTEMA (Logo, nombre y datos base)
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_sistema VARCHAR(150) NOT NULL DEFAULT 'Sistema Comunitario Integral',
    subtitulo VARCHAR(200) DEFAULT 'Gestión Integral de Consejos Comunales y Censos',
    logo_sistema VARCHAR(255) DEFAULT 'uploads/sistema/default_logo.png',
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO configuracion (nombre_sistema, subtitulo, logo_sistema)
VALUES ('Sistema Comunitario Integral', 'Gestión Integral de Consejos Comunales y Censos', 'uploads/sistema/default_logo.png');

-- 1. TABLA MAESTRA DE COMUNIDADES (Consejos Comunales / Sectores)
CREATE TABLE comunidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    rif_o_codigo VARCHAR(30) UNIQUE,
    parroquia VARCHAR(100),
    municipio VARCHAR(100),
    estado VARCHAR(100),
    direccion_referencia TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. TABLA DE CALLES O VEREDAS (Subdivisión interna de cada comunidad)
CREATE TABLE calles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT NOT NULL,
    nombre_calle VARCHAR(100) NOT NULL,
    lider_calle VARCHAR(100),
    telefono_lider VARCHAR(20),
    FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. TABLA DE VIVIENDAS / CASAS
CREATE TABLE viviendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT NOT NULL,
    calle_id INT,
    numero_casa VARCHAR(30) NOT NULL,
    tipo_vivienda ENUM('Casa', 'Apartamento', 'Anexo', 'Rancho', 'Quinta', 'Otro') DEFAULT 'Casa',
    tenencia ENUM('Propia', 'Alquilada', 'Prestada', 'Invadida', 'Pagándose') DEFAULT 'Propia',
    condicion_estructura ENUM('Buena', 'Regular', 'Mala', 'En riesgo') DEFAULT 'Buena',
    posee_agua BOOLEAN DEFAULT TRUE,
    posee_electricidad BOOLEAN DEFAULT TRUE,
    posee_gas_directo BOOLEAN DEFAULT FALSE,
    posee_internet BOOLEAN DEFAULT FALSE,
    observaciones TEXT,
    FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE CASCADE,
    FOREIGN KEY (calle_id) REFERENCES calles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. TABLA DE GRUPOS FAMILIARES (Pueden vivir varias familias en una misma casa)
CREATE TABLE familias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT NOT NULL,
    vivienda_id INT NOT NULL,
    nombre_familia VARCHAR(100) NOT NULL, -- Ej: "Familia Pérez Gómez"
    jefe_familia_cedula VARCHAR(20),
    cantidad_cilindros_gas INT DEFAULT 1,
    ingreso_mensual_aprox DECIMAL(10, 2) DEFAULT 0.00,
    fecha_censo DATE,
    FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE CASCADE,
    FOREIGN KEY (vivienda_id) REFERENCES viviendas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. TABLA DE HABITANTES (Personas, hijos, adultos mayores, etc.)
CREATE TABLE habitantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT NOT NULL,
    familia_id INT NOT NULL,
    nacionalidad ENUM('V', 'E') DEFAULT 'V',
    cedula VARCHAR(20) UNIQUE, -- Puede ser NULL para niños sin cédula
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    sexo ENUM('M', 'F') NOT NULL,
    parentesco ENUM('Jefe de Familia', 'Cónyuge', 'Hijo/a', 'Padre/Madre', 'Nieto/a', 'Abuelo/a', 'Hermano/a', 'Otro') NOT NULL,
    estado_civil ENUM('Soltero/a', 'Casado/a', 'Concubinato', 'Divorciado/a', 'Viudo/a') DEFAULT 'Soltero/a',
    telefono VARCHAR(20),
    correo VARCHAR(100),
    ocupacion VARCHAR(100),
    trabaja_actualmente BOOLEAN DEFAULT FALSE,
    es_estudiante BOOLEAN DEFAULT FALSE,
    posee_discapacidad BOOLEAN DEFAULT FALSE,
    tipo_discapacidad VARCHAR(100),
    enfermedad_cronica VARCHAR(150),
    es_embarazada BOOLEAN DEFAULT FALSE,
    es_lactante BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE, -- FALSE si se muda o fallece
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE CASCADE,
    FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. TABLA DE ESTUDIANTES (Detalle académico de los habitantes marcados como estudiantes)
CREATE TABLE estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habitante_id INT UNIQUE NOT NULL,
    nivel_educativo ENUM('Maternal', 'Preescolar', 'Primaria', 'Secundaria', 'Técnico', 'Universitario', 'Misión Educativa') NOT NULL,
    grado_o_semestre VARCHAR(50),
    nombre_institucion VARCHAR(150),
    tipo_institucion ENUM('Pública', 'Privada') DEFAULT 'Pública',
    requiere_beca_o_utiles BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (habitante_id) REFERENCES habitantes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. TABLA DE BENEFICIOS / ENTREGAS (Control de cajas CLAP, gas, bolsas, bonos, jornadas)
CREATE TABLE beneficios_entregados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT NOT NULL,
    familia_id INT NOT NULL,
    tipo_beneficio VARCHAR(100) NOT NULL, -- Ej: "Bolsa de Alimentación", "Cilindro Gas 10kg", "Medicamentos"
    cantidad INT DEFAULT 1,
    fecha_entrega DATE NOT NULL,
    entregado_por VARCHAR(100),
    observacion VARCHAR(255),
    FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE CASCADE,
    FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. TABLA DE FAMILIARES FALLECIDOS
CREATE TABLE fallecidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT NOT NULL,
    familia_id INT NOT NULL,
    habitante_id INT UNIQUE NOT NULL,
    fecha_fallecimiento DATE NOT NULL,
    causa_fallecimiento VARCHAR(150),
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE CASCADE,
    FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE,
    FOREIGN KEY (habitante_id) REFERENCES habitantes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. TABLA DE USUARIOS DEL SISTEMA (Voceros o administradores)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT, -- NULL si es SuperAdmin de todas las comunidades
    cedula VARCHAR(20) UNIQUE,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    foto VARCHAR(255) DEFAULT 'uploads/usuarios/default_avatar.png',
    rol ENUM('SuperAdmin', 'Administrador Comunidad', 'Vocero/Censador') DEFAULT 'Vocero/Censador',
    estado BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================================
-- AUTOMATIZACIÓN Y VISTAS DE CONSULTA RÁPIDA
-- ============================================================================

-- TRIGGER: Al registrar un fallecido, lo desactiva automáticamente del censo de habitantes vivos
DELIMITER //
CREATE TRIGGER after_insert_fallecido
AFTER INSERT ON fallecidos
FOR EACH ROW
BEGIN
    UPDATE habitantes 
    SET activo = FALSE 
    WHERE id = NEW.habitante_id;
END;
//
DELIMITER ;

-- VISTA 1: Consulta completa de ciudadanos y su núcleo familiar (calcula edad actual)
CREATE OR REPLACE VIEW vista_consulta_ciudadano AS
SELECT 
    h.id AS habitante_id,
    h.nacionalidad,
    h.cedula,
    CONCAT(h.nombres, ' ', h.apellidos) AS nombre_completo,
    h.fecha_nacimiento,
    TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) AS edad_actual,
    h.sexo,
    h.parentesco,
    h.estado_civil,
    h.telefono,
    h.ocupacion,
    h.es_estudiante,
    h.posee_discapacidad,
    h.tipo_discapacidad,
    h.enfermedad_cronica,
    h.activo AS esta_vivo_o_activo,
    f.id AS familia_id,
    f.nombre_familia,
    v.numero_casa,
    v.tipo_vivienda,
    ca.nombre_calle,
    co.id AS comunidad_id,
    co.nombre AS comunidad
FROM habitantes h
INNER JOIN familias f ON h.familia_id = f.id
INNER JOIN viviendas v ON f.vivienda_id = v.id
LEFT JOIN calles ca ON v.calle_id = ca.id
INNER JOIN comunidades co ON h.comunidad_id = co.id;

-- VISTA 2: Historial de fallecidos con año, mes y edad exacta al momento de fallecer
CREATE OR REPLACE VIEW vista_fallecidos_detalle AS
SELECT 
    fa.id AS fallecimiento_id,
    co.id AS comunidad_id,
    co.nombre AS comunidad,
    f.nombre_familia AS familia,
    CONCAT(h.nacionalidad, '-', IFNULL(h.cedula, 'Sin Cédula')) AS cedula,
    CONCAT(h.nombres, ' ', h.apellidos) AS nombre_fallecido,
    h.parentesco,
    fa.fecha_fallecimiento,
    YEAR(fa.fecha_fallecimiento) AS anio_fallecimiento,
    MONTH(fa.fecha_fallecimiento) AS mes_numero,
    CASE MONTH(fa.fecha_fallecimiento)
        WHEN 1 THEN 'Enero' WHEN 2 THEN 'Febrero' WHEN 3 THEN 'Marzo'
        WHEN 4 THEN 'Abril' WHEN 5 THEN 'Mayo' WHEN 6 THEN 'Junio'
        WHEN 7 THEN 'Julio' WHEN 8 THEN 'Agosto' WHEN 9 THEN 'Septiembre'
        WHEN 10 THEN 'Octubre' WHEN 11 THEN 'Noviembre' WHEN 12 THEN 'Diciembre'
    END AS mes_fallecimiento,
    TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, fa.fecha_fallecimiento) AS edad_al_fallecer,
    fa.causa_fallecimiento,
    fa.observaciones
FROM fallecidos fa
INNER JOIN habitantes h ON fa.habitante_id = h.id
INNER JOIN familias f ON fa.familia_id = f.id
INNER JOIN comunidades co ON fa.comunidad_id = co.id;

-- ============================================================================
-- USUARIO INICIAL SUPERADMIN (Reemplaza PEGA_AQUI_EL_HASH por el hash generado)
-- ============================================================================
INSERT INTO usuarios (comunidad_id, cedula, nombre_usuario, password_hash, nombre_completo, rol, estado)
VALUES (NULL, 'V-00000000', 'admin', 'PEGA_AQUI_EL_HASH', 'Administrador General', 'SuperAdmin', 1);

-- ============================================================================
-- ============================================================================
-- ============================================================================

USE sistema_comunitario_integral;

DELETE FROM usuarios WHERE nombre_usuario = 'admin';

INSERT INTO usuarios (comunidad_id, cedula, nombre_usuario, password_hash, nombre_completo, rol, estado)
VALUES (
    NULL,
    'V-00000000',
    'admin',
    '$2y$10$e18oacJ5elEhvqAfJudtweiblPrTT4q7ejDj5zKJkgUVYAVKlp2Hu',
    'Administrador General',
    'SuperAdmin',
    1
);