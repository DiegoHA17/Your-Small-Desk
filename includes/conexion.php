<?php

function valorEntorno(string $nombre, string $porDefecto = ''): string
{
    $valor = getenv($nombre);
    return $valor === false || $valor === '' ? $porDefecto : (string) $valor;
}

function valorBooleanoEntorno(string $nombre, bool $porDefecto): bool
{
    $valor = getenv($nombre);
    if ($valor === false || trim((string) $valor) === '') {
        return $porDefecto;
    }

    $normalizado = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $normalizado ?? $porDefecto;
}

define('APP_ENV', strtolower(valorEntorno('APP_ENV', 'production')));
define('APP_URL', rtrim(valorEntorno('APP_URL', ''), '/'));
define('DEBUG_APP', valorBooleanoEntorno('DEBUG', APP_ENV !== 'production'));

function esProduccion(): bool
{
    return APP_ENV === 'production';
}

function registrarError(Throwable|string $error): void
{
    $mensaje = $error instanceof Throwable ? $error->getMessage() : $error;
    error_log('[JJH Space] ' . $mensaje);
}

ini_set('display_errors', DEBUG_APP && !esProduccion() ? '1' : '0');
ini_set('log_errors', '1');

define('DB_HOST', valorEntorno('DB_HOST', 'localhost'));
define('DB_PORT', (int) valorEntorno('DB_PORT', '3306'));
define('DB_USER', valorEntorno('DB_USER', 'root'));
define('DB_PASS', valorEntorno('DB_PASS', ''));
define('DB_NAME', valorEntorno('DB_NAME', 'jjh_space'));
define('DB_CHARSET', valorEntorno('DB_CHARSET', 'utf8mb4'));

define('SMTP_HOST_DEFAULT', 'smtp.mailrelay.com');
define('SMTP_PORT_DEFAULT', 587);
define('SMTP_USER_DEFAULT', 'TU_USUARIO_MAILRELAY');
define('SMTP_PASS_DEFAULT', 'TU_PASSWORD_MAILRELAY');
define('SMTP_FROM_DEFAULT', 'facturas@tudominio.com');
define('SMTP_FROM_NAME_DEFAULT', 'Podas y Talas JJH');

define('MAILRELAY_API_URL_DEFAULT', 'https://TU_CUENTA.ipzmarketing.com/api/v1/send_emails');
define('MAILRELAY_API_KEY_DEFAULT', 'TU_API_KEY_MAILRELAY');
define('MAILRELAY_FROM_EMAIL_DEFAULT', 'facturas@tudominio.com');
define('MAILRELAY_FROM_NAME_DEFAULT', 'Podas y Talas JJH');

function obtenerConexion(): mysqli
{
    static $conexion = null;

    if ($conexion instanceof mysqli) {
        return $conexion;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conexion->set_charset(DB_CHARSET);

    return $conexion;
}
