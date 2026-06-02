<?php
require_once __DIR__ . '/includes/seguridad.php';

if (setupCompletado()) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuracion inicial | Your Small Desk</title>
    <meta name="theme-color" content="#174D2A">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16x16.png">
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="setup-page">
    <main class="setup-shell">
        <div class="setup-card card card-soft">
            <div class="card-body p-4 p-md-5">
                <div class="setup-head mb-4">
                    <div>
                        <p class="setup-eyebrow mb-1">Primera configuracion</p>
                        <h1 class="h3 mb-2">Configuracion inicial</h1>
                        <p class="text-muted mb-0">Personaliza Your Small Desk para tu empresa: datos, logo del documento y usuario administrador.</p>
                    </div>
                    <img src="assets/img/logo-jjh.png" alt="" class="setup-logo">
                </div>

                <form id="formSetup" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="guardar_setup_inicial">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(obtenerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">

                    <section class="setup-step">
                        <div class="setup-step-title">
                            <span>1</span>
                            <div>
                                <h2>Datos de empresa</h2>
                                <p>Se mostraran en presupuestos y PDFs.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nombre comercial</label><input class="form-control" name="nombre_comercial" required></div>
                            <div class="col-md-6"><label class="form-label">Nombre fiscal / razon social</label><input class="form-control" name="nombre_fiscal"></div>
                            <div class="col-md-4"><label class="form-label">NIF/CIF</label><input class="form-control" name="nif_cif_empresa"></div>
                            <div class="col-md-4"><label class="form-label">Email empresa</label><input class="form-control" type="email" name="email_empresa"></div>
                            <div class="col-md-4"><label class="form-label">Telefono</label><input class="form-control" name="telefono_empresa"></div>
                            <div class="col-12"><label class="form-label">Direccion</label><input class="form-control" name="direccion_empresa"></div>
                            <div class="col-md-3"><label class="form-label">Ciudad</label><input class="form-control" name="ciudad_empresa"></div>
                            <div class="col-md-3"><label class="form-label">Provincia</label><input class="form-control" name="provincia_empresa"></div>
                            <div class="col-md-3"><label class="form-label">Codigo postal</label><input class="form-control" name="codigo_postal_empresa"></div>
                            <div class="col-md-3"><label class="form-label">Pais</label><input class="form-control" name="pais_empresa"></div>
                            <div class="col-12"><label class="form-label">Web opcional</label><input class="form-control" type="url" name="web_empresa" placeholder="https://..."></div>
                        </div>
                    </section>

                    <section class="setup-step">
                        <div class="setup-step-title">
                            <span>2</span>
                            <div>
                                <h2>Logo del documento</h2>
                                <p>Se usara solo en presupuestos y documentos PDF.</p>
                            </div>
                        </div>
                        <input class="form-control" type="file" name="logo_documento" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                        <div class="form-text">PNG, JPG, JPEG o WEBP. Maximo 2 MB.</div>
                    </section>

                    <section class="setup-step">
                        <div class="setup-step-title">
                            <span>3</span>
                            <div>
                                <h2>Usuario administrador</h2>
                                <p>Con este correo entraras a Your Small Desk.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="admin_nombre" required></div>
                            <div class="col-md-6"><label class="form-label">Email de acceso</label><input class="form-control" type="email" name="admin_email" required></div>
                            <div class="col-md-6"><label class="form-label">Contrasena</label><input class="form-control" type="password" name="admin_password" minlength="8" required></div>
                            <div class="col-md-6"><label class="form-label">Repetir contrasena</label><input class="form-control" type="password" name="admin_password_repetir" minlength="8" required></div>
                        </div>
                    </section>

                    <section class="setup-step">
                        <div class="setup-step-title">
                            <span>4</span>
                            <div>
                                <h2>Confirmacion</h2>
                                <p>Al finalizar se activara el login normal.</p>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none" id="setupError"></div>
                        <div class="alert alert-success d-none" id="setupOk"></div>
                        <button class="btn btn-primary btn-lg" type="submit" id="btnSetup">
                            <i class="bi bi-check2-circle"></i> Completar configuracion
                        </button>
                    </section>
                </form>
            </div>
        </div>
        <footer class="setup-copy">Your Small Desk &middot; Desarrollado por Diego Herrera Ayuso</footer>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/setup.js"></script>
</body>
</html>
