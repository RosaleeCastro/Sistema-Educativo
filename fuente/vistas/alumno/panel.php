<?php // fuente/vistas/alumno/panel.php
// Panel del alumno — HTML base. panel.js rellena #cursos-alumno
// via GET /api/cursos/alumno
?>
<div class="pagina-titulo flex-entre">
    <div>
        <h1>👨‍🎓 Mi panel de aprendizaje</h1>
        <p>Bienvenido, <?= htmlspecialchars($usuarioNombre) ?>. Aquí están tus cursos activos.</p>
    </div>
</div>

<div class="grid-3" id="cursos-alumno">
    <div class="tarjeta" style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--texto-suave)">
        <div class="spinner spinner-oscuro" style="margin:0 auto 1rem"></div>
        Cargando tus cursos...
    </div>
</div>