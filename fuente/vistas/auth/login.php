<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — EduSystem</title>
    <?php $base = rtrim($_ENV['APP_URL'] ?? '/sistema-educativo/publico', '/'); ?>
    <?php echo "<!-- BASE: " . $base . " -->"; ?>
    <link rel="stylesheet" href="<?= $base ?>/estilos/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/estilos/base.css">
    <link rel="stylesheet" href="<?= $base ?>/estilos/login.css">
</head>
<body class="pagina-login">

<div class="login-tarjeta">

    <!-- Cabecera -->
    <div class="login-cabecera">
        <div class="logo">🎓</div>
        <h1>EduSystem</h1>
        <p>Introduce tus credenciales para continuar</p>
    </div>

    <!-- Alertas flash -->
     
    <?php if (!empty($error)): ?>
    <div class="alerta alerta-error">
        <span>⚠️</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
    <div class="alerta alerta-info">
        <span>✅</span>
        <span><?= htmlspecialchars($info) ?></span>
    </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form method="POST" action="" id="form-login" novalidate>
        <!-- Token CSRF oculto — protección contra ataques -->
        <input type="hidden" 
       name="csrf_token" 
       value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? $token ?? '') ?>">

        <div class="campo">
            <label for="correo">Correo electrónico</label>
            <input
                class="input"
                type="email"
                id="correo"
                name="correo"
                placeholder="tu@correo.com"
                autocomplete="email"
                required
            >
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input
                class="input"
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
            >
        </div>

        <button type="submit" class="btn btn-primario btn-bloque btn-lg mt-2" id="btn-submit">
            <span id="btn-texto">Iniciar sesión</span>
            <span class="spinner oculto" id="spinner"></span>
        </button>
    </form>

    <!-- Cuentas de demo para el TFG -->
    <button class="login-demo-btn" onclick="rellenar('admin@edusystem.com')">
        <span class="demo-rol">👑 Admin</span>
        <span class="demo-correo">admin@edusystem.com</span>
    </button>
    <button class="login-demo-btn" onclick="rellenar('laura.martinez@edusystem.com')">
        <span class="demo-rol">👨‍🏫 Profesor</span>
        <span class="demo-correo">laura.martinez</span>
    </button>
    <button class="login-demo-btn" onclick="rellenar('maria.garcia@edusystem.com')">
        <span class="demo-rol">👨‍🎓 Alumno</span>
        <span class="demo-correo">maria.garcia</span>
    </button>

    <div class="pie">EduSystem v1.0 · TFG DAW</div>
</div>

<script>
// Rellena el formulario con una cuenta de demo
// La contraseña de demo es siempre: password123
function rellenar(correo) {
    document.getElementById('correo').value   = correo;
    document.getElementById('password').value = 'password123';
    document.getElementById('correo').focus();
}

// Muestra spinner al enviar el formulario
document.getElementById('form-login').addEventListener('submit', function(e) {
    const correo   = document.getElementById('correo').value.trim();
    const password = document.getElementById('password').value.trim();

    if (!correo || !password) {
        e.preventDefault();
        return;
    }

    document.getElementById('btn-texto').style.display = 'none';
    document.getElementById('spinner').style.display   = 'block';
    document.getElementById('btn-submit').disabled     = true;
});
</script>

</body>
</html>