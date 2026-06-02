<?php
require_once __DIR__ . '/includes/seguridad.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/logo-documento.php';
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
    $nombreComercial = limpiarCadena($_POST['nombre_comercial'] ?? $nombreEmpresa);
    $nombreFiscal = limpiarCadena($_POST['nombre_fiscal'] ?? '');
    $paisEmpresa = limpiarCadena($_POST['pais_empresa'] ?? '');
    $webEmpresa = limpiarCadena($_POST['web_empresa'] ?? '');
    $logoDocumento = (string) ($configuracionGuardada['logo_documento'] ?? '');
    $eliminarLogoDocumento = isset($_POST['eliminar_logo_documento']);

    if ($nombreEmpresa === '' && $nombreComercial !== '') {
        $nombreEmpresa = $nombreComercial;
    }
    if ($nombreComercial === '') {
        $nombreComercial = $nombreEmpresa;
    }
    if ($nombreEmpresa === '') {
        responderJson(false, 'Indica el nombre de la empresa.');
    }
    if ($webEmpresa !== '' && !filter_var($webEmpresa, FILTER_VALIDATE_URL)) {
        responderJson(false, 'Indica una web valida o deja el campo vacio.');
    }
    if ($emailEmpresa !== '' && !filter_var($emailEmpresa, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'Indica un email de empresa valido.');
    }

    try {
        if ($eliminarLogoDocumento) {
            eliminarLogoDocumentoSeguro($logoDocumento);
            $logoDocumento = '';
        }
        $logoDocumento = guardarLogoDocumentoSubido($_FILES['logo_documento'] ?? [], $logoDocumento);

        if (esSqlite()) {
            $stmtExiste = $conexion->prepare('SELECT 1 FROM configuracion WHERE id_configuracion = 1 LIMIT 1');
            $stmtExiste->execute();
            if (!$stmtExiste->get_result()->fetch_assoc()) {
                $stmtInsert = $conexion->prepare('INSERT INTO configuracion (id_configuracion, nombre_empresa) VALUES (1, ?)');
                $stmtInsert->bind_param('s', $nombreEmpresa);
                $stmtInsert->execute();
            }
        } else {
            $stmtBase = $conexion->prepare('INSERT INTO configuracion (id_configuracion, nombre_empresa) VALUES (1, ?) ON DUPLICATE KEY UPDATE nombre_empresa = nombre_empresa');
            $stmtBase->bind_param('s', $nombreEmpresa);
            $stmtBase->execute();
        }

        $stmt = $conexion->prepare(
            'UPDATE configuracion SET
             nombre_empresa = ?, email_empresa = ?, telefono_empresa = ?, direccion_empresa = ?, nif_cif_empresa = ?,
             ciudad_empresa = ?, provincia_empresa = ?, codigo_postal_empresa = ?, nombre_comercial = ?, nombre_fiscal = ?,
             pais_empresa = ?, web_empresa = ?, logo_documento = ?
             WHERE id_configuracion = 1'
        );
        $stmt->bind_param(
            'sssssssssssss',
            $nombreEmpresa,
            $emailEmpresa,
            $telefonoEmpresa,
            $direccionEmpresa,
            $nifCifEmpresa,
            $ciudadEmpresa,
            $provinciaEmpresa,
            $codigoPostalEmpresa,
            $nombreComercial,
            $nombreFiscal,
            $paisEmpresa,
            $webEmpresa,
            $logoDocumento
        );
        $stmt->execute();
        responderJson(true, 'Datos de empresa guardados correctamente.', [
            'logo_documento_url' => $logoDocumento,
        ]);
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudieron guardar los datos de empresa.');
    }

}

$configuracion = obtenerConfiguracion();
$claveRealConfigurada = !empty($configuracion['mailrelay_api_key'])
    && $configuracion['mailrelay_api_key'] !== MAILRELAY_API_KEY_DEFAULT;
$passwordSmtpConfigurada = !empty($configuracion['mailrelay_smtp_password']);
$metodoEnvioActual = obtenerMetodoEnvioCorreo($configuracion);
$tituloPagina = 'Configuracion';
$jsPagina = 'configuracion.js';
$jsExtra = ['manual.js'];
require __DIR__ . '/includes/layout-header.php';
?>
<form method="post" id="formConfiguracion" enctype="multipart/form-data">
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
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ayuda-tab" data-bs-toggle="tab" data-bs-target="#configAyuda" type="button" role="tab" aria-controls="configAyuda" aria-selected="false"><i class="bi bi-book me-2"></i>Manual y licencia</button>
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
                <div class="col-md-6"><label class="form-label">Nombre comercial</label><input class="form-control" name="nombre_comercial" value="<?php echo htmlspecialchars($configuracion['nombre_comercial'] ?? ($configuracion['nombre_empresa'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="col-md-6"><label class="form-label">Nombre fiscal / razon social</label><input class="form-control" name="nombre_fiscal" value="<?php echo htmlspecialchars($configuracion['nombre_fiscal'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-12"><label class="form-label">Nombre empresa interno</label><input class="form-control" name="nombre_empresa" value="<?php echo htmlspecialchars($configuracion['nombre_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="col-md-6"><label class="form-label">Email empresa</label><input class="form-control" type="email" name="email_empresa" value="<?php echo htmlspecialchars($configuracion['email_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-6"><label class="form-label">Telefono empresa</label><input class="form-control" name="telefono_empresa" value="<?php echo htmlspecialchars($configuracion['telefono_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-12"><label class="form-label">Direccion empresa</label><input class="form-control" name="direccion_empresa" value="<?php echo htmlspecialchars($configuracion['direccion_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-12"><label class="form-label">NIF/CIF empresa</label><input class="form-control" name="nif_cif_empresa" value="<?php echo htmlspecialchars($configuracion['nif_cif_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-4"><label class="form-label">Ciudad</label><input class="form-control" name="ciudad_empresa" value="<?php echo htmlspecialchars($configuracion['ciudad_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-4"><label class="form-label">Provincia</label><input class="form-control" name="provincia_empresa" value="<?php echo htmlspecialchars($configuracion['provincia_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-4"><label class="form-label">Codigo postal</label><input class="form-control" name="codigo_postal_empresa" value="<?php echo htmlspecialchars($configuracion['codigo_postal_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-6"><label class="form-label">Pais</label><input class="form-control" name="pais_empresa" value="<?php echo htmlspecialchars($configuracion['pais_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="col-md-6"><label class="form-label">Web</label><input class="form-control" type="url" name="web_empresa" value="<?php echo htmlspecialchars($configuracion['web_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://..."></div>
                <div class="col-12 pt-3 border-top mt-4">
                    <h3 class="settings-subtitle">Logo del documento</h3>
                    <?php if (!empty($configuracion['logo_documento'])): ?>
                        <img id="logoDocumentoActual" src="<?php echo htmlspecialchars($configuracion['logo_documento'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo actual" class="logo-documento-preview mb-2">
                        <div id="logoDocumentoVacio" class="text-muted small d-none">No hay logo configurado.</div>
                    <?php else: ?>
                        <img id="logoDocumentoActual" src="" alt="Logo actual" class="logo-documento-preview mb-2 d-none">
                        <div id="logoDocumentoVacio" class="text-muted small">No hay logo configurado.</div>
                    <?php endif; ?>
                    <input class="form-control" type="file" name="logo_documento" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                    <div class="form-text">Este logo se usara en presupuestos y documentos PDF. Maximo 2 MB.</div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="eliminar_logo_documento" value="1" id="eliminarLogoDocumento">
                        <label class="form-check-label" for="eliminarLogoDocumento">Eliminar logo actual</label>
                    </div>
                </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="configApi" role="tabpanel" aria-labelledby="api-tab" tabindex="0">
                    <div class="settings-title">
                        <h2>Mailrelay API y diagnostico</h2>
                        <p>La API por HTTPS es recomendable si tu servidor o proveedor bloquea la salida SMTP.</p>
                    </div>
                    <div class="row g-3 settings-fields">
                        <div class="col-12">
                            <div class="alert alert-info mb-0 small">
                                Algunos hostings bloquean SMTP saliente. Usa Mailrelay API por HTTPS si SMTP no conecta o confirma conectividad desde el diagnostico.
                                <a class="alert-link ms-1" href="diagnostico-mailrelay.php">Abrir diagnostico Mailrelay</a>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">URL API Mailrelay</label>
                            <input class="form-control" type="url" name="mailrelay_api_url" value="<?php echo htmlspecialchars($configuracion['mailrelay_api_url'] ?? MAILRELAY_API_URL_DEFAULT, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">API Key Mailrelay</label>
                            <input class="form-control" type="password" name="mailrelay_api_key" autocomplete="new-password" placeholder="<?php echo $claveRealConfigurada ? 'Clave configurada' : 'Introduce tu API key'; ?>">
                            <div class="form-text">La API key se usa solo desde el servidor y se conserva si dejas el campo vacio.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email remitente API</label>
                            <input class="form-control" type="email" name="mailrelay_from_email" value="<?php echo htmlspecialchars($configuracion['mailrelay_from_email'] ?? MAILRELAY_FROM_EMAIL_DEFAULT, ENT_QUOTES, 'UTF-8'); ?>" placeholder="facturas@tu-dominio.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nombre remitente API</label>
                            <input class="form-control" name="mailrelay_from_name" value="<?php echo htmlspecialchars($configuracion['mailrelay_from_name'] ?? MAILRELAY_FROM_NAME_DEFAULT, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-text">Estos datos se usan como remitente compartido para API y SMTP.</div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="button" id="btnGuardarConfigApi">
                                <i class="bi bi-save"></i> Guardar configuracion API
                            </button>
                        </div>
                        <div class="col-12 pt-3 border-top mt-4">
                            <label class="form-label">Email para pruebas API</label>
                            <div class="input-group mb-2">
                                <input class="form-control" type="email" id="mailrelayEmailPrueba" placeholder="destinatario@ejemplo.com">
                                <button class="btn btn-outline-primary" type="button" id="btnProbarMailrelay">
                                    <i class="bi bi-envelope-check"></i> Probar API
                                </button>
                            </div>
                            <div class="form-text">Esta prueba envia un correo real al destinatario indicado y no adjunta PDF.</div>
                            <pre class="d-none bg-light border rounded p-3 mt-3 small text-break" id="mailrelayDiagnostico"></pre>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="configSmtp" role="tabpanel" aria-labelledby="smtp-tab" tabindex="0">
                    <div class="settings-title">
                        <h2>Mailrelay SMTP para presupuestos adjuntos</h2>
                        <p>Usalo si tu servidor permite salida SMTP y la prueba de conectividad responde correctamente.</p>
                    </div>
                    <div class="row g-3 settings-fields">
                        <div class="col-lg-7">
                            <label class="form-label">Metodo de envio</label>
                            <select class="form-select" name="mailrelay_metodo_envio">
                                <option value="api" <?php echo $metodoEnvioActual === 'api' ? 'selected' : ''; ?>>API Mailrelay (HTTPS)</option>
                                <option value="smtp" <?php echo $metodoEnvioActual === 'smtp' ? 'selected' : ''; ?>>SMTP Mailrelay (465/587)</option>
                            </select>
                            <div class="form-text">API usa HTTPS y suele funcionar mejor si tu red o servidor bloquea SMTP. SMTP permite envio clasico y copia oculta real si el entorno lo permite.</div>
                        </div>
                        <div class="col-lg-5 d-flex align-items-end pb-2">
                            <button class="btn btn-primary" type="button" id="btnGuardarMetodoCorreo">
                                <i class="bi bi-save"></i> Guardar metodo
                            </button>
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
                                <option value="none" <?php echo in_array($seguridadSmtp, ['none', 'ninguna'], true) ? 'selected' : ''; ?>>Ninguna</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <button class="btn btn-primary" type="button" id="btnGuardarConfigSmtp">
                                <i class="bi bi-save"></i> Guardar configuracion SMTP
                            </button>
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
                            <button class="btn btn-primary mt-3" type="button" id="btnGuardarConfigBcc">
                                <i class="bi bi-save"></i> Guardar BCC
                            </button>
                            <div class="form-text">La copia oculta real solo se garantiza con SMTP.</div>
                            <div class="alert alert-warning mt-3 mb-0 <?php echo (!empty($configuracion['mailrelay_bcc_activo']) && $metodoEnvioActual === 'api') ? '' : 'd-none'; ?>" id="alertaBccMetodoApi" role="alert">
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
                <div class="tab-pane fade" id="configAyuda" role="tabpanel" aria-labelledby="ayuda-tab" tabindex="0">
                    <div class="settings-title">
                        <h2>Manual de uso y licencia</h2>
                        <p>Consulta las pautas basicas de uso, copias de seguridad, correo y condiciones de licencia.</p>
                    </div>
                    <div class="row g-3 settings-fields">
                        <div class="col-md-6">
                            <div class="help-card">
                                <div class="help-card-icon"><i class="bi bi-book"></i></div>
                                <h3>Manual de uso</h3>
                                <p>Guia rapida para configurar la empresa, crear presupuestos, usar estados, hacer backups y entender Mailrelay.</p>
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalManual">
                                    <i class="bi bi-journal-text"></i> Ver manual
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="help-card">
                                <div class="help-card-icon"><i class="bi bi-shield-check"></i></div>
                                <h3>Licencia</h3>
                                <p>Uso gratuito para autonomos y pequenas empresas dentro de los limites indicados en la licencia.</p>
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalLicencia">
                                    <i class="bi bi-file-earmark-text"></i> Ver licencia
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="license-note">
                                Your Small Desk &middot; Desarrollado por Diego Herrera Ayuso
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar datos de empresa</button>
    </div>
</form>

<div class="row g-4 mt-2 settings-security">
    <div class="col-xl-7">
        <div class="card card-soft h-100">
            <div class="card-body p-4">
                <div class="settings-title mb-4">
                    <h2><i class="bi bi-person-lock me-2"></i>Cuenta</h2>
                    <p>Cambia el correo y la contrasena de acceso a Your Small Desk.</p>
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

<div class="modal fade" id="modalManual" tabindex="-1" aria-labelledby="modalManualTitulo" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content manual-modal">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="modalManualTitulo">Manual rapido de Your Small Desk</h2>
                    <div class="text-muted small">Guia local para uso diario de la app.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <pre class="manual-output" id="manualTypewriter"></pre>
                <script type="text/plain" id="manualTexto">Manual rapido de Your Small Desk

1. Que es Your Small Desk
Your Small Desk es una app local para crear presupuestos, clientes y PDFs. Esta pensada para autonomos y pequenos negocios. En modo escritorio no necesita servidor externo.

2. Primer uso
Configura los datos de empresa, sube el logo del documento, crea el usuario administrador y cambia la contrasena inicial despues del primer acceso.

3. Clientes
Puedes crear, editar y eliminar clientes. Los clientes se pueden seleccionar al crear un presupuesto para rellenar sus datos en el documento.

4. Presupuestos
Crea un presupuesto, anade conceptos, elige Con IVA o Sin IVA, guarda, descarga el PDF, envia por correo si Mailrelay esta configurado o abre WhatsApp con el mensaje preparado.

5. Estados
Los estados disponibles son borrador, emitido, enviado, cobrado, rechazado y vencido. Los resumenes se calculan desde el estado actual de cada presupuesto.

6. Trimestres
La vista de trimestres ayuda a revisar cobrados, pendientes y rechazados. Sirve para control interno y para preparar informacion para la gestoria.

7. Copias de seguridad
Guarda una copia de data/jjh_space.sqlite y de la carpeta storage/. Ahi estan la base de datos, PDFs generados, logos y archivos de trabajo.

8. Mailrelay y correos
La API usa HTTPS y suele funcionar incluso en servidores que bloquean SMTP. SMTP usa puertos de correo como 465 o 587, puede estar bloqueado por algunos proveedores y es recomendable si necesitas copia oculta real BCC y el servidor lo permite. La configuracion API y SMTP se guarda por separado. Guardar API no borra SMTP y guardar SMTP no borra API.

9. Servidores sin salida SMTP
Algunos servidores bloquean puertos SMTP. En ese caso usa API. SMTP sirve para enviar correo.

10. Licencia
Your Small Desk tiene uso gratuito limitado para autonomos, pequenos negocios, uso personal y uso educativo. Grandes empresas, facturacion alta o usos comerciales ampliados requieren autorizacion del autor. No elimines la autoria de Diego Herrera Ayuso.</script>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="btnManualMostrarTodo">Mostrar todo</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLicencia" tabindex="-1" aria-labelledby="modalLicenciaTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modalLicenciaTitulo">Licencia Your Small Desk Community License</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body license-summary">
                <p><strong>Your Small Desk Â· Desarrollado por Diego Herrera Ayuso.</strong></p>
                <p>Uso gratuito permitido para autonomos, pequenos negocios, microempresas, uso personal, uso educativo y uso interno no masivo.</p>
                <p>No esta permitido sin autorizacion escrita del autor: uso por empresas medianas o grandes, facturacion anual superior a 100.000 EUR, reventa, redistribucion como producto propio, ofrecerlo como SaaS o servicio alojado a terceros, integrarlo en productos comerciales de terceros o eliminar avisos de autoria.</p>
                <p>Si superas estos limites o quieres usar Your Small Desk en una empresa mayor, contacta con Diego Herrera Ayuso para obtener una licencia comercial.</p>
                <p>Consulta el archivo LICENSE del proyecto para ver las condiciones completas.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
