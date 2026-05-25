<?php
require_once __DIR__ . '/includes/seguridad.php';
require_once __DIR__ . '/includes/funciones.php';
exigirSesion();

$clientes = obtenerClientesParaSelector();
$codigoPrevisto = generarCodigoFactura();
$configuracionVista = obtenerConfiguracion();
$emailEmpresaVista = $configuracionVista['email_empresa'] ?: ($configuracionVista['mailrelay_from_email'] ?? '');
$telefonoEmpresaVista = (string) ($configuracionVista['telefono_empresa'] ?? '');
$direccionEmpresaVista = trim(implode(' ', array_filter([
    $configuracionVista['direccion_empresa'] ?? '',
    $configuracionVista['codigo_postal_empresa'] ?? '',
    $configuracionVista['ciudad_empresa'] ?? '',
    $configuracionVista['provincia_empresa'] ?? '',
])));
$tituloPagina = 'Nuevo presupuesto';
$jsPagina = 'facturas.js';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="builder-hero mb-4">
    <div>
        <div class="eyebrow">Presupuesto nuevo</div>
        <h2>Crear presupuesto</h2>
        <p>Guarda los datos editables. El PDF se genera actualizado al descargar, enviar o compartir.</p>
    </div>
    <div class="builder-hero-code">
        <span>Presupuesto N&ordm; previsto</span>
        <strong><?php echo (int) $codigoPrevisto; ?></strong>
    </div>
</div>

<form id="formFactura">
    <input type="hidden" name="id_factura" id="id_factura">
    <input type="hidden" name="estado" id="estado" value="borrador">
    <input type="hidden" id="despues_guardar" value="">

    <div class="row g-4 quote-builder">
        <div class="col-12 col-xl-6 editor-column">
            <div class="card card-soft form-section mb-4">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-person-lines-fill"></i> Cliente</div>
                <div class="card-body">
                    <div class="d-flex gap-2 mb-3">
                        <select class="form-select" name="id_cliente" id="id_cliente" required>
                            <option value="">Seleccionar cliente</option>
                            <?php foreach ($clientes as $c): ?>
                            <option value="<?php echo (int) $c['id_cliente']; ?>"
                                data-nombre="<?php echo htmlspecialchars($c['nombre'] ?? '', ENT_QUOTES); ?>"
                                data-empresa="<?php echo htmlspecialchars($c['empresa'] ?? '', ENT_QUOTES); ?>"
                                data-email="<?php echo htmlspecialchars($c['email'] ?? '', ENT_QUOTES); ?>"
                                data-telefono="<?php echo htmlspecialchars($c['telefono'] ?? '', ENT_QUOTES); ?>"
                                data-nif="<?php echo htmlspecialchars($c['nif_cif'] ?? '', ENT_QUOTES); ?>"
                                data-direccion="<?php echo htmlspecialchars($c['direccion'] ?? '', ENT_QUOTES); ?>"
                                data-ciudad="<?php echo htmlspecialchars($c['ciudad'] ?? '', ENT_QUOTES); ?>"
                                data-provincia="<?php echo htmlspecialchars($c['provincia'] ?? '', ENT_QUOTES); ?>"
                                data-cp="<?php echo htmlspecialchars($c['codigo_postal'] ?? '', ENT_QUOTES); ?>">
                                <?php echo htmlspecialchars($c['nombre'] . ($c['empresa'] ? ' - ' . $c['empresa'] : '')); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalClienteRapido" title="Nuevo cliente"><i class="bi bi-person-plus"></i></button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control preview-cliente" id="preview_cliente_nombre" readonly></div>
                        <div class="col-md-6"><label class="form-label">Empresa</label><input class="form-control preview-cliente" id="preview_cliente_empresa" readonly></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control preview-cliente" id="preview_cliente_email" readonly></div>
                        <div class="col-md-6"><label class="form-label">Telefono</label><input class="form-control preview-cliente" id="preview_cliente_telefono" readonly></div>
                        <div class="col-md-4"><label class="form-label">NIF/CIF</label><input class="form-control preview-cliente" id="preview_cliente_nif" readonly></div>
                        <div class="col-md-8"><label class="form-label">Direccion</label><input class="form-control preview-cliente" id="preview_cliente_direccion" readonly></div>
                        <div class="col-md-4"><label class="form-label">Ciudad</label><input class="form-control preview-cliente" id="preview_cliente_ciudad" readonly></div>
                        <div class="col-md-4"><label class="form-label">Provincia</label><input class="form-control preview-cliente" id="preview_cliente_provincia" readonly></div>
                        <div class="col-md-4"><label class="form-label">Codigo postal</label><input class="form-control preview-cliente" id="preview_cliente_cp" readonly></div>
                    </div>
                </div>
            </div>

            <div class="card card-soft form-section mb-4">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-file-earmark-text"></i> Presupuesto</div>
                <div class="card-body row g-3">
                    <div class="col-md-5"><label class="form-label">Numero</label><input class="form-control" id="codigo_preview" value="<?php echo (int) $codigoPrevisto; ?>" readonly></div>
                    <div class="col-md-7"><label class="form-label">Fecha emision</label><input class="form-control campo-preview" type="date" name="fecha_emision" id="fecha_emision" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="col-12"><label class="form-label">Concepto general</label><input class="form-control campo-preview" name="concepto_general" id="concepto_general" maxlength="255"></div>
                    <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control campo-preview" name="observaciones" id="observaciones" rows="3" maxlength="2000"></textarea></div>
                    <div class="col-12">
                        <label class="form-label d-block">Categoria IVA</label>
                        <div class="btn-group" role="group" aria-label="Categoria IVA">
                            <input type="radio" class="btn-check radio-iva" name="aplica_iva" id="aplica_iva_si" value="1" checked>
                            <label class="btn btn-outline-primary" for="aplica_iva_si">Con IVA</label>
                            <input type="radio" class="btn-check radio-iva" name="aplica_iva" id="aplica_iva_no" value="0">
                            <label class="btn btn-outline-primary" for="aplica_iva_no">Sin IVA</label>
                        </div>
                    </div>
                    <div class="col-md-4" id="bloqueIva"><label class="form-label">IVA porcentaje</label><input class="form-control" type="text" inputmode="decimal" maxlength="6" name="iva_porcentaje" id="iva_porcentaje" value="21"></div>
                </div>
            </div>

            <div class="card card-soft form-section mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-list-columns"></i> Lineas</span>
                    <button class="btn btn-sm btn-outline-primary" type="button" id="btnAgregarLinea"><i class="bi bi-plus"></i> Añadir linea</button>
                </div>
                <div class="table-responsive tabla-lineas-editor-wrap">
                    <table class="table align-middle mb-0 tabla-lineas-editor">
                        <thead><tr><th>Descripcion</th><th style="width:115px;">Cantidad</th><th style="width:140px;">Precio</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody id="tablaLineasFactura">
                            <tr class="fila-linea">
                                <td data-label="Concepto"><input class="form-control linea-descripcion" name="lineas[0][descripcion]" maxlength="1000" required></td>
                                <td data-label="Cantidad"><input class="form-control linea-cantidad" type="text" inputmode="decimal" maxlength="20" name="lineas[0][cantidad]" value="1" required></td>
                                <td data-label="Precio unitario"><input class="form-control linea-precio" type="text" inputmode="decimal" maxlength="20" name="lineas[0][precio_unitario]" value="0" required></td>
                                <td data-label="Total" class="text-end total-linea">0,00 EUR</td>
                                <td class="linea-actions"><button class="btn btn-outline-danger btn-sm btnEliminarLinea" type="button"><i class="bi bi-trash"></i> <span class="d-md-none">Eliminar linea</span></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white d-grid d-md-flex gap-2 action-buttons-mobile">
                    <button class="btn btn-outline-secondary accion-guardar" type="submit" data-estado="borrador" data-despues="">Guardar borrador</button>
                    <button class="btn btn-outline-dark accion-guardar" type="submit" data-estado="emitida" data-despues="descargar"><i class="bi bi-file-earmark-pdf"></i> Descargar presupuesto</button>
                    <button class="btn btn-outline-info accion-guardar" type="submit" data-estado="emitida" data-despues="enviar"><i class="bi bi-envelope"></i> Enviar presupuesto</button>
                    <button class="btn btn-success accion-guardar" type="submit" data-estado="emitida" data-despues="whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp</button>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6 preview-column">
            <div class="preview-toolbar">
                <span><i class="bi bi-eye"></i> Vista previa</span>
                <small>Hoja A4 aproximada</small>
            </div>
            <div class="preview-paper position-sticky" style="top: 132px;">
                <div class="paper-header">
                    <div class="paper-brand">
                        <img src="assets/img/logo-presupuesto-jjh.png" class="paper-logo paper-logo-documento" alt="Podas y Talas JJH">
                        <div class="paper-company-data" aria-label="Datos de la empresa">
                            <?php if ($emailEmpresaVista !== '' || $telefonoEmpresaVista !== ''): ?>
                                <div class="paper-contact"><?php echo htmlspecialchars(implode(' | ', array_filter([$emailEmpresaVista, $telefonoEmpresaVista]))); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($configuracionVista['nif_cif_empresa'])): ?><div class="paper-contact">NIF/CIF: <?php echo htmlspecialchars($configuracionVista['nif_cif_empresa']); ?></div><?php endif; ?>
                            <?php if ($direccionEmpresaVista !== ''): ?><div class="paper-contact"><?php echo htmlspecialchars($direccionEmpresaVista); ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="paper-document">
                        <div class="paper-document-title">PRESUPUESTO N&ordm; <strong id="previewCodigo"><?php echo (int) $codigoPrevisto; ?></strong></div>
                        <div>Fecha: <span id="previewFecha"><?php echo date('d/m/Y'); ?></span></div>
                    </div>
                </div>
                <div class="paper-title-row">
                    <div>
                        <div class="paper-box paper-details">
                            <div class="paper-label">Datos del cliente</div>
                            <div id="previewCliente">Selecciona un cliente</div>
                        </div>
                    </div>
                    <div class="paper-box paper-details datos-presupuesto">
                        <div class="paper-label">Datos del presupuesto</div>
                        <div class="texto-concepto-presupuesto"><strong>Concepto:</strong> <span id="previewConcepto"></span></div>
                        <div><strong>Categoria:</strong> <span id="previewCategoriaIva" class="js-categoria-iva-preview"></span></div>
                    </div>
                </div>
                <table class="table paper-table tabla-presupuesto mt-4">
                    <thead><tr><th class="col-cantidad">Cant.</th><th class="col-concepto">Concepto</th><th class="text-end col-precio">Precio unitario</th><th class="text-end col-total">Total</th></tr></thead>
                    <tbody id="previewLineas"></tbody>
                </table>
                <table class="resumen-totales">
                    <tbody>
                        <tr class="paper-total"><td>Subtotal</td><td class="importe" id="subtotalVista">0,00 EUR</td></tr>
                        <tr class="paper-total" id="filaIvaPreview"><td id="ivaEtiquetaPreview">IVA</td><td class="importe" id="ivaVista">0,00 EUR</td></tr>
                        <tr class="paper-total total-final"><td>Total</td><td class="importe" id="totalVista">0,00 EUR</td></tr>
                    </tbody>
                </table>
                <div class="paper-box paper-notes mt-4"><div class="paper-label">Observaciones</div><div id="previewObservaciones" class="observaciones-texto"></div></div>
                <div class="paper-signature mt-4"><div class="paper-signature-line"></div>Firma / conformidad del cliente</div>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="modalClienteRapido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <form class="modal-content" id="formClienteRapido">
            <div class="modal-header"><h5 class="modal-title">Nuevo cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre" required></div>
                <div class="col-md-6"><label class="form-label">Empresa</label><input class="form-control" name="empresa"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email"></div>
                <div class="col-md-6"><label class="form-label">Telefono</label><input class="form-control" name="telefono"></div>
                <div class="col-md-6"><label class="form-label">NIF/CIF</label><input class="form-control" name="nif_cif"></div>
                <div class="col-md-6"><label class="form-label">Direccion</label><input class="form-control" name="direccion"></div>
                <div class="col-md-4"><label class="form-label">Ciudad</label><input class="form-control" name="ciudad"></div>
                <div class="col-md-4"><label class="form-label">Provincia</label><input class="form-control" name="provincia"></div>
                <div class="col-md-4"><label class="form-label">Codigo postal</label><input class="form-control" name="codigo_postal"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar cliente</button></div>
        </form>
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
