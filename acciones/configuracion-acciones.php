<?php
require_once __DIR__ . '/../includes/seguridad.php';

if (empty($_SESSION['id_usuario'])) {
    responderJson(false, 'Sesion no valida.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(false, 'Metodo no permitido.');
}

validarCsrf();

$accion = limpiarCadena($_POST['accion'] ?? '');
$conexion = obtenerConexion();

function obtenerUsuarioCuenta(mysqli $conexion): ?array
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
