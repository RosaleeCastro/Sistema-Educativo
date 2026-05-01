<?php
// configuracion/GestorSesion.php
// ============================================================
// Centraliza toda la lógica de sesiones de PHP.
//
// RESPONSABILIDADES:
//   - Iniciar la sesión de forma segura (una sola vez)
//   - Guardar y leer los datos del usuario logueado
//   - Verificar si la sesión ha expirado
//   - Regenerar el ID al hacer login (previene fijación de sesión)
//   - Destruir la sesión al hacer logout
//   - Mensajes flash (aparecen una vez y desaparecen)
//
// USO:
//   - index.php llama GestorSesion::iniciar() UNA sola vez al arrancar
//   - Los controladores usan GestorSesion::obtenerIdUsuario(), etc.
//   - Nunca usar $_SESSION directamente fuera de esta clase
// ============================================================

declare(strict_types=1);

namespace App\Configuracion;

class GestorSesion
{
    // Claves internas de $_SESSION — usar constantes evita typos
    private const CLAVE_ID     = 'usuario_id';
    private const CLAVE_ROL    = 'usuario_rol';
    private const CLAVE_NOMBRE = 'usuario_nombre';
    private const CLAVE_CORREO = 'usuario_correo';
    private const CLAVE_ULTIMA = 'ultima_actividad';
    private const CLAVE_IP     = 'ip_origen';

    // ── Arranque ─────────────────────────────────────────────

    /**
     * Inicia la sesión de forma segura.
     * Llamar UNA SOLA VEZ desde index.php al arrancar.
     * Si la sesión ya está activa no hace nada.
     */
    public static function iniciar(): void
    {
        // Si ya hay sesión activa no hacer nada
        if (session_status() === PHP_SESSION_ACTIVE) return;

        // Si ya se enviaron cabeceras HTTP no podemos iniciar sesión
        if (headers_sent()) return;

        session_start();

        // Si el usuario ya tenía sesión verificamos que siga siendo válida
        if (self::estaAutenticado()) {
            self::verificarExpiracion();
            self::verificarIP();
        }
    }

    // ── Login y logout ───────────────────────────────────────

    /**
     * Guarda los datos del usuario en sesión tras un login exitoso.
     * Regenera el ID de sesión para prevenir ataques de fijación de sesión.
     */
    public static function iniciarSesionUsuario(array $usuario): void
    {
        // Regenerar ID ANTES de guardar datos — invalida el ID anterior
        session_regenerate_id(true);

        $_SESSION[self::CLAVE_ID]     = $usuario['id'];
        $_SESSION[self::CLAVE_ROL]    = $usuario['rol'];
        $_SESSION[self::CLAVE_NOMBRE] = $usuario['nombre'] . ' ' . $usuario['apellidos'];
        $_SESSION[self::CLAVE_CORREO] = $usuario['correo'];
        $_SESSION[self::CLAVE_ULTIMA] = time();
        $_SESSION[self::CLAVE_IP]     = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Cierra la sesión completamente.
     * Vacía los datos, elimina la cookie y destruye la sesión.
     */
    public static function cerrar(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // ── Lectura de datos del usuario ─────────────────────────

    public static function estaAutenticado(): bool
    {
        return isset($_SESSION[self::CLAVE_ID]);
    }

    public static function obtenerIdUsuario(): ?int
    {
        return isset($_SESSION[self::CLAVE_ID])
            ? (int) $_SESSION[self::CLAVE_ID]
            : null;
    }

    public static function obtenerRol(): ?string
    {
        return $_SESSION[self::CLAVE_ROL] ?? null;
    }

    public static function obtenerNombre(): string
    {
        return $_SESSION[self::CLAVE_NOMBRE] ?? 'Invitado';
    }

    public static function obtenerCorreo(): ?string
    {
        return $_SESSION[self::CLAVE_CORREO] ?? null;
    }

    public static function esAlumno(): bool
    {
        return self::obtenerRol() === ROL_ALUMNO;
    }

    public static function esProfesor(): bool
    {
        return self::obtenerRol() === ROL_PROFESOR;
    }

    public static function esAdmin(): bool
    {
        return self::obtenerRol() === ROL_ADMIN;
    }

    /**
     * Token hasheado de la sesión.
     * Usado en consultas_ia sin exponer el session_id real.
     */
    public static function obtenerTokenSesion(): string
    {
        return hash('sha256', session_id());
    }

    // ── Mensajes flash ───────────────────────────────────────

    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'][$tipo] = $mensaje;
    }

    public static function obtenerFlash(string $tipo): ?string
    {
        if (!isset($_SESSION['flash'][$tipo])) return null;
        $mensaje = $_SESSION['flash'][$tipo];
        unset($_SESSION['flash'][$tipo]);
        return $mensaje;
    }

    public static function hayFlash(string $tipo): bool
    {
        return isset($_SESSION['flash'][$tipo]);
    }

    // ── Seguridad interna ────────────────────────────────────

    private static function verificarExpiracion(): void
    {
        $limite          = SESION_DURACION_HORAS * 3600;
        $ultimaActividad = $_SESSION[self::CLAVE_ULTIMA] ?? 0;

        if ((time() - $ultimaActividad) > $limite) {
            self::cerrar();
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login?razon=expiracion');
            exit;
        }

        $_SESSION[self::CLAVE_ULTIMA] = time();
    }

    private static function verificarIP(): void
    {
        $ipActual = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipSesion = $_SESSION[self::CLAVE_IP] ?? '';

        if ($ipSesion && $ipActual !== $ipSesion) {
            error_log("[Seguridad] Cambio de IP. Original: {$ipSesion} | Actual: {$ipActual}");
            self::cerrar();
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login?razon=seguridad');
            exit;
        }
    }
}