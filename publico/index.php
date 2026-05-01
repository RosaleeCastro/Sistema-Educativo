<?php
declare(strict_types=1);

// ── 1. Ruta raíz ─────────────────────────────────────────────
define('RAIZ', dirname(__DIR__));

// ── 2. Constantes ────────────────────────────────────────────
require_once RAIZ . '/configuracion/constantes.php';

// ── 3. Autoloader ────────────────────────────────────────────
require_once RAIZ . '/autoload.php';

// ── 4. Imports — deben ir antes de usar las clases ───────────
use App\Configuracion\Entorno;
use App\Configuracion\GestorSesion;
use App\Configuracion\Enrutador;
use App\Controladores\ControladorAuth;
use App\Controladores\ControladorAlumno;
use App\Controladores\ControladorProfesor;
use App\Controladores\ControladorAdmin;
use App\Controladores\ControladorCurso;
use App\Controladores\ControladorUnidad;
use App\Controladores\ControladorAsistencia;
use App\Controladores\ControladorRecurso;
use App\Controladores\ControladorIA;
use App\Controladores\ControladorNotificacion;

// ── 5. Entorno ───────────────────────────────────────────────
Entorno::inicializar();

// ── 6. Sesión ────────────────────────────────────────────────
GestorSesion::iniciar();

// Evitar que el navegador cachee páginas autenticadas
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// ── 7. Rutas ─────────────────────────────────────────────────
$enrutador = new Enrutador();

// Rutas públicas
$enrutador->get('/',       [ControladorAuth::class, 'inicio']);
$enrutador->get('/login',  [ControladorAuth::class, 'mostrarLogin']);
$enrutador->post('/login', [ControladorAuth::class, 'procesarLogin']);
$enrutador->get('/salir',  [ControladorAuth::class, 'cerrarSesion']);

// Rutas alumno
$enrutador->get('/alumno/panel',
    [ControladorAlumno::class, 'panel'], ROL_ALUMNO);
$enrutador->get('/alumno/curso/:id',
    [ControladorCurso::class, 'verComoAlumno'], ROL_ALUMNO);
$enrutador->get('/alumno/mis-asistencias',
    [ControladorAlumno::class, 'misAsistencias'], ROL_ALUMNO);
$enrutador->get('/alumno/asistente',
    [ControladorIA::class, 'mostrarChat'], ROL_ALUMNO);

// Rutas profesor
$enrutador->get('/profesor/panel',
    [ControladorProfesor::class, 'panel'], ROL_PROFESOR);
$enrutador->get('/profesor/cursos',
    [ControladorProfesor::class, 'misCursos'], ROL_PROFESOR);
$enrutador->get('/profesor/curso/:id/unidad/nueva',
    [ControladorCurso::class, 'formularioNuevo'], ROL_PROFESOR);
$enrutador->post('/profesor/curso/:id/unidad/nueva',
    [ControladorCurso::class, 'crear'], ROL_PROFESOR);
$enrutador->get('/profesor/unidad/:id/editar',
    [ControladorUnidad::class, 'formularioEditar'], ROL_PROFESOR);
$enrutador->post('/profesor/unidad/:id/editar',
    [ControladorUnidad::class, 'actualizar'], ROL_PROFESOR);
$enrutador->get('/profesor/unidad/:id/asistencia',
    [ControladorAsistencia::class, 'vistaProfesor'], ROL_PROFESOR);

// Rutas admin
$enrutador->get('/admin/panel',
    [ControladorAdmin::class, 'panel'], ROL_ADMIN);
$enrutador->get('/admin/usuarios',
    [ControladorAdmin::class, 'listarUsuarios'], ROL_ADMIN);
$enrutador->get('/admin/usuario/nuevo',
    [ControladorAdmin::class, 'formularioNuevoUsuario'], ROL_ADMIN);
$enrutador->post('/admin/usuario/nuevo',
    [ControladorAdmin::class, 'crearUsuario'], ROL_ADMIN);
$enrutador->get('/admin/programas',
    [ControladorAdmin::class, 'listarProgramas'], ROL_ADMIN);
$enrutador->get('/admin/logs',
    [ControladorAdmin::class, 'verLogs'], ROL_ADMIN);

// API — devuelven JSON
$enrutador->get('/api/cursos/alumno',
    [ControladorCurso::class, 'apiCursosAlumno']);
$enrutador->get('/api/cursos/profesor',
    [ControladorCurso::class, 'apiCursosProfesor']);
$enrutador->get('/api/stats/admin',
    [ControladorCurso::class, 'apiStatsAdmin'], ROL_ADMIN);
$enrutador->post('/api/asistencia',
    [ControladorAsistencia::class, 'marcar']);
$enrutador->get('/api/asistencia/:id',
    [ControladorAsistencia::class, 'obtenerPorUnidad']);
$enrutador->get('/api/asistencia/:id/exportar',
    [ControladorAsistencia::class, 'exportarCsv'], ROL_PROFESOR);
$enrutador->post('/api/recurso/subir',
    [ControladorRecurso::class, 'subir'], ROL_PROFESOR);
$enrutador->get('/api/recursos/:id',
    [ControladorRecurso::class, 'listarPorUnidad']);
$enrutador->post('/api/ia/consulta',
    [ControladorIA::class, 'procesarConsulta']);
$enrutador->get('/api/ia/historial',
    [ControladorIA::class, 'obtenerHistorial']);
$enrutador->get('/api/notificaciones',
    [ControladorNotificacion::class, 'obtener']);
$enrutador->post('/api/notificaciones/:id/leer',
    [ControladorNotificacion::class, 'marcarLeida']);

// ── 8. Despachar ─────────────────────────────────────────────
$enrutador->despachar();