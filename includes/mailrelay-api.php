<?php
require_once __DIR__ . '/pdf.php';

const FORMATO_ADJUNTO_MAILRELAY = 'a';

function obtenerConfiguracionMailrelay(): array
{
    $configuracion = obtenerConfiguracion();

    return [
        'mailrelay_api_url' => trim((string) ($configuracion['mailrelay_api_url'] ?? MAILRELAY_API_URL_DEFAULT)),
        'mailrelay_api_key' => trim((string) ($configuracion['mailrelay_api_key'] ?? MAILRELAY_API_KEY_DEFAULT)),
        'mailrelay_from_email' => trim((string) ($configuracion['mailrelay_from_email'] ?? MAILRELAY_FROM_EMAIL_DEFAULT)),
        'mailrelay_from_name' => trim((string) ($configuracion['mailrelay_from_name'] ?? MAILRELAY_FROM_NAME_DEFAULT)),
        'mailrelay_bcc_email' => trim((string) ($configuracion['mailrelay_bcc_email'] ?? '')),
        'mailrelay_bcc_activo' => (int) ($configuracion['mailrelay_bcc_activo'] ?? 0) === 1,
    ];
}

function mailrelayPermiteDiagnostico(): bool
{
    return DEBUG_APP && in_array(APP_ENV, ['local', 'dev', 'development'], true);
}

function obtenerEstadoConfiguracionMailrelay(): array
{
    $configuracion = obtenerConfiguracionMailrelay();
    $apiUrl = $configuracion['mailrelay_api_url'];
    $rutaApi = rtrim((string) parse_url($apiUrl, PHP_URL_PATH), '/');

    return [
        'origen' => 'base_datos',
        'url_configurada' => $apiUrl !== '' && $apiUrl !== MAILRELAY_API_URL_DEFAULT,
        'endpoint_valido' => str_ends_with($rutaApi, '/api/v1/send_emails'),
        'endpoint_usado' => $apiUrl !== MAILRELAY_API_URL_DEFAULT ? $apiUrl : '',
        'api_key_configurada' => $configuracion['mailrelay_api_key'] !== ''
            && $configuracion['mailrelay_api_key'] !== MAILRELAY_API_KEY_DEFAULT,
        'from_email_configurado' => filter_var($configuracion['mailrelay_from_email'], FILTER_VALIDATE_EMAIL) !== false,
        'from_name_configurado' => $configuracion['mailrelay_from_name'] !== '',
    ];
}

function errorMailrelay(string $mensaje, array $datos = []): array
{
    return [
        'ok' => false,
        'mensaje' => $mensaje,
        'datos' => $datos,
    ];
}

function validarDatosEnvioMailrelay(string $destinatario, string $asunto, string $mensaje): ?array
{
    $configuracion = obtenerConfiguracionMailrelay();
    $apiUrl = $configuracion['mailrelay_api_url'];
    $apiKey = $configuracion['mailrelay_api_key'];
    $emailRemitente = $configuracion['mailrelay_from_email'];
    $nombreRemitente = $configuracion['mailrelay_from_name'];

    if ($apiUrl === '' || $apiUrl === MAILRELAY_API_URL_DEFAULT) {
        return errorMailrelay('Falta la URL de la API de Mailrelay.');
    }
    if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
        return errorMailrelay('La URL de la API de Mailrelay no es valida.');
    }

    $rutaApi = rtrim((string) parse_url($apiUrl, PHP_URL_PATH), '/');
    if (!str_ends_with($rutaApi, '/api/v1/send_emails')) {
        return errorMailrelay('La URL de Mailrelay debe terminar en /api/v1/send_emails.');
    }
    if ($apiKey === '' || $apiKey === MAILRELAY_API_KEY_DEFAULT) {
        return errorMailrelay('Falta la API key de Mailrelay.');
    }
    if (!filter_var($emailRemitente, FILTER_VALIDATE_EMAIL)) {
        return errorMailrelay('El email remitente no es valido.');
    }
    if ($nombreRemitente === '') {
        return errorMailrelay('Falta el nombre remitente de Mailrelay.');
    }
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        return errorMailrelay('El email destinatario no es valido.');
    }
    if ($asunto === '' || mb_strlen($asunto) > 180) {
        return errorMailrelay('El asunto es obligatorio y no puede superar 180 caracteres.');
    }
    if ($mensaje === '' || mb_strlen($mensaje) > 5000) {
        return errorMailrelay('El mensaje es obligatorio y no puede superar 5000 caracteres.');
    }

    return null;
}

function construirDatosAdjuntoMailrelay(string $contenidoArchivo, string $nombreArchivo, string $mime, string $variante = 'a'): array
{
    $base64 = base64_encode($contenidoArchivo);
    if ($base64 === '' || base64_decode($base64, true) !== $contenidoArchivo) {
        throw new RuntimeException('No se pudo codificar el archivo adjunto.');
    }

    $adjunto = [
        'content' => $base64,
        'file_name' => $nombreArchivo,
        'content_type' => $mime,
    ];
    if ($variante !== 'b') {
        $adjunto['content_id'] = '';
    }

    return $adjunto;
}

function construirAdjuntoMailrelay(string $rutaArchivo, string $nombreArchivo, string $mime, string $variante = 'a'): array
{
    if (!file_exists($rutaArchivo) || !is_file($rutaArchivo) || !is_readable($rutaArchivo)) {
        throw new RuntimeException('No se pudo leer el archivo adjunto.');
    }

    $tamanoArchivo = filesize($rutaArchivo);
    if ($tamanoArchivo === false || $tamanoArchivo <= 0) {
        throw new RuntimeException('El archivo adjunto esta vacio.');
    }
    if ($tamanoArchivo >= 10 * 1024 * 1024) {
        throw new RuntimeException('El archivo adjunto supera el limite de 10 MB.');
    }

    $contenidoArchivo = file_get_contents($rutaArchivo);
    if ($contenidoArchivo === false) {
        throw new RuntimeException('No se pudo leer el archivo adjunto.');
    }
    if ($mime === 'application/pdf' && substr($contenidoArchivo, 0, 4) !== '%PDF') {
        throw new RuntimeException('El archivo generado no parece ser un PDF valido.');
    }

    return construirDatosAdjuntoMailrelay($contenidoArchivo, $nombreArchivo, $mime, $variante);
}

function construirPayloadMailrelay(
    string $destinatario,
    string $asunto,
    string $mensaje,
    ?string $rutaArchivo = null,
    ?string $nombreArchivo = null,
    string $mime = 'application/pdf',
    string $varianteAdjunto = FORMATO_ADJUNTO_MAILRELAY,
    ?string $contenidoAdjunto = null,
    bool $esPruebaAdjunto = false
): array {
    $configuracion = obtenerConfiguracionMailrelay();
    $payload = [
        'from' => [
            'email' => $configuracion['mailrelay_from_email'],
            'name' => $configuracion['mailrelay_from_name'],
        ],
        'to' => [
            [
                'email' => $destinatario,
            ],
        ],
        'subject' => $asunto,
        'html_part' => nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8')),
        'text_part' => $mensaje,
    ];

    if ($esPruebaAdjunto) {
        $payload['to'][0]['name'] = 'Prueba';
        $payload['html_part'] = '<html><body><p>Prueba con adjunto TXT.</p></body></html>';
        $payload['text_part'] = 'Prueba con adjunto TXT.';
        if ($varianteAdjunto !== 'c') {
            $payload['text_part_auto'] = false;
        }
    }

    if ($rutaArchivo !== null || $contenidoAdjunto !== null) {
        $payload['attachments'] = [
            $rutaArchivo !== null
                ? construirAdjuntoMailrelay($rutaArchivo, $nombreArchivo ?: basename($rutaArchivo), $mime, $varianteAdjunto)
                : construirDatosAdjuntoMailrelay((string) $contenidoAdjunto, (string) $nombreArchivo, $mime, $varianteAdjunto),
        ];
    }

    return $payload;
}

function construirPayloadPruebaSimpleMailrelay(string $destinatario): array
{
    $configuracion = obtenerConfiguracionMailrelay();

    return [
        'from' => [
            'email' => $configuracion['mailrelay_from_email'],
            'name' => $configuracion['mailrelay_from_name'],
        ],
        'to' => [
            [
                'email' => $destinatario,
            ],
        ],
        'subject' => 'Prueba Mailrelay Your Small Desk',
        'html_part' => '<html><body><p>Prueba de envio desde Your Small Desk.</p></body></html>',
        'text_part' => 'Prueba de envio desde Your Small Desk.',
    ];
}

function depurarPayloadMailrelay(array $payload): array
{
    $adjunto = $payload['attachments'][0] ?? null;
    $contenidoCodificado = is_array($adjunto) ? ($adjunto['content'] ?? null) : null;
    $nombreAdjunto = is_array($adjunto) ? ($adjunto['file_name'] ?? null) : null;
    $mime = is_array($adjunto) ? ($adjunto['content_type'] ?? null) : null;
    $tamanoAdjunto = null;
    $base64Valido = null;
    $empiezaPorPdf = null;

    if (is_string($contenidoCodificado) && $contenidoCodificado !== '') {
        $contenido = base64_decode($contenidoCodificado, true);
        $base64Valido = $contenido !== false;
        $tamanoAdjunto = $contenido === false ? null : strlen($contenido);
        if ($mime === 'application/pdf') {
            $empiezaPorPdf = $contenido !== false && substr($contenido, 0, 4) === '%PDF';
        }
    }

    return [
        'from_email' => $payload['from']['email'] ?? '',
        'destinatario' => $payload['to'][0]['email'] ?? '',
        'asunto' => $payload['subject'] ?? '',
        'tiene_adjunto' => is_array($adjunto),
        'nombre_adjunto' => $nombreAdjunto,
        'mime' => $mime,
        'tamano_bytes' => $tamanoAdjunto,
        'base64_length' => is_string($contenidoCodificado) ? strlen($contenidoCodificado) : null,
        'base64_valido' => $base64Valido,
        'pdf_valido' => $empiezaPorPdf,
        'tiene_bcc' => isset($payload['bcc']),
        'formato_adjunto' => is_array($adjunto)
            ? (array_key_exists('content_id', $adjunto) ? 'con_content_id' : 'sin_content_id')
            : null,
    ];
}

function ejecutarPeticionMailrelay(array $payload): array
{
    $configuracion = obtenerConfiguracionMailrelay();
    $apiUrl = $configuracion['mailrelay_api_url'];
    $apiKey = $configuracion['mailrelay_api_key'];

    if (!function_exists('curl_init')) {
        return errorMailrelay('La extension cURL de PHP no esta activa.', [
            'http_code' => 0,
            'error' => 'La extension cURL de PHP no esta activa.',
        ]);
    }

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        return errorMailrelay('No se pudo preparar el JSON para Mailrelay.', [
            'http_code' => 0,
            'json_error' => json_last_error_msg(),
        ]);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => [
            'X-AUTH-TOKEN: ' . $apiKey,
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_TIMEOUT => 30,
    ]);

    $respuestaRaw = curl_exec($ch);
    $errorCurl = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $respuestaRaw = is_string($respuestaRaw) ? str_replace($apiKey, '[oculto]', $respuestaRaw) : '';
    $errorCurl = str_replace($apiKey, '[oculto]', $errorCurl);
    $respuestaJson = json_decode($respuestaRaw, true);
    $ok = $errorCurl === '' && $httpCode >= 200 && $httpCode < 300;
    $respuestaMinuscula = strtolower($respuestaRaw);
    $cuentaEnRevision = str_contains($respuestaMinuscula, 'account is currently under review')
        || str_contains($respuestaMinuscula, 'account under review');

    $datos = [
        'http_code' => $httpCode,
        'curl_error' => $errorCurl,
        'endpoint_usado' => $apiUrl,
        'api_key_configurada' => $apiKey !== '' && $apiKey !== MAILRELAY_API_KEY_DEFAULT,
        'respuesta_json' => is_array($respuestaJson) ? $respuestaJson : null,
        'cuenta_en_revision' => $cuentaEnRevision,
        'respuesta_registro' => $respuestaRaw,
        'error_registro' => trim(
            ($httpCode > 0 ? 'HTTP ' . $httpCode : '')
            . ($errorCurl !== '' ? ' | cURL: ' . $errorCurl : '')
            . ($respuestaRaw !== '' ? ' | Mailrelay: ' . $respuestaRaw : '')
        ),
    ];

    if (mailrelayPermiteDiagnostico()) {
        $datos['curl_error'] = $errorCurl;
        $datos['respuesta_raw'] = $respuestaRaw;
        $datos['respuesta_json'] = is_array($respuestaJson) ? $respuestaJson : null;
        $datos['endpoint_usado'] = $apiUrl;
        $datos['configuracion_debug'] = [
            'api_url' => $apiUrl,
            'api_key_configurada' => $apiKey !== '' && $apiKey !== MAILRELAY_API_KEY_DEFAULT,
            'from_email' => $configuracion['mailrelay_from_email'],
            'from_name' => $configuracion['mailrelay_from_name'],
        ];
        $datos['payload_debug'] = depurarPayloadMailrelay($payload);
        $payloadDepurado = $payload;
        if (isset($payloadDepurado['attachments'][0]['content'])) {
            $payloadDepurado['attachments'][0]['content'] = '[BASE64 omitido: '
                . strlen((string) $payload['attachments'][0]['content'])
                . ' caracteres]';
        }
        $datos['json_payload'] = $payloadDepurado;
    } else {
        $datos['error'] = $ok ? null : 'Mailrelay ha rechazado o no ha procesado el envio.';
    }

    $mensaje = 'Correo enviado correctamente.';
    if ($errorCurl !== '') {
        $mensaje = 'Error cURL al conectar con Mailrelay.';
    } elseif ($cuentaEnRevision) {
        $mensaje = 'La cuenta de Mailrelay esta en revision. La API HTTPS conecta, pero Mailrelay todavia no permite enviar.';
    } elseif (!$ok) {
        $mensaje = 'Mailrelay ha rechazado el envio.';
    }

    if ($ok) {
        $datos['respuesta'] = is_array($respuestaJson) ? $respuestaJson : $respuestaRaw;
    }

    return [
        'ok' => $ok,
        'mensaje' => $mensaje,
        'datos' => $datos,
    ];
}

function registrarEnvioMailrelayApi(?int $idFactura, string $destinatario, string $asunto, string $mensaje, array $resultado): void
{
    $estado = $resultado['ok'] ? 'enviado' : 'error';
    $respuestaError = $resultado['ok'] ? null : (string) ($resultado['datos']['error_registro'] ?? $resultado['mensaje']);
    $respuestaApi = (string) ($resultado['datos']['respuesta_registro'] ?? '');
    $respuestaApi = $respuestaApi !== '' ? $respuestaApi : null;
    $metodo = 'api';
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare(
        'INSERT INTO envios (id_factura, destinatario, bcc_email, asunto, mensaje, estado, metodo, respuesta_error, respuesta_api)
         VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssssss', $idFactura, $destinatario, $asunto, $mensaje, $estado, $metodo, $respuestaError, $respuestaApi);
    $stmt->execute();
}

function enviarCorreoSimpleMailrelayApi(string $destinatario, string $asunto, string $mensaje): array
{
    $asuntoPrueba = 'Prueba Mailrelay Your Small Desk';
    $mensajePrueba = 'Prueba de envio desde Your Small Desk.';
    $error = validarDatosEnvioMailrelay($destinatario, $asuntoPrueba, $mensajePrueba);
    if ($error !== null) {
        return $error;
    }

    $resultado = ejecutarPeticionMailrelay(construirPayloadPruebaSimpleMailrelay($destinatario));
    registrarEnvioMailrelayApi(null, $destinatario, $asuntoPrueba, $mensajePrueba, $resultado);
    if ($resultado['ok']) {
        $resultado['mensaje'] = 'Correo de prueba enviado correctamente.';
    }
    unset($resultado['datos']['respuesta_registro'], $resultado['datos']['error_registro']);
    return $resultado;
}

function probarMailrelayConAdjuntoTxt(string $destinatario, string $variante = 'a'): array
{
    if (!in_array($variante, ['a', 'b', 'c'], true)) {
        return errorMailrelay('La variante de adjunto no es valida.');
    }

    $asunto = 'Prueba adjunto TXT';
    $mensaje = 'Prueba con adjunto TXT.';
    $error = validarDatosEnvioMailrelay($destinatario, $asunto, $mensaje);
    if ($error !== null) {
        return $error;
    }

    try {
        $payload = construirPayloadMailrelay(
            $destinatario,
            $asunto,
            $mensaje,
            null,
            'prueba.txt',
            'text/plain',
            $variante,
            'Prueba de adjunto desde Your Small Desk',
            true
        );
        $resultado = ejecutarPeticionMailrelay($payload);
        registrarEnvioMailrelayApi(null, $destinatario, $asunto, $mensaje, $resultado);
        unset($resultado['datos']['respuesta_registro'], $resultado['datos']['error_registro']);
        return $resultado;
    } catch (Throwable $e) {
        return errorMailrelay($e->getMessage());
    }
}

function enviarCorreoMailrelayApi(
    string $destinatario,
    string $asunto,
    string $mensaje,
    string $rutaPdf,
    string $nombrePdf
): array {
    $error = validarDatosEnvioMailrelay($destinatario, $asunto, $mensaje);
    if ($error !== null) {
        return $error;
    }

    try {
        $payload = construirPayloadMailrelay($destinatario, $asunto, $mensaje, $rutaPdf, $nombrePdf, 'application/pdf');
    } catch (Throwable $e) {
        return errorMailrelay($e->getMessage());
    }

    return ejecutarPeticionMailrelay($payload);
}

function enviarFacturaPorMailrelayApi(int $idFactura, string $destinatario, string $asunto, string $mensaje): array
{
    if ($idFactura <= 0) {
        return errorMailrelay('Presupuesto no valido.');
    }

    $configuracion = obtenerConfiguracionMailrelay();
    if ($configuracion['mailrelay_bcc_activo']) {
        return errorMailrelay(
            'La copia oculta real solo esta disponible usando SMTP en esta configuracion.',
            [
                'cliente_enviado' => false,
                'bcc_enviado' => false,
                'metodo' => 'api',
            ]
        );
    }

    $error = validarDatosEnvioMailrelay($destinatario, $asunto, $mensaje);
    if ($error !== null) {
        return $error;
    }

    $datosFactura = obtenerDatosFactura($idFactura);
    if (!$datosFactura) {
        return errorMailrelay('Presupuesto no encontrado.');
    }

    try {
        $pdf = generarPdfFactura($idFactura);
    } catch (Throwable $e) {
        return errorMailrelay('No se pudo generar el PDF del presupuesto.');
    }

    $resultado = enviarCorreoMailrelayApi($destinatario, $asunto, $mensaje, $pdf['ruta_absoluta'], $pdf['nombre_pdf']);
    registrarEnvioMailrelayApi($idFactura, $destinatario, $asunto, $mensaje, $resultado);

    if ($resultado['ok']) {
        $conexion = obtenerConexion();
        $nuevoEstado = 'enviada';
        $stmtEstado = $conexion->prepare('UPDATE facturas SET estado = ? WHERE id_factura = ?');
        $stmtEstado->bind_param('si', $nuevoEstado, $idFactura);
        $stmtEstado->execute();
    }

    unset($resultado['datos']['respuesta_registro'], $resultado['datos']['error_registro']);
    return $resultado;
}
