<?php
require_once __DIR__ . '/includes/seguridad.php';

if (!empty($_SESSION['id_usuario'])) {
    header('Location: inicio.php');
    exit;
}
$appUrlPublica = rtrim((string) (getenv('APP_URL') ?: ''), '/');
$imagenSocial = $appUrlPublica !== '' ? $appUrlPublica . '/assets/img/og-image.png' : 'assets/img/og-image.png';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar | JJH Space</title>
    <meta name="theme-color" content="#174D2A">
    <meta name="application-name" content="JJH Space">
    <meta name="apple-mobile-web-app-title" content="JJH Space">
    <meta property="og:title" content="JJH Space">
    <meta property="og:description" content="Gestión de presupuestos de Podas y Talas JJH">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?php echo htmlspecialchars($imagenSocial, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="JJH Space">
    <meta name="twitter:description" content="Gestión de presupuestos de Podas y Talas JJH">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($imagenSocial, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16x16.png">
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
    <link rel="manifest" href="manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="card card-soft login-card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <img src="assets/img/logo-jjh.png" alt="Podas y Talas JJH" class="login-logo mb-3">
                <h1 class="h4 mb-1">JJH Space</h1>
                <div class="text-muted">Podas y Talas JJH</div>
            </div>
            <form id="formLogin">
                <input type="hidden" name="accion" value="login">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(obtenerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Contraseña</label>
                    <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button class="btn btn-primary w-100" type="submit">
                    <i class="bi bi-box-arrow-in-right"></i> Entrar
                </button>
                <div class="alert alert-danger mt-3 d-none" id="loginError"></div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $('#formLogin').on('submit', function (e) {
        e.preventDefault();
        $('#loginError').addClass('d-none').text('');
        $.post('acciones/login-acciones.php', $(this).serialize(), function (res) {
            if (res.ok) {
                window.location.href = 'inicio.php';
                return;
            }
            $('#loginError').removeClass('d-none').text(res.mensaje);
        }, 'json').fail(function () {
            $('#loginError').removeClass('d-none').text('No se pudo iniciar sesion.');
        });
    });
    </script>
</body>
</html>
