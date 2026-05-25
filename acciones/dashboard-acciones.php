<?php
require_once __DIR__ . '/../includes/seguridad.php';
exigirSesion();

$accion = $_GET['accion'] ?? 'estadisticas';
$conexion = obtenerConexion();

if ($accion === 'estadisticas') {
    $stmt = $conexion->prepare('SELECT estado, COUNT(*) AS total FROM facturas GROUP BY estado');
    $stmt->execute();
    $resultado = $stmt->get_result();
    $estados = [];
    while ($fila = $resultado->fetch_assoc()) {
        $estados[$fila['estado']] = (int) $fila['total'];
    }
    responderJson(true, 'Estadisticas cargadas.', ['estados' => $estados]);
}

if ($accion === 'ultimas_facturas') {
    $stmt = $conexion->prepare(
        'SELECT f.id_factura, f.codigo_factura, f.fecha_emision, f.total, f.estado, c.nombre AS cliente_nombre
         FROM facturas f
         INNER JOIN clientes c ON c.id_cliente = f.id_cliente
         ORDER BY f.fecha_creacion DESC
         LIMIT 8'
    );
    $stmt->execute();
    responderJson(true, 'Ultimos presupuestos cargados.', ['facturas' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

responderJson(false, 'Accion no valida.');
