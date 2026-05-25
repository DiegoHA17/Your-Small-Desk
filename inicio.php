<?php
require_once __DIR__ . '/includes/seguridad.php';
require_once __DIR__ . '/includes/funciones.php';
exigirSesion();

$conexion = obtenerConexion();

$stmtResumen = $conexion->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN estado IN ('emitida','enviada','cobrada','vencida') THEN total ELSE 0 END), 0) AS total_emitido,
        COALESCE(SUM(CASE WHEN estado = 'cobrada' THEN total ELSE 0 END), 0) AS total_cobrado,
        COALESCE(SUM(CASE WHEN estado IN ('emitida','enviada','vencida') THEN total ELSE 0 END), 0) AS total_pendiente,
        SUM(CASE WHEN estado IN ('emitida','enviada','cobrada','rechazada','vencida') THEN 1 ELSE 0 END) AS no_borrador,
        SUM(CASE WHEN estado IN ('emitida','enviada','cobrada','rechazada','vencida') THEN 1 ELSE 0 END) AS emitidas,
        SUM(CASE WHEN estado = 'cobrada' THEN 1 ELSE 0 END) AS cobradas,
        SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) AS rechazadas,
        SUM(CASE WHEN estado = 'borrador' THEN 1 ELSE 0 END) AS borradores,
        SUM(CASE WHEN estado = 'vencida' THEN 1 ELSE 0 END) AS vencidas,
        SUM(CASE WHEN estado IN ('emitida','enviada','vencida') THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN iva_porcentaje > 0 THEN 1 ELSE 0 END) AS con_iva,
        SUM(CASE WHEN iva_porcentaje <= 0 THEN 1 ELSE 0 END) AS sin_iva
     FROM facturas"
);
$stmtResumen->execute();
$resumen = $stmtResumen->get_result()->fetch_assoc();
$porcentajeCobradas = ((int) $resumen['no_borrador']) > 0 ? round(((int) $resumen['cobradas'] / (int) $resumen['no_borrador']) * 100, 1) : 0;

$stmtEstados = $conexion->prepare('SELECT estado, COUNT(*) AS total FROM facturas GROUP BY estado');
$stmtEstados->execute();
$resultadoEstados = $stmtEstados->get_result();
$estados = [];
while ($fila = $resultadoEstados->fetch_assoc()) {
    $estados[$fila['estado']] = (int) $fila['total'];
}

$stmtUltimas = $conexion->prepare(
    'SELECT f.id_factura, f.codigo_factura, f.fecha_emision, f.total, f.estado, f.iva_porcentaje, c.nombre AS cliente_nombre, c.email AS cliente_email
     FROM facturas f
     INNER JOIN clientes c ON c.id_cliente = f.id_cliente
     ORDER BY f.fecha_creacion DESC
     LIMIT 8'
);
$stmtUltimas->execute();
$ultimas = $stmtUltimas->get_result()->fetch_all(MYSQLI_ASSOC);

$tituloPagina = 'Inicio';
$cargarChart = true;
$jsPagina = 'inicio.js';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="row g-3 mb-4 metrics-grid">
    <div class="col-6 col-md-4 col-xl"><div class="metric-card"><div class="text-muted small">Total emitido</div><div class="metric-value"><?php echo formatoEuros((float) $resumen['total_emitido']); ?></div></div></div>
    <div class="col-6 col-md-4 col-xl"><div class="metric-card"><div class="text-muted small">Total cobrado</div><div class="metric-value text-success"><?php echo formatoEuros((float) $resumen['total_cobrado']); ?></div></div></div>
    <div class="col-6 col-md-4 col-xl"><div class="metric-card"><div class="text-muted small">Total pendiente</div><div class="metric-value text-warning"><?php echo formatoEuros((float) $resumen['total_pendiente']); ?></div></div></div>
    <div class="col-6 col-xl"><div class="metric-card"><div class="text-muted small">Presupuestos emitidos</div><div class="metric-value"><?php echo (int) $resumen['emitidas']; ?></div></div></div>
    <div class="col-6 col-xl"><div class="metric-card"><div class="text-muted small">Presupuestos cobrados</div><div class="metric-value text-success"><?php echo (int) $resumen['cobradas']; ?></div></div></div>
    <div class="col-6 col-xl"><div class="metric-card"><div class="text-muted small">Presupuestos rechazados</div><div class="metric-value text-danger"><?php echo (int) $resumen['rechazadas']; ?></div></div></div>
    <div class="col-6 col-xl"><div class="metric-card"><div class="text-muted small">% cobradas</div><div class="metric-value"><?php echo $porcentajeCobradas; ?>%</div></div></div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card card-soft mb-4">
            <div class="card-header bg-white fw-semibold">Presupuestos por estado</div>
            <div class="card-body"><canvas id="graficaEstados" height="260"></canvas></div>
        </div>
        <div class="card card-soft">
            <div class="card-header bg-white fw-semibold">Avisos</div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between"><span>Borradores</span><strong><?php echo (int) $resumen['borradores']; ?></strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Vencidas manuales</span><strong><?php echo (int) $resumen['vencidas']; ?></strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Pendientes de cobro</span><strong><?php echo (int) $resumen['pendientes']; ?></strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Con IVA</span><strong><?php echo (int) $resumen['con_iva']; ?></strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Sin IVA</span><strong><?php echo (int) $resumen['sin_iva']; ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card card-soft">
            <div class="card-header bg-white fw-semibold">Ultimos presupuestos</div>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Numero</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>IVA</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($ultimas as $f): ?>
                        <tr>
                            <td>Presupuesto N&ordm; <?php echo (int) $f['codigo_factura']; ?></td>
                            <td><?php echo htmlspecialchars($f['cliente_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($f['fecha_emision']); ?></td>
                            <td><?php echo formatoEuros((float) $f['total']); ?></td>
                            <td><span class="badge text-bg-<?php echo (float) $f['iva_porcentaje'] > 0 ? 'success' : 'secondary'; ?>"><?php echo (float) $f['iva_porcentaje'] > 0 ? 'Con IVA' : 'Sin IVA'; ?></span></td>
                            <td><?php echo badgeEstado($f['estado']); ?></td>
                            <td class="text-end table-actions">
                                <a class="btn btn-sm btn-outline-primary" href="factura-ver.php?id=<?php echo (int) $f['id_factura']; ?>" title="Ver"><i class="bi bi-eye"></i></a>
                                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="descargarPdfFactura(<?php echo (int) $f['id_factura']; ?>)" title="Descargar presupuesto"><i class="bi bi-file-earmark-pdf"></i></button>
                                <button class="btn btn-sm btn-outline-info" type="button" onclick="abrirModalEnvioFactura(<?php echo (int) $f['id_factura']; ?>, <?php echo htmlspecialchars(json_encode($f['cliente_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>, <?php echo (int) $f['codigo_factura']; ?>)" title="Enviar presupuesto"><i class="bi bi-envelope"></i></button>
                                <button class="btn btn-sm btn-outline-success" type="button" onclick="prepararWhatsappFactura(<?php echo (int) $f['id_factura']; ?>)" title="WhatsApp"><i class="bi bi-whatsapp"></i></button>
                                <button class="btn btn-sm btn-outline-success" type="button" onclick="cambiarEstadoFactura(<?php echo (int) $f['id_factura']; ?>, 'cobrada')" title="Marcar cobrado"><i class="bi bi-check2-circle"></i></button>
                                <button class="btn btn-sm btn-outline-danger" type="button" onclick="cambiarEstadoFactura(<?php echo (int) $f['id_factura']; ?>, 'rechazada')" title="Marcar rechazado"><i class="bi bi-x-circle"></i></button>
                                <button class="btn btn-sm btn-outline-danger" type="button" onclick="eliminarFactura(<?php echo (int) $f['id_factura']; ?>)" title="Eliminar presupuesto"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ultimas): ?><tr><td colspan="7" class="text-center text-muted py-4">Todavia no hay presupuestos.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mobile-record-list d-md-none p-3">
                <?php foreach ($ultimas as $f): ?>
                <article class="mobile-record-card">
                    <div class="mobile-record-head">
                        <strong>Presupuesto N&ordm; <?php echo (int) $f['codigo_factura']; ?></strong>
                        <?php echo badgeEstado($f['estado']); ?>
                    </div>
                    <div class="mobile-record-data">
                        <div><span>Cliente</span><?php echo htmlspecialchars($f['cliente_nombre']); ?></div>
                        <div><span>Fecha</span><?php echo htmlspecialchars($f['fecha_emision']); ?></div>
                        <div><span>Total</span><strong><?php echo formatoEuros((float) $f['total']); ?></strong></div>
                    </div>
                    <div class="mobile-primary-actions">
                        <a class="btn btn-sm btn-outline-primary" href="factura-ver.php?id=<?php echo (int) $f['id_factura']; ?>"><i class="bi bi-eye"></i> Ver</a>
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="descargarPdfFactura(<?php echo (int) $f['id_factura']; ?>)"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                        <button class="btn btn-sm btn-outline-info" type="button" onclick="abrirModalEnvioFactura(<?php echo (int) $f['id_factura']; ?>, <?php echo htmlspecialchars(json_encode($f['cliente_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>, <?php echo (int) $f['codigo_factura']; ?>)"><i class="bi bi-envelope"></i> Enviar</button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">Mas</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button class="dropdown-item" type="button" onclick="prepararWhatsappFactura(<?php echo (int) $f['id_factura']; ?>)"><i class="bi bi-whatsapp me-2"></i>WhatsApp</button></li>
                                <li><button class="dropdown-item" type="button" onclick="cambiarEstadoFactura(<?php echo (int) $f['id_factura']; ?>, 'cobrada')"><i class="bi bi-check2-circle me-2"></i>Marcar cobrado</button></li>
                                <li><button class="dropdown-item" type="button" onclick="cambiarEstadoFactura(<?php echo (int) $f['id_factura']; ?>, 'rechazada')"><i class="bi bi-x-circle me-2"></i>Marcar rechazado</button></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button class="dropdown-item text-danger" type="button" onclick="eliminarFactura(<?php echo (int) $f['id_factura']; ?>)"><i class="bi bi-trash me-2"></i>Eliminar</button></li>
                            </ul>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php if (!$ultimas): ?><p class="text-center text-muted mb-0 py-3">Todavia no hay presupuestos.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEnvio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <form class="modal-content" id="formEnvioFactura">
            <div class="modal-header"><h5 class="modal-title">Enviar presupuesto</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id_factura" id="envio_id_factura">
                <div class="mb-3"><label class="form-label">Destinatario</label><input class="form-control" type="email" name="destinatario" id="envio_destinatario" required></div>
                <div class="mb-3"><label class="form-label">Asunto</label><input class="form-control" name="asunto" id="envio_asunto" required></div>
                <div class="mb-3"><label class="form-label">Mensaje</label><textarea class="form-control" name="mensaje" id="envio_mensaje" rows="6" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Enviar correo</button></div>
        </form>
    </div>
</div>

<script>window.JJH_ESTADOS = <?php echo json_encode($estados, JSON_UNESCAPED_UNICODE); ?>;</script>
<?php require __DIR__ . '/includes/layout-footer.php'; ?>
