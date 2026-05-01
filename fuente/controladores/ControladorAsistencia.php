<?php
// fuente/controladores/ControladorAsistencia.php
// ============================================================
// CONTROLADOR DE ASISTENCIA
// ============================================================
// Gestiona el registro y consulta de asistencias.
//
// METODOS TIPO API (devuelven JSON para asistencia.js):
//   marcar()           POST /api/asistencia
//   obtenerPorUnidad() GET  /api/asistencia/:id
//   exportarCsv()      GET  /api/asistencia/:id/exportar
//
// METODOS TIPO VISTA (devuelven HTML):
//   vistaProfesor()    GET  /profesor/unidad/:id/asistencia
//
// CONEXIONES:
//   <- asistencia.js llama a POST /api/asistencia
//   <- panel.js puede llamar a GET /api/asistencia/:id
//   -> Usa Modelo Asistencia, Unidad y Usuario
// ============================================================

declare(strict_types=1);

namespace App\Controladores;

use App\Modelos\Asistencia;
use App\Modelos\Unidad;
use App\Modelos\Usuario;
use App\Configuracion\GestorSesion;
use App\Servicios\ServicioLog;

class ControladorAsistencia extends ControladorBase
{
    private Asistencia $modeloAsistencia;
    private Unidad     $modeloUnidad;
    private Usuario    $modeloUsuario;

    public function __construct()
    {
        $this->modeloAsistencia = new Asistencia();
        $this->modeloUnidad     = new Unidad();
        $this->modeloUsuario    = new Usuario();
    }

    // ════════════════════════════════════════════════════════
    // ENDPOINTS API — devuelven JSON
    // ════════════════════════════════════════════════════════

    /**
     * POST /api/asistencia
     * Registra o actualiza la asistencia de un alumno.
     * Llamado por asistencia.js al hacer clic en el boton.
     *
     * Body JSON esperado:
     *   { "unidad_id": 5, "estado": "presente" }
     */
    public function marcar(array $params = []): void
    {
        // Verificar que es una peticion AJAX legitima
        if (!$this->esPeticionAjax()) {
            $this->jsonError('Peticion no permitida.', 403);
            return;
        }

        $this->verificarCSRF();

        $alumnoId = GestorSesion::obtenerIdUsuario();
        if (!$alumnoId) {
            $this->jsonError('No autenticado.', 401);
            return;
        }

        // Leer el JSON del body de la peticion
        $datos    = $this->leerBodyJson();
        $unidadId = (int) ($datos['unidad_id'] ?? 0);
        $estado   = $datos['estado'] ?? '';

        // Validar datos
        if (!$unidadId) {
            $this->jsonError('Unidad no especificada.', 422);
            return;
        }

        $estadosValidos = [ASIST_PRESENTE, ASIST_AUSENTE, ASIST_TARDANZA];
        if (!in_array($estado, $estadosValidos, true)) {
            $this->jsonError('Estado de asistencia no valido.', 422);
            return;
        }

        // Registrar en BD — usa ON DUPLICATE KEY UPDATE internamente
        $exito = $this->modeloAsistencia->registrar($alumnoId, $unidadId, $estado);

        ServicioLog::registrar('marcar_asistencia', 'unidad', $unidadId, [
            'estado' => $estado,
        ]);

        // Devolver resumen actualizado para que el profesor lo vea
        $resumen = $this->modeloAsistencia->resumenPorUnidad($unidadId);

        $this->jsonOk([
            'registrado' => $exito,
            'estado'     => $estado,
            'resumen'    => $resumen,
        ], 'Asistencia registrada correctamente.');
    }

    /**
     * GET /api/asistencia/:id
     * Devuelve la lista de asistencias de una unidad.
     * El profesor la usa para ver quien ha marcado en tiempo real.
     */
    public function obtenerPorUnidad(array $params = []): void
    {
        $unidadId = $this->paramRuta($params, 'id');

        if (!$unidadId) {
            $this->jsonError('Unidad no especificada.', 422);
            return;
        }

        $asistencias = $this->modeloAsistencia->listarPorUnidad($unidadId);
        $resumen     = $this->modeloAsistencia->resumenPorUnidad($unidadId);

        // Si es alumno solo devolvemos su propia asistencia
        if (GestorSesion::esAlumno()) {
            $alumnoId    = GestorSesion::obtenerIdUsuario();
            $miAsistencia = $this->modeloAsistencia
                ->obtenerDeAlumnoEnUnidad($alumnoId, $unidadId);

            $this->jsonOk([
                'mi_asistencia' => $miAsistencia,
                'resumen'       => $resumen,
            ]);
            return;
        }

        // Profesor y admin ven toda la lista
        $this->jsonOk([
            'asistencias' => $asistencias,
            'resumen'     => $resumen,
        ]);
    }

    /**
     * GET /api/asistencia/:id/exportar
     * Descarga el CSV de asistencia de una unidad.
     * Solo accesible para profesores.
     */
    public function exportarCsv(array $params = []): void
    {
        $unidadId = $this->paramRuta($params, 'id');
        $unidad   = $this->modeloUnidad->buscarPorId($unidadId);

        if (!$unidad) {
            $this->jsonError('Unidad no encontrada.', 404);
            return;
        }

        ServicioLog::registrar('exportar_csv', 'unidad', $unidadId);

        // El modelo genera y descarga el CSV directamente
        $this->modeloAsistencia->exportarCsvUnidad(
            $unidadId,
            $unidad['titulo']
        );
    }

    // ════════════════════════════════════════════════════════
    // VISTAS — devuelven HTML
    // ════════════════════════════════════════════════════════

    /**
     * GET /profesor/unidad/:id/asistencia
     * Vista del profesor para gestionar la asistencia de una unidad.
     * Muestra la lista de alumnos y sus estados en tiempo real.
     */
    public function vistaProfesor(array $params = []): void
    {
        $unidadId   = $this->paramRuta($params, 'id');
        $profesorId = GestorSesion::obtenerIdUsuario();

        // Verificar que la unidad pertenece a este profesor
        if (!$this->modeloUnidad->perteneceAProfesor($unidadId, $profesorId)) {
            $this->redirigirConMensaje(
                '/profesor/panel',
                'error',
                'No tienes permiso para ver esta unidad.'
            );
            return;
        }

        $unidad      = $this->modeloUnidad->buscarPorId($unidadId);
        $asistencias = $this->modeloAsistencia->listarPorUnidad($unidadId);
        $resumen     = $this->modeloAsistencia->resumenPorUnidad($unidadId);
        $alumnos     = $this->modeloUsuario->alumnosDeUnCurso(
            $unidad['curso_id'] ?? 0
        );

        $this->vista('profesor/asistencia', [
            'tituloPagina' => 'Asistencia — ' . ($unidad['titulo'] ?? ''),
            'unidad'       => $unidad,
            'asistencias'  => $asistencias,
            'resumen'      => $resumen,
            'alumnos'      => $alumnos,
            'unidadId'     => $unidadId,
        ]);
    }
}