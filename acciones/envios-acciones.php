<?php
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/mailrelay-api.php';
require_once __DIR__ . '/../includes/mailrelay-smtp.php';
exigirSesion();
exigirMetodoPost();

$accion = (string) ($_POST['accion'] ?? '');
if (!in_array($accion, ['enviar_correo', 'probar_mailrelay_simple', 'probar_mailrelay_smtp_simple', 'probar_mailrelay_adjunto_txt', 'probar_adjunto_txt', 'estado_diagnostico_mailrelay', 'probar_conectividad_smtp'], true)) {
    responderJson(false, 'Accion no valida.');
}

validarCsrf();

if ($accion === 'estado_diagnostico_mailrelay') {
    $configuracion = obtenerConfiguracion();
    $metodo = obtenerMetodoEnvioCorreo($configuracion);
    responderJson(true, 'Configuracion de correo cargada correctamente.', [
        'api' => obtenerEstadoConfiguracionMailrelay(),
        'smtp' => obtenerEstadoConfiguracionMailrelaySmtp(),
        'metodo_presupuestos' => in_array($metodo, ['api', 'smtp'], true) ? $metodo : 'smtp',
        'entorno' => APP_ENV,
        'debug_detallado' => mailrelayPermiteDiagnostico() || smtpPermiteDiagnostico(),
    ]);
}

if ($accion === 'probar_conectividad_smtp') {
    $resultado = probarConectividadMailrelaySmtp();
    responderJson($resultado['ok'], $resultado['mensaje'], $resultado['datos'] ?? []);
}

$destinatario = limpiarCadena($_POST['destinatario'] ?? '');
$asunto = limpiarCadena($_POST['asunto'] ?? '');
$mensaje = trim((string) ($_POST['mensaje'] ?? ''));

if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    responderJson(false, 'El email destinatario no es valido.');
}
if ($asunto === '' || mb_strlen($asunto) > 180) {
    responderJson(false, 'El asunto es obligatorio y no puede superar 180 caracteres.');
}
if ($mensaje === '' || mb_strlen($mensaje) > 5000) {
    responderJson(false, 'El mensaje es obligatorio y no puede superar 5000 caracteres.');
}

try {
    if ($accion === 'probar_mailrelay_simple') {
        $resultado = enviarCorreoSimpleMailrelayApi($destinatario, $asunto, $mensaje);
        unset($resultado['datos']['respuesta_registro'], $resultado['datos']['error_registro']);
        responderJson($resultado['ok'], $resultado['mensaje'], $resultado['datos'] ?? []);
    }
    if ($accion === 'probar_mailrelay_smtp_simple') {
        $resultado = enviarCorreoSimpleMailrelaySmtp($destinatario, $asunto, $mensaje);
        responderJson($resultado['ok'], $resultado['mensaje'], $resultado['datos'] ?? []);
    }
    if (in_array($accion, ['probar_mailrelay_adjunto_txt', 'probar_adjunto_txt'], true)) {
        $varianteAdjunto = strtolower(limpiarCadena($_POST['variante_adjunto'] ?? 'a'));
        $resultado = probarMailrelayConAdjuntoTxt($destinatario, $varianteAdjunto);
        unset($resultado['datos']['respuesta_registro'], $resultado['datos']['error_registro']);
        responderJson($resultado['ok'], $resultado['mensaje'], $resultado['datos'] ?? []);
    }

    $idFactura = (int) ($_POST['id_factura'] ?? 0);
    if ($idFactura <= 0) {
        responderJson(false, 'Presupuesto no valido.');
    }

    $configuracion = obtenerConfiguracion();
    $metodoEnvio = obtenerMetodoEnvioCorreo($configuracion);

    if ($metodoEnvio === 'smtp') {
        $resultado = enviarFacturaPorMailrelaySmtp($idFactura, $destinatario, $asunto, $mensaje);
    } else {
        if ((int) ($configuracion['mailrelay_bcc_activo'] ?? 0) === 1) {
            responderJson(false, 'La copia oculta real solo esta disponible usando SMTP en esta configuracion.', [
                'cliente_enviado' => false,
                'bcc_enviado' => false,
                'metodo' => 'api',
            ]);
        }
        $resultado = enviarFacturaPorMailrelayApi($idFactura, $destinatario, $asunto, $mensaje);
    }
    responderJson($resultado['ok'], $resultado['mensaje'], $resultado['datos'] ?? []);
} catch (Throwable $e) {
    $datos = [];
    $detalle = $e->getMessage();
    $configuracionSegura = obtenerConfiguracion();
    foreach (['mailrelay_api_key', 'mailrelay_smtp_password', 'smtp_password'] as $campoSecreto) {
        $secreto = (string) ($configuracionSegura[$campoSecreto] ?? '');
        if ($secreto !== '') {
            $detalle = str_replace($secreto, '[oculto]', $detalle);
        }
    }
    registrarError('Envio correo: ' . $detalle);
    if (mailrelayPermiteDiagnostico() || smtpPermiteDiagnostico()) {
        $datos['error_tecnico'] = $detalle;
    }
    responderJson(false, 'No se pudo enviar el correo.', $datos);
}
