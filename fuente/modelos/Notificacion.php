<?php

// ============================================================
// Gestiona las notificaciones internas del sistema:
//   - El profesor publica una unidad → notifica a sus alumnos
//   - El alumno ve el badge con el número de no leídas
//   - Marcar como leída al hacer clic
// ============================================================

declare(strict_types=1);

namespace App\Modelos;

class Notificacion extends ModeloBase
{
    protected string $tabla = 'notificaciones';

    protected array $camposPermitidos = [
        'usuario_id', 'titulo', 'mensaje', 'url_accion', 'leida',
    ];

    // ── Creación ─────────────────────────────────────────────

    /**
     * Crea una notificación para un usuario concreto.
     */
    public function crear(array $datos): int
    {
        return parent::crear($datos);
    }

    /**
     * Notifica a TODOS los alumnos inscritos en un curso.
     * Usado cuando el profesor publica una unidad nueva.
     */
    public function notificarAlumnosDeCurso(
        int    $cursoId,
        string $titulo,
        string $mensaje,
        string $urlAccion
    ): int {
        // Obtenemos los IDs de todos los alumnos del curso
        $stmt = $this->bd->prepare(
            'SELECT alumno_id FROM inscripciones WHERE curso_id = :cid'
        );
        $stmt->execute([':cid' => $cursoId]);
        $alumnos = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $creadas = 0;
        $insert  = $this->bd->prepare(
            'INSERT INTO notificaciones (usuario_id, titulo, mensaje, url_accion)
             VALUES (:uid, :titulo, :mensaje, :url)'
        );

        foreach ($alumnos as $alumnoId) {
            $insert->execute([
                ':uid'     => $alumnoId,
                ':titulo'  => $titulo,
                ':mensaje' => $mensaje,
                ':url'     => $urlAccion,
            ]);
            $creadas++;
        }

        return $creadas;
    }

    // ── Consultas ─────────────────────────────────────────────

    /**
     * Devuelve las últimas notificaciones de un usuario.
     * Primero las no leídas, luego por fecha.
     */
    public function listarDeUsuario(int $usuarioId, int $limite = 20): array
    {
        $stmt = $this->bd->prepare(
            'SELECT * FROM notificaciones
              WHERE usuario_id = :uid
           ORDER BY leida ASC, created_at DESC
              LIMIT :limite'
        );
        $stmt->bindValue(':uid',    $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite,    \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Cuenta las notificaciones NO leídas de un usuario.
     * Es el número que aparece en el badge del menú.
     */
    public function contarNoLeidas(int $usuarioId): int
    {
        $stmt = $this->bd->prepare(
            'SELECT COUNT(*) FROM notificaciones
              WHERE usuario_id = :uid AND leida = 0'
        );
        $stmt->execute([':uid' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    // ── Acciones ─────────────────────────────────────────────

    /**
     * Marca una notificación como leída.
     * Verifica que pertenece al usuario antes de actualizarla.
     */
    public function marcarLeida(int $notifId, int $usuarioId): bool
    {
        $stmt = $this->bd->prepare(
            'UPDATE notificaciones
                SET leida = 1
              WHERE id = :id AND usuario_id = :uid'
        );
        $stmt->execute([':id' => $notifId, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Marca TODAS las notificaciones de un usuario como leídas.
     */
    public function marcarTodasLeidas(int $usuarioId): void
    {
        $stmt = $this->bd->prepare(
            'UPDATE notificaciones SET leida = 1 WHERE usuario_id = :uid'
        );
        $stmt->execute([':uid' => $usuarioId]);
    }
}