<?php
require_once __DIR__ . '/pdf.php';

function obtenerConfiguracionMailrelaySmtp(): array
{
    $configuracion = obtenerConfiguracion();

    return [
        'mailrelay_smtp_host' => trim((string) ($configuracion['mailrelay_smtp_host'] ?? '')),
        'mailrelay_smtp_port' => (int) ($configuracion['mailrelay_smtp_port'] ?? 0),
        'mailrelay_smtp_usuario' => trim((string) ($configuracion['mailrelay_smtp_usuario'] ?? '')),
        'mailrelay_smtp_password' => (string) ($configuracion['mailrelay_smtp_password'] ?? ''),
        'mailrelay_smtp_seguridad' => strtolower(trim((string) ($configuracion['mailrelay_smtp_seguridad'] ?? 'tls'))),
        'mailrelay_from_email' => trim((string) ($configuracion['mailrelay_from_email'] ?? '')),
        'mailrelay_from_name' => trim((string) ($configuracion['mailrelay_from_name'] ?? '')),
        'mailrelay_bcc_email' => trim((string) ($configuracion['mailrelay_bcc_email'] ?? '')),
        'mailrelay_bcc_activo' => (int) ($configuracion['mailrelay_bcc_activo'] ?? 0) === 1,
    ];
}

function smtpPermiteDiagnostico(): bool
{
    return DEBUG_APP && in_array(APP_ENV, ['local', 'dev', 'development'], true);
}

function obtenerEstadoConfiguracionMailrelaySmtp(): array
{
    $configuracion = obtenerConfiguracionMailrelaySmtp();

    return [
        'origen' => 'base_datos',
        'host_configurado' => $configuracion['mailrelay_smtp_host'] !== '',
        'puerto_configurado' => $configuracion['mailrelay_smtp_port'] > 0
            && $configuracion['mailrelay_smtp_port'] <= 65535,
        'host' => $configuracion['mailrelay_smtp_host'],
        'puerto' => $configuracion['mailrelay_smtp_port'],
        'seguridad' => $configuracion['mailrelay_smtp_seguridad'],
        'usuario_configurado' => $configuracion['mailrelay_smtp_usuario'] !== '',
        'smtp_password_configurada' => $configuracion['mailrelay_smtp_password'] !== '',
        'from_email_configurado' => filter_var($configuracion['mailrelay_from_email'], FILTER_VALIDATE_EMAIL) !== false,
        'from_name_configurado' => $configuracion['mailrelay_from_name'] !== '',
    ];
}

function errorMailrelaySmtp(string $mensaje, array $datos = []): array
{
    return ['ok' => false, 'mensaje' => $mensaje, 'datos' => $datos];
}

function probarConexionSmtp(string $host, int $puerto, int $timeout = 5): array
{
    $host = trim($host);
    if ($host === '' || $puerto <= 0 || $puerto > 65535) {
        return errorMailrelaySmtp('Configura un host y puerto SMTP validos antes de probar la conectividad.', [
            'reachable' => false,
            'puerto' => $puerto,
        ]);
    }

    $inicio = microtime(true);
    $numeroError = 0;
    $detalleError = '';
    $conexion = @stream_socket_client(
        'tcp://' . $host . ':' . $puerto,
        $numeroError,
        $detalleError,
        $timeout,
        STREAM_CLIENT_CONNECT
    );
    $tiempoMs = (int) round((microtime(true) - $inicio) * 1000);

    if (is_resource($conexion)) {
        fclose($conexion);
        return [
            'ok' => true,
            'mensaje' => 'SMTP parece accesible desde este entorno. Puedes probar el envio SMTP con PHPMailer.',
            'datos' => [
                'reachable' => true,
                'host' => $host,
                'puerto' => $puerto,
                'tiempo_ms' => $tiempoMs,
            ],
        ];
    }

    return errorMailrelaySmtp(
        'SMTP no es accesible desde este servicio. Algunos proveedores bloquean la salida SMTP.',
        [
            'reachable' => false,
            'host' => $host,
            'puerto' => $puerto,
            'tiempo_ms' => $tiempoMs,
            'error' => trim($detalleError) !== '' ? trim($detalleError) : 'No se pudo abrir la conexion TCP.',
            'error_code' => $numeroError,
        ]
    );
}

function probarConectividadMailrelaySmtp(): array
{
    $configuracion = obtenerConfiguracionMailrelaySmtp();
    $resultado = probarConexionSmtp(
        $configuracion['mailrelay_smtp_host'],
        $configuracion['mailrelay_smtp_port']
    );
    $resultado['datos'] = array_merge($resultado['datos'] ?? [], [
        'seguridad' => $configuracion['mailrelay_smtp_seguridad'],
        'usuario_configurado' => $configuracion['mailrelay_smtp_usuario'] !== '',
        'smtp_password_configurada' => $configuracion['mailrelay_smtp_password'] !== '',
    ]);

    return $resultado;
}

function validarConfiguracionMailrelaySmtp(array $configuracion, string $destinatario, string $asunto, string $mensaje): ?array
{
    if ($configuracion['mailrelay_smtp_host'] === '') {
        return errorMailrelaySmtp('Falta el host SMTP de Mailrelay.');
    }
    if ($configuracion['mailrelay_smtp_port'] <= 0 || $configuracion['mailrelay_smtp_port'] > 65535) {
        return errorMailrelaySmtp('El puerto SMTP de Mailrelay no es valido.');
    }
    if ($configuracion['mailrelay_smtp_usuario'] === '') {
        return errorMailrelaySmtp('Falta el usuario SMTP de Mailrelay.');
    }
    if ($configuracion['mailrelay_smtp_password'] === '') {
        return errorMailrelaySmtp('Falta la contrasena SMTP de Mailrelay.');
    }
    if (!in_array($configuracion['mailrelay_smtp_seguridad'], ['tls', 'ssl', 'none', 'ninguna'], true)) {
        return errorMailrelaySmtp('La seguridad SMTP seleccionada no es valida.');
    }
    if (!filter_var($configuracion['mailrelay_from_email'], FILTER_VALIDATE_EMAIL)) {
        return errorMailrelaySmtp('El email remitente de Mailrelay no es valido.');
    }
    if ($configuracion['mailrelay_from_name'] === '') {
        return errorMailrelaySmtp('Falta el nombre remitente de Mailrelay.');
    }
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        return errorMailrelaySmtp('El email destinatario no es valido.');
    }
    if ($asunto === '' || mb_strlen($asunto) > 180) {
        return errorMailrelaySmtp('El asunto es obligatorio y no puede superar 180 caracteres.');
    }
    if ($mensaje === '' || mb_strlen($mensaje) > 5000) {
        return errorMailrelaySmtp('El mensaje es obligatorio y no puede superar 5000 caracteres.');
    }
    if ($configuracion['mailrelay_bcc_activo'] && !filter_var($configuracion['mailrelay_bcc_email'], FILTER_VALIDATE_EMAIL)) {
        return errorMailrelaySmtp('La copia oculta esta activa, pero el email CCO/BCC no es valido.');
    }

    return null;
}

function validarPdfParaSmtp(string $rutaPdf): ?string
{
    if (!file_exists($rutaPdf) || !is_file($rutaPdf) || !is_readable($rutaPdf)) {
        return 'No se pudo leer el PDF del presupuesto.';
    }

    $tamano = filesize($rutaPdf);
    if ($tamano === false || $tamano <= 0) {
        return 'El PDF del presupuesto esta vacio.';
    }

    $contenido = file_get_contents($rutaPdf, false, null, 0, 4);
    if ($contenido === false || $contenido !== '%PDF') {
        return 'El archivo generado no parece ser un PDF valido.';
    }

    return null;
}

function registrarEnvioMailrelaySmtp(
    ?int $idFactura,
    string $destinatario,
    string $asunto,
    string $mensaje,
    array $resultado,
    ?string $bccEmail
): void {
    $conexion = obtenerConexion();
    $estado = $resultado['ok'] ? 'enviado' : 'error';
    $respuestaError = $resultado['ok'] ? null : (string) ($resultado['datos']['error_registro'] ?? $resultado['mensaje']);
    $respuestaApi = $resultado['ok'] ? 'SMTP: correo enviado correctamente.' : null;
    $metodo = 'smtp';
    $stmt = $conexion->prepare(
        'INSERT INTO envios (id_factura, destinatario, bcc_email, asunto, mensaje, estado, metodo, respuesta_error, respuesta_api)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issssssss', $idFactura, $destinatario, $bccEmail, $asunto, $mensaje, $estado, $metodo, $respuestaError, $respuestaApi);
    $stmt->execute();

    if ($resultado['ok'] && $idFactura !== null) {
        $nuevoEstado = 'enviada';
        $stmtEstado = $conexion->prepare('UPDATE facturas SET estado = ? WHERE id_factura = ?');
        $stmtEstado->bind_param('si', $nuevoEstado, $idFactura);
        $stmtEstado->execute();
    }
}

function cargarPhpmailerMailrelaySmtp(): ?array
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        return errorMailrelaySmtp('Faltan las dependencias de Composer para enviar por SMTP.');
    }
    require_once $autoload;

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return errorMailrelaySmtp('PHPMailer no esta instalado.');
    }

    return null;
}

function usuarioSmtpDepurado(string $usuario): string
{
    return $usuario === '' ? '' : substr($usuario, 0, min(3, strlen($usuario))) . '***';
}

function limpiarDetalleSmtp(string $detalle, array $configuracion): string
{
    foreach (['mailrelay_smtp_password', 'mailrelay_smtp_usuario'] as $campo) {
        $secreto = (string) ($configuracion[$campo] ?? '');
        if ($secreto !== '') {
            $detalle = str_replace($secreto, '[oculto]', $detalle);
        }
    }

    return $detalle;
}

function crearMailerMailrelaySmtp(array $configuracion, string $destinatario, string $asunto, string $mensaje, string &$debugSmtp): \PHPMailer\PHPMailer\PHPMailer
{
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isSMTP();
    $mail->Host = $configuracion['mailrelay_smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $configuracion['mailrelay_smtp_usuario'];
    $mail->Password = $configuracion['mailrelay_smtp_password'];
    $mail->Port = $configuracion['mailrelay_smtp_port'];

    if (smtpPermiteDiagnostico()) {
        $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
        $mail->Debugoutput = static function (string $str, int $nivel) use (&$debugSmtp, $configuracion): void {
            $debugSmtp .= '[' . $nivel . '] ' . limpiarDetalleSmtp($str, $configuracion) . "\n";
        };
    }

    if ($configuracion['mailrelay_smtp_seguridad'] === 'tls') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($configuracion['mailrelay_smtp_seguridad'] === 'ssl') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPAutoTLS = false;
    }

    $mail->setFrom($configuracion['mailrelay_from_email'], $configuracion['mailrelay_from_name']);
    $mail->addAddress($destinatario);
    $mail->isHTML(true);
    $mail->Subject = $asunto;
    $mail->Body = '<html><body>' . nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8')) . '</body></html>';
    $mail->AltBody = $mensaje;

    return $mail;
}

function enviarCorreoSimpleMailrelaySmtp(string $destinatario, string $asunto, string $mensaje): array
{
    $configuracion = obtenerConfiguracionMailrelaySmtp();
    $error = validarConfiguracionMailrelaySmtp($configuracion, $destinatario, $asunto, $mensaje);
    if ($error !== null) {
        registrarEnvioMailrelaySmtp(null, $destinatario, $asunto, $mensaje, $error, null);
        return $error;
    }

    $errorCarga = cargarPhpmailerMailrelaySmtp();
    if ($errorCarga !== null) {
        registrarEnvioMailrelaySmtp(null, $destinatario, $asunto, $mensaje, $errorCarga, null);
        return $errorCarga;
    }

    $debugSmtp = '';
    try {
        $mail = crearMailerMailrelaySmtp($configuracion, $destinatario, $asunto, $mensaje, $debugSmtp);
        $mail->send();
        $resultado = [
            'ok' => true,
            'mensaje' => 'Correo de prueba SMTP enviado correctamente.',
            'datos' => ['metodo_envio' => 'smtp'],
        ];
    } catch (Throwable $e) {
        $detalle = limpiarDetalleSmtp($e->getMessage(), $configuracion);
        $datos = ['error_registro' => 'SMTP: ' . $detalle, 'metodo_envio' => 'smtp'];
        if (smtpPermiteDiagnostico()) {
            $datos += [
                'host' => $configuracion['mailrelay_smtp_host'],
                'puerto' => $configuracion['mailrelay_smtp_port'],
                'seguridad' => $configuracion['mailrelay_smtp_seguridad'],
                'usuario_parcial' => usuarioSmtpDepurado($configuracion['mailrelay_smtp_usuario']),
                'from_email' => $configuracion['mailrelay_from_email'],
                'destinatario' => $destinatario,
                'error_phpmailer' => $detalle,
                'smtp_debug' => limpiarDetalleSmtp($debugSmtp, $configuracion),
            ];
        }
        $resultado = errorMailrelaySmtp('No se pudo enviar el correo de prueba por SMTP.', $datos);
    }

    registrarEnvioMailrelaySmtp(null, $destinatario, $asunto, $mensaje, $resultado, null);
    unset($resultado['datos']['error_registro']);
    return $resultado;
}

function enviarFacturaPorMailrelaySmtp(int $idFactura, string $destinatario, string $asunto, string $mensaje): array
{
    if ($idFactura <= 0) {
        return errorMailrelaySmtp('Presupuesto no valido.');
    }
    if (!obtenerDatosFactura($idFactura)) {
        return errorMailrelaySmtp('Presupuesto no encontrado.');
    }

    $configuracion = obtenerConfiguracionMailrelaySmtp();
    $datosEnvio = [
        'cliente_enviado' => false,
        'bcc_enviado' => false,
        'bcc_activo' => (bool) $configuracion['mailrelay_bcc_activo'],
        'metodo' => 'smtp',
    ];
    $error = validarConfiguracionMailrelaySmtp($configuracion, $destinatario, $asunto, $mensaje);
    if ($error !== null) {
        $error['datos'] = array_merge($datosEnvio, $error['datos'] ?? []);
        registrarEnvioMailrelaySmtp($idFactura, $destinatario, $asunto, $mensaje, $error, null);
        return $error;
    }

    $bccEmail = $configuracion['mailrelay_bcc_activo']
        && filter_var($configuracion['mailrelay_bcc_email'], FILTER_VALIDATE_EMAIL)
        ? $configuracion['mailrelay_bcc_email']
        : null;

    try {
        $pdf = generarPdfFactura($idFactura);
    } catch (Throwable $e) {
        $resultado = errorMailrelaySmtp('No se pudo generar el PDF del presupuesto.', $datosEnvio);
        registrarEnvioMailrelaySmtp($idFactura, $destinatario, $asunto, $mensaje, $resultado, $bccEmail);
        return $resultado;
    }

    $errorPdf = validarPdfParaSmtp($pdf['ruta_absoluta']);
    if ($errorPdf !== null) {
        $resultado = errorMailrelaySmtp($errorPdf, $datosEnvio);
        registrarEnvioMailrelaySmtp($idFactura, $destinatario, $asunto, $mensaje, $resultado, $bccEmail);
        return $resultado;
    }

    $errorCarga = cargarPhpmailerMailrelaySmtp();
    if ($errorCarga !== null) {
        $resultado = $errorCarga;
        $resultado['datos'] = array_merge($datosEnvio, $resultado['datos'] ?? []);
        registrarEnvioMailrelaySmtp($idFactura, $destinatario, $asunto, $mensaje, $resultado, $bccEmail);
        return $resultado;
    }

    $debugSmtp = '';
    try {
        $mail = crearMailerMailrelaySmtp($configuracion, $destinatario, $asunto, $mensaje, $debugSmtp);
        if ($bccEmail !== null) {
            $mail->addBCC($bccEmail);
        }
        $mail->addAttachment($pdf['ruta_absoluta'], $pdf['nombre_pdf']);
        $mail->send();

        $resultado = [
            'ok' => true,
            'mensaje' => 'Presupuesto enviado correctamente.',
            'datos' => [
                'cliente_enviado' => true,
                'bcc_enviado' => $bccEmail !== null,
                'bcc_activo' => (bool) $configuracion['mailrelay_bcc_activo'],
                'metodo' => 'smtp',
            ],
        ];
        if ($bccEmail !== null) {
            $resultado['datos']['bcc_email'] = $bccEmail;
        }
    } catch (Throwable $e) {
        $detalle = limpiarDetalleSmtp($e->getMessage(), $configuracion);
        $datos = $datosEnvio + ['error_registro' => 'SMTP: ' . $detalle];
        if (smtpPermiteDiagnostico()) {
            $datos += [
                'host' => $configuracion['mailrelay_smtp_host'],
                'puerto' => $configuracion['mailrelay_smtp_port'],
                'seguridad' => $configuracion['mailrelay_smtp_seguridad'],
                'usuario_parcial' => usuarioSmtpDepurado($configuracion['mailrelay_smtp_usuario']),
                'destinatario' => $destinatario,
                'from_email' => $configuracion['mailrelay_from_email'],
                'error_phpmailer' => $detalle,
                'smtp_debug' => limpiarDetalleSmtp($debugSmtp, $configuracion),
            ];
        }
        $resultado = errorMailrelaySmtp('No se pudo enviar el presupuesto al cliente.', $datos);
    }

    registrarEnvioMailrelaySmtp($idFactura, $destinatario, $asunto, $mensaje, $resultado, $bccEmail);
    unset($resultado['datos']['error_registro']);
    return $resultado;
}
