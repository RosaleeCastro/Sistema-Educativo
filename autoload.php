<?php
// autoload.php
// ============================================================
// Autoloader PSR-4 artesanal
// Convierte el namespace en una ruta de archivo automáticamente
//
// Ejemplo:
//   new App\Modelos\Usuario()
//   → busca: /fuente/modelos/Usuario.php
//
//   new App\Controladores\ControladorAuth()
//   → busca: /fuente/controladores/ControladorAuth.php
// ============================================================

spl_autoload_register(function (string $claseCompleta): void {

    // Prefijo base del namespace de la aplicación
    $prefijo    = 'App\\';
    $directorioBase = __DIR__ . '/fuente/';

    // Si la clase no pertenece a nuestro namespace, ignorar
    if (strncmp($prefijo, $claseCompleta, strlen($prefijo)) !== 0) {
        return;
    }

    // Quitamos el prefijo "App\" para quedarnos con el resto
    // Ej: "App\Modelos\Usuario" → "Modelos\Usuario"
    $claseRelativa = substr($claseCompleta, strlen($prefijo));

    // Convertimos las barras de namespace en separadores de carpeta
    // y añadimos la extensión .php
    // Ej: "Modelos\Usuario" → "/fuente/modelos/Usuario.php"
    $rutaArchivo = $directorioBase
        . strtolower(str_replace('\\', '/', dirname($claseRelativa)))
        . '/'
        . basename(str_replace('\\', '/', $claseRelativa))
        . '.php';

    // Si el archivo existe, lo cargamos
    if (file_exists($rutaArchivo)) {
        require_once $rutaArchivo;
    }
});
