<?php
require_once __DIR__ . '/../includes/seguridad.php';
exigirSesion();

$accion = $_REQUEST['accion'] ?? 'listar';
$conexion = obtenerConexion();

if ($accion === 'listar') {
    $busqueda = '%' . limpiarCadena($_GET['busqueda'] ?? '') . '%';
    $stmt = $conexion->prepare(
        'SELECT * FROM clientes
         WHERE nombre LIKE ? OR empresa LIKE ? OR email LIKE ?
         ORDER BY fecha_creacion DESC'
    );
    $stmt->bind_param('sss', $busqueda, $busqueda, $busqueda);
    $stmt->execute();
    responderJson(true, 'Clientes cargados.', ['clientes' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

if ($accion === 'obtener') {
    $idCliente = (int) ($_GET['id_cliente'] ?? 0);
    if ($idCliente <= 0) {
        responderJson(false, 'Cliente no valido.');
    }
    $stmt = $conexion->prepare('SELECT * FROM clientes WHERE id_cliente = ?');
    $stmt->bind_param('i', $idCliente);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();

    if (!$cliente) {
        responderJson(false, 'Cliente no encontrado.');
    }

    responderJson(true, 'Cliente encontrado.', ['cliente' => $cliente]);
}

if ($accion === 'guardar') {
    exigirMetodoPost();
    validarCsrf();
    $idCliente = (int) ($_POST['id_cliente'] ?? 0);
    $nombre = limpiarCadena($_POST['nombre'] ?? '');
    $empresa = limpiarCadena($_POST['empresa'] ?? '');
    $email = limpiarCadena($_POST['email'] ?? '');
    $telefono = limpiarCadena($_POST['telefono'] ?? '');
    $nifCif = limpiarCadena($_POST['nif_cif'] ?? '');
    $direccion = limpiarCadena($_POST['direccion'] ?? '');
    $ciudad = limpiarCadena($_POST['ciudad'] ?? '');
    $provincia = limpiarCadena($_POST['provincia'] ?? '');
    $codigoPostal = limpiarCadena($_POST['codigo_postal'] ?? '');

    if ($nombre === '') {
        responderJson(false, 'El nombre del cliente es obligatorio.');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responderJson(false, 'El email del cliente no es valido.');
    }
    $limites = [
        'nombre' => [$nombre, 160],
        'empresa' => [$empresa, 160],
        'email' => [$email, 160],
        'telefono' => [$telefono, 50],
        'nif_cif' => [$nifCif, 50],
        'direccion' => [$direccion, 255],
        'ciudad' => [$ciudad, 120],
        'provincia' => [$provincia, 120],
        'codigo_postal' => [$codigoPostal, 20],
    ];
    foreach ($limites as $campo => [$valor, $maximo]) {
        $longitud = function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
        if ($longitud > $maximo) {
            responderJson(false, 'El campo ' . $campo . ' supera la longitud permitida.');
        }
    }

    if ($idCliente > 0) {
        $stmt = $conexion->prepare(
            'UPDATE clientes
             SET nombre = ?, empresa = ?, email = ?, telefono = ?, nif_cif = ?, direccion = ?, ciudad = ?, provincia = ?, codigo_postal = ?
             WHERE id_cliente = ?'
        );
        $stmt->bind_param('sssssssssi', $nombre, $empresa, $email, $telefono, $nifCif, $direccion, $ciudad, $provincia, $codigoPostal, $idCliente);
        $stmt->execute();
        responderJson(true, 'Cliente actualizado.');
    }

    $stmt = $conexion->prepare(
        'INSERT INTO clientes (nombre, empresa, email, telefono, nif_cif, direccion, ciudad, provincia, codigo_postal)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssssssss', $nombre, $empresa, $email, $telefono, $nifCif, $direccion, $ciudad, $provincia, $codigoPostal);
    $stmt->execute();

    responderJson(true, 'Cliente creado.', ['id_cliente' => $conexion->insert_id]);
}

if ($accion === 'eliminar') {
    exigirMetodoPost();
    validarCsrf();
    $idCliente = (int) ($_POST['id_cliente'] ?? 0);
    if ($idCliente <= 0) {
        responderJson(false, 'Cliente no valido.');
    }

    $stmt = $conexion->prepare('SELECT COUNT(*) AS total FROM facturas WHERE id_cliente = ?');
    $stmt->bind_param('i', $idCliente);
    $stmt->execute();
    $totalFacturas = (int) $stmt->get_result()->fetch_assoc()['total'];

    if ($totalFacturas > 0) {
        responderJson(false, 'No se puede eliminar un cliente con presupuestos asociados.');
    }

    $stmtEliminar = $conexion->prepare('DELETE FROM clientes WHERE id_cliente = ?');
    $stmtEliminar->bind_param('i', $idCliente);
    $stmtEliminar->execute();

    responderJson(true, 'Cliente eliminado.');
}

responderJson(false, 'Accion no valida.');
