<?php

function rutaLogoDocumentoAbsoluta(string $rutaRelativa): string
{
    if ($rutaRelativa === '') {
        return '';
    }

    $raiz = realpath(__DIR__ . '/..');
    $logos = realpath(__DIR__ . '/../storage/logos');
    $ruta = realpath(__DIR__ . '/../' . ltrim($rutaRelativa, '/\\'));

    if (!$raiz || !$logos || !$ruta || !str_starts_with($ruta, $logos . DIRECTORY_SEPARATOR)) {
        return '';
    }

    return $ruta;
}

function eliminarLogoDocumentoSeguro(string $rutaRelativa): void
{
    $ruta = rutaLogoDocumentoAbsoluta($rutaRelativa);
    if ($ruta !== '' && is_file($ruta)) {
        @unlink($ruta);
    }
}

function guardarLogoDocumentoSubido(array $archivo, string $rutaAnterior = ''): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $rutaAnterior;
    }

    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir el logo.');
    }

    if ((int) ($archivo['size'] ?? 0) <= 0 || (int) $archivo['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('El logo debe pesar menos de 2 MB.');
    }

    $tmp = (string) ($archivo['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('El archivo de logo no es valido.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $extensiones = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    if (!isset($extensiones[$mime])) {
        throw new RuntimeException('El logo debe ser PNG, JPG, JPEG o WEBP.');
    }

    if (getimagesize($tmp) === false) {
        throw new RuntimeException('La imagen no tiene un formato valido.');
    }

    $directorio = __DIR__ . '/../storage/logos';
    if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo crear la carpeta de logos.');
    }

    $proteccion = $directorio . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($proteccion)) {
        file_put_contents($proteccion, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php[0-9]?|phar|cgi|pl|py|sh)$\">\nRequire all denied\n</FilesMatch>\n");
    }

    $nombre = 'logo_empresa_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extensiones[$mime];
    $destino = $directorio . DIRECTORY_SEPARATOR . $nombre;

    if (!move_uploaded_file($tmp, $destino)) {
        throw new RuntimeException('No se pudo guardar el logo.');
    }

    if ($rutaAnterior !== '') {
        eliminarLogoDocumentoSeguro($rutaAnterior);
    }

    return 'storage/logos/' . $nombre;
}
