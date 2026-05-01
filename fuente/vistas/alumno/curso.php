<?php // fuente/vistas/alumno/curso.php
// Vista del curso con lista de unidades.
// El alumno puede marcar su asistencia directamente desde aqui.
// asistencia.js gestiona el clic sin recargar la pagina.
?>

<!-- Cabecera del curso -->
<div class="pagina-titulo flex-entre">
    <div>
        <a href="/alumno/panel" style="font-size:.875rem;color:var(--texto-suave);
           text-decoration:none;display:inline-flex;align-items:center;gap:.25rem;margin-bottom:.5rem">
            ← Volver a mis cursos
        </a>
        <h1><?= htmlspecialchars($curso['nombre'] ?? '') ?></h1>
        <p>
            👨‍🏫 <?= htmlspecialchars($curso['nombre_profesor'] ?? '') ?>
            <?php if (!empty($curso['nombre_programa'])): ?>
                · 🎓 <?= htmlspecialchars($curso['nombre_programa']) ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Lista de unidades -->
<?php if (empty($unidades)): ?>
<div class="tarjeta texto-centro" style="padding:3rem">
    <div style="font-size:3rem;margin-bottom:1rem">📭</div>
    <h3>No hay unidades publicadas aun</h3>
    <p class="mt-1">El profesor publicara el contenido proximamente.</p>
</div>

<?php else: ?>
<div style="display:flex;flex-direction:column;gap:.75rem">
    <?php foreach ($unidades as $unidad): ?>
    <?php
        $estado       = $unidad['estado_asistencia'] ?? null;
        $tipoIconos   = [
            'video'    => '🎬',
            'lectura'  => '📖',
            'quiz'     => '📝',
            'sincrona' => '📹',
        ];
        $icono = $tipoIconos[$unidad['tipo_recurso']] ?? '📄';
    ?>
    <div class="tarjeta" data-unidad-fila data-estado="<?= htmlspecialchars($estado ?? '') ?>"
         style="display:grid;grid-template-columns:auto 1fr auto;gap:1rem;align-items:center">

        <!-- Icono y numero de orden -->
        <div style="width:44px;height:44px;border-radius:10px;
                    background:var(--primario-lt);color:var(--primario);
                    display:flex;align-items:center;justify-content:center;
                    font-size:1.25rem;flex-shrink:0">
            <?= $icono ?>
        </div>

        <!-- Titulo y descripcion -->
        <div>
            <div style="font-weight:600;font-size:.9375rem;color:var(--texto);margin-bottom:.2rem">
                <?= htmlspecialchars($unidad['titulo']) ?>
            </div>
            <div style="font-size:.8rem;color:var(--texto-suave);display:flex;gap:.75rem;flex-wrap:wrap">
                <span><?= ucfirst($unidad['tipo_recurso']) ?></span>
                <?php if ($unidad['duracion_min']): ?>
                    <span>⏱ <?= $unidad['duracion_min'] ?> min</span>
                <?php endif; ?>
                <?php if ($unidad['total_recursos'] > 0): ?>
                    <span>📎 <?= $unidad['total_recursos'] ?> recursos</span>
                <?php endif; ?>
                <?php if ($unidad['tipo_recurso'] === 'sincrona' && $unidad['fecha_inicio']): ?>
                    <span>📅 <?= date('d/m/Y H:i', strtotime($unidad['fecha_inicio'])) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Boton de asistencia o estado actual -->
        <div style="flex-shrink:0">
            <?php if ($estado): ?>
                <?php
                $badgeConfig = [
                    'presente' => ['clase' => 'badge-exito', 'texto' => '✅ Presente'],
                    'tardanza' => ['clase' => 'badge-aviso', 'texto' => '⏰ Tardanza'],
                    'ausente'  => ['clase' => 'badge-error', 'texto' => '❌ Ausente'],
                ];
                $badge = $badgeConfig[$estado] ?? ['clase' => 'badge-gris', 'texto' => $estado];
                ?>
                <span class="badge <?= $badge['clase'] ?>">
                    <?= $badge['texto'] ?>
                </span>
            <?php else: ?>
                <!-- Boton que asistencia.js intercepta -->
                <button
                    class="btn btn-primario btn-sm"
                    data-accion="marcar-asistencia"
                    data-unidad="<?= $unidad['id'] ?>"
                    data-estado="presente">
                    Marcar asistencia
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>