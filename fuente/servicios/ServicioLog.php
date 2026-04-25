<?php
// fuente/servicios/ServicioLog.php
// ============================================================
// Registra las acciones del sistema en dos lugares:
//
//   1. Base de datos (tabla registros_actividad)
//      → Para consultarlos desde el panel de administración
//        y mostrarlos en el dashboard
//
//   2. Archivo de log en almacenamiento/logs/
//      → Para diagnóstico técnico y errores del servidor
//
// Uso desde cualquier controlador:
//   ServicioLog::registrar('login', null, null, ['rol' => 'alumno']);
//   ServicioLog::registrar('crear_curso', 'curso', 5);
//   ServicioLog::error('Error inesperado en ControladorCurso', $excepcion);
// ============================================================

declare(strict_types=1);

namespace App\Servicios;

use App\Configuracion\BaseDatos;
use App\Configuracion\GestorSesion;
use PDO;
use Throwable;

class ServicioLog
{
    /**
     * Registra una acción en la BD y en el archivo de log.
     *
     * @param string      $accion      Qué ocurrió: 'login', 'crear_curso', 'marcar_asistencia'
     * @param string|null $entidadTipo Qué tipo de registro afectó: 'curso', 'unidad', 'usuario'
     * @param int|null    $entidadId   ID del registro afectado
     * @param array       $detalle     Datos extra en formato clave-valor
     */
    public static function registrar(
        string  $accion,
        ?string $entidadTipo = null,
        ?int    $entidadId   = null,
        array   $detalle     = []
    ): void {
        $usuarioId = GestorSesion::obtenerIdUsuario();
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        // ── Guardar en base de datos ──────────────────────────
        try {
            $pdo  = BaseDatos::obtenerConexion();
            $stmt = $pdo->prepare(
                'INSERT INTO registros_actividad
                   (usuario_id, accion, entidad_tipo, entidad_id, detalle, ip)
                 VALUES
                   (:uid, :accion, :tipo, :entidad_id, :detalle, :ip)'
            );
            $stmt->execute([
                ':uid'        => $usuarioId,
                ':accion'     => $accion,
                ':tipo'       => $entidadTipo,
                ':entidad_id' => $entidadId,
                ':detalle'    => !empty($detalle) ? json_encode($detalle) : null,
                ':ip'         => $ip,
            ]);
        } catch (Throwable $e) {
            // Si falla el log en BD, al menos lo guardamos en archivo
            self::escribirArchivo("ERROR AL REGISTRAR EN BD: {$e->getMessage()}");
        }

        // ── Guardar en archivo de log ─────────────────────────
        $nombreUsuario = GestorSesion::obtenerNombre();
        $mensaje = sprintf(
            '[%s] Usuario: %s (ID:%s) | Acción: %s | %s:%s | IP: %s',
            date('Y-m-d H:i:s'),
            $nombreUsuario ?: 'Anónimo',
            $usuarioId     ?? '-',
            $accion,
            $entidadTipo   ?? '-',
            $entidadId     ?? '-',
            $ip            ?? '-'
        );

        self::escribirArchivo($mensaje);
    }

    /**
     * Registra un error técnico con el stack trace completo.
     * Solo va al archivo, no a la BD (puede ser un error de conexión).
     */
    public static function error(string $contexto, Throwable $excepcion): void
    {
        $mensaje = sprintf(
            '[%s] ERROR en %s: %s | Archivo: %s línea %d',
            date('Y-m-d H:i:s'),
            $contexto,
            $excepcion->getMessage(),
            $excepcion->getFile(),
            $excepcion->getLine()
        );

        self::escribirArchivo($mensaje, 'error');

        // En desarrollo mostramos el error completo
        if (($_ENV['ENTORNO'] ?? 'desarrollo') === 'desarrollo') {
            error_log($mensaje);
        }
    }

    /**
     * Escribe una línea en el archivo de log correspondiente.
     */
    private static function escribirArchivo(string $mensaje, string $tipo = 'actividad'): void
    {
        $entorno   = $_ENV['ENTORNO'] ?? 'desarrollo';
        $nombreLog = $tipo === 'error' ? 'errores' : $entorno;
        $rutaLog   = DIR_LOGS . "/{$nombreLog}.log";

        // Creamos el directorio si no existe
        if (!is_dir(DIR_LOGS)) {
            mkdir(DIR_LOGS, 0755, true);
        }

        file_put_contents(
            $rutaLog,
            $mensaje . PHP_EOL,
            FILE_APPEND | LOCK_EX  // LOCK_EX evita escrituras simultáneas corruptas
        );
    }
}