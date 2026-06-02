<?php
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/logo-documento.php';

exigirMetodoPost();
validarCsrf();

if (setupCompletado()) {
    responderJson(false, 'La configuracion inicial ya esta completada.');
}

$accion = limpiarCadena($_POST['accion'] ?? '');
if ($accion !== 'guardar_setup_inicial') {
    responderJson(false, 'Accion no valida.');
}

$nombreComercial = limpiarCadena($_POST['nombre_comercial'] ?? '');
$nombreFiscal = limpiarCadena($_POST['nombre_fiscal'] ?? '');
$nifCif = limpiarCadena($_POST['nif_cif_empresa'] ?? '');
$emailEmpresa = limpiarCadena($_POST['email_empresa'] ?? '');
$telefonoEmpresa = limpiarCadena($_POST['telefono_empresa'] ?? '');
$direccionEmpresa = limpiarCadena($_POST['direccion_empresa'] ?? '');
$ciudadEmpresa = limpiarCadena($_POST['ciudad_empresa'] ?? '');
$provinciaEmpresa = limpiarCadena($_POST['provincia_empresa'] ?? '');
$codigoPostalEmpresa = limpiarCadena($_POST['codigo_postal_empresa'] ?? '');
$paisEmpresa = limpiarCadena($_POST['pais_empresa'] ?? '');
$webEmpresa = limpiarCadena($_POST['web_empresa'] ?? '');
$adminNombre = limpiarCadena($_POST['admin_nombre'] ?? '');
$adminEmail = strtolower(limpiarCadena($_POST['admin_email'] ?? ''));
$password = (string) ($_POST['admin_password'] ?? '');
$passwordRepetida = (string) ($_POST['admin_password_repetir'] ?? '');

if ($nombreComercial === '') {
    responderJson(false, 'Indica el nombre comercial de la empresa.');
}
if ($emailEmpresa !== '' && !filter_var($emailEmpresa, FILTER_VALIDATE_EMAIL)) {
    responderJson(false, 'Indica un email de empresa valido.');
}
if ($webEmpresa !== '' && !filter_var($webEmpresa, FILTER_VALIDATE_URL)) {
    responderJson(false, 'Indica una web valida o deja el campo vacio.');
}
if ($adminNombre === '') {
    responderJson(false, 'Indica el nombre del administrador.');
}
if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    responderJson(false, 'Indica un email de acceso valido.');
}
if ($password !== $passwordRepetida) {
    responderJson(false, 'Las contrasenas no coinciden.');
}
if (strlen($password) < 8) {
    responderJson(false, 'La contrasena debe tener al menos 8 caracteres.');
}

$conexion = obtenerConexion();

try {
    $logoDocumento = guardarLogoDocumentoSubido($_FILES['logo_documento'] ?? []);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $conexion->begin_transaction();

    $stmtUsuario = $conexion->prepare('SELECT id_usuario FROM usuarios ORDER BY id_usuario ASC LIMIT 1');
    $stmtUsuario->execute();
    $usuarioActual = $stmtUsuario->get_result()->fetch_assoc();

    if ($usuarioActual) {
        $idUsuario = (int) $usuarioActual['id_usuario'];
        $stmtDuplicado = $conexion->prepare('SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario <> ? LIMIT 1');
        $stmtDuplicado->bind_param('si', $adminEmail, $idUsuario);
        $stmtDuplicado->execute();
        if ($stmtDuplicado->get_result()->fetch_assoc()) {
            $conexion->rollback();
            responderJson(false, 'Ese email ya esta usado por otro usuario.');
        }

        $stmtActualizarUsuario = $conexion->prepare('UPDATE usuarios SET nombre = ?, email = ?, password_hash = ? WHERE id_usuario = ?');
        $stmtActualizarUsuario->bind_param('sssi', $adminNombre, $adminEmail, $hash, $idUsuario);
        $stmtActualizarUsuario->execute();
    } else {
        $stmtInsertarUsuario = $conexion->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)');
        $stmtInsertarUsuario->bind_param('sss', $adminNombre, $adminEmail, $hash);
        $stmtInsertarUsuario->execute();
    }

    $stmtConfig = $conexion->prepare('SELECT 1 FROM configuracion WHERE id_configuracion = 1 LIMIT 1');
    $stmtConfig->execute();
    $existeConfig = (bool) $stmtConfig->get_result()->fetch_assoc();

    if ($existeConfig) {
        $stmtGuardarConfig = $conexion->prepare(
            'UPDATE configuracion SET
             nombre_empresa = ?, nombre_comercial = ?, nombre_fiscal = ?, nif_cif_empresa = ?,
             email_empresa = ?, telefono_empresa = ?, direccion_empresa = ?, ciudad_empresa = ?,
             provincia_empresa = ?, codigo_postal_empresa = ?, pais_empresa = ?, web_empresa = ?,
             logo_documento = ?, setup_completado = 1
             WHERE id_configuracion = 1'
        );
    } else {
        $stmtGuardarConfig = $conexion->prepare(
            'INSERT INTO configuracion
             (id_configuracion, nombre_empresa, nombre_comercial, nombre_fiscal, nif_cif_empresa,
              email_empresa, telefono_empresa, direccion_empresa, ciudad_empresa, provincia_empresa,
              codigo_postal_empresa, pais_empresa, web_empresa, logo_documento, setup_completado)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
    }

    $stmtGuardarConfig->bind_param(
        'sssssssssssss',
        $nombreComercial,
        $nombreComercial,
        $nombreFiscal,
        $nifCif,
        $emailEmpresa,
        $telefonoEmpresa,
        $direccionEmpresa,
        $ciudadEmpresa,
        $provinciaEmpresa,
        $codigoPostalEmpresa,
        $paisEmpresa,
        $webEmpresa,
        $logoDocumento
    );
    $stmtGuardarConfig->execute();

    $conexion->commit();
    responderJson(true, 'Configuracion inicial completada.', [
        'redirect' => 'login.php',
        'logo_cargado' => $logoDocumento !== '',
    ]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        try {
            $conexion->rollback();
        } catch (Throwable) {
        }
    }
    registrarError($e);
    responderJson(false, $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo completar la configuracion inicial.');
}
