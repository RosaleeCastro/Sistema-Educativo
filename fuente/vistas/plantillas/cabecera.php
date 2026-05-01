<?php
// fuente/vistas/plantillas/cabecera.php
// Se carga automaticamente en cada vista autenticada via ControladorBase::vista()
// Contiene: HTML head, CSS, navbar, sidebar segun rol y apertura del main
$urlBase = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sistema-educativo/publico', '/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'EduSystem') ?></title>
    <link rel="stylesheet" href="<?= $urlBase ?>/estilos/variables.css">
    <link rel="stylesheet" href="<?= $urlBase ?>/estilos/base.css">
    <link rel="stylesheet" href="<?= $urlBase ?>/estilos/layout.css">
    <?php if (isset($cssExtra)) echo $cssExtra; ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <meta name="app-url"    content="<?= $urlBase ?>">
</head>
<body data-rol="<?= htmlspecialchars($usuarioRol ?? '') ?>">

<nav class="navbar">
    <a href="/" class="navbar-logo">
        <span class="icono">🎓</span> EduSystem
    </a>
    <div class="navbar-espaciador"></div>
    <button class="notif-btn" id="btn-notif" title="Notificaciones">
        🔔
        <span class="notif-badge" id="badge-notif">0</span>
    </button>
    <div class="usuario-info">
        <div class="avatar">
            <?= strtoupper(substr($usuarioNombre ?? 'U', 0, 1)) ?>
        </div>
        <div>
            <div class="usuario-nombre"><?= htmlspecialchars($usuarioNombre ?? '') ?></div>
            <div class="usuario-rol"><?= htmlspecialchars(ucfirst($usuarioRol ?? '')) ?></div>
        </div>
    </div>
    <a href="/salir" class="btn-salir">Salir</a>
</nav>

<aside class="sidebar" id="sidebar">
    <?php
    $urlActual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $activo    = fn($r) => str_starts_with($urlActual, $r) ? 'activo' : '';
    ?>

    <?php if ($usuarioRol === ROL_ALUMNO): ?>
    <div class="sidebar-seccion">
        <div class="sidebar-seccion-titulo">Mi aprendizaje</div>
        <a href="/alumno/panel"           class="sidebar-link <?= $activo('/alumno/panel') ?>">
            <span class="icono">🏠</span> Mi panel</a>
        <a href="/alumno/mis-asistencias" class="sidebar-link <?= $activo('/alumno/mis-asistencias') ?>">
            <span class="icono">📋</span> Mis asistencias</a>
        <a href="/alumno/asistente"       class="sidebar-link <?= $activo('/alumno/asistente') ?>">
            <span class="icono">🤖</span> Asistente IA</a>
    </div>

    <?php elseif ($usuarioRol === ROL_PROFESOR): ?>
    <div class="sidebar-seccion">
        <div class="sidebar-seccion-titulo">Gestion</div>
        <a href="/profesor/panel"  class="sidebar-link <?= $activo('/profesor/panel') ?>">
            <span class="icono">🏠</span> Mi panel</a>
        <a href="/profesor/cursos" class="sidebar-link <?= $activo('/profesor/cursos') ?>">
            <span class="icono">📚</span> Mis cursos</a>
    </div>

    <?php elseif ($usuarioRol === ROL_ADMIN): ?>
    <div class="sidebar-seccion">
        <div class="sidebar-seccion-titulo">Administracion</div>
        <a href="/admin/panel"     class="sidebar-link <?= $activo('/admin/panel') ?>">
            <span class="icono">🏠</span> Panel</a>
        <a href="/admin/usuarios"  class="sidebar-link <?= $activo('/admin/usuarios') ?>">
            <span class="icono">👥</span> Usuarios</a>
        <a href="/admin/programas" class="sidebar-link <?= $activo('/admin/programas') ?>">
            <span class="icono">🎓</span> Programas</a>
        <a href="/admin/logs"      class="sidebar-link <?= $activo('/admin/logs') ?>">
            <span class="icono">📝</span> Logs</a>
    </div>
    <?php endif; ?>
</aside>

<main class="contenido">

<?php if (!empty($flashExito)): ?>
<div class="flash flash-exito">✅ <?= htmlspecialchars($flashExito) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
<div class="flash flash-error">⚠️ <?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>
<?php if (!empty($flashInfo)): ?>
<div class="flash flash-info">ℹ️ <?= htmlspecialchars($flashInfo) ?></div>
<?php endif; ?>