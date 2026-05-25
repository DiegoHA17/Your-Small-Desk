<?php
require_once __DIR__ . '/includes/seguridad.php';
exigirSesion();

$anioActual = (int) date('Y');
$trimestreActual = (int) ceil(((int) date('n')) / 3);
$conexion = obtenerConexion();
$stmtAnios = $conexion->prepare('SELECT DISTINCT YEAR(fecha_emision) AS anio FROM facturas ORDER BY anio DESC');
$stmtAnios->execute();
$anios = array_map('intval', array_column($stmtAnios->get_result()->fetch_all(MYSQLI_ASSOC), 'anio'));
if (!in_array($anioActual, $anios, true)) {
    array_unshift($anios, $anioActual);
}

$tituloPagina = 'Trimestres';
$cargarChart = true;
$jsPagina = 'trimestres.js';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-center justify-content-between mb-4">
    <div>
        <h2 class="h4 mb-1 text-primary">Control trimestral</h2>
        <p class="text-muted mb-0">Presupuestos agrupados por fecha de emision para preparacion fiscal y gestoria.</p>
    </div>
    <a class="btn btn-primary" href="factura-nueva.php"><i class="bi bi-plus-circle"></i> Nuevo presupuesto</a>
</div>

<div class="card card-soft mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold" for="filtroAnioTrimestre">Año</label>
                <select class="form-select" id="filtroAnioTrimestre">
                    <?php foreach ($anios as $anio): ?>
                        <option value="<?php echo $anio; ?>" <?php echo $anio === $anioActual ? 'selected' : ''; ?>><?php echo $anio; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold" for="filtroTrimestre">Trimestre</label>
                <select class="form-select" id="filtroTrimestre">
                    <?php for ($t = 1; $t <= 4; $t++): ?>
                        <option value="<?php echo $t; ?>" <?php echo $t === $trimestreActual ? 'selected' : ''; ?>>T<?php echo $t; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" for="filtroEstadoTrimestre">Estado</label>
                <select class="form-select" id="filtroEstadoTrimestre">
                    <option value="">Todos</option>
                    <option value="borrador">Borrador</option>
                    <option value="emitida">Emitida</option>
                    <option value="enviada">Enviada</option>
                    <option value="cobrada">Cobrada</option>
                    <option value="rechazada">Rechazada</option>
                    <option value="vencida">Vencida</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold" for="buscarTrimestre">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input class="form-control" type="search" id="buscarTrimestre" placeholder="Numero, cliente o empresa">
                </div>
            </div>
            <div class="col-12 col-md-auto">
                <button class="btn btn-outline-primary w-100" id="btnBuscarTrimestre" type="button">Buscar</button>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4 metrics-grid">
    <div class="col-6 col-md-4 col-xl-2"><div class="metric-card"><div class="text-muted small">Total emitido</div><div class="metric-value" id="trTotalEmitido">0,00 EUR</div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="metric-card"><div class="text-muted small">Total cobrado</div><div class="metric-value text-success" id="trTotalCobrado">0,00 EUR</div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="metric-card"><div class="text-muted small">Total pendiente</div><div class="metric-value text-warning" id="trTotalPendiente">0,00 EUR</div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="metric-card"><div class="text-muted small">Total rechazado</div><div class="metric-value text-danger" id="trTotalRechazado">0,00 EUR</div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="metric-card"><div class="text-muted small">IVA total</div><div class="metric-value" id="trIvaTotal">0,00 EUR</div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="metric-card"><div class="text-muted small">Presupuestos / borradores</div><div class="metric-value"><span id="trNumeroFacturas">0</span> <small class="text-muted fs-6">/ <span id="trNumeroBorradores">0</span></small></div></div></div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card card-soft desktop-data-table">
            <div class="card-header bg-white fw-semibold">Presupuestos por estado</div>
            <div class="card-body"><canvas id="graficaTrimestreEstados" height="270"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card card-soft">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Presupuestos del trimestre</span>
                <small class="text-muted" id="periodoTrimestre"></small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>N&ordm; presupuesto</th><th>Cliente</th><th>Fecha emision</th><th>Base imponible</th><th>IVA</th><th>Total</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody id="tablaFacturasTrimestre"><tr><td colspan="8" class="text-center text-muted py-4">Cargando presupuestos...</td></tr></tbody>
                </table>
            </div>
        </div>
        <div class="mobile-record-list d-md-none" id="listaFacturasTrimestreMovil">
            <div class="card card-soft p-4 text-center text-muted">Cargando presupuestos...</div>
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
