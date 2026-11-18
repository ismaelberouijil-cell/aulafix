-- Esquema de AulaFix (MySQL 8). Las restricciones (UNIQUE, ENUM, claves foráneas)
-- garantizan la integridad aunque falle la validación de la aplicación.
CREATE DATABASE IF NOT EXISTS aulafix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aulafix;

DROP TABLE IF EXISTS historial;
DROP TABLE IF EXISTS incidencias;
DROP TABLE IF EXISTS equipos;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(80)  NOT NULL,
    email      VARCHAR(190) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    rol        ENUM('alumno','profesor') NOT NULL DEFAULT 'alumno',
    creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE equipos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(20)  NOT NULL UNIQUE,
    descripcion VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE incidencias (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipo_id      INT UNSIGNED NOT NULL,
    usuario_id     INT UNSIGNED NOT NULL,
    categoria      ENUM('hardware','software','red','otro') NOT NULL,
    prioridad      ENUM('baja','media','alta') NOT NULL,
    descripcion    VARCHAR(500) NOT NULL,
    estado         ENUM('abierta','en_curso','resuelta') NOT NULL DEFAULT 'abierta',
    creada_en      DATETIME NOT NULL,
    actualizada_en DATETIME NOT NULL,
    FOREIGN KEY (equipo_id)  REFERENCES equipos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE historial (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incidencia_id   INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    estado_anterior ENUM('abierta','en_curso','resuelta') NOT NULL,
    estado_nuevo    ENUM('abierta','en_curso','resuelta') NOT NULL,
    comentario      VARCHAR(300) NOT NULL,
    creado_en       DATETIME NOT NULL,
    -- El historial no tiene sentido sin su incidencia: se borra con ella.
    FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)    REFERENCES usuarios(id)
) ENGINE=InnoDB;
