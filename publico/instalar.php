<?php
// publico/instalar.php
// ============================================================
// Asistente de instalación del sistema educativo
// Verifica requisitos, crea la BD y genera el .env
// Eliminar o proteger este archivo tras la instalación
// ============================================================

define('RAIZ', dirname(__DIR__));
$errores  = [];
$exito    = false;
$paso     = $_POST['paso'] ?? 0;

// ── Funciones auxiliares ────────────────────────────────────

function verificarRequisitos(): array
{
    $checks = [];
    $checks['php_version'] = [
        'nombre'  => 'PHP 8.0 o superior',
        'ok'      => version_compare(PHP_VERSION, '8.0.0', '>='),
        'actual'  => PHP_VERSION,
    ];
    $checks['pdo_mysql'] = [
        'nombre'  => 'Extensión PDO MySQL',
        'ok'      => extension_loaded('pdo_mysql'),
        'actual'  => extension_loaded('pdo_mysql') ? 'Instalada' : 'NO instalada',
    ];
    $checks['mod_rewrite'] = [
        'nombre'  => 'Apache mod_rewrite',
        'ok'      => function_exists('apache_get_modules')
                     ? in_array('mod_rewrite', apache_get_modules())
                     : true, // En XAMPP suele estar activo
        'actual'  => 'Revisar manualmente en XAMPP',
    ];
    $checks['carpeta_subidas'] = [
        'nombre'  => 'Carpeta almacenamiento/ escribible',
        'ok'      => is_writable(RAIZ . '/almacenamiento'),
        'actual'  => is_writable(RAIZ . '/almacenamiento') ? 'Escribible' : 'Sin permisos',
    ];
    return $checks;
}

function crearBaseDatos(string $host, string $puerto, string $usuario, string $clave, string $nombre): array
{
    try {
        // Conectamos sin seleccionar BD para poder crearla
        $pdo = new PDO(
            "mysql:host={$host};port={$puerto};charset=utf8mb4",
            $usuario, $clave,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$nombre}`
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$nombre}`");

        // Ejecutar el script SQL principal
        $sql = file_get_contents(RAIZ . '/bd/esquema.sql');
        if ($sql) {
            $pdo->exec($sql);
        }

        // Ejecutar datos semilla
        $semilla = file_get_contents(RAIZ . '/bd/semilla.sql');
        if ($semilla) {
            $pdo->exec($semilla);
        }

        return ['ok' => true, 'mensaje' => 'Base de datos creada correctamente'];
    } catch (PDOException $e) {
        return ['ok' => false, 'mensaje' => $e->getMessage()];
    }
}

function generarEnv(array $datos): bool
{
    $claveSecreta = bin2hex(random_bytes(32));
    $contenido = <<<ENV
    ENTORNO=desarrollo

    DB_HOST={$datos['host']}
    DB_PUERTO={$datos['puerto']}
    DB_NOMBRE={$datos['nombre_bd']}
    DB_USUARIO={$datos['usuario']}
    DB_CLAVE={$datos['clave']}

    APP_URL=http://localhost/sistema-educativo/publico
    APP_CLAVE_SECRETA={$claveSecreta}

    IA_URL=http://localhost:11434
    IA_MODELO=llama3
    IA_MAX_CONSULTAS_POR_SESION=20

    SUBIDAS_MAX_MB=10
    SUBIDAS_TIPOS_PERMITIDOS=pdf,jpg,jpeg,png,mp4,zip
    ENV;

    return file_put_contents(RAIZ . '/.env', trim($contenido)) !== false;
}

// ── Procesar formulario ──────────────────────────────────────
$resultadoBD  = null;
$requisitos   = verificarRequisitos();
$todosOk      = array_reduce($requisitos, fn($carry, $r) => $carry && $r['ok'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $todosOk) {
    $datos = [
        'host'      => trim($_POST['db_host']    ?? 'localhost'),
        'puerto'    => trim($_POST['db_puerto']  ?? '3307'),
        'nombre_bd' => trim($_POST['db_nombre']  ?? 'sistema_educativo'),
        'usuario'   => trim($_POST['db_usuario'] ?? 'root'),
        'clave'     => trim($_POST['db_clave']   ?? ''),
    ];

    $resultadoBD = crearBaseDatos(
        $datos['host'], $datos['puerto'],
        $datos['usuario'], $datos['clave'], $datos['nombre_bd']
    );

    if ($resultadoBD['ok']) {
        $exito = generarEnv($datos);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación — EduSystem</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f5f5f5;
               display: flex; justify-content: center; padding: 2rem; }
        .tarjeta { background: white; border-radius: 12px; padding: 2rem;
                   max-width: 600px; width: 100%; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        h1 { font-size: 1.5rem; margin-bottom: .5rem; }
        h2 { font-size: 1.1rem; margin: 1.5rem 0 .75rem; color: #444; }
        .check { display: flex; align-items: center; gap: .5rem;
                 padding: .4rem 0; border-bottom: 1px solid #f0f0f0; font-size: .9rem; }
        .ok   { color: #1a7f4b; }
        .fail { color: #c0392b; }
        label { display: block; font-size: .875rem; color: #555; margin-bottom: .25rem; margin-top: .75rem; }
        input { width: 100%; padding: .5rem .75rem; border: 1px solid #ddd;
                border-radius: 6px; font-size: .9rem; }
        input:focus { outline: none; border-color: #5b6af0; }
        button { margin-top: 1.25rem; width: 100%; padding: .7rem;
                 background: #5b6af0; color: white; border: none;
                 border-radius: 8px; font-size: 1rem; cursor: pointer; }
        button:disabled { background: #aaa; cursor: not-allowed; }
        .alerta-ok   { background: #e8f8f0; border: 1px solid #a8e6c4;
                       color: #1a7f4b; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .alerta-fail { background: #fef0ee; border: 1px solid #f5c6c0;
                       color: #c0392b; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .aviso { background: #fff8e1; border: 1px solid #ffe082; color: #7d5a00;
                 padding: .75rem; border-radius: 6px; font-size: .85rem; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="tarjeta">
    <h1>🎓 Instalación de EduSystem</h1>
    <p style="color:#666; font-size:.9rem">Asistente de configuración inicial del sistema educativo</p>

    <h2>1. Verificación de requisitos</h2>
    <?php foreach ($requisitos as $req): ?>
    <div class="check">
        <span><?= $req['ok'] ? '✅' : '❌' ?></span>
        <span><?= htmlspecialchars($req['nombre']) ?></span>
        <span style="margin-left:auto; font-size:.8rem; color:#888">
            <?= htmlspecialchars($req['actual']) ?>
        </span>
    </div>
    <?php endforeach; ?>

    <?php if (!$exito): ?>
    <h2>2. Configuración de la base de datos</h2>
    <form method="POST">
        <label>Host</label>
        <input name="db_host" value="localhost">
        <label>Puerto</label>
        <input name="db_puerto" value="3306">
        <label>Nombre de la base de datos</label>
        <input name="db_nombre" value="sistema_educativo">
        <label>Usuario MySQL</label>
        <input name="db_usuario" value="root">
        <label>Contraseña MySQL</label>
        <input type="password" name="db_clave" placeholder="Vacío en XAMPP por defecto">

        <?php if ($resultadoBD && !$resultadoBD['ok']): ?>
        <div class="alerta-fail">
            ❌ <?= htmlspecialchars($resultadoBD['mensaje']) ?>
        </div>
        <?php endif; ?>

        <button type="submit" <?= !$todosOk ? 'disabled' : '' ?>>
            <?= $todosOk ? 'Instalar sistema' : 'Corrige los requisitos primero' ?>
        </button>
    </form>

    <?php else: ?>
    <div class="alerta-ok">
        <strong>✅ Instalación completada correctamente</strong><br>
        La base de datos ha sido creada y el archivo .env generado.
    </div>
    <div class="aviso">
        ⚠️ <strong>Importante:</strong> elimina o renombra el archivo
        <code>publico/instalar.php</code> antes de pasar a producción.
    </div>
    <a href="/sistema-educativo/publico" style="display:block; text-align:center; margin-top:1rem; color:#5b6af0">
        → Ir al sistema
    </a>
    <?php endif; ?>
</div>
</body>
</html>