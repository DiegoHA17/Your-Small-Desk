<?php
require_once __DIR__ . '/includes/seguridad.php';
exigirSesion();

$tituloPagina = 'Presupuestos';
$jsPagina = 'facturas.js';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-center justify-content-between mb-3">
    <div class="d-flex flex-column flex-md-row gap-2">
        <div class="input-group" style="max-width: 360px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" type="search" id="buscarFactura" placeholder="Buscar numero o cliente">
        </div>
        <select class="form-select" id="filtroEstado" style="max-width: 220px;">
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="emitida">Emitida</option>
            <option value="enviada">Enviada</option>
            <option value="cobrada">Cobrada</option>
            <option value="rechazada">Rechazada</option>
            <option value="vencida">Vencida</option>
        </select>
    </div>
    <a class="btn btn-primary" href="factura-nueva.php"><i class="bi bi-plus-circle"></i> Nuevo presupuesto</a>
</div>

<div class="card card-soft desktop-data-table">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Numero</th><th>Cliente</th><th>Fecha emision</th><th>Total</th><th>IVA</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody id="tablaFacturas"><tr><td colspan="7" class="text-center text-muted py-4">Cargando presupuestos...</td></tr></tbody>
        </table>
    </div>
</div>
<div class="mobile-record-list d-md-none" id="listaFacturasMovil">
    <div class="card card-soft p-4 text-center text-muted">Cargando presupuestos...</div>
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
