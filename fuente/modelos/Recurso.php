<?php
// fuente/modelos/Recurso.php
// ============================================================
// Gestiona los recursos documentales adjuntos a las unidades:
//   - Subida segura de archivos al servidor
//   - Validación de tipo y tamaño
//   - Listado de recursos por unidad
//   - Eliminación física del archivo y del registro en BD
// ============================================================

declare(strict_types=1);

namespace App\Modelos;

class Recurso extends ModeloBase
{
    protected string $tabla = 'recursos_documentales';

    protected array $camposPermitidos = [
        'unidad_id', 'subido_por', 'titulo',
        'tipo', 'ruta_archivo', 'tamano_bytes',
    ];

    // Tipos MIME permitidos agrupados por tipo de recurso
    private array $mimePermitidos = [
        'pdf'    => ['application/pdf'],
        'imagen' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video'  => ['video/mp4', 'video/webm'],
        'otro'   => ['application/zip', 'application/x-zip-compressed',
                     'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'application/vnd.ms-powerpoint',
                     'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    ];

    // ── Subida ───────────────────────────────────────────────

    /**
     * Procesa y guarda un archivo subido por un profesor.
     * Valida tipo, tamaño y mueve el archivo a almacenamiento/.
     *
     * @param array $archivo  El elemento de $_FILES['archivo']
     * @param int   $unidadId Unidad a la que pertenece
     * @param int   $userId   Profesor que sube el archivo
     * @param string $titulo  Nombre descriptivo del recurso
     * @return array ['ok' => bool, 'mensaje' => string, 'id' => int|null]
     */
    public function subirArchivo(
        array  $archivo,
        int    $unidadId,
        int    $userId,
        string $titulo
    ): array {
        // ── Validaciones básicas ──────────────────────────────
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'mensaje' => $this->mensajeErrorSubida($archivo['error'])];
        }

        $maxBytes = (int)($_ENV['SUBIDAS_MAX_MB'] ?? 10) * 1024 * 1024;
        if ($archivo['size'] > $maxBytes) {
            $maxMb = $_ENV['SUBIDAS_MAX_MB'] ?? 10;
            return ['ok' => false, 'mensaje' => "El archivo supera el límite de {$maxMb} MB."];
        }

        // ── Validar tipo MIME real (no solo la extensión) ─────
        // mime_content_type lee los bytes del archivo, no el nombre
        // Esto previene subir un .php renombrado como documento.pdf
        $mimeReal = mime_content_type($archivo['tmp_name']);
        $tipoRecurso = $this->detectarTipo($mimeReal);

        if (!$tipoRecurso) {
            return ['ok' => false, 'mensaje' => 'Tipo de archivo no permitido.'];
        }

        // ── Generar nombre único y mover el archivo ───────────
        $extension    = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombreSeguro = bin2hex(random_bytes(16)) . '.' . strtolower($extension);
        $subcarpeta   = date('Y/m');  // Organiza por año/mes: 2024/11/
        $dirDestino   = DIR_SUBIDAS . '/' . $subcarpeta;

        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        $rutaCompleta = $dirDestino . '/' . $nombreSeguro;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            return ['ok' => false, 'mensaje' => 'Error al guardar el archivo en el servidor.'];
        }

        // ── Guardar en BD ─────────────────────────────────────
        // Guardamos la ruta relativa, no la absoluta
        // Así funciona aunque el proyecto se mueva de servidor
        $rutaRelativa = $subcarpeta . '/' . $nombreSeguro;

        $id = $this->crear([
            'unidad_id'    => $unidadId,
            'subido_por'   => $userId,
            'titulo'       => $titulo,
            'tipo'         => $tipoRecurso,
            'ruta_archivo' => $rutaRelativa,
            'tamano_bytes' => $archivo['size'],
        ]);

        return ['ok' => true, 'mensaje' => 'Archivo subido correctamente.', 'id' => $id];
    }

    /**
     * Guarda un enlace externo como recurso (sin archivo físico).
     */
    public function guardarEnlace(
        int    $unidadId,
        int    $userId,
        string $titulo,
        string $url
    ): int {
        return $this->crear([
            'unidad_id'    => $unidadId,
            'subido_por'   => $userId,
            'titulo'       => $titulo,
            'tipo'         => 'enlace',
            'ruta_archivo' => $url,
            'tamano_bytes' => null,
        ]);
    }

    // ── Listados ─────────────────────────────────────────────

    /**
     * Devuelve todos los recursos de una unidad.
     */
    public function listarPorUnidad(int $unidadId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT r.*,
                    CONCAT(u.nombre, " ", u.apellidos) AS nombre_profesor
               FROM recursos_documentales r
               JOIN usuarios u ON u.id = r.subido_por
              WHERE r.unidad_id = :uid
           ORDER BY r.created_at ASC'
        );
        $stmt->execute([':uid' => $unidadId]);
        return $stmt->fetchAll();
    }

    // ── Eliminación ──────────────────────────────────────────

    /**
     * Elimina el registro de BD y el archivo físico del servidor.
     */
    public function eliminarConArchivo(int $id): bool
    {
        $recurso = $this->buscarPorId($id);
        if (!$recurso) return false;

        // Solo intentamos borrar el archivo si no es un enlace externo
        if ($recurso['tipo'] !== 'enlace') {
            $rutaFisica = DIR_SUBIDAS . '/' . $recurso['ruta_archivo'];
            if (file_exists($rutaFisica)) {
                unlink($rutaFisica);
            }
        }

        return $this->eliminar($id);
    }

    // ── Utilidades ───────────────────────────────────────────

    /**
     * Detecta el tipo de recurso a partir del MIME real del archivo.
     * Devuelve null si el tipo no está permitido.
     */
    private function detectarTipo(string $mime): ?string
    {
        foreach ($this->mimePermitidos as $tipo => $mimes) {
            if (in_array($mime, $mimes, true)) return $tipo;
        }
        return null;
    }

    private function mensajeErrorSubida(int $codigo): string
    {
        return match($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL  => 'El archivo se subió parcialmente. Inténtalo de nuevo.',
            UPLOAD_ERR_NO_FILE  => 'No se seleccionó ningún archivo.',
            default             => 'Error desconocido al subir el archivo.',
        };
    }
}