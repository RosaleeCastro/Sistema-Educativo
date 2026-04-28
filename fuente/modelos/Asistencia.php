<?php
// Gestiona rl registro y consulta de asistencias:
//  - Marcar presencia, ausencia o tardanza
//  - Consultar asistencia por unidad (vista profesor)
//  - consultar asistencia por alumno (vista alumno)
//  - Generar datos para exportar a csv 
//  - Estadisticas para el Dashboard


declare(strict_types=1);

namespace App\Modelos;

use PDO;

class Asistencia extends ModeloBase
{
  protected string $tabla = 'asistencias';

  protected array $camposPermitidos = [
    'alumno_id', 'unidad_id', 'estado', 'ip_origen',
  ];

  //-----REGISTRO --------------

  /**
   * Registra o actualiza la asistencia de un alumno
   * Si ya existe un registro para ese alumno + unidad,
   * lo actualiza (INSERT ....ON DUPLICATE KEY UPADATE).
   * 
   * la restricción UNIQUE KEY uq_asistencia en la BD 
   * garantiza que no puede haber dos registros.
   */

  public function registrar( int $alumnoId, int $unidadId, string $estado): bool
  {
      $ip = $_SERVER['REMOTE_ADDR'] ?? null;
      $stmt = $this->bd->prepare(
        'INSERT INTO asistencias (alumno_id, unidad_id, estado, ip_origen)
             VALUES (:aid, :uid, :estado, :ip)
             ON DUPLICATE KEY UPDATE
               estado    = VALUES(estado),
               ip_origen = VALUES(ip_origen)'
      );
      $stmt->execute([
         ':aid'    => $alumnoId,
            ':uid'    => $unidadId,
            ':estado' => $estado,
            ':ip'     => $ip,
      ]);
      return $stmt-> rowCount() > 0;
 }
    
    
    
      //---------Consultas -------------

    /**
     * Devuelve la asistencia de un alumno en una unidad concreta.
     * 
     */

 public function obtenerDeAlumnoEnUnidad(int $alumnoId, int $unidadId): ?array
    {
        $stmt = $this->bd->prepare(
            'SELECT * FROM asistencias
              WHERE alumno_id = :aid AND unidad_id = :uid
              LIMIT 1'
        );
        $stmt->execute([':aid' => $alumnoId, ':uid' => $unidadId]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Lista completa de asistencia de una unidad
     * Incluye nombre del alumno para mostrar en la tabla del profesor 
     */

    public function listarPorUnidad(int $unidadId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT a.*,
                    u.nombre    AS alumno_nombre,
                    u.apellidos AS alumno_apellidos,
                    u.correo    AS alumno_correo
               FROM asistencias a
               JOIN usuarios u ON u.id = a.alumno_id
              WHERE a.unidad_id = :uid
           ORDER BY u.apellidos ASC, u.nombre ASC'
        );
        $stmt->execute([':uid' => $unidadId]);
        return $stmt->fetchAll();
    }


    /**
     * Historial completo de asitencia de un alumno en todas 
     * las unidades de un curso
     */

   public function historialDeAlumnoEnCurso(int $alumnoId, int $cursoId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT un.titulo,
                    un.tipo_recurso,
                    un.orden,
                    a.estado,
                    a.created_at AS fecha
               FROM unidades un
               LEFT JOIN asistencias a ON a.unidad_id  = un.id
                                     AND a.alumno_id   = :aid
              WHERE un.curso_id = :cid
                AND un.publicada = 1
           ORDER BY un.orden ASC'
        );
        $stmt->execute([':aid' => $alumnoId, ':cid' => $cursoId]);
        return $stmt->fetchAll();
    }


    //Estadisticas---------------------

    /**
     * Resumen de asistencias de una unidad : totales por estado.
     * Usado en el contador en tiempo real del profesor.
     */

    public function resumenPorUnidad(int $unidadId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT
               SUM(estado = "presente")  AS presentes,
               SUM(estado = "ausente")   AS ausentes,
               SUM(estado = "tardanza")  AS tardanzas,
               COUNT(*)                  AS total
             FROM asistencias
            WHERE unidad_id = :uid'
        );
        $stmt->execute([':uid' => $unidadId]);
        return $stmt->fetch() ?: [
            'presentes' => 0, 'ausentes' => 0,
            'tardanzas' => 0, 'total'    => 0,
        ];
    }

    /**
     * Porcentaje de asistencia de un alumno en un curso.
     */
    public function porcentajeAlumnoEnCurso(int $alumnoId, int $cursoId): float
    {
        $stmt = $this->bd->prepare(
            'SELECT
               COUNT(DISTINCT un.id)                                   AS total_unidades,
               SUM(a.estado = "presente" OR a.estado = "tardanza")     AS asistidas
             FROM unidades un
             LEFT JOIN asistencias a ON a.unidad_id = un.id
                                   AND a.alumno_id  = :aid
            WHERE un.curso_id  = :cid
              AND un.publicada = 1'
        );
        $stmt->execute([':aid' => $alumnoId, ':cid' => $cursoId]);
        $datos = $stmt->fetch();
 
        if (!$datos || (int) $datos['total_unidades'] === 0) return 0.0;
 
        return round(
            ((int) $datos['asistidas'] / (int) $datos['total_unidades']) * 100,
            1
        );
    }

// ── Exportación a CSV ─────────────────────────────────────
 
    /**
     * Genera y descarga el CSV de asistencia de una unidad.
     * Enviamos las cabeceras HTTP adecuadas para forzar la descarga.
     */
    public function exportarCsvUnidad(int $unidadId, string $nombreUnidad): void
    {
        $datos = $this->listarPorUnidad($unidadId);
 
        // Nombre del archivo: sin caracteres especiales
        $nombreArchivo = 'asistencia_'
            . preg_replace('/[^a-z0-9]/i', '_', $nombreUnidad)
            . '_' . date('Y-m-d')
            . '.csv';
 
        // Cabeceras HTTP para forzar descarga
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
        header('Pragma: no-cache');
        header('Expires: 0');
 
        // BOM UTF-8 para que Excel abra el CSV correctamente
        echo "\xEF\xBB\xBF";
 
        $salida = fopen('php://output', 'w');
 
        // Cabecera del CSV
        fputcsv($salida, ['Apellidos', 'Nombre', 'Correo', 'Estado', 'Fecha'], ';');
 
        foreach ($datos as $fila) {
            fputcsv($salida, [
                $fila['alumno_apellidos'],
                $fila['alumno_nombre'],
                $fila['alumno_correo'],
                ucfirst($fila['estado']),
                $fila['created_at'],
            ], ';');
        }
 
        fclose($salida);
        exit;
    }
       

 
}
?>