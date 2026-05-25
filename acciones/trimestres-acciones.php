<?php
require_once __DIR__ . '/../includes/seguridad.php';
exigirSesion();

$accion = (string) ($_GET['accion'] ?? 'resumen');
$anio = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT);
$trimestre = filter_input(INPUT_GET, 'trimestre', FILTER_VALIDATE_INT);
$estado = limpiarCadena($_GET['estado'] ?? '');
$busqueda = limpiarCadena($_GET['busqueda'] ?? '');
$estadosValidos = ['borrador', 'emitida', 'enviada', 'cobrada', 'rechazada', 'vencida'];

if (!$anio || $anio < 2000 || $anio > 2100) {
    responderJson(false, 'El año seleccionado no es valido.');
}
if (!$trimestre || $trimestre < 1 || $trimestre > 4) {
    responderJson(false, 'El trimestre seleccionado no es valido.');
}
if ($estado !== '' && !in_array($estado, $estadosValidos, true)) {
    responderJson(false, 'El estado seleccionado no es valido.');
}

$mesInicio = (($trimestre - 1) * 3) + 1;
$mesFin = $mesInicio + 2;
$conexion = obtenerConexion();

if ($accion === 'resumen') {
    $stmt = $conexion->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN estado <> 'borrador' THEN total ELSE 0 END), 0) AS total_emitido,
            COALESCE(SUM(CASE WHEN estado = 'cobrada' THEN total ELSE 0 END), 0) AS total_cobrado,
            COALESCE(SUM(CASE WHEN estado IN ('emitida','enviada','vencida') THEN total ELSE 0 END), 0) AS total_pendiente,
            COALESCE(SUM(CASE WHEN estado = 'rechazada' THEN total ELSE 0 END), 0) AS total_rechazado,
            COALESCE(SUM(CASE WHEN estado <> 'borrador' THEN iva_total ELSE 0 END), 0) AS iva_total,
            SUM(CASE WHEN estado <> 'borrador' THEN 1 ELSE 0 END) AS numero_facturas,
            SUM(CASE WHEN estado = 'borrador' THEN 1 ELSE 0 END) AS numero_borradores
         FROM facturas
         WHERE YEAR(fecha_emision) = ?
           AND MONTH(fecha_emision) BETWEEN ? AND ?"
    );
    $stmt->bind_param('iii', $anio, $mesInicio, $mesFin);
    $stmt->execute();
    $resumen = $stmt->get_result()->fetch_assoc();

    $stmtEstados = $conexion->prepare(
        'SELECT estado, COUNT(*) AS total
         FROM facturas
         WHERE YEAR(fecha_emision) = ?
           AND MONTH(fecha_emision) BETWEEN ? AND ?
         GROUP BY estado'
    );
    $stmtEstados->bind_param('iii', $anio, $mesInicio, $mesFin);
    $stmtEstados->execute();
    $estados = [];
    $resultadoEstados = $stmtEstados->get_result();
    while ($fila = $resultadoEstados->fetch_assoc()) {
        $estados[$fila['estado']] = (int) $fila['total'];
    }

    responderJson(true, 'Resumen cargado correctamente.', [
        'total_emitido' => (float) $resumen['total_emitido'],
        'total_cobrado' => (float) $resumen['total_cobrado'],
        'total_pendiente' => (float) $resumen['total_pendiente'],
        'total_rechazado' => (float) $resumen['total_rechazado'],
        'iva_total' => (float) $resumen['iva_total'],
        'numero_facturas' => (int) $resumen['numero_facturas'],
        'numero_borradores' => (int) $resumen['numero_borradores'],
        'estados' => $estados,
    ]);
}

if ($accion === 'listar_facturas') {
    $sql = 'SELECT f.id_factura, f.codigo_factura, f.fecha_emision, f.subtotal, f.iva_total, f.total,
                   f.estado, c.nombre AS cliente_nombre, c.empresa AS cliente_empresa, c.email AS cliente_email
            FROM facturas f
            INNER JOIN clientes c ON c.id_cliente = f.id_cliente
            WHERE YEAR(f.fecha_emision) = ?
              AND MONTH(f.fecha_emision) BETWEEN ? AND ?';
    $parametros = [$anio, $mesInicio, $mesFin];
    $tipos = 'iii';

    if ($estado !== '') {
        $sql .= ' AND f.estado = ?';
        $parametros[] = $estado;
        $tipos .= 's';
    }
    if ($busqueda !== '') {
        $sql .= ' AND (CAST(f.codigo_factura AS CHAR) LIKE ? OR c.nombre LIKE ? OR c.empresa LIKE ?)';
        $termino = '%' . $busqueda . '%';
        $parametros[] = $termino;
        $parametros[] = $termino;
        $parametros[] = $termino;
        $tipos .= 'sss';
    }
    $sql .= ' ORDER BY f.fecha_emision DESC, f.codigo_factura DESC';

    $stmt = $conexion->prepare($sql);
    $referencias = [];
    foreach ($parametros as $indice => $parametro) {
        $referencias[$indice] = &$parametros[$indice];
    }
    $stmt->bind_param($tipos, ...$referencias);
    $stmt->execute();

    responderJson(true, 'Presupuestos del trimestre cargados correctamente.', [
        'facturas' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
    ]);
}

responderJson(false, 'Accion no valida.');
