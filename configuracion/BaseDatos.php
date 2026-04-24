<?php
// configuracion/BaseDatos.php
// ============================================================
// Gestiona la conexión a MySQL usando PDO.
//
// Patrón Singleton: garantiza que solo existe UNA conexión
// activa durante toda la petición, sin importar desde cuántos
// modelos se llame. Evita abrir 10 conexiones innecesarias.
//
// Uso desde cualquier modelo:
//   $pdo = BaseDatos::obtenerConexion();
//   $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
// ============================================================

declare(strict_types=1);

namespace App\Configuracion;

use PDO;
use PDOException;

final class BaseDatos
{
    // La única instancia de PDO que existirá en toda la app
    private static ?PDO $instancia = null;

    // Bloqueamos la instanciación directa y la clonación
    // nadie puede hacer: new BaseDatos() — solo BaseDatos::obtenerConexion()
    private function __construct() {}
    private function __clone()     {}

    /**
     * Devuelve la conexión PDO activa.
     * Si aún no existe, la crea. Si ya existe, la reutiliza.
     */
    public static function obtenerConexion(): PDO
    {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        // Construimos el DSN (Data Source Name) con los datos del .env
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST']   ?? 'localhost',
            $_ENV['DB_PUERTO'] ?? '3307',
            $_ENV['DB_NOMBRE'] ?? 'sistema_educativo'
        );

        // Opciones de seguridad y comportamiento de PDO
        $opciones = [
            // Lanza excepciones en vez de errores silenciosos
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Los resultados llegan como arrays asociativos por defecto
            // ['id' => 1, 'nombre' => 'María'] en vez de [0 => 1, 1 => 'María']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // MUY IMPORTANTE: desactiva los "prepares simulados"
            // Con false, PDO envía la consulta preparada REAL a MySQL
            // Esto previene la segunda capa de SQL injection
            PDO::ATTR_EMULATE_PREPARES   => false,

            // Fuerza UTF-8 en cada nueva conexión
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$instancia = new PDO(
                $dsn,
                $_ENV['DB_USUARIO'] ?? 'root',
                $_ENV['DB_CLAVE']   ?? '',
                $opciones
            );
        } catch (PDOException $e) {
            // Registramos el error real en el log del servidor
            error_log('[BaseDatos] Error de conexión: ' . $e->getMessage());

            // Al usuario NUNCA le mostramos detalles técnicos
            // (no queremos exponer credenciales o estructura interna)
            http_response_code(500);
            exit(json_encode([
                'estado'  => 'error',
                'mensaje' => 'Error interno del servidor. Inténtalo más tarde.'
            ]));
        }

        return self::$instancia;
    }

    /**
     * Cierra la conexión explícitamente.
     * Útil en scripts de mantenimiento o tests.
     */
    public static function cerrarConexion(): void
    {
        self::$instancia = null;
    }
}