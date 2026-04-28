<?php
// autoload.php
// PSR-4 con dos rutas base:
//   App\Configuracion\ → /configuracion/
//   App\(resto)\       → /fuente/(subcarpeta)/

spl_autoload_register(function (string $claseCompleta): void {

    $prefijo = 'App\\';

    if (strncmp($prefijo, $claseCompleta, strlen($prefijo)) !== 0) {
        return;
    }

    $claseRelativa = substr($claseCompleta, strlen($prefijo));

    // App\Configuracion\BaseDatos → configuracion/BaseDatos.php
    if (strncmp('Configuracion\\', $claseRelativa, 14) === 0) {
        $nombreClase = substr($claseRelativa, 14);
        $rutaArchivo = __DIR__ . '/configuracion/' . $nombreClase . '.php';

    // App\Modelos\Usuario → fuente/modelos/Usuario.php
    // App\Controladores\X → fuente/controladores/X.php
    // App\Servicios\X     → fuente/servicios/X.php
    } else {
        $partes      = explode('\\', $claseRelativa);
        $nombreClase = array_pop($partes);
        $subcarpeta  = strtolower(implode('/', $partes));
        $rutaArchivo = __DIR__ . '/fuente/' . $subcarpeta . '/' . $nombreClase . '.php';
    }

    if (file_exists($rutaArchivo)) {
        require_once $rutaArchivo;
    }
});