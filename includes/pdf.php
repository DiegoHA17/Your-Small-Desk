<?php
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/seguridad.php';

function obtenerDatosFactura(int $idFactura): ?array
{
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare(
        'SELECT f.*, c.nombre AS cliente_nombre, c.empresa AS cliente_empresa, c.email AS cliente_email,
                c.telefono AS cliente_telefono, c.nif_cif AS cliente_nif_cif, c.direccion AS cliente_direccion,
                c.ciudad AS cliente_ciudad, c.provincia AS cliente_provincia, c.codigo_postal AS cliente_codigo_postal
         FROM facturas f
         INNER JOIN clientes c ON c.id_cliente = f.id_cliente
         WHERE f.id_factura = ?'
    );
    $stmt->bind_param('i', $idFactura);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();

    if (!$factura) {
        return null;
    }

    $stmtLineas = $conexion->prepare('SELECT * FROM factura_lineas WHERE id_factura = ? ORDER BY id_linea ASC');
    $stmtLineas->bind_param('i', $idFactura);
    $stmtLineas->execute();

    return [
        'factura' => $factura,
        'lineas' => $stmtLineas->get_result()->fetch_all(MYSQLI_ASSOC),
        'configuracion' => obtenerConfiguracion(),
    ];
}

function renderizarPlantillaFactura(array $datos): string
{
    ob_start();

    $facturaPdf = $datos['factura'];
    $lineasPdf = $datos['lineas'];
    $configuracionPdf = $datos['configuracion'];

    require __DIR__ . '/../plantillas/factura-base.php';

    return (string) ob_get_clean();
}

function separarHtmlFactura(string $html): array
{
    $css = '';
    $body = $html;

    if (preg_match('/<style\b[^>]*>(.*?)<\/style>/is', $html, $coincidencias)) {
        $css = trim($coincidencias[1]);
        $body = str_replace($coincidencias[0], '', $body);
    }

    if (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $body, $coincidencias)) {
        $body = trim($coincidencias[1]);
    }

    return [$css, $body];
}

function escribirHtmlMpdf(\Mpdf\Mpdf $mpdf, string $html): void
{
    $html = trim($html);
    if ($html === '') {
        return;
    }

    $limite = 60000;
    if (strlen($html) <= $limite) {
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        return;
    }

    /*
     * Una factura larga se parte solo despues de filas completas.
     * El delimitador se conserva para no enviar etiquetas rotas a mPDF.
     */
    $partes = preg_split('/(<\/tr>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    if ($partes === false || count($partes) <= 1) {
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        return;
    }

    $bloque = '';
    foreach ($partes as $parte) {
        $bloque .= $parte;
        if (stripos($parte, '</tr>') !== false && strlen($bloque) >= $limite) {
            $mpdf->WriteHTML($bloque, \Mpdf\HTMLParserMode::HTML_BODY);
            $bloque = '';
        }
    }

    if (trim($bloque) !== '') {
        $mpdf->WriteHTML($bloque, \Mpdf\HTMLParserMode::HTML_BODY);
    }
}

function generarPdfFactura(int $idFactura): array
{
    ini_set('pcre.backtrack_limit', '10000000');
    ini_set('pcre.recursion_limit', '10000000');

    $datos = obtenerDatosFactura($idFactura);
    if (!$datos) {
        throw new RuntimeException('La factura no existe.');
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Faltan las dependencias de Composer. Ejecuta composer install.');
    }
    require_once $autoload;
    if (!class_exists(\Mpdf\Mpdf::class)) {
        throw new RuntimeException('mPDF no esta disponible.');
    }

    $rutaDirectorio = __DIR__ . '/../storage/facturas';
    if (!is_dir($rutaDirectorio) && !mkdir($rutaDirectorio, 0775, true) && !is_dir($rutaDirectorio)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento de facturas.');
    }
    $directorio = realpath($rutaDirectorio);
    if (!$directorio) {
        throw new RuntimeException('No se pudo preparar el almacenamiento de facturas.');
    }

    $tmp = $directorio . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($tmp) && !mkdir($tmp, 0775, true) && !is_dir($tmp)) {
        throw new RuntimeException('No se pudo crear el directorio temporal de mPDF.');
    }

    $factura = $datos['factura'];
    $nombreArchivo = 'factura_' . (int) $factura['codigo_factura'] . '.pdf';
    $rutaAbsoluta = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;

    $html = renderizarPlantillaFactura($datos);
    [$css, $body] = separarHtmlFactura($html);

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0,
        'tempDir' => $tmp,
    ]);

    $mpdf->SetTitle("Presupuesto N\u{00BA} " . (int) $factura['codigo_factura']);
    $mpdf->SetAuthor($datos['configuracion']['nombre_empresa'] ?: 'Podas y Talas JJH');
    $mpdf->showImageErrors = false;

    if ($css !== '') {
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    }

    escribirHtmlMpdf($mpdf, $body);
    $mpdf->Output($rutaAbsoluta, \Mpdf\Output\Destination::FILE);

    $rutaRelativa = 'storage/facturas/' . $nombreArchivo;
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare('UPDATE facturas SET ruta_pdf = ? WHERE id_factura = ?');
    $stmt->bind_param('si', $rutaRelativa, $idFactura);
    $stmt->execute();

    return [
        'ruta_absoluta' => $rutaAbsoluta,
        'ruta_pdf' => $rutaRelativa,
        'nombre_pdf' => $nombreArchivo,
    ];
}
