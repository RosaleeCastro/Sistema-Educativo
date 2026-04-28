<?php
// publico/diagnostico.php
// ============================================================
// Archivo de diagnóstico del sistema.
// Verifica que todas las capas funcionan correctamente.
//
// IMPORTANTE: Eliminar o proteger este archivo en producción.
// Acceso: http://localhost/sistema-educativo/publico/diagnostico.php
// ============================================================

if (!defined('RAIZ')) {
    define('RAIZ', dirname(__DIR__));
}

// Cargamos constantes y autoloader manualmente
// (sin pasar por index.php para aislar los problemas)
$erroresCriticos = [];

// ── Verificaciones previas ───────────────────────────────────
$checks = [];

// 1. Versión de PHP
$checks['php'] = [
    'nombre'  => 'PHP 8.0 o superior',
    'ok'      => version_compare(PHP_VERSION, '8.0.0', '>='),
    'detalle' => 'Versión actual: ' . PHP_VERSION,
];

// 2. Extensión PDO MySQL
$checks['pdo'] = [
    'nombre'  => 'Extensión PDO MySQL',
    'ok'      => extension_loaded('pdo_mysql'),
    'detalle' => extension_loaded('pdo_mysql') ? 'Instalada correctamente' : 'NO instalada — activa en php.ini',
];

// 3. Archivo .env
$checks['env'] = [
    'nombre'  => 'Archivo .env',
    'ok'      => file_exists(RAIZ . '/.env'),
    'detalle' => file_exists(RAIZ . '/.env')
                 ? 'Encontrado en ' . RAIZ . '/.env'
                 : 'NO encontrado — copia .env.ejemplo como .env',
];

// 4. Autoloader
$checks['autoloader'] = [
    'nombre'  => 'Autoloader PSR-4',
    'ok'      => file_exists(RAIZ . '/autoload.php'),
    'detalle' => file_exists(RAIZ . '/autoload.php') ? 'autoload.php existe' : 'NO encontrado',
];

// 5. Carpetas críticas
$carpetas = ['configuracion', 'fuente/modelos', 'fuente/controladores',
             'fuente/servicios', 'fuente/vistas', 'almacenamiento/subidas',
             'almacenamiento/logs', 'bd'];

foreach ($carpetas as $carpeta) {
    $checks['dir_' . str_replace('/', '_', $carpeta)] = [
        'nombre'  => "Carpeta {$carpeta}/",
        'ok'      => is_dir(RAIZ . '/' . $carpeta),
        'detalle' => is_dir(RAIZ . '/' . $carpeta) ? 'Existe' : 'NO encontrada',
    ];
}

// 6. Archivos clave
$archivos = [
    'configuracion/constantes.php'       => 'Constantes del sistema',
    'configuracion/BaseDatos.php'        => 'Clase BaseDatos',
    'configuracion/Enrutador.php'        => 'Clase Enrutador',
    'configuracion/GestorSesion.php'     => 'Clase GestorSesion',
    'configuracion/Entorno.php'          => 'Clase Entorno',
    'fuente/modelos/ModeloBase.php'      => 'Modelo Base',
    'fuente/modelos/Usuario.php'         => 'Modelo Usuario',
    'fuente/modelos/Curso.php'           => 'Modelo Curso',
    'fuente/modelos/Unidad.php'          => 'Modelo Unidad',
    'fuente/modelos/Asistencia.php'      => 'Modelo Asistencia',
    'fuente/modelos/Recurso.php'         => 'Modelo Recurso',
    'fuente/modelos/Notificacion.php'    => 'Modelo Notificacion',
    'fuente/controladores/ControladorBase.php' => 'Controlador Base',
    'fuente/servicios/ServicioLog.php'   => 'Servicio Log',
    'publico/index.php'                  => 'Punto de entrada',
    'bd/esquema.sql'                     => 'Script SQL esquema',
    'bd/semilla.sql'                     => 'Script SQL semilla',
];

foreach ($archivos as $ruta => $nombre) {
    $checks['file_' . md5($ruta)] = [
        'nombre'  => $nombre,
        'ok'      => file_exists(RAIZ . '/' . $ruta),
        'detalle' => file_exists(RAIZ . '/' . $ruta)
                     ? $ruta
                     : "NO encontrado: {$ruta}",
    ];
}

// ── Cargar sistema para pruebas más profundas ────────────────
$sistemaOk = false;
$bdOk      = false;
$modelosOk = [];
$datosDB   = [];

if (file_exists(RAIZ . '/configuracion/constantes.php') &&
    file_exists(RAIZ . '/autoload.php') &&
    file_exists(RAIZ . '/.env')) {

    require_once RAIZ . '/configuracion/constantes.php';
    require_once RAIZ . '/autoload.php';

    try {
        App\Configuracion\Entorno::inicializar();
        $sistemaOk = true;
    } catch (Throwable $e) {
        $erroresCriticos[] = 'Entorno: ' . $e->getMessage();
    }

    // 7. Conexión a la base de datos
    if ($sistemaOk) {
        try {
            $pdo = App\Configuracion\BaseDatos::obtenerConexion();

            // Verificamos que la BD existe y tiene las tablas
            $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            $tablasEsperadas = [
                'usuarios', 'programas', 'categorias', 'cursos',
                'cursos_categorias', 'inscripciones', 'unidades',
                'asistencias', 'recursos_documentales', 'notificaciones',
                'intentos_login', 'registros_actividad', 'consultas_ia',
            ];

            $checks['bd_conexion'] = [
                'nombre'  => 'Conexión a MySQL',
                'ok'      => true,
                'detalle' => 'Conectado a: ' . ($_ENV['DB_NOMBRE'] ?? 'sistema_educativo'),
            ];

            foreach ($tablasEsperadas as $tabla) {
                $existe = in_array($tabla, $tablas);
                $checks['tabla_' . $tabla] = [
                    'nombre'  => "Tabla {$tabla}",
                    'ok'      => $existe,
                    'detalle' => $existe ? 'Existe' : 'NO encontrada — ejecuta esquema.sql',
                ];
            }

            $bdOk = true;

            // Contamos registros de las tablas principales
            foreach (['usuarios', 'programas', 'cursos', 'unidades', 'asistencias'] as $t) {
                if (in_array($t, $tablas)) {
                    $count = $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
                    $datosDB[$t] = (int)$count;
                }
            }

        } catch (Throwable $e) {
            $checks['bd_conexion'] = [
                'nombre'  => 'Conexión a MySQL',
                'ok'      => false,
                'detalle' => $e->getMessage(),
            ];
        }
    }

    // 8. Prueba de modelos
    if ($bdOk) {
        $pruebasModelos = [
            'Usuario'      => fn() => (new App\Modelos\Usuario())->contar(),
            'Curso'        => fn() => (new App\Modelos\Curso())->contar(),
            'Unidad'       => fn() => (new App\Modelos\Unidad())->contar(),
            'Asistencia'   => fn() => (new App\Modelos\Asistencia())->contar(),
            'Recurso'      => fn() => (new App\Modelos\Recurso())->contar(),
            'Notificacion' => fn() => (new App\Modelos\Notificacion())->contar(),
        ];

        foreach ($pruebasModelos as $nombre => $prueba) {
            try {
                $resultado = $prueba();
                $modelosOk[$nombre] = ['ok' => true, 'registros' => $resultado];
            } catch (Throwable $e) {
                $modelosOk[$nombre] = ['ok' => false, 'error' => $e->getMessage()];
            }
        }
    }
}

// ── Calcular resumen ─────────────────────────────────────────
$totalChecks = count($checks);
$checksOk    = count(array_filter($checks, fn($c) => $c['ok']));
$porcentaje  = $totalChecks > 0 ? round(($checksOk / $totalChecks) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico — EduSystem</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f5f5f5;
            color: #1a1a1a;
            padding: 2rem;
            font-size: 14px;
        }
        .contenedor { max-width: 900px; margin: 0 auto; }
        h1 { font-size: 1.4rem; font-weight: 600; margin-bottom: .25rem; }
        .subtitulo { color: #666; font-size: .875rem; margin-bottom: 1.5rem; }

        /* Tarjetas resumen */
        .resumen {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: .75rem;
            margin-bottom: 1.5rem;
        }
        .tarjeta {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid #e5e5e5;
            text-align: center;
        }
        .tarjeta .numero { font-size: 2rem; font-weight: 600; line-height: 1.1; }
        .tarjeta .etiqueta { font-size: .75rem; color: #666; margin-top: .25rem; }
        .verde { color: #1a7f4b; }
        .rojo  { color: #c0392b; }
        .azul  { color: #1565c0; }

        /* Barra de progreso */
        .progreso-wrap {
            background: white;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e5e5;
        }
        .progreso-label {
            display: flex;
            justify-content: space-between;
            font-size: .8rem;
            color: #666;
            margin-bottom: .5rem;
        }
        .barra-bg {
            background: #f0f0f0;
            border-radius: 8px;
            height: 12px;
            overflow: hidden;
        }
    .barra-fill {
    height: 12px;
    border-radius: 8px;
    transition: width .5s ease;
}

        /* Secciones */
        .seccion {
            background: white;
            border-radius: 10px;
            border: 1px solid #e5e5e5;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .seccion-titulo {
            padding: .75rem 1rem;
            font-weight: 600;
            font-size: .875rem;
            background: #f8f8f8;
            border-bottom: 1px solid #e5e5e5;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .seccion-titulo:hover { background: #f0f0f0; }
        .seccion-cuerpo { padding: .5rem 0; }

        /* Filas de verificación */
        .fila {
            display: grid;
            grid-template-columns: 28px 1fr 2fr;
            gap: .5rem;
            align-items: center;
            padding: .4rem 1rem;
            border-bottom: 1px solid #f5f5f5;
            font-size: .8125rem;
        }
        .fila:last-child { border-bottom: none; }
        .icono { font-size: 1rem; text-align: center; }
        .fila-nombre { font-weight: 500; }
        .fila-detalle { color: #666; font-size: .75rem; font-family: monospace; }
        .fila.fila-error { background: #fef9f9; }
        .fila.fila-ok    { background: white; }

        /* Modelos */
        .modelos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: .75rem;
            padding: 1rem;
        }
        .modelo-card {
            border-radius: 8px;
            padding: .75rem;
            text-align: center;
            border: 1px solid #e5e5e5;
        }
        .modelo-card.ok   { background: #f0faf5; border-color: #a8e6c4; }
        .modelo-card.fail { background: #fef9f9; border-color: #f5c6c0; }
        .modelo-nombre    { font-weight: 600; font-size: .8rem; margin-bottom: .25rem; }
        .modelo-dato      { font-size: .75rem; color: #666; }
        .modelo-dato.ok-txt  { color: #1a7f4b; }
        .modelo-dato.err-txt { color: #c0392b; font-family: monospace; }

        /* Tabla BD */
        .datos-tabla {
            width: 100%;
            padding: 1rem;
        }
        .datos-tabla table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8125rem;
        }
        .datos-tabla th {
            text-align: left;
            padding: .4rem .75rem;
            background: #f8f8f8;
            color: #666;
            font-weight: 500;
            font-size: .75rem;
            border-bottom: 1px solid #e5e5e5;
        }
        .datos-tabla td {
            padding: .4rem .75rem;
            border-bottom: 1px solid #f5f5f5;
        }
        .datos-tabla tr:last-child td { border-bottom: none; }
        .numero-registros {
            font-weight: 600;
            color: <?= $bdOk ? '#1a7f4b' : '#c0392b' ?>;
        }

        /* Errores críticos */
        .errores-criticos {
            background: #fef9f9;
            border: 1px solid #f5c6c0;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }
        .errores-criticos h2 { color: #c0392b; font-size: .875rem; margin-bottom: .5rem; }
        .error-item { font-family: monospace; font-size: .75rem; color: #c0392b; padding: .25rem 0; }

        /* Aviso producción */
        .aviso-prod {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .8rem;
            color: #7d5a00;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
<div class="contenedor">

    <h1>🔍 Diagnóstico — EduSystem</h1>
    <p class="subtitulo">
        Verificación del sistema al completo · Fases 0–3 ·
        <?= date('d/m/Y H:i:s') ?>
    </p>

    <?php if (!empty($erroresCriticos)): ?>
    <div class="errores-criticos">
        <h2>⛔ Errores críticos detectados</h2>
        <?php foreach ($erroresCriticos as $error): ?>
            <div class="error-item">→ <?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Resumen numérico -->
    <div class="resumen">
        <div class="tarjeta">
            <div class="numero <?= $checksOk === $totalChecks ? 'verde' : 'rojo' ?>">
                <?= $checksOk ?>/<?= $totalChecks ?>
            </div>
            <div class="etiqueta">Verificaciones superadas</div>
        </div>
        <div class="tarjeta">
            <div class="numero <?= $porcentaje >= 90 ? 'verde' : ($porcentaje >= 70 ? '' : 'rojo') ?>">
                <?= $porcentaje ?>%
            </div>
            <div class="etiqueta">Estado general</div>
        </div>
        <div class="tarjeta">
            <div class="numero <?= $bdOk ? 'verde' : 'rojo' ?>">
                <?= $bdOk ? 'OK' : 'FALLO' ?>
            </div>
            <div class="etiqueta">Base de datos</div>
        </div>
        <div class="tarjeta">
            <div class="numero <?= $sistemaOk ? 'verde' : 'rojo' ?>">
                <?= $sistemaOk ? 'OK' : 'FALLO' ?>
            </div>
            <div class="etiqueta">Motor PHP</div>
        </div>
    </div>

    <!-- Barra de progreso -->
    <div class="progreso-wrap">
        <div class="progreso-label">
            <span>Progreso de verificación</span>
            <span><?= $checksOk ?> de <?= $totalChecks ?> checks correctos</span>
        </div>
        <div class="barra-bg">
            <div class="barra-fill" style="width:<?= $porcentaje ?>%; background:<?= $porcentaje >= 90 ? '#1a7f4b' : ($porcentaje >= 70 ? '#e67e22' : '#c0392b') ?>;"></div>
        </div>
    </div>

    <!-- Modelos -->
    <?php if (!empty($modelosOk)): ?>
    <div class="seccion">
        <div class="seccion-titulo">
            📦 Modelos — prueba de conexión con la BD
            <span><?= count(array_filter($modelosOk, fn($m) => $m['ok'])) ?>/<?= count($modelosOk) ?> OK</span>
        </div>
        <div class="modelos-grid">
            <?php foreach ($modelosOk as $nombre => $resultado): ?>
            <div class="modelo-card <?= $resultado['ok'] ? 'ok' : 'fail' ?>">
                <div class="modelo-nombre">
                    <?= $resultado['ok'] ? '✅' : '❌' ?> <?= $nombre ?>
                </div>
                <?php if ($resultado['ok']): ?>
                    <div class="modelo-dato ok-txt"><?= $resultado['registros'] ?> registros</div>
                <?php else: ?>
                    <div class="modelo-dato err-txt"><?= htmlspecialchars(substr($resultado['error'], 0, 60)) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Datos en BD -->
    <?php if (!empty($datosDB)): ?>
    <div class="seccion">
        <div class="seccion-titulo">🗄️ Datos en base de datos</div>
        <div class="datos-tabla">
            <table>
                <thead>
                    <tr>
                        <th>Tabla</th>
                        <th>Registros</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datosDB as $tabla => $count): ?>
                    <tr>
                        <td><?= $tabla ?></td>
                        <td class="numero-registros"><?= $count ?></td>
                        <td><?= $count > 0 ? '✅ Con datos' : '⚠️ Vacía — ejecuta semilla.sql' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Verificaciones agrupadas -->
    <?php
    $grupos = [
        'Entorno y archivos base'  => array_filter($checks, fn($k) => in_array($k, ['php','pdo','env','autoloader']), ARRAY_FILTER_USE_KEY),
        'Carpetas del proyecto'    => array_filter($checks, fn($k) => str_starts_with($k, 'dir_'), ARRAY_FILTER_USE_KEY),
        'Archivos PHP del sistema' => array_filter($checks, fn($k) => str_starts_with($k, 'file_'), ARRAY_FILTER_USE_KEY),
        'Base de datos y tablas'   => array_filter($checks, fn($k) => str_starts_with($k, 'bd_') || str_starts_with($k, 'tabla_'), ARRAY_FILTER_USE_KEY),
    ];
    ?>

    <?php foreach ($grupos as $titulo => $items): ?>
    <?php if (empty($items)) continue; ?>
    <div class="seccion">
        <div class="seccion-titulo">
            <?= $titulo ?>
            <span>
                <?= count(array_filter($items, fn($c) => $c['ok'])) ?>/<?= count($items) ?> OK
            </span>
        </div>
        <div class="seccion-cuerpo">
            <?php foreach ($items as $check): ?>
            <div class="fila <?= $check['ok'] ? 'fila-ok' : 'fila-error' ?>">
                <div class="icono"><?= $check['ok'] ? '✅' : '❌' ?></div>
                <div class="fila-nombre"><?= htmlspecialchars($check['nombre']) ?></div>
                <div class="fila-detalle"><?= htmlspecialchars($check['detalle']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="aviso-prod">
        ⚠️ <strong>Recuerda:</strong> elimina o renombra este archivo
        antes de pasar a producción.
        <code>publico/diagnostico.php</code>
    </div>

</div>
</body>
</html>