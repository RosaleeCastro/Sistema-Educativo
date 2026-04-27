<?php
// fuente/modelos/Unidad.php
// ============================================================
// Gestiona las unidades temáticas de cada curso:
//   - Tipos: video, lectura, quiz, sincrona
//   - Orden de las unidades dentro del curso
//   - Publicación / despublicación
//   - Progreso del alumno en las unidades
// ============================================================

declare(strict_types=1);

namespace App\Modelos;

class Unidad extends ModeloBase
{
    protected string $tabla = 'unidades';

    protected array $camposPermitidos = [
        'curso_id', 'titulo', 'contenido', 'orden',
        'tipo_recurso', 'url_recurso', 'duracion_min',
        'fecha_inicio', 'publicada',
    ];

    // ── Listados ─────────────────────────────────────────────

    /**
     * Devuelve todas las unidades de un curso ordenadas.
     * El alumno solo ve las publicadas; el profesor las ve todas.
     */
    public function listarPorCurso(int $cursoId, bool $soloPublicadas = true): array
    {
        $sql = 'SELECT u.*,
                       COUNT(DISTINCT r.id) AS total_recursos
                  FROM unidades u
                  LEFT JOIN recursos_documentales r ON r.unidad_id = u.id
                 WHERE u.curso_id = :cid';

        if ($soloPublicadas) {
            $sql .= ' AND u.publicada = 1';
        }

        $sql .= ' GROUP BY u.id ORDER BY u.orden ASC';

        $stmt = $this->bd->prepare($sql);
        $stmt->execute([':cid' => $cursoId]);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve las unidades de un curso con el estado de
     * asistencia de un alumno concreto.
     * Usado en el panel del alumno para mostrar progreso.
     */
    public function listarConAsistencia(int $cursoId, int $alumnoId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT u.*,
                    a.estado      AS estado_asistencia,
                    a.created_at  AS fecha_asistencia,
                    COUNT(DISTINCT r.id) AS total_recursos
               FROM unidades u
               LEFT JOIN asistencias            a ON a.unidad_id  = u.id
                                                 AND a.alumno_id  = :aid
               LEFT JOIN recursos_documentales  r ON r.unidad_id  = u.id
              WHERE u.curso_id  = :cid
                AND u.publicada = 1
           GROUP BY u.id
           ORDER BY u.orden ASC'
        );
        $stmt->execute([':cid' => $cursoId, ':aid' => $alumnoId]);
        return $stmt->fetchAll();
    }

    /**
     * Próximas unidades síncronas (con fecha_inicio futura).
     * Útil para el widget de calendario en el panel.
     */
    public function proximasSincronas(int $cursoId, int $limite = 5): array
    {
        $stmt = $this->bd->prepare(
            'SELECT u.*,
                    c.nombre AS nombre_curso
               FROM unidades u
               JOIN cursos c ON c.id = u.curso_id
              WHERE u.curso_id     = :cid
                AND u.tipo_recurso = "sincrona"
                AND u.fecha_inicio > NOW()
                AND u.publicada   = 1
           ORDER BY u.fecha_inicio ASC
              LIMIT :limite'
        );
        $stmt->bindValue(':cid',    $cursoId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite,  \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ── Orden ────────────────────────────────────────────────

    /**
     * Mueve una unidad hacia arriba o abajo intercambiando
     * su orden con la unidad adyacente.
     */
    public function moverOrden(int $unidadId, string $direccion): bool
    {
        $unidad = $this->buscarPorId($unidadId);
        if (!$unidad) return false;

        $operador = $direccion === 'arriba' ? '<' : '>';
        $ordenDir = $direccion === 'arriba' ? 'DESC' : 'ASC';

        // Buscamos la unidad adyacente en el mismo curso
        $stmt = $this->bd->prepare(
            "SELECT id, orden FROM unidades
              WHERE curso_id = :cid
                AND orden {$operador} :orden
           ORDER BY orden {$ordenDir}
              LIMIT 1"
        );
        $stmt->execute([':cid' => $unidad['curso_id'], ':orden' => $unidad['orden']]);
        $adyacente = $stmt->fetch();

        if (!$adyacente) return false;

        // Intercambiamos los órdenes
        $this->actualizar($unidadId,          ['orden' => $adyacente['orden']]);
        $this->actualizar($adyacente['id'],    ['orden' => $unidad['orden']]);

        return true;
    }

    /**
     * Devuelve el siguiente número de orden para una unidad nueva.
     */
    public function siguienteOrden(int $cursoId): int
    {
        $stmt = $this->bd->prepare(
            'SELECT COALESCE(MAX(orden), 0) + 1
               FROM unidades
              WHERE curso_id = :cid'
        );
        $stmt->execute([':cid' => $cursoId]);
        return (int) $stmt->fetchColumn();
    }

    // ── Publicación ──────────────────────────────────────────

    public function publicar(int $id): bool
    {
        return $this->actualizar($id, ['publicada' => 1]);
    }

    public function despublicar(int $id): bool
    {
        return $this->actualizar($id, ['publicada' => 0]);
    }

    // ── Verificaciones ───────────────────────────────────────

    /**
     * Comprueba que una unidad pertenece a un profesor concreto.
     * Seguridad: evita que un profesor edite unidades de otro.
     */
    public function perteneceAProfesor(int $unidadId, int $profesorId): bool
    {
        $stmt = $this->bd->prepare(
            'SELECT COUNT(*) FROM unidades u
               JOIN cursos c ON c.id = u.curso_id
              WHERE u.id          = :uid
                AND c.profesor_id = :pid'
        );
        $stmt->execute([':uid' => $unidadId, ':pid' => $profesorId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}