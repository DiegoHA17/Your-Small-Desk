<?php
require_once __DIR__ . '/conexion.php';

function formatoEuros(float $importe): string
{
    if (!is_finite($importe)) {
        $importe = 0.0;
    }
    return number_format($importe, 2, ',', '.') . ' EUR';
}

function estadoClase(string $estado): string
{
    $clases = [
        'borrador' => 'secondary',
        'emitida' => 'primary',
        'enviada' => 'info',
        'cobrada' => 'success',
        'rechazada' => 'danger',
        'vencida' => 'warning',
    ];

    return $clases[$estado] ?? 'secondary';
}

function badgeEstado(string $estado): string
{
    $estadoSeguro = htmlspecialchars($estado, ENT_QUOTES, 'UTF-8');
    return '<span class="badge text-bg-' . estadoClase($estado) . '">' . ucfirst($estadoSeguro) . '</span>';
}

function configuracionPorDefecto(): array
{
    return [
        'nombre_empresa' => 'Podas y Talas JJH',
        'email_empresa' => '',
        'telefono_empresa' => '',
        'direccion_empresa' => '',
        'nif_cif_empresa' => '',
        'ciudad_empresa' => '',
        'provincia_empresa' => '',
        'codigo_postal_empresa' => '',
        'mailrelay_api_url' => MAILRELAY_API_URL_DEFAULT,
        'mailrelay_api_key' => MAILRELAY_API_KEY_DEFAULT,
        'mailrelay_from_email' => MAILRELAY_FROM_EMAIL_DEFAULT,
        'mailrelay_from_name' => MAILRELAY_FROM_NAME_DEFAULT,
        'mailrelay_bcc_email' => '',
        'mailrelay_bcc_activo' => 0,
        'mailrelay_metodo_envio_facturas' => 'smtp',
        'mailrelay_smtp_fallback_activo' => 1,
        'mailrelay_smtp_host' => '',
        'mailrelay_smtp_port' => 587,
        'mailrelay_smtp_usuario' => '',
        'mailrelay_smtp_password' => '',
        'mailrelay_smtp_seguridad' => 'tls',
        'smtp_host' => SMTP_HOST_DEFAULT,
        'smtp_port' => SMTP_PORT_DEFAULT,
        'smtp_usuario' => SMTP_USER_DEFAULT,
        'smtp_password' => SMTP_PASS_DEFAULT,
        'smtp_from_email' => SMTP_FROM_DEFAULT,
        'smtp_from_name' => SMTP_FROM_NAME_DEFAULT,
    ];
}

function obtenerConfiguracionPersistida(): array
{
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare('SELECT * FROM configuracion ORDER BY id_configuracion ASC LIMIT 1');
    $stmt->execute();
    $resultado = $stmt->get_result();
    $configuracion = $resultado->fetch_assoc();

    return $configuracion ?: configuracionPorDefecto();
}

function obtenerConfiguracion(): array
{
    return obtenerConfiguracionPersistida();
}

function ocultarSecretosConfiguracion(array $configuracion): array
{
    unset(
        $configuracion['mailrelay_api_key'],
        $configuracion['mailrelay_smtp_password'],
        $configuracion['smtp_password']
    );

    return $configuracion;
}

function generarCodigoFactura(): int
{
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare('SELECT COALESCE(MAX(codigo_factura), 0) + 1 AS siguiente FROM facturas');
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['siguiente'];
}

function obtenerClientesParaSelector(): array
{
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare('SELECT id_cliente, nombre, empresa, email, telefono, nif_cif, direccion, ciudad, provincia, codigo_postal FROM clientes ORDER BY nombre ASC');
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

function urlBaseAplicacion(): string
{
    if (APP_URL !== '') {
        return APP_URL;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $protocolo = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $ruta = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return $protocolo . '://' . $host . ($ruta ? $ruta : '');
}
