<?php
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones.php';
exigirSesion();

$accion = $_REQUEST['accion'] ?? 'listar';
$conexion = obtenerConexion();
$estadosPermitidos = ['borrador', 'emitida', 'enviada', 'cobrada', 'rechazada', 'vencida'];

function validarFacturaId(): int
{
    $id = (int) ($_REQUEST['id_factura'] ?? $_REQUEST['id'] ?? 0);
    if ($id <= 0) {
        responderJson(false, 'Presupuesto no valido.');
    }
    return $id;
}

function normalizarDecimalFactura(mixed $valor): float
{
    $texto = str_replace(' ', '', trim((string) $valor));
    if ($texto === '') {
        return 0.0;
    }

    if (str_contains($texto, ',') && str_contains($texto, '.')) {
        if (strrpos($texto, ',') > strrpos($texto, '.')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } else {
            $texto = str_replace(',', '', $texto);
        }
    } else {
        $texto = str_replace(',', '.', $texto);
    }

    return is_numeric($texto) ? (float) $texto : 0.0;
}

function validarDecimalFactura(mixed $valor, float $maximo, string $campo): float
{
    $texto = str_replace(' ', '', trim((string) $valor));
    if ($texto === '') {
        responderJson(false, 'Indica un valor valido para ' . $campo . '.');
    }

    $numero = normalizarDecimalFactura($texto);
    $normalizado = str_replace(',', '.', $texto);
    if (str_contains($texto, ',') && str_contains($texto, '.')) {
        $normalizado = strrpos($texto, ',') > strrpos($texto, '.')
            ? str_replace(',', '.', str_replace('.', '', $texto))
            : str_replace(',', '', $texto);
    }

    if (!is_numeric($normalizado) || !is_finite($numero) || $numero < 0) {
        responderJson(false, 'Indica un valor valido para ' . $campo . '.');
    }
    if ($numero > $maximo) {
        responderJson(false, 'El importe introducido es demasiado alto.');
    }

    return $numero;
}

function longitudTextoFactura(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

function existeFactura(mysqli $conexion, int $idFactura): bool
{
    $stmt = $conexion->prepare('SELECT 1 FROM facturas WHERE id_factura = ?');
    $stmt->bind_param('i', $idFactura);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

function resolverRutaPdfFactura(string $rutaRelativa): string
{
    if ($rutaRelativa === '') {
        return '';
    }

    $rutaCandidata = realpath(__DIR__ . '/../' . $rutaRelativa);
    $directorioFacturas = realpath(__DIR__ . '/../storage/facturas');
    if (!$rutaCandidata || !$directorioFacturas || !str_starts_with($rutaCandidata, $directorioFacturas . DIRECTORY_SEPARATOR)) {
        return '';
    }

    return $rutaCandidata;
}

if ($accion === 'listar') {
    $busquedaTexto = limpiarCadena($_GET['busqueda'] ?? '');
    $busqueda = '%' . $busquedaTexto . '%';
    $estado = limpiarCadena($_GET['estado'] ?? '');

    if ($estado !== '' && in_array($estado, $estadosPermitidos, true)) {
        $stmt = $conexion->prepare(
            'SELECT f.*, c.nombre AS cliente_nombre, c.email AS cliente_email
             FROM facturas f
             INNER JOIN clientes c ON c.id_cliente = f.id_cliente
             WHERE f.estado = ? AND (CAST(f.codigo_factura AS CHAR) LIKE ? OR c.nombre LIKE ? OR c.empresa LIKE ?)
             ORDER BY f.fecha_creacion DESC'
        );
        $stmt->bind_param('ssss', $estado, $busqueda, $busqueda, $busqueda);
    } else {
        $stmt = $conexion->prepare(
            'SELECT f.*, c.nombre AS cliente_nombre, c.email AS cliente_email
             FROM facturas f
             INNER JOIN clientes c ON c.id_cliente = f.id_cliente
             WHERE CAST(f.codigo_factura AS CHAR) LIKE ? OR c.nombre LIKE ? OR c.empresa LIKE ?
             ORDER BY f.fecha_creacion DESC'
        );
        $stmt->bind_param('sss', $busqueda, $busqueda, $busqueda);
    }

    $stmt->execute();
    responderJson(true, 'Presupuestos cargados.', ['facturas' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

if ($accion === 'obtener') {
    require_once __DIR__ . '/../includes/pdf.php';
    $datos = obtenerDatosFactura(validarFacturaId());
    if ($datos && isset($datos['configuracion']) && is_array($datos['configuracion'])) {
        $datos['configuracion'] = ocultarSecretosConfiguracion($datos['configuracion']);
    }
    responderJson((bool) $datos, $datos ? 'Presupuesto encontrado.' : 'Presupuesto no encontrado.', $datos ?: []);
}

if ($accion === 'guardar') {
    exigirMetodoPost();
    validarCsrf();

    $idFactura = (int) ($_POST['id_factura'] ?? 0);
    $idCliente = (int) ($_POST['id_cliente'] ?? 0);
    $fechaEmision = limpiarCadena($_POST['fecha_emision'] ?? date('Y-m-d'));
    $estado = limpiarCadena($_POST['estado'] ?? 'borrador');
    $conceptoGeneral = limpiarCadena($_POST['concepto_general'] ?? '');
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
    $categoriaConIva = (int) ($_POST['aplica_iva'] ?? 1) === 1;
    $ivaPorcentaje = $categoriaConIva ? validarDecimalFactura($_POST['iva_porcentaje'] ?? 21, 100, 'el IVA') : 0.0;
    if ($categoriaConIva && $ivaPorcentaje <= 0) {
        responderJson(false, 'La categoria Con IVA requiere un porcentaje mayor que cero.');
    }
    $aplicaIva = $ivaPorcentaje > 0 ? 1 : 0;
    if (!$aplicaIva) {
        $ivaPorcentaje = 0.0;
    }
    $lineasPost = $_POST['lineas'] ?? [];

    if ($idCliente <= 0) {
        responderJson(false, 'Selecciona un cliente.');
    }
    $fechaValida = DateTime::createFromFormat('!Y-m-d', $fechaEmision);
    if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fechaEmision) {
        responderJson(false, 'La fecha de emision no es valida.');
    }
    if (!in_array($estado, ['borrador', 'emitida'], true)) {
        $estado = 'borrador';
    }
    if (longitudTextoFactura($conceptoGeneral) > 255) {
        responderJson(false, 'El concepto general no puede superar 255 caracteres.');
    }
    if (longitudTextoFactura($observaciones) > 2000) {
        responderJson(false, 'Las observaciones no pueden superar 2000 caracteres.');
    }
    if (!is_array($lineasPost)) {
        responderJson(false, 'Las lineas del presupuesto no son validas.');
    }

    $lineas = [];
    $subtotal = 0.0;
    $importeMaximoBd = 9999999999.99;
    foreach ($lineasPost as $linea) {
        $descripcion = limpiarCadena($linea['descripcion'] ?? '');
        if ($descripcion === '') {
            continue;
        }
        if (longitudTextoFactura($descripcion) > 1000) {
            responderJson(false, 'La descripcion de una linea no puede superar 1000 caracteres.');
        }
        $cantidad = validarDecimalFactura($linea['cantidad'] ?? 0, 999999, 'la cantidad');
        $precioUnitario = validarDecimalFactura($linea['precio_unitario'] ?? 0, 9999999.99, 'el precio unitario');
        if ($cantidad <= 0) {
            responderJson(false, 'La cantidad debe ser mayor que cero.');
        }
        $totalLinea = round($cantidad * $precioUnitario, 2);
        if ($totalLinea > $importeMaximoBd) {
            responderJson(false, 'El importe introducido es demasiado alto.');
        }
        $subtotal += $totalLinea;
        if ($subtotal > $importeMaximoBd) {
            responderJson(false, 'El total del presupuesto es demasiado alto.');
        }
        $lineas[] = compact('descripcion', 'cantidad', 'precioUnitario', 'totalLinea');
    }

    if (!$lineas) {
        responderJson(false, 'Añade al menos una linea.');
    }

    $subtotal = round($subtotal, 2);
    $ivaTotal = $aplicaIva ? round($subtotal * ($ivaPorcentaje / 100), 2) : 0.0;
    $total = round($subtotal + $ivaTotal, 2);
    if ($ivaTotal > $importeMaximoBd || $total > $importeMaximoBd) {
        responderJson(false, 'El total del presupuesto es demasiado alto.');
    }
    $rutaPdfAnterior = '';

    if ($idFactura > 0) {
        $stmtExistente = $conexion->prepare('SELECT ruta_pdf FROM facturas WHERE id_factura = ?');
        $stmtExistente->bind_param('i', $idFactura);
        $stmtExistente->execute();
        $facturaExistente = $stmtExistente->get_result()->fetch_assoc();
        if (!$facturaExistente) {
            responderJson(false, 'Presupuesto no encontrado.');
        }
        $rutaPdfAnterior = resolverRutaPdfFactura((string) ($facturaExistente['ruta_pdf'] ?? ''));
    }

    $conexion->begin_transaction();
    try {
        if ($idFactura > 0) {
            $stmt = $conexion->prepare(
                'UPDATE facturas
                 SET id_cliente = ?, fecha_emision = ?, estado = ?, concepto_general = ?, observaciones = ?,
                     aplica_iva = ?, subtotal = ?, iva_porcentaje = ?, iva_total = ?, total = ?, ruta_pdf = NULL
                 WHERE id_factura = ?'
            );
            $stmt->bind_param('issssiddddi', $idCliente, $fechaEmision, $estado, $conceptoGeneral, $observaciones, $aplicaIva, $subtotal, $ivaPorcentaje, $ivaTotal, $total, $idFactura);
            $stmt->execute();

            $stmtEliminar = $conexion->prepare('DELETE FROM factura_lineas WHERE id_factura = ?');
            $stmtEliminar->bind_param('i', $idFactura);
            $stmtEliminar->execute();
        } else {
            $codigoFactura = generarCodigoFactura();
            $stmt = $conexion->prepare(
                'INSERT INTO facturas
                 (codigo_factura, id_cliente, fecha_emision, estado, concepto_general, observaciones, aplica_iva, subtotal, iva_porcentaje, iva_total, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('iissssidddd', $codigoFactura, $idCliente, $fechaEmision, $estado, $conceptoGeneral, $observaciones, $aplicaIva, $subtotal, $ivaPorcentaje, $ivaTotal, $total);
            $stmt->execute();
            $idFactura = $conexion->insert_id;
        }

        $stmtLinea = $conexion->prepare(
            'INSERT INTO factura_lineas (id_factura, descripcion, cantidad, precio_unitario, total_linea)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($lineas as $linea) {
            $stmtLinea->bind_param('isddd', $idFactura, $linea['descripcion'], $linea['cantidad'], $linea['precioUnitario'], $linea['totalLinea']);
            $stmtLinea->execute();
        }

        $stmtCodigo = $conexion->prepare('SELECT codigo_factura FROM facturas WHERE id_factura = ?');
        $stmtCodigo->bind_param('i', $idFactura);
        $stmtCodigo->execute();
        $codigoFactura = (int) $stmtCodigo->get_result()->fetch_assoc()['codigo_factura'];

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        registrarError($e);
        responderJson(false, 'No se pudo guardar el presupuesto.');
    }

    if ($rutaPdfAnterior !== '' && is_file($rutaPdfAnterior)) {
        @unlink($rutaPdfAnterior);
    }

    responderJson(true, 'Presupuesto guardado.', [
        'id_factura' => $idFactura,
        'codigo_factura' => $codigoFactura,
    ]);
}

if ($accion === 'descargar_pdf') {
    require_once __DIR__ . '/../includes/pdf.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validarCsrf();
        try {
            $idFactura = validarFacturaId();
            $pdf = generarPdfFactura($idFactura);
            responderJson(true, 'PDF preparado.', [
                'id_factura' => $idFactura,
                'nombre_pdf' => $pdf['nombre_pdf'],
                'url_descarga_pdf' => 'acciones/facturas-acciones.php?accion=descargar_pdf&id_factura=' . $idFactura . '&descargar=1',
            ]);
        } catch (Throwable $e) {
            registrarError($e);
            responderJson(false, 'No se pudo generar el PDF del presupuesto.');
        }
    }

    try {
        $pdf = generarPdfFactura(validarFacturaId());
        if (!is_file($pdf['ruta_absoluta'])) {
            throw new RuntimeException('El archivo PDF no existe.');
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $pdf['nombre_pdf'] . '"');
        header('Content-Length: ' . filesize($pdf['ruta_absoluta']));
        readfile($pdf['ruta_absoluta']);
        exit;
    } catch (Throwable $e) {
        registrarError($e);
        http_response_code(404);
        exit('No se pudo descargar el PDF.');
    }
}

if ($accion === 'preparar_whatsapp') {
    exigirMetodoPost();
    validarCsrf();
    require_once __DIR__ . '/../includes/pdf.php';
    try {
        $idFactura = validarFacturaId();
        $pdf = generarPdfFactura($idFactura);
        $datos = obtenerDatosFactura($idFactura);
        $codigoFactura = (int) $datos['factura']['codigo_factura'];
        $mensaje = "Hola, te env\u{00ED}o el presupuesto N\u{00BA} " . $codigoFactura . ' de Podas y Talas JJH.';
        responderJson(true, 'WhatsApp preparado. Se ha generado el PDF actualizado.', [
            'id_factura' => $idFactura,
            'codigo_factura' => $codigoFactura,
            'nombre_pdf' => $pdf['nombre_pdf'],
            'url_descarga_pdf' => 'acciones/facturas-acciones.php?accion=descargar_pdf&id_factura=' . $idFactura . '&descargar=1',
            'mensaje_whatsapp' => $mensaje,
            'url_whatsapp' => 'https://wa.me/?text=' . rawurlencode($mensaje),
        ]);
    } catch (Throwable $e) {
        registrarError($e);
        responderJson(false, 'No se pudo generar el PDF del presupuesto.');
    }
}

if ($accion === 'cambiar_estado') {
    exigirMetodoPost();
    validarCsrf();
    $idFactura = validarFacturaId();
    $estado = limpiarCadena($_POST['estado'] ?? '');
    if (!in_array($estado, $estadosPermitidos, true)) {
        responderJson(false, 'Estado no valido.');
    }
    if (!existeFactura($conexion, $idFactura)) {
        responderJson(false, 'Presupuesto no encontrado.');
    }

    $stmt = $conexion->prepare('UPDATE facturas SET estado = ? WHERE id_factura = ?');
    $stmt->bind_param('si', $estado, $idFactura);
    $stmt->execute();
    responderJson(true, 'Estado actualizado.');
}

if ($accion === 'duplicar') {
    exigirMetodoPost();
    validarCsrf();
    $idFactura = validarFacturaId();

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare('SELECT * FROM facturas WHERE id_factura = ?');
        $stmt->bind_param('i', $idFactura);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        if (!$factura) {
            throw new RuntimeException('Presupuesto no encontrado.');
        }

        $codigoFactura = generarCodigoFactura();
        $estado = 'borrador';
        $idCliente = (int) $factura['id_cliente'];
        $concepto = $factura['concepto_general'];
        $observaciones = $factura['observaciones'];
        $aplicaIva = (int) $factura['aplica_iva'];
        $subtotal = (float) $factura['subtotal'];
        $ivaPorcentaje = (float) $factura['iva_porcentaje'];
        $ivaTotal = (float) $factura['iva_total'];
        $total = (float) $factura['total'];

        $stmtNuevo = $conexion->prepare(
            'INSERT INTO facturas
             (codigo_factura, id_cliente, fecha_emision, estado, concepto_general, observaciones, aplica_iva, subtotal, iva_porcentaje, iva_total, total)
             VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmtNuevo->bind_param('iisssidddd', $codigoFactura, $idCliente, $estado, $concepto, $observaciones, $aplicaIva, $subtotal, $ivaPorcentaje, $ivaTotal, $total);
        $stmtNuevo->execute();
        $idNuevo = $conexion->insert_id;

        $stmtLineas = $conexion->prepare('SELECT descripcion, cantidad, precio_unitario, total_linea FROM factura_lineas WHERE id_factura = ?');
        $stmtLineas->bind_param('i', $idFactura);
        $stmtLineas->execute();
        $lineas = $stmtLineas->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmtInsertLinea = $conexion->prepare('INSERT INTO factura_lineas (id_factura, descripcion, cantidad, precio_unitario, total_linea) VALUES (?, ?, ?, ?, ?)');
        foreach ($lineas as $linea) {
            $stmtInsertLinea->bind_param('isddd', $idNuevo, $linea['descripcion'], $linea['cantidad'], $linea['precio_unitario'], $linea['total_linea']);
            $stmtInsertLinea->execute();
        }

        $conexion->commit();
        responderJson(true, 'Presupuesto duplicado.', ['id_factura' => $idNuevo]);
    } catch (Throwable $e) {
        $conexion->rollback();
        registrarError($e);
        responderJson(false, 'No se pudo duplicar el presupuesto.');
    }
}

if (in_array($accion, ['eliminar', 'eliminar_factura'], true)) {
    exigirMetodoPost();
    validarCsrf();
    $idFactura = validarFacturaId();

    $stmt = $conexion->prepare('SELECT estado, ruta_pdf FROM facturas WHERE id_factura = ?');
    $stmt->bind_param('i', $idFactura);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();

    if (!$factura) {
        responderJson(false, 'Presupuesto no encontrado.');
    }
    $rutaPdf = '';
    if (!empty($factura['ruta_pdf'])) {
        $rutaPdf = resolverRutaPdfFactura((string) $factura['ruta_pdf']);
    }

    $conexion->begin_transaction();
    try {
        /*
         * La tabla envios no tiene ON DELETE CASCADE, así que hay que borrar primero
         * los registros dependientes. Las líneas sí tienen cascade, pero las eliminamos
         * igualmente para que el comportamiento sea explícito.
         */
        $stmtEnvios = $conexion->prepare('DELETE FROM envios WHERE id_factura = ?');
        $stmtEnvios->bind_param('i', $idFactura);
        $stmtEnvios->execute();

        $stmtLineas = $conexion->prepare('DELETE FROM factura_lineas WHERE id_factura = ?');
        $stmtLineas->bind_param('i', $idFactura);
        $stmtLineas->execute();

        $stmtEliminar = $conexion->prepare('DELETE FROM facturas WHERE id_factura = ?');
        $stmtEliminar->bind_param('i', $idFactura);
        $stmtEliminar->execute();

        if ($stmtEliminar->affected_rows < 1) {
            throw new RuntimeException('No se eliminó ninguna factura.');
        }

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        registrarError($e);
        responderJson(false, 'No se pudo eliminar el presupuesto.');
    }

    if ($rutaPdf && is_file($rutaPdf)) {
        @unlink($rutaPdf);
    }

    responderJson(true, 'Presupuesto eliminado correctamente.');
}

responderJson(false, 'Accion no valida.');
