<?php
// autoload.php
// Autoloader PSR-4 que maneja dos rutas base:
//   App\Configuracion\ → configuracion/
//   App\             → fuente/

spl_autoload_register(function (string $claseCompleta): void {

    $prefijo = 'App\\';

    if (strncmp($prefijo, $claseCompleta, strlen($prefijo)) !== 0) {
        return;
    }

    $claseRelativa = substr($claseCompleta, strlen($prefijo));

    // Si empieza por Configuracion\ → carpeta configuracion/
    if (strncmp('Configuracion\\', $claseRelativa, 14) === 0) {
        $claseSin = substr($claseRelativa, 14); // quita Configuracion\
        $rutaArchivo = __DIR__ . '/configuracion/' . $claseSin . '.php';
    } else {
        // Todo lo demás → carpeta fuente/
        $rutaArchivo = __DIR__ . '/fuente/'
            . strtolower(str_replace('\\', '/', dirname($claseRelativa)))
            . '/' . basename(str_replace('\\', '/', $claseRelativa))
            . '.php';
    }

    if (file_exists($rutaArchivo)) {
        require_once $rutaArchivo;
    }
});