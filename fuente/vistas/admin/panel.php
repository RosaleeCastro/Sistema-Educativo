<?php // fuente/vistas/admin/panel.php
// Panel admin — los contadores los rellena panel.js via GET /api/stats/admin
// NO tiene contenedores de cursos — solo stats y accesos rápidos
?>

<div class="pagina-titulo">
    <h1>👑 Panel de Administración</h1>
    <p>Bienvenido, <?= htmlspecialchars($usuarioNombre) ?>. Estado general del sistema.</p>
</div>

<div class="grid-4 mb-3">
    <div class="tarjeta texto-centro">
        <div style="font-size:2rem;font-weight:700;color:var(--primario)" id="stat-alumnos">0</div>
        <div style="font-size:.875rem;color:var(--texto-suave);margin-top:.25rem">👨‍🎓 Alumnos</div>
    </div>
    <div class="tarjeta texto-centro">
        <div style="font-size:2rem;font-weight:700;color:var(--exito)" id="stat-profesores">0</div>
        <div style="font-size:.875rem;color:var(--texto-suave);margin-top:.25rem">👨‍🏫 Profesores</div>
    </div>
    <div class="tarjeta texto-centro">
        <div style="font-size:2rem;font-weight:700;color:var(--aviso)" id="stat-cursos">0</div>
        <div style="font-size:.875rem;color:var(--texto-suave);margin-top:.25rem">📚 Cursos</div>
    </div>
    <div class="tarjeta texto-centro">
        <div style="font-size:2rem;font-weight:700;color:var(--info,#1d4ed8)" id="stat-unidades">0</div>
        <div style="font-size:.875rem;color:var(--texto-suave);margin-top:.25rem">📖 Unidades</div>
    </div>
</div>

<div class="grid-3">
    <a href="/admin/usuarios" class="tarjeta"
       style="text-decoration:none;display:block;transition:box-shadow .15s"
       onmouseover="this.style.boxShadow='var(--sombra)'"
       onmouseout="this.style.boxShadow='var(--sombra-sm)'">
        <div class="tarjeta-titulo">👥 Gestión de usuarios</div>
        <p>Crear y gestionar alumnos, profesores y administradores.</p>
        <div class="mt-2"><span class="badge badge-primario">Ver usuarios →</span></div>
    </a>
    <a href="/admin/programas" class="tarjeta"
       style="text-decoration:none;display:block;transition:box-shadow .15s"
       onmouseover="this.style.boxShadow='var(--sombra)'"
       onmouseout="this.style.boxShadow='var(--sombra-sm)'">
        <div class="tarjeta-titulo">🎓 Programas y ciclos</div>
        <p>Gestionar ciclos formativos y cursos independientes.</p>
        <div class="mt-2"><span class="badge badge-exito">Ver programas →</span></div>
    </a>
    <a href="/admin/logs" class="tarjeta"
       style="text-decoration:none;display:block;transition:box-shadow .15s"
       onmouseover="this.style.boxShadow='var(--sombra)'"
       onmouseout="this.style.boxShadow='var(--sombra-sm)'">
        <div class="tarjeta-titulo">📝 Logs del sistema</div>
        <p>Consultar el historial de actividad y auditoría.</p>
        <div class="mt-2"><span class="badge badge-aviso">Ver logs →</span></div>
    </a>
</div>