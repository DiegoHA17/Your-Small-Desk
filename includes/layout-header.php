<?php
$tituloPagina = $tituloPagina ?? 'Your Small Desk';
$appUrlPublica = rtrim((string) (getenv('APP_URL') ?: ''), '/');
$imagenSocial = $appUrlPublica !== '' ? $appUrlPublica . '/assets/img/og-image.png' : 'assets/img/og-image.png';
$nombreEmpresaLayout = 'Your Small Desk';
if (function_exists('obtenerConfiguracion')) {
    $configLayout = obtenerConfiguracion();
    $nombreEmpresaLayout = trim((string) ($configLayout['nombre_comercial'] ?: ($configLayout['nombre_empresa'] ?? ''))) ?: 'Your Small Desk';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?> | Your Small Desk</title>
    <meta name="theme-color" content="#174D2A">
    <meta name="application-name" content="Your Small Desk">
    <meta name="apple-mobile-web-app-title" content="Your Small Desk">
    <meta property="og:title" content="Your Small Desk">
    <meta property="og:description" content="Gestion de presupuestos">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?php echo htmlspecialchars($imagenSocial, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Your Small Desk">
    <meta name="twitter:description" content="Gestion de presupuestos">
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
<body>
<div class="app-shell">
    <?php require __DIR__ . '/layout-sidebar.php'; ?>
    <main class="app-main">
        <header class="topbar">
            <button class="btn btn-outline-primary topbar-menu d-lg-none" id="btnSidebar" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMovil" aria-controls="sidebarMovil" aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-mobile-brand d-lg-none">
                <img src="assets/img/logo-jjh.png" alt="" class="brand-logo">
                <span>Your Small Desk</span>
            </div>
            <div class="topbar-page-title">
                <h1 class="h5 mb-0"><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></h1>
                <small class="text-muted"><?php echo htmlspecialchars($nombreEmpresaLayout, ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <div class="ms-auto text-end small topbar-user">
                <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </header>
        <section class="content-wrap">
