<?php
// fuente/controladores/ControladorBase.php
// ============================================================
// Clase padre de todos los controladores del sistema.
//
// Proporciona métodos comunes que todos los controladores
// necesitan, evitando repetir código:
//   - Renderizar vistas pasándoles variables
//   - Responder con JSON para peticiones AJAX
//   - Redirigir a otras rutas
//   - Generar y verificar tokens CSRF
//   - Verificar si la petición es AJAX
// ============================================================

declare(strict_types=1);

namespace App\Controladores;

use App\Configuracion\GestorSesion;

abstract class ControladorBase
{
    // ── Renderizado de vistas ────────────────────────────────

    /**
     * Carga una vista pasándole variables.
     *
     * Uso: $this->vista('alumno/panel', ['cursos' => $listaCursos])
     * Carga el archivo: fuente/vistas/alumno/panel.php
     * con $cursos disponible directamente en la vista.
     */
    protected function vista(string $ruta, array $datos = []): void
    {
        // extract() convierte ['cursos' => [...]] en $cursos = [...]
        // Las variables quedan disponibles dentro de la vista
        extract($datos, EXTR_SKIP);

        // Datos que SIEMPRE están disponibles en todas las vistas
        $usuarioNombre = GestorSesion::obtenerNombre();
        $usuarioRol    = GestorSesion::obtenerRol();
        $usuarioId     = GestorSesion::obtenerIdUsuario();
        $flashExito    = GestorSesion::obtenerFlash('exito');
        $flashError    = GestorSesion::obtenerFlash('error');
        $flashInfo     = GestorSesion::obtenerFlash('info');

        $archivoVista = DIR_VISTAS . '/' . $ruta . '.php';

        if (!file_exists($archivoVista)) {
            error_log("[Vista] No encontrada: {$archivoVista}");
            http_response_code(500);
            exit('Error: vista no encontrada.');
        }

        // Cargamos la plantilla con cabecera y pie
        require DIR_VISTAS . '/plantillas/cabecera.php';
        require $archivoVista;
        require DIR_VISTAS . '/plantillas/pie.php';
    }

    /**
     * Carga una vista SIN plantilla (cabecera/pie).
     * Útil para: páginas de login, instalador, errores.
     */
    protected function vistaSimple(string $ruta, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $archivoVista = DIR_VISTAS . '/' . $ruta . '.php';

        if (!file_exists($archivoVista)) {
            http_response_code(500);
            exit('Error: vista no encontrada.');
        }

        require $archivoVista;
    }

    // ── Respuestas JSON (para endpoints AJAX) ────────────────

    /**
     * Envía una respuesta JSON de éxito.
     * Uso: $this->jsonOk(['cursos' => $lista])
     */
    protected function jsonOk(array $datos = [], string $mensaje = 'OK'): void
    {
        $this->enviarJson(array_merge(
            ['estado' => API_OK, 'mensaje' => $mensaje],
            $datos
        ));
    }

    /**
     * Envía una respuesta JSON de error.
     * Uso: $this->jsonError('Datos inválidos', 422)
     */
    protected function jsonError(string $mensaje, int $codigoHttp = 400): void
    {
        http_response_code($codigoHttp);
        $this->enviarJson(['estado' => API_ERROR, 'mensaje' => $mensaje]);
    }

    private function enviarJson(array $datos): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Redirecciones ────────────────────────────────────────

    /**
     * Redirige a otra ruta del sistema.
     * Uso: $this->redirigir('/alumno/panel')
     */
    protected function redirigir(string $ruta): void
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        header("Location: {$base}{$ruta}");
        exit;
    }

    /**
     * Redirige con un mensaje flash (aparece una sola vez en la siguiente página).
     * Uso: $this->redirigirConMensaje('/alumno/panel', 'exito', 'Curso inscrito correctamente')
     */
    protected function redirigirConMensaje(string $ruta, string $tipo, string $mensaje): void
    {
        GestorSesion::flash($tipo, $mensaje);
        $this->redirigir($ruta);
    }

    // ── Seguridad CSRF ───────────────────────────────────────

    /**
     * Genera un token CSRF y lo guarda en sesión.
     * Se incluye en los formularios como campo oculto.
     *
     * El CSRF (Cross-Site Request Forgery) es un ataque donde
     * una web maliciosa envía peticiones en nombre del usuario
     * sin que él lo sepa. El token lo previene porque la web
     * atacante no puede conocerlo.
     */
    protected function generarTokenCSRF(): string
    {
        if (empty($_SESSION[TOKEN_CSRF_NOMBRE])) {
            $_SESSION[TOKEN_CSRF_NOMBRE] = bin2hex(random_bytes(32));
        }
        return $_SESSION[TOKEN_CSRF_NOMBRE];
    }

    /**
     * Verifica que el token CSRF recibido es válido.
     * Llamar al inicio de cualquier método que procese un POST.
     */
    protected function verificarCSRF(): void
    {
        $tokenRecibido = $_POST[TOKEN_CSRF_NOMBRE]
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']  // Para peticiones AJAX
            ?? '';

        $tokenEsperado = $_SESSION[TOKEN_CSRF_NOMBRE] ?? '';

        if (!hash_equals($tokenEsperado, $tokenRecibido)) {
            error_log('[CSRF] Token inválido. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
            $this->jsonError('Petición no válida.', 403);
        }
    }

    // ── Utilidades ───────────────────────────────────────────

    /**
     * Comprueba si la petición viene de JavaScript (AJAX/Fetch).
     * Se usa para decidir si responder con JSON o con HTML.
     */
    protected function esPeticionAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /**
     * Lee y decodifica el body de una petición JSON (Fetch API).
     * Uso típico: $datos = $this->leerBodyJson();
     */
    protected function leerBodyJson(): array
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?? [];
    }

    /**
     * Obtiene y sanea un parámetro GET o POST.
     * Nunca devuelve HTML sin escapar (previene XSS).
     */
    protected function param(string $clave, string $defecto = ''): string
    {
        $valor = $_POST[$clave] ?? $_GET[$clave] ?? $defecto;
        return htmlspecialchars(trim((string) $valor), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtiene un parámetro numérico de la URL (los :id de las rutas).
     * Uso: $id = $this->paramRuta($parametros, 'id');
     */
    protected function paramRuta(array $parametros, string $clave): int
    {
        return (int) ($parametros[$clave] ?? 0);
    }
}