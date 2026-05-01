</main><!-- fin .contenido -->

<!-- ── Scripts JS — se cargan al final para no bloquear el HTML ── -->
<!-- api.js DEBE ir primero — los demás dependen de él -->
<script src="/sistema-educativo/publico/scripts/api.js"></script>
<script src="/sistema-educativo/publico/scripts/app.js"></script>
<script src="/sistema-educativo/publico/scripts/panel.js"></script>
<script src="/sistema-educativo/publico/scripts/asistencia.js"></script> 
<?php if (isset($scriptsExtra)) echo $scriptsExtra; ?>

</body>
</html>