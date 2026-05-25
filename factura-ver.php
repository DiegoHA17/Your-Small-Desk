<?php
require_once __DIR__ . '/includes/seguridad.php';
require_once __DIR__ . '/includes/pdf.php';
exigirSesion();

$idFactura = (int) ($_GET['id'] ?? 0);
$datos = obtenerDatosFactura($idFactura);

if (!$datos) {
    exit('Presupuesto no encontrado.');
}

$factura = $datos['factura'];
$lineas = $datos['lineas'];

$stmtEnvios = obtenerConexion()->prepare('SELECT * FROM envios WHERE id_factura = ? ORDER BY fecha_envio DESC');
$stmtEnvios->bind_param('i', $idFactura);
$stmtEnvios->execute();
$envios = $stmtEnvios->get_result()->fetch_all(MYSQLI_ASSOC);

$tituloPagina = 'Presupuesto No ' . $factura['codigo_factura'];
$jsPagina = 'facturas.js';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="d-flex flex-column flex-xl-row gap-3 justify-content-between mb-4">
    <div>
        <h2 class="h4 mb-1">Presupuesto N&ordm; <?php echo (int) $factura['codigo_factura']; ?> <?php echo badgeEstado($factura['estado']); ?></h2>
        <div class="text-muted"><?php echo htmlspecialchars($factura['cliente_nombre']); ?></div>
    </div>
    <div class="d-flex flex-wrap gap-2 detail-actions">
        <button class="btn btn-outline-secondary" type="button" onclick="descargarPdfFactura(<?php echo (int) $factura['id_factura']; ?>)"><i class="bi bi-file-earmark-pdf"></i> Descargar presupuesto</button>
        <button class="btn btn-outline-info" type="button" onclick="abrirModalEnvioFactura(<?php echo (int) $factura['id_factura']; ?>, <?php echo htmlspecialchars(json_encode($factura['cliente_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>, <?php echo (int) $factura['codigo_factura']; ?>)"><i class="bi bi-envelope"></i> Enviar presupuesto</button>
        <button class="btn btn-success" type="button" onclick="prepararWhatsappFactura(<?php echo (int) $factura['id_factura']; ?>)"><i class="bi bi-whatsapp"></i> WhatsApp</button>
        <button class="btn btn-outline-success" type="button" onclick="cambiarEstadoFactura(<?php echo (int) $factura['id_factura']; ?>, 'cobrada')"><i class="bi bi-check2-circle"></i> Cobrado</button>
        <button class="btn btn-outline-danger" type="button" onclick="cambiarEstadoFactura(<?php echo (int) $factura['id_factura']; ?>, 'rechazada')"><i class="bi bi-x-circle"></i> Rechazado</button>
        <button class="btn btn-outline-dark" type="button" onclick="duplicarFactura(<?php echo (int) $factura['id_factura']; ?>)"><i class="bi bi-files"></i> Duplicar</button>
        <button class="btn btn-outline-danger" type="button" onclick="eliminarFactura(<?php echo (int) $factura['id_factura']; ?>)"><i class="bi bi-trash"></i> Eliminar presupuesto</button>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-soft mb-4">
            <div class="card-header bg-white fw-semibold">Lineas del presupuesto</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 tabla-presupuesto tabla-detalle-presupuesto">
                    <thead><tr><th class="col-concepto">Descripcion</th><th class="text-end col-cantidad">Cantidad</th><th class="text-end col-precio">Precio</th><th class="text-end col-total">Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($lineas as $linea): ?>
                        <tr>
                            <td data-label="Descripcion" class="descripcion-linea"><?php echo htmlspecialchars($linea['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Cantidad" class="text-end"><?php echo number_format((float) $linea['cantidad'], 2, ',', '.'); ?></td>
                            <td data-label="Precio" class="text-end importe"><?php echo formatoEuros((float) $linea['precio_unitario']); ?></td>
                            <td data-label="Total" class="text-end fw-semibold importe"><?php echo formatoEuros((float) $linea['total_linea']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-soft mb-4">
            <div class="card-header bg-white fw-semibold">Observaciones</div>
            <div class="card-body observaciones-texto"><?php echo nl2br(htmlspecialchars($factura['observaciones'] ?: 'Sin observaciones.', ENT_QUOTES, 'UTF-8')); ?></div>
        </div>

        <div class="card card-soft">
            <div class="card-header bg-white fw-semibold">Historial de envios</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 tabla-historial-envios">
                    <thead><tr><th>Fecha</th><th>Destinatario</th><th>Asunto</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php foreach ($envios as $envio): ?>
                        <tr>
                            <td data-label="Fecha"><?php echo htmlspecialchars($envio['fecha_envio']); ?></td>
                            <td data-label="Destinatario"><?php echo htmlspecialchars($envio['destinatario']); ?></td>
                            <td data-label="Asunto"><?php echo htmlspecialchars($envio['asunto']); ?></td>
                            <td data-label="Estado"><?php echo badgeEstado($envio['estado'] === 'enviado' ? 'enviada' : 'rechazada'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$envios): ?><tr><td colspan="4" class="text-center text-muted py-4">No hay envios registrados.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-soft mb-4">
            <div class="card-header bg-white fw-semibold">Datos</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Cliente</dt><dd class="col-7"><?php echo htmlspecialchars($factura['cliente_nombre']); ?></dd>
                    <dt class="col-5">Email</dt><dd class="col-7"><?php echo htmlspecialchars($factura['cliente_email'] ?? ''); ?></dd>
                    <dt class="col-5">Emision</dt><dd class="col-7"><?php echo htmlspecialchars($factura['fecha_emision']); ?></dd>
                    <dt class="col-5">Concepto</dt><dd class="col-7 texto-concepto-presupuesto"><?php echo htmlspecialchars($factura['concepto_general'] ?? '', ENT_QUOTES, 'UTF-8'); ?></dd>
                    <dt class="col-5">Categoria</dt><dd class="col-7"><?php echo (float) $factura['iva_porcentaje'] > 0 ? 'Con IVA' : 'Sin IVA'; ?></dd>
                </dl>
            </div>
        </div>
        <div class="card card-soft">
            <div class="card-header bg-white fw-semibold">Totales</div>
            <div class="card-body">
                <table class="resumen-totales resumen-totales-detalle">
                    <tbody>
                        <tr><td>Subtotal</td><td class="importe"><?php echo formatoEuros((float) $factura['subtotal']); ?></td></tr>
                        <?php if ((float) $factura['iva_porcentaje'] > 0): ?>
                        <tr><td>IVA <?php echo number_format((float) $factura['iva_porcentaje'], 2, ',', '.'); ?>%</td><td class="importe"><?php echo formatoEuros((float) $factura['iva_total']); ?></td></tr>
                        <?php endif; ?>
                        <tr class="total-final"><td>Total</td><td class="importe"><?php echo formatoEuros((float) $factura['total']); ?></td></tr>
                    </tbody>
                </table>
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

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
