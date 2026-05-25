<?php
require_once __DIR__ . '/includes/seguridad.php';
require_once __DIR__ . '/includes/funciones.php';
exigirSesion();

$conexion = obtenerConexion();
$configuracionGuardada = obtenerConfiguracionPersistida();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $nombreEmpresa = limpiarCadena($_POST['nombre_empresa'] ?? '');
    $emailEmpresa = limpiarCadena($_POST['email_empresa'] ?? '');
    $telefonoEmpresa = limpiarCadena($_POST['telefono_empresa'] ?? '');
    $direccionEmpresa = limpiarCadena($_POST['direccion_empresa'] ?? '');
    $nifCifEmpresa = limpiarCadena($_POST['nif_cif_empresa'] ?? '');
    $ciudadEmpresa = limpiarCadena($_POST['ciudad_empresa'] ?? '');
    $provinciaEmpresa = limpiarCadena($_POST['provincia_empresa'] ?? '');
    $codigoPostalEmpresa = limpiarCadena($_POST['codigo_postal_empresa'] ?? '');
    $mailrelayApiUrl = limpiarCadena($_POST['mailrelay_api_url'] ?? '');
    $mailrelayApiKeyNueva = trim((string) ($_POST['mailrelay_api_key'] ?? ''));
    // TODO: En produccion, mover la API key a variables de entorno o guardarla cifrada.
    $mailrelayApiKey = $mailrelayApiKeyNueva !== ''
        ? $mailrelayApiKeyNueva
        : (string) ($configuracionGuardada['mailrelay_api_key'] ?? MAILRELAY_API_KEY_DEFAULT);
    $mailrelayFromEmail = limpiarCadena($_POST['mailrelay_from_email'] ?? '');
    $mailrelayFromName = limpiarCadena($_POST['mailrelay_from_name'] ?? '');
    $mailrelayBccEmail = limpiarCadena($_POST['mailrelay_bcc_email'] ?? '');
    $mailrelayBccActivo = isset($_POST['mailrelay_bcc_activo']) ? 1 : 0;
    $mailrelayMetodoEnvio = strtolower(limpiarCadena($_POST['mailrelay_metodo_envio_facturas'] ?? 'smtp'));
    $mailrelaySmtpFallbackActivo = isset($_POST['mailrelay_smtp_fallback_activo']) ? 1 : 0;
    $mailrelaySmtpHost = limpiarCadena($_POST['mailrelay_smtp_host'] ?? '');
    $mailrelaySmtpPort = (int) ($_POST['mailrelay_smtp_port'] ?? 587);
    $mailrelaySmtpUsuario = limpiarCadena($_POST['mailrelay_smtp_usuario'] ?? '');
    $mailrelaySmtpPasswordNueva = trim((string) ($_POST['mailrelay_smtp_password'] ?? ''));
    // TODO: En produccion, mover la password SMTP a variables de entorno o guardarla cifrada.
    $mailrelaySmtpPassword = $mailrelaySmtpPasswordNueva !== ''
        ? $mailrelaySmtpPasswordNueva
        : (string) ($configuracionGuardada['mailrelay_smtp_password'] ?? '');
    $mailrelaySmtpSeguridad = strtolower(limpiarCadena($_POST['mailrelay_smtp_seguridad'] ?? 'tls'));

    // Campos legacy mantenidos para instalaciones anteriores; el SMTP operativo usa mailrelay_smtp_*.
    $smtpHost = (string) ($configuracionGuardada['smtp_host'] ?? SMTP_HOST_DEFAULT);
    $smtpPort = (int) ($configuracionGuardada['smtp_port'] ?? SMTP_PORT_DEFAULT);
    $smtpUsuario = (string) ($configuracionGuardada['smtp_usuario'] ?? SMTP_USER_DEFAULT);
    $smtpPassword = (string) ($configuracionGuardada['smtp_password'] ?? SMTP_PASS_DEFAULT);
    $smtpFromEmail = (string) ($configuracionGuardada['smtp_from_email'] ?? SMTP_FROM_DEFAULT);
    $smtpFromName = (string) ($configuracionGuardada['smtp_from_name'] ?? SMTP_FROM_NAME_DEFAULT);

    if ($nombreEmpresa === '') {
        responderJson(false, 'Indica el nombre de la empresa.');
    }
    if ($emailEmpresa !== '' && !filter_var($emailEmpresa, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'Indica un email de empresa valido.');
    }
    if ($mailrelayApiUrl === '' || !filter_var($mailrelayApiUrl, FILTER_VALIDATE_URL)) {
        responderJson(false, 'Indica una URL valida para la API de Mailrelay.');
    }
    $rutaMailrelayApi = rtrim((string) parse_url($mailrelayApiUrl, PHP_URL_PATH), '/');
    if (!str_ends_with($rutaMailrelayApi, '/api/v1/send_emails')) {
        responderJson(false, 'La URL de Mailrelay debe terminar en /api/v1/send_emails.');
    }
    if ($mailrelayApiKey === '' || $mailrelayApiKey === MAILRELAY_API_KEY_DEFAULT) {
        responderJson(false, 'Indica la API key de Mailrelay.');
    }
    if (!filter_var($mailrelayFromEmail, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'Indica un email remitente valido.');
    }
    if (!str_ends_with(strtolower($mailrelayFromEmail), '@podasytalasjjh.es')) {
        responderJson(false, 'El email remitente debe pertenecer al dominio autenticado @podasytalasjjh.es.');
    }
    if ($mailrelayFromName === '') {
        responderJson(false, 'Indica el nombre remitente.');
    }
    if ($mailrelayBccActivo === 1 && !filter_var($mailrelayBccEmail, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'Indica un email valido para la copia oculta.');
    }
    if (!in_array($mailrelayMetodoEnvio, ['api', 'smtp'], true)) {
        responderJson(false, 'Selecciona un metodo de envio de presupuestos valido.');
    }
    if (!in_array($mailrelaySmtpSeguridad, ['tls', 'ssl', 'ninguna'], true)) {
        responderJson(false, 'Selecciona una seguridad SMTP valida.');
    }
    if ($mailrelaySmtpPort <= 0 || $mailrelaySmtpPort > 65535) {
        responderJson(false, 'Indica un puerto SMTP valido.');
    }

    try {
        $stmt = $conexion->prepare(
            'INSERT INTO configuracion
             (id_configuracion, nombre_empresa, email_empresa, telefono_empresa, direccion_empresa, nif_cif_empresa,
              ciudad_empresa, provincia_empresa, codigo_postal_empresa,
              mailrelay_api_url, mailrelay_api_key, mailrelay_from_email, mailrelay_from_name,
              mailrelay_bcc_email, mailrelay_bcc_activo,
              mailrelay_metodo_envio_facturas, mailrelay_smtp_fallback_activo,
              mailrelay_smtp_host, mailrelay_smtp_port, mailrelay_smtp_usuario, mailrelay_smtp_password, mailrelay_smtp_seguridad,
              smtp_host, smtp_port, smtp_usuario, smtp_password, smtp_from_email, smtp_from_name)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
              nombre_empresa = VALUES(nombre_empresa),
              email_empresa = VALUES(email_empresa),
              telefono_empresa = VALUES(telefono_empresa),
              direccion_empresa = VALUES(direccion_empresa),
              nif_cif_empresa = VALUES(nif_cif_empresa),
              ciudad_empresa = VALUES(ciudad_empresa),
              provincia_empresa = VALUES(provincia_empresa),
              codigo_postal_empresa = VALUES(codigo_postal_empresa),
              mailrelay_api_url = VALUES(mailrelay_api_url),
              mailrelay_api_key = VALUES(mailrelay_api_key),
              mailrelay_from_email = VALUES(mailrelay_from_email),
              mailrelay_from_name = VALUES(mailrelay_from_name),
              mailrelay_bcc_email = VALUES(mailrelay_bcc_email),
              mailrelay_bcc_activo = VALUES(mailrelay_bcc_activo),
              mailrelay_metodo_envio_facturas = VALUES(mailrelay_metodo_envio_facturas),
              mailrelay_smtp_fallback_activo = VALUES(mailrelay_smtp_fallback_activo),
              mailrelay_smtp_host = VALUES(mailrelay_smtp_host),
              mailrelay_smtp_port = VALUES(mailrelay_smtp_port),
              mailrelay_smtp_usuario = VALUES(mailrelay_smtp_usuario),
              mailrelay_smtp_password = VALUES(mailrelay_smtp_password),
              mailrelay_smtp_seguridad = VALUES(mailrelay_smtp_seguridad),
              smtp_host = VALUES(smtp_host),
              smtp_port = VALUES(smtp_port),
              smtp_usuario = VALUES(smtp_usuario),
              smtp_password = VALUES(smtp_password),
              smtp_from_email = VALUES(smtp_from_email),
              smtp_from_name = VALUES(smtp_from_name)'
        );
        $tiposConfiguracion = str_repeat('s', 13) . 'isisissssissss';
        $stmt->bind_param(
            $tiposConfiguracion,
            $nombreEmpresa,
            $emailEmpresa,
            $telefonoEmpresa,
            $direccionEmpresa,
            $nifCifEmpresa,
            $ciudadEmpresa,
            $provinciaEmpresa,
            $codigoPostalEmpresa,
            $mailrelayApiUrl,
            $mailrelayApiKey,
            $mailrelayFromEmail,
            $mailrelayFromName,
            $mailrelayBccEmail,
            $mailrelayBccActivo,
            $mailrelayMetodoEnvio,
            $mailrelaySmtpFallbackActivo,
            $mailrelaySmtpHost,
            $mailrelaySmtpPort,
            $mailrelaySmtpUsuario,
            $mailrelaySmtpPassword,
            $mailrelaySmtpSeguridad,
            $smtpHost,
            $smtpPort,
            $smtpUsuario,
            $smtpPassword,
            $smtpFromEmail,
            $smtpFromName
        );
        $stmt->execute();
        responderJson(true, 'Configuracion guardada correctamente.');
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudo guardar la configuracion.');
    }
}

$configuracion = obtenerConfiguracion();
$claveRealConfigurada = !empty($configuracion['mailrelay_api_key'])
    && $configuracion['mailrelay_api_key'] !== MAILRELAY_API_KEY_DEFAULT;
$passwordSmtpConfigurada = !empty($configuracion['mailrelay_smtp_password']);
$tituloPagina = 'Configuracion';
$jsPagina = 'configuracion.js';
require __DIR__ . '/includes/layout-header.php';
?>
<form method="post" id="formConfiguracion">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(obtenerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="card card-soft settings-panel">
        <div class="card-header bg-white border-bottom-0 pt-3 px-3">
            <ul class="nav nav-tabs settings-tabs" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#configEmpresa" type="button" role="tab" aria-controls="configEmpresa" aria-selected="true"><i class="bi bi-building me-2"></i>Empresa</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="api-tab" data-bs-toggle="tab" data-bs-target="#configApi" type="button" role="tab" aria-controls="configApi" aria-selected="false"><i class="bi bi-code-slash me-2"></i>API y pruebas</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="smtp-tab" data-bs-toggle="tab" data-bs-target="#configSmtp" type="button" role="tab" aria-controls="configSmtp" aria-selected="false"><i class="bi bi-paperclip me-2"></i>SMTP presupuestos</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="configEmpresa" role="tabpanel" aria-labelledby="empresa-tab" tabindex="0">
                    <div class="settings-title">
                        <h2>Datos de la empresa</h2>
                        <p>Informacion visible en el presupuesto y en la plantilla PDF.</p>
                    </div>
                    <div class="row g-3 settings-fields">
                <div class="col-12"><label class="form-label">Nombre empresa</label><input class="form-control" name="nombre_empresa" value="<?php echo htmlspecialchars($configuracion['nombre_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="col-md-6"><label class="form-label">Email empresa</label><input class="form-control" type="email" name="email_empresa" value="<?php echo htmlspecialchars($configuracion['email_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-6"><label class="form-label">Telefono empresa</label><input class="form-control" name="telefono_empresa" value="<?php echo htmlspecialchars($configuracion['telefono_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-12"><label class="form-label">Direccion empresa</label><input class="form-control" name="direccion_empresa" value="<?php echo htmlspecialchars($configuracion['direccion_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-12"><label class="form-label">NIF/CIF empresa</label><input class="form-control" name="nif_cif_empresa" value="<?php echo htmlspecialchars($configuracion['nif_cif_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-4"><label class="form-label">Ciudad</label><input class="form-control" name="ciudad_empresa" value="<?php echo htmlspecialchars($configuracion['ciudad_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-4"><label class="form-label">Provincia</label><input class="form-control" name="provincia_empresa" value="<?php echo htmlspecialchars($configuracion['provincia_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-4"><label class="form-label">Codigo postal</label><input class="form-control" name="codigo_postal_empresa" value="<?php echo htmlspecialchars($configuracion['codigo_postal_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="configApi" role="tabpanel" aria-labelledby="api-tab" tabindex="0">
                    <div class="settings-title">
                        <h2>Mailrelay API y diagnostico</h2>
                        <p>La API se mantiene para pruebas. Los adjuntos de presupuesto se envian preferentemente por SMTP.</p>
                    </div>
                    <div class="row g-3 settings-fields">
                        <div class="col-12">
                            <label class="form-label">URL API Mailrelay</label>
                            <input class="form-control" type="url" name="mailrelay_api_url" value="<?php echo htmlspecialchars($configuracion['mailrelay_api_url'] ?? MAILRELAY_API_URL_DEFAULT, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">API Key Mailrelay</label>
                            <input class="form-control" type="password" name="mailrelay_api_key" autocomplete="new-password" placeholder="<?php echo $claveRealConfigurada ? 'Clave configurada' : 'Introduce tu API key'; ?>">
                            <div class="form-text">La API key se usa solo desde el servidor y se conserva si dejas el campo vacio.</div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-light border mb-0 small">Las pruebas API usan el email y nombre remitente definidos en la pestana <strong>SMTP presupuestos</strong>.</div>
                        </div>
                        <div class="col-12 pt-3 border-top mt-4">
                            <label class="form-label">Email para pruebas API</label>
                            <div class="input-group mb-2">
                                <input class="form-control" type="email" id="mailrelayEmailPrueba" placeholder="destinatario@ejemplo.com">
                                <button class="btn btn-outline-primary" type="button" id="btnProbarMailrelay">
                                    <i class="bi bi-envelope-check"></i> Probar envio Mailrelay
                                </button>
                            </div>
                            <div class="input-group">
                                <select class="form-select" id="mailrelayVarianteAdjunto" aria-label="Variante de payload de adjunto">
                                    <option value="a">Variante A: content_id vacio</option>
                                    <option value="b">Variante B: sin content_id</option>
                                    <option value="c">Variante C: sin text_part_auto</option>
                                </select>
                                <button class="btn btn-outline-primary" type="button" id="btnProbarMailrelayTxt">
                                    <i class="bi bi-paperclip"></i> Probar TXT
                                </button>
                            </div>
                            <div class="form-text">Estas pruebas envian un correo real al destinatario indicado y, en local/dev, muestran la respuesta depurada de Mailrelay.</div>
                            <pre class="d-none bg-light border rounded p-3 mt-3 small text-break" id="mailrelayDiagnostico"></pre>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="configSmtp" role="tabpanel" aria-labelledby="smtp-tab" tabindex="0">
                    <div class="settings-title">
                        <h2>Mailrelay SMTP para presupuestos adjuntos</h2>
                        <p>Canal recomendado para enviar el PDF real de cada presupuesto al cliente.</p>
                    </div>
                    <div class="row g-3 settings-fields">
                        <div class="col-lg-7">
                            <label class="form-label">Metodo de envio</label>
                            <select class="form-select" name="mailrelay_metodo_envio_facturas">
                                <option value="smtp" <?php echo ($configuracion['mailrelay_metodo_envio_facturas'] ?? 'smtp') === 'smtp' ? 'selected' : ''; ?>>SMTP Mailrelay (recomendado para adjuntos)</option>
                                <option value="api" <?php echo ($configuracion['mailrelay_metodo_envio_facturas'] ?? 'smtp') === 'api' ? 'selected' : ''; ?>>API Mailrelay</option>
                            </select>
                            <div class="form-text">Para presupuestos con PDF adjunto y copia oculta real, se recomienda usar SMTP.</div>
                        </div>
                        <div class="col-lg-5 d-flex align-items-end pb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="mailrelay_smtp_fallback_activo" id="mailrelaySmtpFallbackActivo" value="1" <?php echo !isset($configuracion['mailrelay_smtp_fallback_activo']) || !empty($configuracion['mailrelay_smtp_fallback_activo']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mailrelaySmtpFallbackActivo">Fallback tras HTTP 422</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">SMTP host</label>
                            <input class="form-control" name="mailrelay_smtp_host" value="<?php echo htmlspecialchars($configuracion['mailrelay_smtp_host'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="smtp1.s.ipzmarketing.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Puerto</label>
                            <input class="form-control" type="number" min="1" max="65535" name="mailrelay_smtp_port" value="<?php echo (int) ($configuracion['mailrelay_smtp_port'] ?? 587); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuario SMTP</label>
                            <input class="form-control" name="mailrelay_smtp_usuario" autocomplete="username" value="<?php echo htmlspecialchars($configuracion['mailrelay_smtp_usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password SMTP</label>
                            <input class="form-control" type="password" name="mailrelay_smtp_password" autocomplete="new-password" placeholder="<?php echo $passwordSmtpConfigurada ? 'Contrasena configurada' : 'Introduce tu password SMTP'; ?>">
                            <div class="form-text">Se conserva si dejas este campo vacio.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Seguridad</label>
                            <select class="form-select" name="mailrelay_smtp_seguridad">
                                <?php $seguridadSmtp = $configuracion['mailrelay_smtp_seguridad'] ?? 'tls'; ?>
                                <option value="tls" <?php echo $seguridadSmtp === 'tls' ? 'selected' : ''; ?>>TLS / STARTTLS</option>
                                <option value="ssl" <?php echo $seguridadSmtp === 'ssl' ? 'selected' : ''; ?>>SSL / SMTPS</option>
                                <option value="ninguna" <?php echo $seguridadSmtp === 'ninguna' ? 'selected' : ''; ?>>Ninguna</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email remitente</label>
                            <input class="form-control" type="email" name="mailrelay_from_email" value="<?php echo htmlspecialchars($configuracion['mailrelay_from_email'] ?? MAILRELAY_FROM_EMAIL_DEFAULT, ENT_QUOTES, 'UTF-8'); ?>" placeholder="info@podasytalasjjh.es" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre remitente</label>
                            <input class="form-control" name="mailrelay_from_name" value="<?php echo htmlspecialchars($configuracion['mailrelay_from_name'] ?? MAILRELAY_FROM_NAME_DEFAULT, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-12 pt-3 border-top mt-4">
                            <div class="fw-semibold mb-2">Copia oculta de respaldo</div>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <div class="form-check form-switch pb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="mailrelay_bcc_activo" id="mailrelayBccActivo" value="1" <?php echo !empty($configuracion['mailrelay_bcc_activo']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mailrelayBccActivo">Activar copia oculta automatica</label>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label">Email CCO/BCC</label>
                                    <input class="form-control" type="email" name="mailrelay_bcc_email" value="<?php echo htmlspecialchars($configuracion['mailrelay_bcc_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="tu-correo@dominio.com">
                                </div>
                            </div>
                            <div class="form-text">Si activas esta opcion, recibiras una copia oculta del mismo correo enviado al cliente. El cliente no vera este correo.</div>
                            <div class="alert alert-warning mt-3 mb-0 <?php echo (!empty($configuracion['mailrelay_bcc_activo']) && ($configuracion['mailrelay_metodo_envio_facturas'] ?? 'smtp') === 'api') ? '' : 'd-none'; ?>" id="alertaBccMetodoApi" role="alert">
                                Advertencia: la copia oculta real puede no estar disponible mediante API. Usa SMTP para garantizar BCC/CCO real.
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-light border mb-0 small">
                                Configuracion de referencia del panel: host <strong>smtp1.s.ipzmarketing.com</strong>, puerto <strong>587</strong> y seguridad <strong>TLS</strong>. Introduce tu usuario y password SMTP reales aqui; no se muestran de nuevo tras guardar.
                            </div>
                        </div>
                        <div class="col-12 pt-3 border-top mt-4">
                            <label class="form-label">Email para prueba SMTP</label>
                            <div class="input-group">
                                <input class="form-control" type="email" id="mailrelayEmailPruebaSmtp" placeholder="destinatario@ejemplo.com">
                                <button class="btn btn-outline-primary" type="button" id="btnProbarMailrelaySmtp">
                                    <i class="bi bi-envelope-check"></i> Probar envio SMTP
                                </button>
                            </div>
                            <div class="form-text">Envia un correo real sin adjunto para validar host, autenticacion y remitente antes de probar un presupuesto.</div>
                            <pre class="d-none bg-light border rounded p-3 mt-3 small text-break" id="mailrelayDiagnosticoSmtp"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar configuracion</button>
    </div>
</form>

<div class="row g-4 mt-2 settings-security">
    <div class="col-xl-7">
        <div class="card card-soft h-100">
            <div class="card-body p-4">
                <div class="settings-title mb-4">
                    <h2><i class="bi bi-person-lock me-2"></i>Cuenta</h2>
                    <p>Cambia el correo y la contrasena de acceso a JJH Space.</p>
                </div>
                <div class="account-current mb-4">
                    <span class="text-muted small">Correo actual</span>
                    <div class="fw-semibold" id="correoCuentaActual"><?php echo htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <form id="formCambiarCorreo" class="pb-4 mb-4 border-bottom">
                    <input type="hidden" name="accion" value="cambiar_correo_cuenta">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(obtenerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                    <h3 class="settings-subtitle">Correo de acceso</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nuevo correo</label>
                            <input class="form-control" type="email" name="nuevo_email" autocomplete="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Repetir nuevo correo</label>
                            <input class="form-control" type="email" name="repetir_email" autocomplete="email" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Contrasena actual</label>
                            <input class="form-control" type="password" name="password_actual" autocomplete="current-password" required>
                        </div>
                        <div class="col-md-5 d-flex align-items-end">
                            <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-envelope-check"></i> Guardar nuevo correo</button>
                        </div>
                    </div>
                </form>
                <form id="formCambiarPassword">
                    <input type="hidden" name="accion" value="cambiar_password_cuenta">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(obtenerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                    <h3 class="settings-subtitle">Contrasena</h3>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Contrasena actual</label>
                            <input class="form-control" type="password" name="password_actual" autocomplete="current-password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nueva contrasena</label>
                            <input class="form-control" type="password" name="password_nueva" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Repetir nueva contrasena</label>
                            <input class="form-control" type="password" name="password_repetida" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="col-12">
                            <div class="form-text mb-3">Minimo 8 caracteres. Se recomienda incluir mayuscula, minuscula y numero.</div>
                            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-key"></i> Cambiar contrasena</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card danger-zone h-100">
            <div class="card-body p-4">
                <div class="settings-title mb-3">
                    <h2><i class="bi bi-exclamation-triangle me-2"></i>Zona peligrosa</h2>
                    <p>Estas acciones eliminan datos de forma permanente. Usalas solo si estas seguro.</p>
                </div>
                <button class="btn btn-outline-danger mb-3" type="button" id="btnGenerarTokenSeguridad">
                    <i class="bi bi-shield-lock"></i> Generar token de seguridad
                </button>
                <div class="alert alert-warning d-none token-security-alert" id="avisoTokenSeguridad" role="alert">
                    <div class="small fw-semibold text-uppercase">Token de seguridad</div>
                    <div class="token-security-value" id="tokenSeguridadTexto"></div>
                    <div class="small">Este token caduca en 5 minutos.</div>
                </div>
                <div class="danger-action">
                    <div>
                        <div class="fw-semibold">Eliminar todos los presupuestos</div>
                        <p>Borra presupuestos, lineas, envios asociados y PDFs generados.</p>
                    </div>
                    <button class="btn btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#modalEliminarPresupuestos">Eliminar</button>
                </div>
                <div class="danger-action">
                    <div>
                        <div class="fw-semibold">Eliminar todos los clientes</div>
                        <p>Solo disponible si no existen presupuestos asociados.</p>
                    </div>
                    <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#modalEliminarClientes">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEliminarPresupuestos" tabindex="-1" aria-labelledby="modalEliminarPresupuestosTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form class="modal-content" id="formEliminarPresupuestos">
            <input type="hidden" name="accion" value="eliminar_todos_presupuestos">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(obtenerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modalEliminarPresupuestosTitulo">Eliminar todos los presupuestos</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Esta accion eliminara todos los presupuestos, sus lineas, envios asociados y PDFs generados. No se puede deshacer.</p>
                <label class="form-label" for="tokenEliminarPresupuestos">Escribe el token de seguridad</label>
                <input class="form-control" id="tokenEliminarPresupuestos" name="token_seguridad" autocomplete="off" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar presupuestos definitivamente</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEliminarClientes" tabindex="-1" aria-labelledby="modalEliminarClientesTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form class="modal-content" id="formEliminarClientes">
            <input type="hidden" name="accion" value="eliminar_todos_clientes">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(obtenerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modalEliminarClientesTitulo">Eliminar todos los clientes</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Esta accion eliminara todos los clientes. Si hay presupuestos asociados, no se podra completar hasta eliminar primero los presupuestos.</p>
                <label class="form-label" for="tokenEliminarClientes">Escribe el token de seguridad</label>
                <input class="form-control" id="tokenEliminarClientes" name="token_seguridad" autocomplete="off" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar clientes definitivamente</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
