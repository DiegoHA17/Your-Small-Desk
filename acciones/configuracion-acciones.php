<?php
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/mailrelay-api.php';
require_once __DIR__ . '/../includes/mailrelay-smtp.php';

if (empty($_SESSION['id_usuario'])) {
    responderJson(false, 'Sesion no valida.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(false, 'Metodo no permitido.');
}

validarCsrf();

$accion = limpiarCadena($_POST['accion'] ?? '');
$conexion = obtenerConexion();

function obtenerUsuarioCuenta($conexion): ?array
{
    $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
    $stmt = $conexion->prepare('SELECT id_usuario, email, password_hash FROM usuarios WHERE id_usuario = ? LIMIT 1');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    return $usuario ?: null;
}

function validarTokenBorradoMasivo(): void
{
    $token = trim((string) ($_POST['token_seguridad'] ?? ''));
    $tokenSesion = (string) ($_SESSION['token_borrado_masivo'] ?? '');
    $expira = (int) ($_SESSION['token_borrado_masivo_expira'] ?? 0);

    if ($token === '' || $tokenSesion === '') {
        responderJson(false, 'Genera e introduce un token de seguridad valido.');
    }
    if ($expira < time()) {
        unset($_SESSION['token_borrado_masivo'], $_SESSION['token_borrado_masivo_expira']);
        responderJson(false, 'El token de seguridad ha caducado. Genera uno nuevo.');
    }
    if (!hash_equals($tokenSesion, $token)) {
        responderJson(false, 'El token de seguridad no es correcto.');
    }
}

function consumirTokenBorradoMasivo(): void
{
    unset($_SESSION['token_borrado_masivo'], $_SESSION['token_borrado_masivo_expira']);
}

function asegurarFilaConfiguracionCorreo($conexion): void
{
    $stmt = $conexion->prepare('SELECT 1 FROM configuracion WHERE id_configuracion = 1 LIMIT 1');
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        return;
    }

    $nombre = 'Your Small Desk';
    $stmtInsert = $conexion->prepare('INSERT INTO configuracion (id_configuracion, nombre_empresa) VALUES (1, ?)');
    $stmtInsert->bind_param('s', $nombre);
    $stmtInsert->execute();
}

function bindValoresConfiguracion($stmt, string $tipos, array &$valores): void
{
    $referencias = [$tipos];
    foreach ($valores as &$valor) {
        $referencias[] = &$valor;
    }
    call_user_func_array([$stmt, 'bind_param'], $referencias);
}

function actualizarConfiguracionCorreo($conexion, array $campos): void
{
    asegurarFilaConfiguracionCorreo($conexion);
    $sets = [];
    $valores = [];
    $tipos = '';
    foreach ($campos as $campo => $valor) {
        $sets[] = $campo . ' = ?';
        $valores[] = $valor;
        $tipos .= is_int($valor) ? 'i' : 's';
    }
    $sql = 'UPDATE configuracion SET ' . implode(', ', $sets) . ' WHERE id_configuracion = 1';
    $stmt = $conexion->prepare($sql);
    bindValoresConfiguracion($stmt, $tipos, $valores);
    $stmt->execute();
}

function validarMailrelayApiUrl(string $apiUrl): void
{
    if ($apiUrl === '' || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
        responderJson(false, 'Indica una URL valida para la API de Mailrelay.');
    }
    $rutaApi = rtrim((string) parse_url($apiUrl, PHP_URL_PATH), '/');
    if (!str_ends_with($rutaApi, '/api/v1/send_emails')) {
        responderJson(false, 'La URL de Mailrelay debe terminar en /api/v1/send_emails.');
    }
}

function limpiarSeguridadSmtp(string $seguridad): string
{
    $seguridad = strtolower(limpiarCadena($seguridad));
    if ($seguridad === 'ninguna') {
        $seguridad = 'none';
    }
    return in_array($seguridad, ['tls', 'ssl', 'none'], true) ? $seguridad : '';
}

function borrarPdfsGenerados(): array
{
    $errores = [];
    $carpetas = [
        __DIR__ . '/../storage/facturas',
        __DIR__ . '/../storage/presupuestos',
    ];

    foreach ($carpetas as $carpeta) {
        if (!is_dir($carpeta)) {
            continue;
        }
        $archivos = glob($carpeta . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
        foreach ($archivos as $archivo) {
            if (is_file($archivo) && strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) === 'pdf' && !@unlink($archivo)) {
                $errores[] = basename($archivo);
            }
        }
    }

    return $errores;
}

if ($accion === 'guardar_metodo_correo') {
    $metodo = strtolower(limpiarCadena($_POST['mailrelay_metodo_envio'] ?? $_POST['metodo_envio_correo'] ?? ''));
    if (!in_array($metodo, ['api', 'smtp'], true)) {
        responderJson(false, 'Selecciona un metodo de envio valido.');
    }

    try {
        actualizarConfiguracionCorreo($conexion, [
            'mailrelay_metodo_envio' => $metodo,
            'mailrelay_metodo_envio_facturas' => $metodo,
        ]);
        responderJson(true, 'Metodo de envio guardado correctamente.');
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudo guardar el metodo de envio.');
    }
}

if ($accion === 'guardar_config_api') {
    $configuracion = obtenerConfiguracionPersistida();
    $apiUrl = limpiarCadena($_POST['mailrelay_api_url'] ?? '');
    $apiKeyNueva = trim((string) ($_POST['mailrelay_api_key'] ?? ''));
    $apiKeyActual = (string) ($configuracion['mailrelay_api_key'] ?? '');
    $apiKey = $apiKeyNueva !== '' ? $apiKeyNueva : $apiKeyActual;
    $fromEmail = limpiarCadena($_POST['mailrelay_from_email'] ?? '');
    $fromName = limpiarCadena($_POST['mailrelay_from_name'] ?? '');

    validarMailrelayApiUrl($apiUrl);
    if ($apiKey === '' || $apiKey === MAILRELAY_API_KEY_DEFAULT) {
        responderJson(false, 'Falta API Key de Mailrelay.');
    }
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'El remitente no es valido.');
    }
    if ($fromName === '') {
        responderJson(false, 'Indica el nombre remitente.');
    }

    try {
        actualizarConfiguracionCorreo($conexion, [
            'mailrelay_api_url' => $apiUrl,
            'mailrelay_api_key' => $apiKey,
            'mailrelay_from_email' => $fromEmail,
            'mailrelay_from_name' => $fromName,
        ]);
        responderJson(true, 'Configuracion API guardada correctamente.', [
            'api_key_configurada' => true,
        ]);
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudo guardar la configuracion API.');
    }
}

if ($accion === 'guardar_config_smtp') {
    $configuracion = obtenerConfiguracionPersistida();
    $host = limpiarCadena($_POST['mailrelay_smtp_host'] ?? '');
    $port = (int) ($_POST['mailrelay_smtp_port'] ?? 0);
    $usuario = limpiarCadena($_POST['mailrelay_smtp_usuario'] ?? '');
    $passwordNueva = trim((string) ($_POST['mailrelay_smtp_password'] ?? ''));
    $passwordActual = (string) ($configuracion['mailrelay_smtp_password'] ?? '');
    $password = $passwordNueva !== '' ? $passwordNueva : $passwordActual;
    $seguridad = limpiarSeguridadSmtp((string) ($_POST['mailrelay_smtp_seguridad'] ?? ''));

    if ($host === '') {
        responderJson(false, 'Falta host SMTP.');
    }
    if ($port <= 0 || $port > 65535) {
        responderJson(false, 'El puerto SMTP no es valido.');
    }
    if ($usuario === '') {
        responderJson(false, 'Falta usuario SMTP.');
    }
    if ($password === '') {
        responderJson(false, 'Falta contrasena SMTP.');
    }
    if ($seguridad === '') {
        responderJson(false, 'La seguridad SMTP seleccionada no es valida.');
    }

    try {
        actualizarConfiguracionCorreo($conexion, [
            'mailrelay_smtp_host' => $host,
            'mailrelay_smtp_port' => $port,
            'mailrelay_smtp_usuario' => $usuario,
            'mailrelay_smtp_password' => $password,
            'mailrelay_smtp_seguridad' => $seguridad,
        ]);
        responderJson(true, 'Configuracion SMTP guardada correctamente.', [
            'smtp_password_configurada' => true,
        ]);
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudo guardar la configuracion SMTP.');
    }
}

if ($accion === 'guardar_config_bcc') {
    $bccActivo = isset($_POST['mailrelay_bcc_activo']) ? 1 : 0;
    $bccEmail = limpiarCadena($_POST['mailrelay_bcc_email'] ?? '');
    if ($bccActivo === 1 && !filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'Indica un email valido para la copia oculta.');
    }

    try {
        actualizarConfiguracionCorreo($conexion, [
            'mailrelay_bcc_activo' => $bccActivo,
            'mailrelay_bcc_email' => $bccEmail,
        ]);
        responderJson(true, 'Configuracion BCC guardada correctamente.');
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudo guardar la configuracion BCC.');
    }
}

if ($accion === 'probar_mailrelay_api') {
    $destinatario = limpiarCadena($_POST['destinatario'] ?? '');
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'El email de prueba no es valido.');
    }
    $resultado = enviarCorreoSimpleMailrelayApi(
        $destinatario,
        'Prueba Mailrelay API - Your Small Desk',
        'Prueba de envio por API desde Your Small Desk.'
    );
    unset($resultado['datos']['respuesta_registro'], $resultado['datos']['error_registro']);
    responderJson($resultado['ok'], $resultado['mensaje'], $resultado['datos'] ?? []);
}

if ($accion === 'probar_mailrelay_smtp') {
    $destinatario = limpiarCadena($_POST['destinatario'] ?? '');
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'El email de prueba SMTP no es valido.');
    }
    $resultado = enviarCorreoSimpleMailrelaySmtp(
        $destinatario,
        'Prueba SMTP Your Small Desk',
        'Prueba de envio SMTP desde Your Small Desk.'
    );
    responderJson($resultado['ok'], $resultado['mensaje'], $resultado['datos'] ?? []);
}

if ($accion === 'cambiar_correo_cuenta') {
    $emailNuevo = strtolower(limpiarCadena($_POST['nuevo_email'] ?? ''));
    $emailRepetido = strtolower(limpiarCadena($_POST['repetir_email'] ?? ''));
    $passwordActual = (string) ($_POST['password_actual'] ?? '');

    if ($emailNuevo === '' || !filter_var($emailNuevo, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'Indica un correo valido.');
    }
    if ($emailNuevo !== $emailRepetido) {
        responderJson(false, 'Los correos no coinciden.');
    }
    $usuario = obtenerUsuarioCuenta($conexion);
    if (!$usuario || !password_verify($passwordActual, (string) $usuario['password_hash'])) {
        responderJson(false, 'La contrasena actual no es correcta.');
    }

    $idUsuario = (int) $usuario['id_usuario'];
    $stmtDuplicado = $conexion->prepare('SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario <> ? LIMIT 1');
    $stmtDuplicado->bind_param('si', $emailNuevo, $idUsuario);
    $stmtDuplicado->execute();
    if ($stmtDuplicado->get_result()->fetch_assoc()) {
        responderJson(false, 'Ese correo ya esta en uso.');
    }

    try {
        $stmt = $conexion->prepare('UPDATE usuarios SET email = ? WHERE id_usuario = ?');
        $stmt->bind_param('si', $emailNuevo, $idUsuario);
        $stmt->execute();
        $_SESSION['email'] = $emailNuevo;
        responderJson(true, 'Correo actualizado correctamente.');
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudo actualizar el correo.');
    }
}

if ($accion === 'cambiar_password_cuenta') {
    $passwordActual = (string) ($_POST['password_actual'] ?? '');
    $passwordNueva = (string) ($_POST['password_nueva'] ?? '');
    $passwordRepetida = (string) ($_POST['password_repetida'] ?? '');

    $usuario = obtenerUsuarioCuenta($conexion);
    if (!$usuario || !password_verify($passwordActual, (string) $usuario['password_hash'])) {
        responderJson(false, 'La contrasena actual no es correcta.');
    }
    if ($passwordNueva !== $passwordRepetida) {
        responderJson(false, 'Las nuevas contrasenas no coinciden.');
    }
    if (strlen($passwordNueva) < 8) {
        responderJson(false, 'La nueva contrasena debe tener al menos 8 caracteres.');
    }

    try {
        $hash = password_hash($passwordNueva, PASSWORD_DEFAULT);
        $idUsuario = (int) $usuario['id_usuario'];
        $stmt = $conexion->prepare('UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?');
        $stmt->bind_param('si', $hash, $idUsuario);
        $stmt->execute();
        session_regenerate_id(true);
        responderJson(true, 'Contrasena actualizada correctamente.');
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudo actualizar la contrasena.');
    }
}

if ($accion === 'generar_token_seguridad') {
    $token = 'JJH-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['token_borrado_masivo'] = $token;
    $_SESSION['token_borrado_masivo_expira'] = time() + 300;
    responderJson(true, 'Token de seguridad generado.', [
        'token' => $token,
        'caduca_en_segundos' => 300,
    ]);
}

if ($accion === 'eliminar_todos_clientes') {
    validarTokenBorradoMasivo();

    $stmtFacturas = $conexion->prepare('SELECT COUNT(*) AS total FROM facturas');
    $stmtFacturas->execute();
    if ((int) $stmtFacturas->get_result()->fetch_assoc()['total'] > 0) {
        responderJson(false, 'No se pueden eliminar todos los clientes porque existen presupuestos asociados. Elimina primero los presupuestos.');
    }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare('DELETE FROM clientes');
        $stmt->execute();
        $eliminados = $stmt->affected_rows;
        $conexion->commit();
        consumirTokenBorradoMasivo();
        responderJson(true, 'Clientes eliminados correctamente.', ['eliminados' => $eliminados]);
    } catch (Throwable $e) {
        $conexion->rollback();
        registrarError($e);
        responderJson(false, 'No se pudieron eliminar los clientes.');
    }
}

if ($accion === 'eliminar_todos_presupuestos') {
    validarTokenBorradoMasivo();

    $conexion->begin_transaction();
    try {
        $stmtEnvios = $conexion->prepare('DELETE FROM envios WHERE id_factura IS NOT NULL');
        $stmtEnvios->execute();
        $stmtLineas = $conexion->prepare('DELETE FROM factura_lineas');
        $stmtLineas->execute();
        $stmtFacturas = $conexion->prepare('DELETE FROM facturas');
        $stmtFacturas->execute();
        $eliminados = $stmtFacturas->affected_rows;
        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        registrarError($e);
        responderJson(false, 'No se pudieron eliminar los presupuestos.');
    }

    $archivosNoEliminados = borrarPdfsGenerados();
    consumirTokenBorradoMasivo();
    if ($archivosNoEliminados) {
        responderJson(true, 'Presupuestos eliminados. Algunos PDF no se pudieron borrar del almacenamiento.', [
            'eliminados' => $eliminados,
            'pdf_no_eliminados' => count($archivosNoEliminados),
        ]);
    }
    responderJson(true, 'Presupuestos, envios y PDFs eliminados correctamente.', ['eliminados' => $eliminados]);
}

responderJson(false, 'Accion no valida.');
