<?php
// publico/index.php
// ============================================================
// PUNTO DE ENTRADA ÚNICO del sistema.
// Todas las peticiones HTTP llegan aquí gracias al .htaccess.
//
// Secuencia de arranque:
//   1. Definir la ruta raíz del proyecto
//   2. Cargar las constantes del sistema
//   3. Cargar el autoloader (para que PHP encuentre las clases)
//   4. Inicializar el entorno (leer .env, configurar PHP)
//   5. Iniciar la sesión de forma segura
//   6. Registrar todas las rutas
//   7. Despachar (ejecutar el controlador que corresponde)
// ============================================================

declare(strict_types=1);

// ── 1. Ruta raíz ─────────────────────────────────────────────
// dirname(__DIR__) sube un nivel desde /publico hasta la raíz
define('RAIZ', dirname(__DIR__));

// ── 2. Constantes ────────────────────────────────────────────
require_once RAIZ . '/configuracion/constantes.php';

// ── 3. Autoloader ────────────────────────────────────────────
require_once RAIZ . '/autoload.php';

// ── 4. Entorno ───────────────────────────────────────────────
use App\Configuracion\Entorno;
use App\Configuracion\GestorSesion;
use App\Configuracion\Enrutador;

// Carga el .env y configura PHP según el entorno
Entorno::inicializar();

// ── 5. Sesión ────────────────────────────────────────────────
GestorSesion::iniciar();

// ── 6. Rutas ─────────────────────────────────────────────────
// Importamos los controladores que usaremos
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

$enrutador = new Enrutador();

// ════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS (no requieren sesión)
// ════════════════════════════════════════════════════════════

// Página de inicio → redirige al panel según el rol
$enrutador->get('/', [ControladorAuth::class, 'inicio']);

// Login
$enrutador->get('/login',  [ControladorAuth::class, 'mostrarLogin']);
$enrutador->post('/login', [ControladorAuth::class, 'procesarLogin']);

// Logout
$enrutador->get('/salir',  [ControladorAuth::class, 'cerrarSesion']);

// ════════════════════════════════════════════════════════════
// RUTAS DEL ALUMNO  (requieren rol: alumno)
// ════════════════════════════════════════════════════════════

// Panel principal del alumno
$enrutador->get('/alumno/panel',
    [ControladorAlumno::class, 'panel'], ROL_ALUMNO);

// Ver un curso y sus unidades
$enrutador->get('/alumno/curso/:id',
    [ControladorCurso::class, 'verComoAlumno'], ROL_ALUMNO);

// Ver el detalle de una unidad
$enrutador->get('/alumno/unidad/:id',
    [ControladorUnidad::class, 'verComoAlumno'], ROL_ALUMNO);

// Historial de asistencia del alumno
$enrutador->get('/alumno/mis-asistencias',
    [ControladorAlumno::class, 'misAsistencias'], ROL_ALUMNO);

// Consultar el asistente IA (página del chat)
$enrutador->get('/alumno/asistente',
    [ControladorIA::class, 'mostrarChat'], ROL_ALUMNO);

// ════════════════════════════════════════════════════════════
// RUTAS DEL PROFESOR  (requieren rol: profesor)
// ════════════════════════════════════════════════════════════

// Panel del profesor
$enrutador->get('/profesor/panel',
    [ControladorProfesor::class, 'panel'], ROL_PROFESOR);

// Gestión de cursos
$enrutador->get('/profesor/cursos',
    [ControladorProfesor::class, 'misCursos'], ROL_PROFESOR);

$enrutador->get('/profesor/curso/nuevo',
    [ControladorCurso::class, 'formularioNuevo'], ROL_PROFESOR);

$enrutador->post('/profesor/curso/nuevo',
    [ControladorCurso::class, 'crear'], ROL_PROFESOR);

$enrutador->get('/profesor/curso/:id/editar',
    [ControladorCurso::class, 'formularioEditar'], ROL_PROFESOR);

$enrutador->post('/profesor/curso/:id/editar',
    [ControladorCurso::class, 'actualizar'], ROL_PROFESOR);

// Gestión de unidades
$enrutador->get('/profesor/curso/:id/unidad/nueva',
    [ControladorUnidad::class, 'formularioNuevo'], ROL_PROFESOR);

$enrutador->post('/profesor/curso/:id/unidad/nueva',
    [ControladorUnidad::class, 'crear'], ROL_PROFESOR);

$enrutador->get('/profesor/unidad/:id/editar',
    [ControladorUnidad::class, 'formularioEditar'], ROL_PROFESOR);

$enrutador->post('/profesor/unidad/:id/editar',
    [ControladorUnidad::class, 'actualizar'], ROL_PROFESOR);

// Asistencia del profesor
$enrutador->get('/profesor/unidad/:id/asistencia',
    [ControladorAsistencia::class, 'vistaProfesor'], ROL_PROFESOR);

// ════════════════════════════════════════════════════════════
// RUTAS DEL ADMINISTRADOR  (requieren rol: admin)
// ════════════════════════════════════════════════════════════

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

// ════════════════════════════════════════════════════════════
// API INTERNA (endpoints AJAX — devuelven JSON)
// ════════════════════════════════════════════════════════════

// Asistencia en tiempo real
$enrutador->post('/api/asistencia',
    [ControladorAsistencia::class, 'marcar']);

$enrutador->get('/api/asistencia/:id',
    [ControladorAsistencia::class, 'obtenerPorUnidad']);

// Exportar asistencia a CSV
$enrutador->get('/api/asistencia/:id/exportar',
    [ControladorAsistencia::class, 'exportarCsv'], ROL_PROFESOR);

// Recursos documentales
$enrutador->post('/api/recurso/subir',
    [ControladorRecurso::class, 'subir'], ROL_PROFESOR);

$enrutador->get('/api/recursos/:id',
    [ControladorRecurso::class, 'listarPorUnidad']);

// Asistente IA
$enrutador->post('/api/ia/consulta',
    [ControladorIA::class, 'procesarConsulta']);

$enrutador->get('/api/ia/historial',
    [ControladorIA::class, 'obtenerHistorial']);

// Notificaciones
$enrutador->get('/api/notificaciones',
    [ControladorNotificacion::class, 'obtener']);

$enrutador->post('/api/notificaciones/:id/leer',
    [ControladorNotificacion::class, 'marcarLeida']);

// ── 7. Despachar ─────────────────────────────────────────────
// Lee la URL actual y ejecuta el controlador correspondiente
$enrutador->despachar();