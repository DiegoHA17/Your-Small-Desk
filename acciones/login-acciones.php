<?php
require_once __DIR__ . '/../includes/seguridad.php';

$accion = $_POST['accion'] ?? 'login';
if ($accion !== 'login') {
    responderJson(false, 'Accion no valida.');
}

exigirMetodoPost();
validarCsrf();

$email = limpiarCadena($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    responderJson(false, 'Introduce email y contraseña.');
}

$conexion = obtenerConexion();
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$stmtIntentos = $conexion->prepare(
    'SELECT COUNT(*) AS total
     FROM intentos_login
     WHERE email = ? AND ip = ? AND correcto = 0 AND fecha_intento >= (NOW() - INTERVAL 15 MINUTE)'
);
$stmtIntentos->bind_param('ss', $email, $ip);
$stmtIntentos->execute();
$fallosRecientes = (int) $stmtIntentos->get_result()->fetch_assoc()['total'];

if ($fallosRecientes >= 5) {
    responderJson(false, 'No se pudo iniciar sesion.');
}

$stmt = $conexion->prepare('SELECT id_usuario, nombre, email, password_hash FROM usuarios WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
    $correcto = 0;
    $stmtLog = $conexion->prepare('INSERT INTO intentos_login (email, ip, correcto) VALUES (?, ?, ?)');
    $stmtLog->bind_param('ssi', $email, $ip, $correcto);
    $stmtLog->execute();
    responderJson(false, 'No se pudo iniciar sesion.');
}

session_regenerate_id(true);
$_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['email'] = $usuario['email'];
$_SESSION['csrf_token'] = generarTokenSeguro(64);

$correcto = 1;
$stmtLog = $conexion->prepare('INSERT INTO intentos_login (email, ip, correcto) VALUES (?, ?, ?)');
$stmtLog->bind_param('ssi', $email, $ip, $correcto);
$stmtLog->execute();

$stmtUltimo = $conexion->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id_usuario = ?');
$stmtUltimo->bind_param('i', $_SESSION['id_usuario']);
$stmtUltimo->execute();

responderJson(true, 'Sesion iniciada.');
