<?php
// fuente/controladores/ControladorCurso.php
// ============================================================
// CONTROLADOR DE CURSOS — MODELO HÍBRIDO
// ============================================================
// Métodos tipo API (devuelven JSON para panel.js):
//   apiCursosAlumno()   → GET /api/cursos/alumno
//   apiCursosProfesor() → GET /api/cursos/profesor
//   apiStatsAdmin()     → GET /api/stats/admin
//
// Métodos tipo Vista (devuelven HTML para el navegador):
//   verComoAlumno()     → GET /alumno/curso/:id
//   formularioNuevo()   → GET /profesor/curso/:id/unidad/nueva
//   crear()             → POST /profesor/curso/:id/unidad/nueva
// ============================================================

declare(strict_types=1);

namespace App\Controladores;

use App\Modelos\Curso;
use App\Modelos\Unidad;
use App\Modelos\Asistencia;
use App\Modelos\Notificacion;
use App\Configuracion\BaseDatos;
use App\Configuracion\GestorSesion;
use App\Servicios\ServicioLog;

class ControladorCurso extends ControladorBase
{
    private Curso  $modeloCurso;
    private Unidad $modeloUnidad;

    public function __construct()
    {
        $this->modeloCurso  = new Curso();
        $this->modeloUnidad = new Unidad();
    }

    // ── Endpoints API (responden JSON) ───────────────────────

    // Llamado por panel.js → cargarPanelAlumno()
    public function apiCursosAlumno(array $params = []): void
    {
        $alumnoId         = GestorSesion::obtenerIdUsuario();
        $cursos           = $this->modeloCurso->listarPorAlumno($alumnoId);
        $modeloAsistencia = new Asistencia();

        foreach ($cursos as &$curso) {
            $curso['porcentaje_asistencia'] =
                $modeloAsistencia->porcentajeAlumnoEnCurso($alumnoId, $curso['id']);
        }

        $this->jsonOk(['cursos' => $cursos]);
    }

    // Llamado por panel.js → cargarPanelProfesor()
    public function apiCursosProfesor(array $params = []): void
    {
        $profesorId = GestorSesion::obtenerIdUsuario();
        $cursos     = $this->modeloCurso->listarPorProfesor($profesorId);
        $this->jsonOk(['cursos' => $cursos]);
    }

    // Llamado por panel.js → cargarPanelAdmin()
    public function apiStatsAdmin(array $params = []): void
    {
        $pdo = BaseDatos::obtenerConexion();
        $this->jsonOk([
            'alumnos'    => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='alumno'")->fetchColumn(),
            'profesores' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='profesor'")->fetchColumn(),
            'cursos'     => (int)$pdo->query("SELECT COUNT(*) FROM cursos WHERE activo=1")->fetchColumn(),
            'unidades'   => (int)$pdo->query("SELECT COUNT(*) FROM unidades WHERE publicada=1")->fetchColumn(),
        ]);
    }

    // ── Vistas (responden HTML) ──────────────────────────────

    public function verComoAlumno(array $params = []): void
    {
        $cursoId  = $this->paramRuta($params, 'id');
        $alumnoId = GestorSesion::obtenerIdUsuario();

        if (!$this->modeloCurso->estaInscrito($alumnoId, $cursoId)) {
            $this->redirigirConMensaje('/alumno/panel', 'error', 'No estás inscrito en este curso.');
            return;
        }

        $curso    = $this->modeloCurso->buscarPorId($cursoId);
        $unidades = $this->modeloUnidad->listarConAsistencia($cursoId, $alumnoId);

        ServicioLog::registrar('ver_curso', 'curso', $cursoId);

        $this->vista('alumno/curso', [
            'tituloPagina' => ($curso['nombre'] ?? 'Curso') . ' — EduSystem',
            'curso'        => $curso,
            'unidades'     => $unidades,
            'alumnoId'     => $alumnoId,
        ]);
    }

    public function formularioNuevo(array $params = []): void
    {
        $cursoId = $this->paramRuta($params, 'id');
        $curso   = $this->modeloCurso->buscarPorId($cursoId);
        $token   = $this->generarTokenCSRF();

        $this->vista('profesor/nueva-unidad', [
            'tituloPagina' => 'Nueva unidad — EduSystem',
            'curso'        => $curso,
            'token'        => $token,
        ]);
    }

    public function crear(array $params = []): void
    {
        $this->verificarCSRF();
        $cursoId = $this->paramRuta($params, 'id');

        $datos = [
            'curso_id'     => $cursoId,
            'titulo'       => $this->param('titulo'),
            'contenido'    => $this->param('contenido'),
            'tipo_recurso' => $this->param('tipo_recurso'),
            'url_recurso'  => $this->param('url_recurso'),
            'duracion_min' => (int)$this->param('duracion_min') ?: null,
            'fecha_inicio' => $this->param('fecha_inicio') ?: null,
            'orden'        => $this->modeloUnidad->siguienteOrden($cursoId),
            'publicada'    => isset($_POST['publicada']) ? 1 : 0,
        ];

        $id = $this->modeloUnidad->crear($datos);

        // Notificar alumnos si la unidad se publica inmediatamente
        if ($datos['publicada']) {
            (new Notificacion())->notificarAlumnosDeCurso(
                $cursoId,
                'Nueva unidad publicada',
                "Se ha publicado: {$datos['titulo']}",
                "/alumno/curso/{$cursoId}"
            );
        }

        ServicioLog::registrar('crear_unidad', 'unidad', $id);
        $this->redirigirConMensaje('/profesor/panel', 'exito', 'Unidad creada correctamente.');
    }

    public function formularioEditar(array $params = []): void
    {
        $id    = $this->paramRuta($params, 'id');
        $curso = $this->modeloCurso->buscarPorId($id);
        $token = $this->generarTokenCSRF();

        $this->vista('profesor/editar-curso', [
            'tituloPagina' => 'Editar curso — EduSystem',
            'curso'        => $curso,
            'token'        => $token,
        ]);
    }

    public function actualizar(array $params = []): void
    {
        $this->verificarCSRF();
        $id = $this->paramRuta($params, 'id');

        $this->modeloCurso->actualizar($id, [
            'nombre'      => $this->param('nombre'),
            'descripcion' => $this->param('descripcion'),
            'nivel'       => $this->param('nivel'),
        ]);

        ServicioLog::registrar('actualizar_curso', 'curso', $id);
        $this->redirigirConMensaje('/profesor/panel', 'exito', 'Curso actualizado.');
    }
}