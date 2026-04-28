-- ============================================================
-- SISTEMA EDUCATIVO — Esquema completo v2.0
-- Motor:   MySQL 8.0+
-- Charset: utf8mb4 / utf8mb4_unicode_ci
-- Autor:   TFG DAW
-- ============================================================
-- ORDEN DE CREACIÓN (respeta dependencias entre tablas)
--
--  1. programas
--  2. categorias
--  3. usuarios
--  4. cursos
--  5. cursos_categorias       (pivote M:M)
--  6. inscripciones
--  7. unidades                (antes llamadas "lecciones")
--  8. asistencias
--  9. recursos_documentales
-- 10. notificaciones
-- 11. intentos_login
-- 12. registros_actividad
-- 13. consultas_ia
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_educativo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sistema_educativo;

-- ============================================================
-- 1. PROGRAMAS  (Ciclos formativos: DAW, DAM, ASIR…)
--    Un programa agrupa varios cursos/módulos.
--    Los cursos pueden existir SIN programa (curso corto).
-- ============================================================
CREATE TABLE programas (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(200)    NOT NULL,
  descripcion     TEXT                NULL,
  duracion_horas  SMALLINT UNSIGNED   NULL  COMMENT 'Duración total del ciclo en horas',
  activo          TINYINT(1)      NOT NULL DEFAULT 1,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_programa_nombre (nombre)
) ENGINE=InnoDB COMMENT='Ciclos formativos (DAW, DAM, ASIR, etc.)';


-- ============================================================
-- 2. CATEGORÍAS  (Desarrollo Web, Diseño, Negocios…)
--    Relación M:M con cursos a través de cursos_categorias.
-- ============================================================
CREATE TABLE categorias (
  id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100)    NOT NULL,
  descripcion VARCHAR(255)        NULL,
  color_hex   VARCHAR(7)          NULL  COMMENT 'Color para la UI ej: #5b6af0',
  created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categoria_nombre (nombre)
) ENGINE=InnoDB COMMENT='Temáticas para agrupar cursos';


-- ============================================================
-- 3. USUARIOS  (alumnos, profesores y administradores)
-- ============================================================
CREATE TABLE usuarios (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(80)     NOT NULL,
  apellidos       VARCHAR(100)    NOT NULL,
  correo          VARCHAR(150)    NOT NULL,
  contrasena      VARCHAR(255)    NOT NULL  COMMENT 'Hash bcrypt',
  rol             ENUM('alumno','profesor','admin')
                                  NOT NULL DEFAULT 'alumno',
  activo          TINYINT(1)      NOT NULL DEFAULT 1,
  avatar_url      VARCHAR(512)        NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuario_correo (correo),
  INDEX idx_rol (rol)
) ENGINE=InnoDB COMMENT='Todos los usuarios del sistema';


-- ============================================================
-- 4. CURSOS  (Módulos de ciclo O cursos cortos independientes)
--    programa_id es NULLABLE → permite cursos independientes
--    nivel: basico | intermedio | avanzado
-- ============================================================
CREATE TABLE cursos (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  programa_id     INT UNSIGNED        NULL  COMMENT 'NULL = curso corto independiente',
  profesor_id     INT UNSIGNED    NOT NULL,
  nombre          VARCHAR(200)    NOT NULL,
  descripcion     TEXT                NULL,
  horas_totales   SMALLINT UNSIGNED   NULL,
  nivel           ENUM('basico','intermedio','avanzado')
                                  NOT NULL DEFAULT 'basico',
  imagen_portada  VARCHAR(512)        NULL,
  activo          TINYINT(1)      NOT NULL DEFAULT 1,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_curso_nombre (nombre),
  CONSTRAINT fk_curso_programa
    FOREIGN KEY (programa_id) REFERENCES programas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_curso_profesor
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_curso_programa  (programa_id),
  INDEX idx_curso_profesor  (profesor_id),
  INDEX idx_curso_nivel     (nivel)
) ENGINE=InnoDB COMMENT='Módulos de ciclo o cursos cortos independientes';


-- ============================================================
-- 5. CURSOS_CATEGORIAS  (pivote M:M entre cursos y categorías)
-- ============================================================
CREATE TABLE cursos_categorias (
  curso_id      INT UNSIGNED  NOT NULL,
  categoria_id  INT UNSIGNED  NOT NULL,
  PRIMARY KEY (curso_id, categoria_id),
  CONSTRAINT fk_cc_curso
    FOREIGN KEY (curso_id)     REFERENCES cursos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cc_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Relación muchos a muchos: cursos ↔ categorías';


-- ============================================================
-- 6. INSCRIPCIONES  (qué alumno está en qué curso)
-- ============================================================
CREATE TABLE inscripciones (
  alumno_id   INT UNSIGNED  NOT NULL,
  curso_id    INT UNSIGNED  NOT NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (alumno_id, curso_id),
  CONSTRAINT fk_insc_alumno
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_insc_curso
    FOREIGN KEY (curso_id)  REFERENCES cursos(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Inscripciones de alumnos a cursos';


-- ============================================================
-- 7. UNIDADES  (antes "lecciones" — temas dentro de un curso)
--    tipo_recurso: video | lectura | quiz | sincrona
--    Las síncronas tienen fecha_inicio para videoconferencia.
-- ============================================================
CREATE TABLE unidades (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  curso_id        INT UNSIGNED    NOT NULL,
  titulo          VARCHAR(200)    NOT NULL,
  contenido       TEXT                NULL  COMMENT 'Descripción o cuerpo HTML de la unidad',
  orden           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  tipo_recurso    ENUM('video','lectura','quiz','sincrona')
                                  NOT NULL DEFAULT 'lectura',
  url_recurso     VARCHAR(512)        NULL  COMMENT 'Enlace a vídeo, Meet, Zoom, etc.',
  duracion_min    SMALLINT UNSIGNED   NULL  COMMENT 'Duración estimada en minutos',
  fecha_inicio    DATETIME            NULL  COMMENT 'Solo para unidades síncronas',
  publicada       TINYINT(1)      NOT NULL DEFAULT 0,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_unidad_curso
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_unidad_curso (curso_id),
  INDEX idx_tipo_recurso (tipo_recurso)
) ENGINE=InnoDB COMMENT='Unidades temáticas (lecciones) de cada curso';


-- ============================================================
-- 8. ASISTENCIAS
--    UNIQUE en (alumno_id, unidad_id) → un registro por alumno/unidad
-- ============================================================
CREATE TABLE asistencias (
  id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  alumno_id   INT UNSIGNED    NOT NULL,
  unidad_id   INT UNSIGNED    NOT NULL,
  estado      ENUM('presente','ausente','tardanza') NOT NULL,
  ip_origen   VARCHAR(45)         NULL  COMMENT 'IPv4 o IPv6 del alumno',
  created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_asistencia (alumno_id, unidad_id),
  CONSTRAINT fk_asist_alumno
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_asist_unidad
    FOREIGN KEY (unidad_id)  REFERENCES unidades(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_asist_unidad (unidad_id)
) ENGINE=InnoDB COMMENT='Registro de asistencia por alumno y unidad';


-- ============================================================
-- 9. RECURSOS_DOCUMENTALES  (adjuntos de cada unidad)
-- ============================================================
CREATE TABLE recursos_documentales (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  unidad_id     INT UNSIGNED    NOT NULL,
  subido_por    INT UNSIGNED    NOT NULL  COMMENT 'FK → usuarios (profesor)',
  titulo        VARCHAR(200)    NOT NULL,
  tipo          ENUM('pdf','video','enlace','imagen','otro') NOT NULL,
  ruta_archivo  VARCHAR(512)    NOT NULL  COMMENT 'Ruta relativa o URL externa',
  tamano_bytes  INT UNSIGNED        NULL,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_recurso_unidad
    FOREIGN KEY (unidad_id)  REFERENCES unidades(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_recurso_usuario
    FOREIGN KEY (subido_por) REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_recurso_unidad (unidad_id)
) ENGINE=InnoDB COMMENT='Archivos y enlaces adjuntos a las unidades';


-- ============================================================
-- 10. NOTIFICACIONES  (avisos internos del sistema)
-- ============================================================
CREATE TABLE notificaciones (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  usuario_id    INT UNSIGNED    NOT NULL  COMMENT 'Destinatario',
  titulo        VARCHAR(200)    NOT NULL,
  mensaje       TEXT                NULL,
  leida         TINYINT(1)      NOT NULL DEFAULT 0,
  url_accion    VARCHAR(512)        NULL  COMMENT 'Enlace al que lleva la notificación',
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_notif_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_notif_usuario (usuario_id),
  INDEX idx_notif_leida   (leida)
) ENGINE=InnoDB COMMENT='Notificaciones internas por usuario';


-- ============================================================
-- 11. INTENTOS_LOGIN  (protección contra fuerza bruta)
-- ============================================================
CREATE TABLE intentos_login (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  ip            VARCHAR(45)     NOT NULL,
  correo        VARCHAR(150)    NOT NULL,
  bloqueado_hasta DATETIME          NULL  COMMENT 'NULL = no bloqueado',
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_ip_correo (ip, correo)
) ENGINE=InnoDB COMMENT='Registro de intentos fallidos de login por IP';


-- ============================================================
-- 12. REGISTROS_ACTIVIDAD  (log de auditoría del sistema)
-- ============================================================
CREATE TABLE registros_actividad (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  usuario_id    INT UNSIGNED        NULL  COMMENT 'NULL si es acción anónima',
  accion        VARCHAR(100)    NOT NULL  COMMENT 'Ej: login, subida_recurso, marcar_asistencia',
  entidad_tipo  VARCHAR(50)         NULL  COMMENT 'Ej: curso, unidad, usuario',
  entidad_id    INT UNSIGNED        NULL  COMMENT 'ID del registro afectado',
  detalle       JSON                NULL  COMMENT 'Datos extra en formato JSON',
  ip            VARCHAR(45)         NULL,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_log_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_log_usuario (usuario_id),
  INDEX idx_log_accion  (accion),
  INDEX idx_log_fecha   (created_at)
) ENGINE=InnoDB COMMENT='Auditoría completa de acciones en el sistema';


-- ============================================================
-- 13. CONSULTAS_IA  (historial de conversaciones con la IA)
-- ============================================================
CREATE TABLE consultas_ia (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  usuario_id    INT UNSIGNED    NOT NULL,
  sesion_token  VARCHAR(64)     NOT NULL  COMMENT 'SHA-256 del session_id de PHP',
  unidad_id     INT UNSIGNED        NULL  COMMENT 'Contexto de la consulta (opcional)',
  pregunta      TEXT            NOT NULL,
  respuesta     LONGTEXT            NULL,
  tokens_usados SMALLINT UNSIGNED   NULL,
  modelo        VARCHAR(80)         NULL  COMMENT 'Nombre del modelo LLM local',
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_ia_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ia_unidad
    FOREIGN KEY (unidad_id)  REFERENCES unidades(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_ia_sesion  (sesion_token),
  INDEX idx_ia_usuario (usuario_id)
) ENGINE=InnoDB COMMENT='Historial de consultas al asistente IA por sesión';