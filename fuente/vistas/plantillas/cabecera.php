<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'EduSystem') ?></title>

    <!-- CSS externos — variables primero, luego base y layout -->
    <link rel="stylesheet" href="/sistema-educativo/publico/estilos/variables.css">
    <link rel="stylesheet" href="/sistema-educativo/publico/estilos/base.css">
    <link rel="stylesheet" href="/sistema-educativo/publico/estilos/layout.css">
    <?php if (isset($cssExtra)) echo $cssExtra; ?>

    <!-- Token CSRF en meta tag — api.js lo lee para proteger peticiones POST -->
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
</head>

<!-- data-rol permite que panel.js sepa qué datos cargar según el rol -->
<body data-rol="<?= htmlspecialchars($usuarioRol ?? '') ?>">

<!-- ── Navbar ─────────────────────────────────────────────── -->
<nav class="navbar">
    <a href="/" class="navbar-logo">
        <span class="icono">🎓</span> EduSystem
    </a>
    <div class="navbar-espaciador"></div>

    <!-- Notificaciones — notificaciones.js actualiza el badge -->
    <button class="notif-btn" id="btn-notif" title="Notificaciones">
        🔔
        <span class="notif-badge" id="badge-notif">0</span>
    </button>

    <!-- Info del usuario logueado -->
    <div class="usuario-info">
        <div class="avatar">
            <?= strtoupper(substr($usuarioNombre ?? 'U', 0, 1)) ?>
        </div>
        <div>
            <div class="usuario-nombre"><?= htmlspecialchars($usuarioNombre ?? '') ?></div>
            <div class="usuario-rol"><?= htmlspecialchars(ucfirst($usuarioRol ?? '')) ?></div>
        </div>
    </div>

    <a href="/salir" class="btn-salir">↩ Salir</a>
</nav>

<!-- ── Sidebar según rol ──────────────────────────────────── -->
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
        <div class="sidebar-seccion-titulo">Gestión</div>
        <a href="/profesor/panel"  class="sidebar-link <?= $activo('/profesor/panel') ?>">
            <span class="icono">🏠</span> Mi panel</a>
        <a href="/profesor/cursos" class="sidebar-link <?= $activo('/profesor/cursos') ?>">
            <span class="icono">📚</span> Mis cursos</a>
    </div>

    <?php elseif ($usuarioRol === ROL_ADMIN): ?>
    <div class="sidebar-seccion">
        <div class="sidebar-seccion-titulo">Administración</div>
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

<!-- ── Contenido principal ───────────────────────────────── -->
<main class="contenido">

    <!-- Mensajes flash — app.js los hace desaparecer a los 4 segundos -->
    <?php if (!empty($flashExito)): ?>
    <div class="flash flash-exito">✅ <?= htmlspecialchars($flashExito) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
    <div class="flash flash-error">⚠️ <?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashInfo)): ?>
    <div class="flash flash-info">ℹ️ <?= htmlspecialchars($flashInfo) ?></div>
    <?php endif; ?>

<!-- El contenido de cada vista se inserta aquí -->