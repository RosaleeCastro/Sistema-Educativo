<?php
// fuente/modelos/Curso.php
// ============================================================
// Gestiona los cursos del sistema:
//   - Cursos pertenecientes a un programa (módulos de ciclo)
//   - Cursos cortos independientes (programa_id = NULL)
//   - Inscripciones de alumnos
//   - Listados filtrados por profesor, programa, nivel
// ============================================================

declare(strict_types=1);

namespace App\Modelos;

use PDO;

class Curso extends ModeloBase
{
    protected string $tabla = 'cursos';

    protected array $camposPermitidos = [
        'programa_id', 'profesor_id', 'nombre', 'descripcion',
        'horas_totales', 'nivel', 'imagen_portada', 'activo',
    ];

    // ── Listados ─────────────────────────────────────────────

    /**
     * Devuelve todos los cursos con el nombre del profesor
     * y del programa (si pertenece a alguno).
     */
    public function listarConDetalles(): array
    {
        $stmt = $this->bd->query(
            'SELECT c.*,
                    CONCAT(u.nombre, " ", u.apellidos) AS nombre_profesor,
                    p.nombre AS nombre_programa
               FROM cursos c
               JOIN usuarios u  ON u.id = c.profesor_id
               LEFT JOIN programas p ON p.id = c.programa_id
              WHERE c.activo = 1
           ORDER BY p.nombre ASC, c.nombre ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Cursos de un profesor concreto.
     */
    public function listarPorProfesor(int $profesorId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT c.*,
                    p.nombre AS nombre_programa,
                    COUNT(DISTINCT i.alumno_id) AS total_alumnos,
                    COUNT(DISTINCT u.id)        AS total_unidades
               FROM cursos c
               LEFT JOIN programas   p ON p.id = c.programa_id
               LEFT JOIN inscripciones i ON i.curso_id = c.id
               LEFT JOIN unidades    u ON u.curso_id = c.id
              WHERE c.profesor_id = :pid
           GROUP BY c.id
           ORDER BY c.nombre ASC'
        );
        $stmt->execute([':pid' => $profesorId]);
        return $stmt->fetchAll();
    }

    /**
     * Cursos en los que está inscrito un alumno.
     */
    public function listarPorAlumno(int $alumnoId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT c.*,
                    CONCAT(u.nombre, " ", u.apellidos) AS nombre_profesor,
                    p.nombre AS nombre_programa,
                    COUNT(DISTINCT un.id)              AS total_unidades,
                    COUNT(DISTINCT a.id)               AS unidades_asistidas
               FROM cursos c
               JOIN inscripciones  i  ON i.curso_id   = c.id AND i.alumno_id = :aid
               JOIN usuarios       u  ON u.id          = c.profesor_id
               LEFT JOIN programas p  ON p.id           = c.programa_id
               LEFT JOIN unidades  un ON un.curso_id    = c.id
               LEFT JOIN asistencias a ON a.unidad_id   = un.id
                                      AND a.alumno_id   = :aid
                                      AND a.estado      = "presente"
           GROUP BY c.id
           ORDER BY p.nombre ASC, c.nombre ASC'
        );
        $stmt->execute([':aid' => $alumnoId]);
        return $stmt->fetchAll();
    }

    /**
     * Cursos independientes (sin programa).
     */
    public function listarIndependientes(): array
    {
        $stmt = $this->bd->query(
            'SELECT c.*,
                    CONCAT(u.nombre, " ", u.apellidos) AS nombre_profesor
               FROM cursos c
               JOIN usuarios u ON u.id = c.profesor_id
              WHERE c.programa_id IS NULL
                AND c.activo = 1
           ORDER BY c.nombre ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Cursos de un programa (módulos del ciclo).
     */
    public function listarPorPrograma(int $programaId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT c.*,
                    CONCAT(u.nombre, " ", u.apellidos) AS nombre_profesor,
                    COUNT(DISTINCT i.alumno_id) AS total_alumnos
               FROM cursos c
               JOIN usuarios u ON u.id = c.profesor_id
               LEFT JOIN inscripciones i ON i.curso_id = c.id
              WHERE c.programa_id = :pid
                AND c.activo = 1
           GROUP BY c.id
           ORDER BY c.nombre ASC'
        );
        $stmt->execute([':pid' => $programaId]);
        return $stmt->fetchAll();
    }

    // ── Búsqueda ─────────────────────────────────────────────

    /**
     * Buscador interno: filtra por nombre, descripción o nivel.
     */
    public function buscar(string $termino, ?string $nivel = null): array
    {
        $sql = 'SELECT c.*,
                       CONCAT(u.nombre, " ", u.apellidos) AS nombre_profesor,
                       p.nombre AS nombre_programa
                  FROM cursos c
                  JOIN usuarios u ON u.id = c.profesor_id
                  LEFT JOIN programas p ON p.id = c.programa_id
                 WHERE c.activo = 1
                   AND (c.nombre LIKE :t OR c.descripcion LIKE :t)';

        $params = [':t' => '%' . $termino . '%'];

        if ($nivel) {
            $sql .= ' AND c.nivel = :nivel';
            $params[':nivel'] = $nivel;
        }

        $sql .= ' ORDER BY c.nombre ASC';
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Inscripciones ─────────────────────────────────────────

    /**
     * Inscribe a un alumno en un curso.
     * Ignora si ya estaba inscrito (INSERT IGNORE).
     */
    public function inscribir(int $alumnoId, int $cursoId): bool
    {
        $stmt = $this->bd->prepare(
            'INSERT IGNORE INTO inscripciones (alumno_id, curso_id)
             VALUES (:aid, :cid)'
        );
        $stmt->execute([':aid' => $alumnoId, ':cid' => $cursoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina la inscripción de un alumno en un curso.
     */
    public function desinscribir(int $alumnoId, int $cursoId): bool
    {
        $stmt = $this->bd->prepare(
            'DELETE FROM inscripciones
              WHERE alumno_id = :aid AND curso_id = :cid'
        );
        $stmt->execute([':aid' => $alumnoId, ':cid' => $cursoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Comprueba si un alumno está inscrito en un curso.
     * Se usa para proteger el acceso a contenido.
     */
    public function estaInscrito(int $alumnoId, int $cursoId): bool
    {
        $stmt = $this->bd->prepare(
            'SELECT COUNT(*) FROM inscripciones
              WHERE alumno_id = :aid AND curso_id = :cid'
        );
        $stmt->execute([':aid' => $alumnoId, ':cid' => $cursoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Categorías (relación M:M) ─────────────────────────────

    /**
     * Asigna categorías a un curso (reemplaza las anteriores).
     */
    public function sincronizarCategorias(int $cursoId, array $categoriaIds): void
    {
        // Borramos las categorías actuales
        $this->bd->prepare(
            'DELETE FROM cursos_categorias WHERE curso_id = :cid'
        )->execute([':cid' => $cursoId]);

        // Insertamos las nuevas
        $stmt = $this->bd->prepare(
            'INSERT INTO cursos_categorias (curso_id, categoria_id)
             VALUES (:cid, :catid)'
        );
        foreach ($categoriaIds as $catId) {
            $stmt->execute([':cid' => $cursoId, ':catid' => (int) $catId]);
        }
    }

    // ── Estadísticas ──────────────────────────────────────────

    /**
     * Resumen de un curso: nº de alumnos, unidades y asistencia media.
     * Usado en el dashboard del profesor.
     */
    public function obtenerEstadisticas(int $cursoId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT
               COUNT(DISTINCT i.alumno_id)                    AS total_alumnos,
               COUNT(DISTINCT u.id)                           AS total_unidades,
               COUNT(DISTINCT a.id)                           AS total_asistencias,
               ROUND(
                 COUNT(DISTINCT a.id) * 100.0 /
                 NULLIF(COUNT(DISTINCT i.alumno_id) * COUNT(DISTINCT u.id), 0),
               1)                                             AS porcentaje_asistencia
             FROM cursos c
             LEFT JOIN inscripciones i ON i.curso_id  = c.id
             LEFT JOIN unidades      u ON u.curso_id  = c.id
             LEFT JOIN asistencias   a ON a.unidad_id = u.id
                                     AND a.estado     = "presente"
            WHERE c.id = :cid'
        );
        $stmt->execute([':cid' => $cursoId]);
        return $stmt->fetch() ?: [];
    }
}