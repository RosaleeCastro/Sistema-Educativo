<?php // fuente/vistas/profesor/panel.php
// Panel del profesor — HTML base. panel.js rellena #cursos-profesor
// via GET /api/cursos/profesor
?>
<div class="pagina-titulo flex-entre">
    <div>
        <h1>👨‍🏫 Panel del profesor</h1>
        <p>Bienvenido, <?= htmlspecialchars($usuarioNombre) ?>. Gestiona tus cursos desde aquí.</p>
    </div>
    <a href="/profesor/curso/nuevo" class="btn btn-primario">+ Nuevo curso</a>
</div>

<div class="grid-3" id="cursos-profesor">
    <div class="tarjeta" style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--texto-suave)">
        <div class="spinner spinner-oscuro" style="margin:0 auto 1rem"></div>
        Cargando tus cursos...
    </div>
</div>