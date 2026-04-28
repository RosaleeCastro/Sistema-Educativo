-- ============================================================
-- SISTEMA EDUCATIVO — Datos semilla v2.0
-- Ejecutar DESPUÉS de esquema.sql
-- Incluye datos de demo para la presentación del TFG
-- ============================================================

USE sistema_educativo;

-- ============================================================
-- CATEGORÍAS
-- ============================================================
INSERT INTO categorias (nombre, descripcion, color_hex) VALUES
  ('Desarrollo Web',        'HTML, CSS, JavaScript, PHP y frameworks web',  '#5b6af0'),
  ('Desarrollo de Apps',    'Android, iOS y aplicaciones multiplataforma',  '#1D9E75'),
  ('Bases de Datos',        'SQL, NoSQL, diseño y optimización',            '#E8593C'),
  ('Sistemas y Redes',      'Administración de sistemas y redes',           '#BA7517'),
  ('Diseño UI/UX',          'Diseño de interfaces y experiencia de usuario','#D4537E'),
  ('Programación General',  'Lógica, algoritmos y lenguajes de propósito general', '#534AB7');


-- ============================================================
-- USUARIOS
-- Contraseñas en bcrypt de 'password123' (para demo)
-- En producción cada usuario cambia su contraseña
-- ============================================================
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol) VALUES
-- Administrador
  ('Admin',    'Sistema',       'admin@edusystem.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),

-- Profesores
  ('Laura',    'Martínez Gil',  'laura.martinez@edusystem.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor'),

  ('Carlos',   'Ruiz Pérez',    'carlos.ruiz@edusystem.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor'),

-- Alumnos
  ('María',    'García López',  'maria.garcia@edusystem.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumno'),

  ('Alejandro','Sánchez Mora',  'alejandro.sanchez@edusystem.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumno'),

  ('Sofía',    'Fernández Ruiz','sofia.fernandez@edusystem.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumno'),

  ('Pablo',    'Torres Vega',   'pablo.torres@edusystem.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumno'),

  ('Elena',    'Díaz Romero',   'elena.diaz@edusystem.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumno');

-- ============================================================
-- PROGRAMAS (Ciclos formativos)
-- ============================================================
INSERT INTO programas (nombre, descripcion, duracion_horas) VALUES
  (
    'Desarrollo de Aplicaciones Web (DAW)',
    'Ciclo formativo de grado superior enfocado en el desarrollo de aplicaciones web tanto en el lado del servidor como del cliente. Comprende módulos de programación, bases de datos, diseño de interfaces y despliegue.',
    2000
  ),
  (
    'Desarrollo de Aplicaciones Multiplataforma (DAM)',
    'Ciclo formativo de grado superior orientado al desarrollo de aplicaciones para múltiples plataformas: escritorio, móvil y web.',
    2000
  );

-- ============================================================
-- CURSOS
-- ─────────────────────────────────────────────────────────────
-- Jerarquía:
--   programa_id = 1 (DAW) → Módulos del ciclo
--   programa_id = NULL    → Cursos cortos independientes
-- ============================================================
INSERT INTO cursos (programa_id, profesor_id, nombre, descripcion, horas_totales, nivel) VALUES

-- ── Módulos del Ciclo DAW (programa_id = 1) ──────────────────
  (
    1, 2,
    'Programación Web en PHP',
    'Módulo de programación del lado del servidor usando PHP nativo. Incluye POO, MVC, acceso a bases de datos con PDO y buenas prácticas de seguridad.',
    264,
    'intermedio'
  ),
  (
    1, 2,
    'Lenguajes de Marcas y Sistemas de Gestión Web',
    'Módulo de HTML5, CSS3, accesibilidad web, SEO básico y sistemas de gestión de contenidos.',
    132,
    'basico'
  ),
  (
    1, 3,
    'Bases de Datos',
    'Diseño entidad-relación, SQL avanzado, normalización, procedimientos almacenados y administración básica de MySQL.',
    198,
    'intermedio'
  ),

-- ── Módulo del Ciclo DAM (programa_id = 2) ───────────────────
  (
    2, 3,
    'Programación Multiplataforma con Java',
    'Módulo de programación orientada a objetos con Java. Incluye colecciones, streams, JavaFX y acceso a datos.',
    264,
    'intermedio'
  ),

-- ── Cursos cortos independientes (programa_id = NULL) ────────
  (
    NULL, 2,
    'React: De cero a experto',
    'Curso intensivo de React 18. Componentes, hooks, estado global con Redux Toolkit, React Router y consumo de APIs REST.',
    60,
    'intermedio'
  ),
  (
    NULL, 3,
    'Docker y Despliegue de Aplicaciones',
    'Introducción práctica a Docker, Docker Compose y despliegue de stacks PHP + MySQL + Nginx en producción.',
    40,
    'avanzado'
  ),
  (
    NULL, 2,
    'JavaScript Moderno (ES2024)',
    'Curso de JavaScript actualizado: async/await, módulos ES, Web APIs, Fetch, y patrones de diseño en frontend.',
    50,
    'intermedio'
  );


-- ============================================================
-- CURSOS_CATEGORIAS  (asignar temáticas a cada curso)
-- ============================================================
INSERT INTO cursos_categorias (curso_id, categoria_id) VALUES
-- Programación Web en PHP → Desarrollo Web + Programación General
  (1, 1), (1, 6),
-- Lenguajes de Marcas → Desarrollo Web + Diseño UI/UX
  (2, 1), (2, 5),
-- Bases de Datos → Bases de Datos
  (3, 3),
-- Programación Java → Desarrollo de Apps + Programación General
  (4, 2), (4, 6),
-- React → Desarrollo Web + Programación General
  (5, 1), (5, 6),
-- Docker → Sistemas y Redes + Desarrollo Web
  (6, 4), (6, 1),
-- JavaScript → Desarrollo Web + Programación General
  (7, 1), (7, 6);


-- ============================================================
-- UNIDADES  (temas de cada curso — ejemplos representativos)
-- ============================================================
INSERT INTO unidades (curso_id, titulo, contenido, orden, tipo_recurso, duracion_min, publicada) VALUES

-- ── Módulo PHP (curso 1) ──────────────────────────────────────
  (1, 'Introducción a PHP y entorno XAMPP',
   'Instalación del entorno de desarrollo, estructura básica de un script PHP, tipos de datos y variables.',
   1, 'video', 45, 1),

  (1, 'Programación Orientada a Objetos en PHP',
   'Clases, objetos, herencia, interfaces y traits. Principios SOLID aplicados a PHP.',
   2, 'lectura', 90, 1),

  (1, 'Patrón MVC artesanal sin frameworks',
   'Implementación paso a paso de un MVC propio: Router, Controller, Model y View.',
   3, 'video', 120, 1),

  (1, 'Acceso a MySQL con PDO',
   'Conexión segura, consultas preparadas, prevención de SQL injection y gestión de errores.',
   4, 'lectura', 60, 1),

  (1, 'Evaluación del módulo PHP',
   'Quiz de evaluación con 20 preguntas sobre los conceptos del módulo.',
   5, 'quiz', 30, 1),

-- ── Módulo Lenguajes de Marcas (curso 2) ─────────────────────
  (2, 'HTML5 semántico y accesibilidad',
   'Etiquetas semánticas, ARIA, formularios accesibles y validación W3C.',
   1, 'video', 60, 1),

  (2, 'CSS Grid y Flexbox en profundidad',
   'Maquetación moderna con Grid y Flexbox. Diseño responsive mobile-first.',
   2, 'lectura', 75, 1),

  (2, 'Tutoría en directo: Revisión de proyectos',
   'Sesión síncrona de revisión de los proyectos de maquetación de los alumnos.',
   3, 'sincrona', 60, 1),

-- ── Bases de Datos (curso 3) ──────────────────────────────────
  (3, 'Modelo Entidad-Relación',
   'Entidades, atributos, relaciones y cardinalidad. Herramienta MySQL Workbench.',
   1, 'lectura', 60, 1),

  (3, 'SQL: DDL y DML',
   'CREATE, ALTER, DROP, INSERT, UPDATE, DELETE y SELECT con JOIN.',
   2, 'video', 90, 1),

  (3, 'SQL avanzado: subconsultas e índices',
   'Subconsultas correlacionadas, vistas, índices compuestos y EXPLAIN.',
   3, 'lectura', 75, 1),

-- ── React (curso 5 — curso independiente) ────────────────────
  (5, 'Fundamentos de React 18',
   'JSX, componentes funcionales, props y renderizado condicional.',
   1, 'video', 60, 1),

  (5, 'Hooks: useState y useEffect',
   'Gestión del estado local y efectos secundarios. Ciclo de vida de los componentes.',
   2, 'video', 75, 1),

  (5, 'Estado global con Redux Toolkit',
   'Store, slices, selectors y RTK Query para consumo de APIs.',
   3, 'lectura', 90, 1),

  (5, 'Proyecto final: App de gestión de tareas',
   'Desarrollo guiado de una aplicación completa con React + API REST.',
   4, 'video', 120, 1),

-- ── Docker (curso 6 — curso independiente) ───────────────────
  (6, 'Contenedores y Docker Engine',
   'Qué es un contenedor, diferencias con VM, instalación y primeros comandos.',
   1, 'lectura', 45, 1),

  (6, 'docker-compose para stacks completos',
   'Definición de servicios, volúmenes y redes. Stack PHP + MySQL + Nginx.',
   2, 'video', 90, 1);


-- ============================================================
-- INSCRIPCIONES  (alumnos matriculados en cursos)
-- ============================================================
INSERT INTO inscripciones (alumno_id, curso_id) VALUES
-- María → PHP, Lenguajes de Marcas, React
  (4, 1), (4, 2), (4, 5),
-- Alejandro → PHP, Bases de Datos, Docker
  (5, 1), (5, 3), (5, 6),
-- Sofía → Lenguajes de Marcas, React, JavaScript
  (6, 2), (6, 5), (6, 7),
-- Pablo → PHP, Bases de Datos, Java
  (7, 1), (7, 3), (7, 4),
-- Elena → React, Docker, JavaScript
  (8, 5), (8, 6), (8, 7);


-- ============================================================
-- ASISTENCIAS  (ejemplos para el dashboard)
-- ============================================================
INSERT INTO asistencias (alumno_id, unidad_id, estado) VALUES
-- Unidad 1 (Introducción PHP) — todos presentes
  (4, 1, 'presente'),
  (5, 1, 'presente'),
  (7, 1, 'tardanza'),
-- Unidad 2 (POO PHP)
  (4, 2, 'presente'),
  (5, 2, 'ausente'),
  (7, 2, 'presente'),
-- Unidad 6 (HTML5) — curso Lenguajes de Marcas
  (4, 6, 'presente'),
  (6, 6, 'presente'),
-- Unidad 12 (Fundamentos React)
  (4, 12, 'presente'),
  (6, 12, 'tardanza'),
  (8, 12, 'presente');


-- ============================================================
-- NOTIFICACIONES de ejemplo
-- ============================================================
INSERT INTO notificaciones (usuario_id, titulo, mensaje, url_accion) VALUES
  (4, 'Nueva unidad publicada',
   'El profesor ha publicado la unidad "Acceso a MySQL con PDO" en el módulo de PHP.',
   '/alumno/curso/1'),

  (5, 'Nueva unidad publicada',
   'El profesor ha publicado la unidad "SQL avanzado: subconsultas e índices".',
   '/alumno/curso/3'),

  (6, 'Tutoría en directo mañana',
   'Recuerda que mañana hay sesión síncrona de revisión de proyectos a las 10:00h.',
   '/alumno/curso/2');


-- ============================================================
-- REGISTROS DE ACTIVIDAD de ejemplo
-- ============================================================
INSERT INTO registros_actividad (usuario_id, accion, entidad_tipo, entidad_id, detalle, ip) VALUES
  (2, 'crear_unidad',    'unidad',  5, '{"titulo": "Evaluación del módulo PHP"}', '127.0.0.1'),
  (4, 'login',           NULL,      NULL, '{"rol": "alumno"}',                    '127.0.0.1'),
  (4, 'marcar_asistencia','unidad', 1,  '{"estado": "presente"}',                 '127.0.0.1'),
  (5, 'login',           NULL,      NULL, '{"rol": "alumno"}',                    '127.0.0.1'),
  (2, 'subir_recurso',   'unidad',  2, '{"tipo": "pdf", "titulo": "Apuntes POO"}','127.0.0.1');