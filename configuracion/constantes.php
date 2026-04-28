<?php
// configuracion/constantes.php
// ============================================================
// Constantes globales del sistema
// Centraliza todos los valores fijos para no repetirlos
// en el código y facilitar futuros cambios
// ============================================================

// ── Versión ─────────────────────────────────────────────────
define('APP_VERSION',  '1.0.0');
define('APP_NOMBRE',   'EduSystem');

// ── Rutas del sistema (absolutas desde el servidor) ─────────
if (!defined('RAIZ')) define('RAIZ', dirname(__DIR__));
define('DIR_FUENTE',    RAIZ . '/fuente');
define('DIR_VISTAS',    DIR_FUENTE . '/vistas');
define('DIR_CONFIG',    RAIZ . '/configuracion');
define('DIR_SUBIDAS',   RAIZ . '/almacenamiento/subidas');
define('DIR_LOGS',      RAIZ . '/almacenamiento/logs');
define('DIR_BD',        RAIZ . '/bd');

// ── Roles de usuario ────────────────────────────────────────
// Usar siempre estas constantes en lugar de strings literales
// Evita errores por typos: ROL_ALUMNO vs 'alummno'
define('ROL_ALUMNO',    'alumno');
define('ROL_PROFESOR',  'profesor');
define('ROL_ADMIN',     'admin');

// ── Estados de asistencia ───────────────────────────────────
define('ASIST_PRESENTE',  'presente');
define('ASIST_AUSENTE',   'ausente');
define('ASIST_TARDANZA',  'tardanza');

// ── Tipos de lección ────────────────────────────────────────
define('LECCION_ASINCRONA', 'asincrona');
define('LECCION_SINCRONA',  'sincrona');

// ── Seguridad ────────────────────────────────────────────────
define('INTENTOS_LOGIN_MAX',      5);       // Intentos antes de bloquear
define('BLOQUEO_MINUTOS',         10);      // Minutos de bloqueo por fuerza bruta
define('SESION_DURACION_HORAS',   8);       // Duración de la sesión activa
define('TOKEN_CSRF_NOMBRE',       'csrf_token');

// ── Paginación ──────────────────────────────────────────────
define('ITEMS_POR_PAGINA',        15);

// ── Respuestas JSON estándar ────────────────────────────────
// Códigos internos de la API (distintos de los códigos HTTP)
define('API_OK',       'ok');
define('API_ERROR',    'error');
define('API_NO_AUTH',  'no_autenticado');
define('API_PROHIBIDO','prohibido');