<?php
// configuracion/Entorno.php
// ============================================================
// Configura el comportamiento de PHP según el entorno actual
// Se llama una sola vez al arrancar la aplicación en index.php
// ============================================================

declare(strict_types=1);

namespace App\Configuracion;

class Entorno
{
    private static string $entornoActual = 'desarrollo';

    /**
     * Carga el archivo .env y configura PHP según el entorno.
     * Llamar una sola vez desde index.php al arrancar.
     */
    public static function inicializar(): void
    {
        self::cargarEnv();
        self::$entornoActual = $_ENV['ENTORNO'] ?? 'desarrollo';
        self::configurarPHP();
    }

    /**
     * Lee el archivo .env línea por línea y carga
     * cada variable en $_ENV y getenv().
     * Hacemos esto a mano para no depender de librerías externas.
     */
    private static function cargarEnv(): void
    {
        $rutaEnv = RAIZ . '/.env';

        if (!file_exists($rutaEnv)) {
            // Si no existe .env, redirige al instalador
            if (!str_contains($_SERVER['REQUEST_URI'] ?? '', 'instalar')) {
                header('Location: /sistema-educativo/publico/instalar.php');
                exit;
            }
            return;
        }

        $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {
            // Ignorar comentarios (líneas que empiezan con #)
            if (str_starts_with(trim($linea), '#')) continue;

            // Separar clave y valor por el primer signo =
            if (!str_contains($linea, '=')) continue;

            [$clave, $valor] = explode('=', $linea, 2);
            $clave  = trim($clave);
            $valor  = trim($valor);

            // Solo cargamos si la variable no existe ya en el entorno
            if (!isset($_ENV[$clave])) {
                $_ENV[$clave] = $valor;
                putenv("{$clave}={$valor}");
            }
        }
    }

    /**
     * Configura el nivel de reporte de errores de PHP
     * según el entorno actual.
     */
    private static function configurarPHP(): void
    {
        switch (self::$entornoActual) {

            case 'desarrollo':
                // Ver todos los errores en pantalla para depurar
                error_reporting(E_ALL);
                ini_set('display_errors', '1');
                ini_set('log_errors', '1');
                ini_set('error_log', DIR_LOGS . '/desarrollo.log');
                break;

            case 'pruebas':
                // Errores en log, no en pantalla
                error_reporting(E_ALL);
                ini_set('display_errors', '0');
                ini_set('log_errors', '1');
                ini_set('error_log', DIR_LOGS . '/pruebas.log');
                break;

            case 'produccion':
                // Nunca mostrar errores al usuario
                error_reporting(0);
                ini_set('display_errors', '0');
                ini_set('log_errors', '1');
                ini_set('error_log', DIR_LOGS . '/produccion.log');
                break;
        }

        // Zona horaria estándar para toda la app
        date_default_timezone_set('Europe/Madrid');

        // Configuración de sesiones seguras
        ini_set('session.cookie_httponly', '1');  // JS no puede leer la cookie
        ini_set('session.use_strict_mode', '1');  // Previene fijación de sesión
        ini_set('session.cookie_samesite', 'Strict');
    }

    public static function obtenerEntorno(): string
    {
        return self::$entornoActual;
    }

    public static function esDevelopment(): bool
    {
        return self::$entornoActual === 'desarrollo';
    }

    public static function esProduccion(): bool
    {
        return self::$entornoActual === 'produccion';
    }
}