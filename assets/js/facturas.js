$(function () {
    if ($('#tablaFacturas').length) {
        cargarFacturas();
        $('#buscarFactura, #filtroEstado').on('input change', cargarFacturas);
    }

    if ($('#tablaLineasFactura').length) {
        $('#btnAgregarLinea').on('click', function () { agregarLinea(); });
        $(document).on('input change', '.linea-descripcion, .linea-cantidad, .linea-precio, #iva_porcentaje, .campo-preview', actualizarPreviewFactura);
        $(document).on('change', '.radio-iva', function () {
            actualizarSeleccionIva();
            actualizarPreviewFactura();
        });
        $(document).on('click', '.btnEliminarLinea', function () {
            if ($('.fila-linea').length > 1) {
                $(this).closest('.fila-linea').remove();
                actualizarPreviewFactura();
            }
        });
        $('#id_cliente').on('change', cargarClienteEnFormulario);
        $('.accion-guardar').on('click', function () {
            $('#estado').val($(this).data('estado'));
            $('#despues_guardar').val($(this).data('despues') || '');
        });
        $('#formFactura').on('submit', function (e) {
            e.preventDefault();
            guardarFactura();
        });
        $('#formClienteRapido').on('submit', guardarClienteRapido);
        actualizarPreviewFactura();
    }
});

function cargarFacturas() {
    $.get('acciones/facturas-acciones.php', {
        accion: 'listar',
        busqueda: $('#buscarFactura').val() || '',
        estado: $('#filtroEstado').val() || ''
    }, function (res) {
        if (!res.ok) {
            mostrarToast(res.mensaje, false);
            return;
        }
        const facturas = res.datos.facturas || [];
        if (!facturas.length) {
            $('#tablaFacturas').html('<tr><td colspan="7" class="text-center text-muted py-4">No hay presupuestos.</td></tr>');
            $('#listaFacturasMovil').html('<div class="card card-soft p-4 text-center text-muted">No hay presupuestos.</div>');
            return;
        }
        $('#tablaFacturas').html(facturas.map(function (f) {
            return `
                <tr>
                    <td>Presupuesto N&ordm; ${escapeHtml(f.codigo_factura)}</td>
                    <td>${escapeHtml(f.cliente_nombre)}</td>
                    <td>${escapeHtml(f.fecha_emision)}</td>
                    <td>${formatoEurosJs(f.total)}</td>
                    <td>${badgeIvaJs(f.iva_porcentaje)}</td>
                    <td>${badgeEstadoJs(f.estado)}</td>
                    <td class="text-end table-actions">
                        <a class="btn btn-sm btn-outline-primary" href="factura-ver.php?id=${f.id_factura}" title="Ver"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="descargarPdfFactura(${f.id_factura})" title="Descargar presupuesto"><i class="bi bi-file-earmark-pdf"></i></button>
                        <button class="btn btn-sm btn-outline-info" type="button" onclick="abrirModalEnvioFactura(${f.id_factura}, '${escapeAttr(f.cliente_email || '')}', '${escapeAttr(f.codigo_factura)}')" title="Enviar presupuesto"><i class="bi bi-envelope"></i></button>
                        <button class="btn btn-sm btn-outline-success" type="button" onclick="prepararWhatsappFactura(${f.id_factura})" title="WhatsApp"><i class="bi bi-whatsapp"></i></button>
                        <button class="btn btn-sm btn-outline-success" type="button" onclick="cambiarEstadoFactura(${f.id_factura}, 'cobrada')" title="Marcar cobrado"><i class="bi bi-check2-circle"></i></button>
                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="cambiarEstadoFactura(${f.id_factura}, 'rechazada')" title="Marcar rechazado"><i class="bi bi-x-circle"></i></button>
                        <button class="btn btn-sm btn-outline-dark" type="button" onclick="duplicarFactura(${f.id_factura})" title="Duplicar"><i class="bi bi-files"></i></button>
                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="eliminarFactura(${f.id_factura})" title="Eliminar presupuesto"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
        }).join(''));
        $('#listaFacturasMovil').html(facturas.map(renderFacturaMovil).join(''));
    }, 'json');
}

function renderFacturaMovil(f) {
    return `
        <article class="mobile-record-card">
            <div class="mobile-record-head">
                <strong>Presupuesto N&ordm; ${escapeHtml(f.codigo_factura)}</strong>
                ${badgeEstadoJs(f.estado)}
            </div>
            <div class="mobile-record-data">
                <div><span>Cliente</span>${escapeHtml(f.cliente_nombre)}</div>
                <div><span>Fecha</span>${escapeHtml(f.fecha_emision)}</div>
                <div><span>Total</span><strong>${formatoEurosJs(f.total)}</strong></div>
                <div><span>Categoria</span>${badgeIvaJs(f.iva_porcentaje)}</div>
            </div>
            <div class="mobile-primary-actions">
                <a class="btn btn-sm btn-outline-primary" href="factura-ver.php?id=${f.id_factura}"><i class="bi bi-eye"></i> Ver</a>
                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="descargarPdfFactura(${f.id_factura})"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button class="btn btn-sm btn-outline-info" type="button" onclick="abrirModalEnvioFactura(${f.id_factura}, '${escapeAttr(f.cliente_email || '')}', '${escapeAttr(f.codigo_factura)}')"><i class="bi bi-envelope"></i> Enviar</button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">Mas</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" type="button" onclick="prepararWhatsappFactura(${f.id_factura})"><i class="bi bi-whatsapp me-2"></i>WhatsApp</button></li>
                        <li><button class="dropdown-item" type="button" onclick="cambiarEstadoFactura(${f.id_factura}, 'cobrada')"><i class="bi bi-check2-circle me-2"></i>Marcar cobrado</button></li>
                        <li><button class="dropdown-item" type="button" onclick="cambiarEstadoFactura(${f.id_factura}, 'rechazada')"><i class="bi bi-x-circle me-2"></i>Marcar rechazado</button></li>
                        <li><button class="dropdown-item" type="button" onclick="duplicarFactura(${f.id_factura})"><i class="bi bi-files me-2"></i>Duplicar</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item text-danger" type="button" onclick="eliminarFactura(${f.id_factura})"><i class="bi bi-trash me-2"></i>Eliminar</button></li>
                    </ul>
                </div>
            </div>
        </article>`;
}

function agregarLinea(descripcion, cantidad, precio) {
    const i = Date.now();
    $('#tablaLineasFactura').append(`
        <tr class="fila-linea">
            <td data-label="Concepto"><input class="form-control linea-descripcion" name="lineas[${i}][descripcion]" maxlength="1000" value="${escapeAttr(descripcion || '')}" required></td>
            <td data-label="Cantidad"><input class="form-control linea-cantidad" type="text" inputmode="decimal" maxlength="20" name="lineas[${i}][cantidad]" value="${cantidad || 1}" required></td>
            <td data-label="Precio unitario"><input class="form-control linea-precio" type="text" inputmode="decimal" maxlength="20" name="lineas[${i}][precio_unitario]" value="${precio || 0}" required></td>
            <td data-label="Total" class="text-end total-linea">0,00 EUR</td>
            <td class="linea-actions"><button class="btn btn-outline-danger btn-sm btnEliminarLinea" type="button"><i class="bi bi-trash"></i> <span class="d-md-none">Eliminar linea</span></button></td>
        </tr>`);
    actualizarPreviewFactura();
}

function cargarClienteEnFormulario() {
    const option = $('#id_cliente option:selected');
    $('#preview_cliente_nombre').val(option.data('nombre') || '');
    $('#preview_cliente_empresa').val(option.data('empresa') || '');
    $('#preview_cliente_email').val(option.data('email') || '');
    $('#preview_cliente_telefono').val(option.data('telefono') || '');
    $('#preview_cliente_nif').val(option.data('nif') || '');
    $('#preview_cliente_direccion').val(option.data('direccion') || '');
    $('#preview_cliente_ciudad').val(option.data('ciudad') || '');
    $('#preview_cliente_provincia').val(option.data('provincia') || '');
    $('#preview_cliente_cp').val(option.data('cp') || '');
    actualizarPreviewFactura();
}

function actualizarPreviewFactura() {
    let subtotal = 0;
    const filasPreview = [];
    $('.fila-linea').each(function () {
        const desc = $(this).find('.linea-descripcion').val() || '';
        const cantidad = normalizarNumeroFacturaJs($(this).find('.linea-cantidad').val()) || 0;
        const precio = normalizarNumeroFacturaJs($(this).find('.linea-precio').val()) || 0;
        const total = cantidad * precio;
        subtotal += total;
        $(this).find('.total-linea').text(formatoEurosJs(total));
        if (desc || cantidad || precio) {
            filasPreview.push(`<tr><td class="text-center col-cantidad">${cantidad.toLocaleString('es-ES')}</td><td class="descripcion-linea col-concepto">${escapeHtml(desc)}</td><td class="text-end importe col-precio">${formatoEurosJs(precio)}</td><td class="text-end importe col-total">${formatoEurosJs(total)}</td></tr>`);
        }
    });

    const ivaPorcentaje = obtenerIvaPorcentajeActual();
    const aplicaIva = ivaPorcentaje > 0;
    const iva = aplicaIva ? subtotal * (ivaPorcentaje / 100) : 0;
    const total = subtotal + iva;

    $('#subtotalVista').text(formatoEurosJs(subtotal));
    $('#ivaVista').text(formatoEurosJs(iva));
    $('#ivaEtiquetaPreview').text('IVA ' + ivaPorcentaje.toLocaleString('es-ES') + '%');
    actualizarCategoriaIva();
    $('#filaIvaPreview').toggleClass('d-none', !aplicaIva);
    $('#totalVista').text(formatoEurosJs(total));
    $('#previewLineas').html(filasPreview.join('') || '<tr><td colspan="4">Sin lineas</td></tr>');
    $('#previewFecha').text(formatearFechaPreview($('#fecha_emision').val()));
    $('#previewConcepto').text($('#concepto_general').val() || '');
    $('#previewObservaciones').text($('#observaciones').val() || '');

    const cliente = [
        $('#preview_cliente_nombre').val(),
        $('#preview_cliente_empresa').val(),
        $('#preview_cliente_nif').val(),
        $('#preview_cliente_direccion').val(),
        [$('#preview_cliente_cp').val(), $('#preview_cliente_ciudad').val(), $('#preview_cliente_provincia').val()].filter(Boolean).join(' '),
        $('#preview_cliente_email').val()
    ].filter(Boolean).map(escapeHtml).join('<br>');
    $('#previewCliente').html(cliente || 'Selecciona un cliente');
}

function actualizarSeleccionIva() {
    const $ivaPorcentaje = $('#iva_porcentaje');
    const conIva = $('input[name="aplica_iva"]:checked').val() === '1';
    const porcentajeActual = normalizarNumeroFacturaJs($ivaPorcentaje.val()) || 0;

    if (!conIva) {
        if (porcentajeActual > 0) {
            $ivaPorcentaje.data('ultimo-iva', porcentajeActual);
        }
        $ivaPorcentaje.val('0');
        $('#bloqueIva').addClass('d-none');
        return;
    }

    if (porcentajeActual <= 0) {
        const porcentajeAnterior = normalizarNumeroFacturaJs($ivaPorcentaje.data('ultimo-iva')) || 21;
        $ivaPorcentaje.val(String(porcentajeAnterior).replace('.', ','));
    }
    $('#bloqueIva').removeClass('d-none');
}

function obtenerIvaPorcentajeActual() {
    const conIva = $('input[name="aplica_iva"]:checked').val() === '1';
    return conIva ? (normalizarNumeroFacturaJs($('#iva_porcentaje').val()) || 0) : 0;
}

function obtenerCategoriaIva() {
    return obtenerIvaPorcentajeActual() > 0 ? 'Con IVA' : 'Sin IVA';
}

function actualizarCategoriaIva() {
    $('.js-categoria-iva-preview').text(obtenerCategoriaIva());
}

function guardarFactura() {
    if (!validarFormularioFactura()) {
        return;
    }
    $.post('acciones/facturas-acciones.php', $('#formFactura').serialize() + '&accion=guardar', function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (!res.ok) return;

        $('#id_factura').val(res.datos.id_factura);
        $('#codigo_preview').val(res.datos.codigo_factura);
        $('#previewCodigo').text(res.datos.codigo_factura);

        const despues = $('#despues_guardar').val();
        if (despues === 'descargar') {
            descargarPdfFactura(res.datos.id_factura);
        } else if (despues === 'enviar') {
            abrirModalEnvioFactura(res.datos.id_factura, $('#id_cliente option:selected').data('email') || '', res.datos.codigo_factura);
        } else if (despues === 'whatsapp') {
            prepararWhatsappFactura(res.datos.id_factura);
        } else {
            window.location.href = 'factura-ver.php?id=' + res.datos.id_factura;
        }
    }, 'json');
}

function guardarClienteRapido(e) {
    e.preventDefault();
    $.post('acciones/clientes-acciones.php', $(this).serialize() + '&accion=guardar', function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (!res.ok) return;
        const f = $('#formClienteRapido');
        const datos = {
            nombre: f.find('[name="nombre"]').val(),
            empresa: f.find('[name="empresa"]').val(),
            email: f.find('[name="email"]').val(),
            telefono: f.find('[name="telefono"]').val(),
            nif: f.find('[name="nif_cif"]').val(),
            direccion: f.find('[name="direccion"]').val(),
            ciudad: f.find('[name="ciudad"]').val(),
            provincia: f.find('[name="provincia"]').val(),
            cp: f.find('[name="codigo_postal"]').val()
        };
        $('#id_cliente').append(`<option value="${res.datos.id_cliente}" data-nombre="${escapeAttr(datos.nombre)}" data-empresa="${escapeAttr(datos.empresa)}" data-email="${escapeAttr(datos.email)}" data-telefono="${escapeAttr(datos.telefono)}" data-nif="${escapeAttr(datos.nif)}" data-direccion="${escapeAttr(datos.direccion)}" data-ciudad="${escapeAttr(datos.ciudad)}" data-provincia="${escapeAttr(datos.provincia)}" data-cp="${escapeAttr(datos.cp)}">${escapeHtml(datos.nombre)}</option>`);
        $('#id_cliente').val(res.datos.id_cliente).trigger('change');
        bootstrap.Modal.getInstance(document.getElementById('modalClienteRapido')).hide();
        f[0].reset();
    }, 'json');
}

function duplicarFactura(idFactura) {
    if (!confirm('Duplicar este presupuesto como borrador?')) return;
    $.post('acciones/facturas-acciones.php', { accion: 'duplicar', id_factura: idFactura }, function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (res.ok) window.location.href = 'factura-ver.php?id=' + res.datos.id_factura;
    }, 'json');
}

function formatearFechaPreview(fecha) {
    const partes = String(fecha || '').split('-');
    return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : String(fecha || '');
}

function normalizarNumeroFacturaJs(valor) {
    const texto = String(valor || '').trim().replace(/\s/g, '');
    if (!texto) return 0;
    if (texto.includes(',') && texto.includes('.')) {
        if (texto.lastIndexOf(',') > texto.lastIndexOf('.')) {
            const numero = parseFloat(texto.replace(/\./g, '').replace(',', '.'));
            return Number.isFinite(numero) ? numero : null;
        }
        const numero = parseFloat(texto.replace(/,/g, ''));
        return Number.isFinite(numero) ? numero : null;
    }
    const numero = parseFloat(texto.replace(',', '.'));
    return Number.isFinite(numero) ? numero : null;
}

function badgeEstadoJs(estado) {
    const clases = { borrador: 'secondary', emitida: 'primary', enviada: 'info', cobrada: 'success', rechazada: 'danger', vencida: 'warning' };
    return `<span class="badge text-bg-${clases[estado] || 'secondary'}">${escapeHtml(estado.charAt(0).toUpperCase() + estado.slice(1))}</span>`;
}

function badgeIvaJs(ivaPorcentaje) {
    return normalizarNumeroFacturaJs(ivaPorcentaje) > 0
        ? '<span class="badge text-bg-success">Con IVA</span>'
        : '<span class="badge text-bg-secondary">Sin IVA</span>';
}

function formatoEurosJs(valor) {
    const importe = Number(valor);
    return (Number.isFinite(importe) ? importe : 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' EUR';
}

function validarFormularioFactura() {
    if (($('#concepto_general').val() || '').length > 255) {
        mostrarToast('El concepto general no puede superar 255 caracteres.', false);
        return false;
    }
    if (($('#observaciones').val() || '').length > 2000) {
        mostrarToast('Las observaciones no pueden superar 2000 caracteres.', false);
        return false;
    }
    if ($('input[name="aplica_iva"]:checked').val() === '1') {
        const iva = normalizarNumeroFacturaJs($('#iva_porcentaje').val());
        if (iva === null || iva <= 0 || iva > 100) {
            mostrarToast('La categoria Con IVA requiere un porcentaje mayor que cero.', false);
            return false;
        }
    }

    let valido = true;
    $('.fila-linea').each(function () {
        const descripcion = $(this).find('.linea-descripcion').val() || '';
        if (!descripcion) {
            return;
        }
        const cantidad = normalizarNumeroFacturaJs($(this).find('.linea-cantidad').val());
        const precio = normalizarNumeroFacturaJs($(this).find('.linea-precio').val());
        if (descripcion.length > 1000) {
            mostrarToast('La descripcion de una linea no puede superar 1000 caracteres.', false);
            valido = false;
            return false;
        }
        if (cantidad === null || cantidad <= 0 || cantidad > 999999 || precio === null || precio < 0 || precio > 9999999.99) {
            mostrarToast('El importe introducido es demasiado alto o no es valido.', false);
            valido = false;
            return false;
        }
    });
    return valido;
}

function escapeHtml(texto) {
    return $('<div>').text(texto === null ? '' : texto).html();
}

function escapeAttr(texto) {
    return String(texto || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, ' ');
}
