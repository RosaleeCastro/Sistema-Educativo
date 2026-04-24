<?php
// configuracion/Enrutador.php
// ============================================================
// El Enrutador recibe la URL de cada petición y la mapea
// al Controlador y método correcto.
//
// También actúa como portero:
//   - Rutas públicas: accesibles sin sesión (login, instalador)
//   - Rutas protegidas: requieren sesión activa
//   - Rutas por rol: solo accesibles para alumno/profesor/admin
//
// Ejemplo de registro de rutas:
//   $enrutador->get('/alumno/panel', [ControladorAlumno::class, 'panel']);
//   $enrutador->post('/api/asistencia', [ControladorAsistencia::class, 'marcar']);
// ============================================================

declare(strict_types=1);

namespace App\Configuracion;

class Enrutador
{
    // Array de todas las rutas registradas
    private array $rutas = [];

    // Rutas que NO requieren sesión iniciada
    private array $rutasPublicas = [
        '/',
        '/login',
        '/instalar',
    ];

    // ── Métodos para registrar rutas ─────────────────────────

    public function get(string $patron, callable|array $manejador, ?string $rol = null): void
    {
        $this->agregar('GET', $patron, $manejador, $rol);
    }

    public function post(string $patron, callable|array $manejador, ?string $rol = null): void
    {
        $this->agregar('POST', $patron, $manejador, $rol);
    }

    private function agregar(
        string         $metodo,
        string         $patron,
        callable|array $manejador,
        ?string        $rol
    ): void {
        $this->rutas[] = [
            'metodo'    => strtoupper($metodo),
            'patron'    => $patron,
            'manejador' => $manejador,
            'rol'       => $rol,         // null = cualquier usuario autenticado
        ];
    }

    // ── Motor principal ──────────────────────────────────────

    /**
     * Lee la URL actual, busca la ruta que coincide
     * y ejecuta el controlador correspondiente.
     * Si no encuentra ninguna, muestra el error 404.
     */
    public function despachar(): void
    {
        $metodoActual = $_SERVER['REQUEST_METHOD'];
        $uriActual    = $this->limpiarUri($_SERVER['REQUEST_URI'] ?? '/');

        // ── Control de acceso ─────────────────────────────────
        // Si la ruta no es pública y no hay sesión → redirigir al login
        if (!$this->esRutaPublica($uriActual) && !GestorSesion::estaAutenticado()) {
            // Guardamos la URL a la que intentaba ir para redirigir después del login
            $_SESSION['url_deseada'] = $uriActual;
            $this->redirigir('/login');
            return;
        }

        // ── Buscar la ruta coincidente ────────────────────────
        foreach ($this->rutas as $ruta) {

            if ($ruta['metodo'] !== $metodoActual) continue;

            // Convertimos :id en expresión regular que captura números
            // Ej: '/alumno/curso/:id' → '/alumno/curso/(?P<id>[0-9]+)'
            $patronRegex = preg_replace(
                '/:([a-z_]+)/',
                '(?P<$1>[0-9]+)',
                $ruta['patron']
            );
            $patronRegex = '@^' . $patronRegex . '$@';

            if (!preg_match($patronRegex, $uriActual, $parametros)) continue;

            // Ruta encontrada → verificar rol si es necesario
            if ($ruta['rol'] !== null) {
                $this->verificarRol($ruta['rol']);
            }

            // Filtramos solo los grupos con nombre (los parámetros :id, etc.)
            $parametros = array_filter($parametros, 'is_string', ARRAY_FILTER_USE_KEY);

            // Ejecutamos el controlador
            $this->ejecutar($ruta['manejador'], $parametros);
            return;
        }

        // Ninguna ruta coincidió → Error 404
        $this->mostrarError(404);
    }

    // ── Métodos auxiliares ───────────────────────────────────

    /**
     * Limpia la URI eliminando la base del proyecto y la query string.
     * '/sistema-educativo/publico/alumno/panel?page=2' → '/alumno/panel'
     */
    private function limpiarUri(string $uri): string
    {
        // Eliminamos la query string (?param=valor)
        $uri = parse_url($uri, PHP_URL_PATH);

        // Eliminamos la base del proyecto si estamos en un subdirectorio
        $base = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_PATH) ?? '';
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . trim($uri, '/') ?: '/';
    }

    private function esRutaPublica(string $uri): bool
    {
        return in_array($uri, $this->rutasPublicas, true);
    }

    /**
     * Comprueba que el usuario tiene el rol requerido.
     * Si no lo tiene, muestra error 403 (Prohibido).
     */
    private function verificarRol(string $rolRequerido): void
    {
        $rolUsuario = GestorSesion::obtenerRol();

        // El admin siempre puede acceder a todo
        if ($rolUsuario === ROL_ADMIN) return;

        if ($rolUsuario !== $rolRequerido) {
            $this->mostrarError(403);
            exit;
        }
    }

    /**
     * Ejecuta el manejador de la ruta.
     * Acepta: [NombreClase::class, 'metodo'] o una función anónima.
     */
    private function ejecutar(callable|array $manejador, array $parametros): void
    {
        if (is_array($manejador)) {
            [$clase, $metodo] = $manejador;
            $controlador = new $clase();
            $controlador->$metodo($parametros);
        } else {
            call_user_func($manejador, $parametros);
        }
    }

    private function redirigir(string $ruta): void
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        header("Location: {$base}{$ruta}");
        exit;
    }

    private function mostrarError(int $codigo): void
    {
        http_response_code($codigo);
        $vista = DIR_VISTAS . "/errores/{$codigo}.php";

        if (file_exists($vista)) {
            require $vista;
        } else {
            echo "<h1>Error {$codigo}</h1>";
        }
        exit;
    }
}