<?php 
// fuente/vistas/profesor/asistencia.php
// Vista del profesor para gestionar la asistencia de una unidad.
// asistencia.js hace polling cada 10 segundos y actualiza la tabla
// sin recargar la pagina.
?>

<div class="pagina-titulo flex-entre">
    <div>
        <a href="/profesor/panel" style="font-size:.875rem;color:var(--texto-suave);
           text-decoration:none;display:inline-flex;align-items:center;gap:.25rem;margin-bottom:.5rem">
            ← Volver al panel
        </a>
        <h1>📋 Asistencia — <?= htmlspecialchars($unidad['titulo'] ?? '') ?></h1>
        <p>Los datos se actualizan automaticamente cada 10 segundos.</p>
    </div>
    <!-- Boton exportar CSV — asistencia.js lo gestiona -->
    <button id="btn-exportar-csv" class="btn btn-secundario">
        📥 Exportar CSV
    </button>
</div>

<!-- Contadores en tiempo real — asistencia.js los actualiza -->
<div class="grid-4 mb-3">
    <div class="tarjeta texto-centro">
        <div style="font-size:1.75rem;font-weight:700;color:var(--exito)"
             id="contador-presentes">
            <?= $resumen['presentes'] ?? 0 ?>
        </div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem">
            ✅ Presentes
        </div>
    </div>
    <div class="tarjeta texto-centro">
        <div style="font-size:1.75rem;font-weight:700;color:var(--aviso)"
             id="contador-tardanzas">
            <?= $resumen['tardanzas'] ?? 0 ?>
        </div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem">
            ⏰ Tardanzas
        </div>
    </div>
    <div class="tarjeta texto-centro">
        <div style="font-size:1.75rem;font-weight:700;color:var(--error)"
             id="contador-ausentes">
            <?= $resumen['ausentes'] ?? 0 ?>
        </div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem">
            ❌ Ausentes
        </div>
    </div>
    <div class="tarjeta texto-centro">
        <div style="font-size:1.75rem;font-weight:700;color:var(--primario)"
             id="contador-total">
            <?= count($alumnos) ?>
        </div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem">
            👥 Total alumnos
        </div>
    </div>
</div>

<!-- Tabla de asistencia -->
<!-- data-unidad le dice a asistencia.js que unidad monitorizar -->
<div class="tarjeta">
    <div class="tarjeta-titulo flex-entre">
        <span>Registro de asistencia</span>
        <span style="font-size:.75rem;color:var(--texto-suave)" id="ultima-actualizacion">
            Actualizando...
        </span>
    </div>

    <div class="tabla-wrap">
        <table class="tabla" id="tabla-asistencia"
               data-unidad="<?= $unidadId ?>">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($asistencias)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;
                        color:var(--texto-suave);padding:2rem">
                        Ningún alumno ha marcado asistencia aún.
                        Los datos se actualizarán automáticamente.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($asistencias as $a): ?>
                <?php
                    $badgeClase = match($a['estado']) {
                        'presente' => 'badge-exito',
                        'tardanza' => 'badge-aviso',
                        'ausente'  => 'badge-error',
                        default    => 'badge-gris',
                    };
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($a['alumno_apellidos']) ?>,
                        <?= htmlspecialchars($a['alumno_nombre']) ?>
                    </td>
                    <td style="font-size:.8rem;color:var(--texto-suave)">
                        <?= htmlspecialchars($a['alumno_correo']) ?>
                    </td>
                    <td>
                        <span class="badge <?= $badgeClase ?>">
                            <?= ucfirst($a['estado']) ?>
                        </span>
                    </td>
                    <td style="font-size:.75rem;color:var(--texto-suave)">
                        <?= date('H:i', strtotime($a['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script para mostrar hora de ultima actualizacion -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('ultima-actualizacion');
    if (!el) return;

    // Actualizar el texto de ultima actualizacion cada vez que
    // asistencia.js hace polling (cada 10 segundos)
    const actualizarHora = () => {
        const ahora = new Date();
        el.textContent = 'Ultima actualizacion: ' +
            ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    };

    actualizarHora();
    setInterval(actualizarHora, 10000);
});
</script>