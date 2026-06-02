<?php
require_once __DIR__ . '/conexion.php';

function solicitudHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $cookieSegura = getenv('SESSION_SECURE') !== false
        ? valorBooleanoEntorno('SESSION_SECURE', solicitudHttps())
        : solicitudHttps();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $cookieSegura,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    header('Content-Type: text/html; charset=UTF-8');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store, private');
    if (esProduccion() && solicitudHttps()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

iniciarSesionSegura();

function exigirSesion(): void
{
    if (!setupCompletado()) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($script, '/acciones/') !== false) {
            responderJson(false, 'Completa la configuracion inicial antes de continuar.');
        }
        header('Location: setup.php');
        exit;
    }

    if (empty($_SESSION['id_usuario'])) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($script, '/acciones/') !== false) {
            responderJson(false, 'La sesion ha caducado. Vuelve a iniciar sesion.');
        }
        $login = strpos($script, '/acciones/') !== false ? '../login.php' : 'login.php';
        header('Location: ' . $login);
        exit;
    }

    $limiteInactividad = (int) valorEntorno('SESSION_IDLE_TIMEOUT', esProduccion() ? '1800' : '7200');
    $ultimaActividad = (int) ($_SESSION['ultima_actividad'] ?? time());
    if ($limiteInactividad > 0 && time() - $ultimaActividad > $limiteInactividad) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($script, '/acciones/') !== false) {
            responderJson(false, 'La sesion ha caducado. Vuelve a iniciar sesion.');
        }
        $login = strpos($script, '/acciones/') !== false ? '../login.php' : 'login.php';
        header('Location: ' . $login);
        exit;
    }

    $_SESSION['ultima_actividad'] = time();
}

function limpiarCadena(?string $valor): string
{
    return trim(strip_tags((string) $valor));
}

function responderJson(bool $ok, string $mensaje, array $datos = []): void
{
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode([
        'ok' => $ok,
        'mensaje' => $mensaje,
        'datos' => $datos,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function generarTokenSeguro(int $longitud = 32): string
{
    return bin2hex(random_bytes((int) ceil($longitud / 2)));
}

function obtenerCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generarTokenSeguro(64);
    }

    return $_SESSION['csrf_token'];
}

function validarCsrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $tokenSesion = (string) ($_SESSION['csrf_token'] ?? '');
    if ($tokenSesion === '' || $token === '' || !hash_equals($tokenSesion, (string) $token)) {
        responderJson(false, 'Solicitud no valida.');
    }
}

function exigirMetodoPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        responderJson(false, 'Metodo no permitido.');
    }
}
