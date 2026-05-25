let graficaTrimestre = null;

$(function () {
    window.recargarVistaFacturas = cargarVistaTrimestre;
    $('#btnBuscarTrimestre').on('click', cargarVistaTrimestre);
    $('#filtroAnioTrimestre, #filtroTrimestre, #filtroEstadoTrimestre').on('change', cargarVistaTrimestre);
    $('#buscarTrimestre').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            cargarVistaTrimestre();
        }
    });
    cargarVistaTrimestre();
});

function filtrosTrimestre() {
    return {
        anio: $('#filtroAnioTrimestre').val(),
        trimestre: $('#filtroTrimestre').val(),
        estado: $('#filtroEstadoTrimestre').val() || '',
        busqueda: $('#buscarTrimestre').val() || ''
    };
}

function cargarVistaTrimestre() {
    const filtros = filtrosTrimestre();
    $('#periodoTrimestre').text('T' + filtros.trimestre + ' / ' + filtros.anio);
    cargarResumenTrimestre(filtros);
    cargarFacturasTrimestre(filtros);
}

function cargarResumenTrimestre(filtros) {
    $.get('acciones/trimestres-acciones.php', {
        accion: 'resumen',
        anio: filtros.anio,
        trimestre: filtros.trimestre
    }, function (res) {
        if (!res.ok) {
            mostrarToast(res.mensaje, false);
            return;
        }
        const datos = res.datos || {};
        $('#trTotalEmitido').text(formatoEurosTrimestre(datos.total_emitido));
        $('#trTotalCobrado').text(formatoEurosTrimestre(datos.total_cobrado));
        $('#trTotalPendiente').text(formatoEurosTrimestre(datos.total_pendiente));
        $('#trTotalRechazado').text(formatoEurosTrimestre(datos.total_rechazado));
        $('#trIvaTotal').text(formatoEurosTrimestre(datos.iva_total));
        $('#trNumeroFacturas').text(datos.numero_facturas || 0);
        $('#trNumeroBorradores').text(datos.numero_borradores || 0);
        dibujarGraficaTrimestre(datos.estados || {});
    }, 'json').fail(function () {
        mostrarToast('No se pudo cargar el resumen trimestral.', false);
    });
}

function cargarFacturasTrimestre(filtros) {
    $.get('acciones/trimestres-acciones.php', {
        accion: 'listar_facturas',
        anio: filtros.anio,
        trimestre: filtros.trimestre,
        estado: filtros.estado,
        busqueda: filtros.busqueda
    }, function (res) {
        if (!res.ok) {
            mostrarToast(res.mensaje, false);
            return;
        }

        const facturas = res.datos.facturas || [];
        if (!facturas.length) {
            $('#tablaFacturasTrimestre').html('<tr><td colspan="8" class="text-center text-muted py-4">No hay presupuestos en este trimestre.</td></tr>');
            $('#listaFacturasTrimestreMovil').html('<div class="card card-soft p-4 text-center text-muted">No hay presupuestos en este trimestre.</div>');
            return;
        }

        $('#tablaFacturasTrimestre').html(facturas.map(function (f) {
            return `
                <tr>
                    <td>Presupuesto N&ordm; ${escaparTrimestre(f.codigo_factura)}</td>
                    <td>
                        ${escaparTrimestre(f.cliente_nombre)}
                        ${f.cliente_empresa ? `<div class="small text-muted">${escaparTrimestre(f.cliente_empresa)}</div>` : ''}
                    </td>
                    <td>${escaparTrimestre(f.fecha_emision)}</td>
                    <td>${formatoEurosTrimestre(f.subtotal)}</td>
                    <td>${formatoEurosTrimestre(f.iva_total)}</td>
                    <td class="fw-semibold">${formatoEurosTrimestre(f.total)}</td>
                    <td>${badgeEstadoTrimestre(f.estado)}</td>
                    <td class="text-end table-actions">
                        <a class="btn btn-sm btn-outline-primary" href="factura-ver.php?id=${f.id_factura}" title="Ver"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="descargarPdfFactura(${f.id_factura})" title="Descargar presupuesto"><i class="bi bi-file-earmark-pdf"></i></button>
                        <button class="btn btn-sm btn-outline-info" type="button" onclick="abrirModalEnvioFactura(${f.id_factura}, '${escaparAtributoTrimestre(f.cliente_email || '')}', '${escaparAtributoTrimestre(f.codigo_factura)}')" title="Enviar presupuesto"><i class="bi bi-envelope"></i></button>
                        <button class="btn btn-sm btn-outline-success" type="button" onclick="prepararWhatsappFactura(${f.id_factura})" title="WhatsApp"><i class="bi bi-whatsapp"></i></button>
                        <button class="btn btn-sm btn-outline-success" type="button" onclick="cambiarEstadoFactura(${f.id_factura}, 'cobrada')" title="Marcar cobrado"><i class="bi bi-check2-circle"></i></button>
                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="cambiarEstadoFactura(${f.id_factura}, 'rechazada')" title="Marcar rechazado"><i class="bi bi-x-circle"></i></button>
                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="eliminarFactura(${f.id_factura})" title="Eliminar presupuesto"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
        }).join(''));
        $('#listaFacturasTrimestreMovil').html(facturas.map(renderFacturaTrimestreMovil).join(''));
    }, 'json').fail(function () {
        mostrarToast('No se pudo cargar el listado trimestral.', false);
    });
}

function renderFacturaTrimestreMovil(f) {
    return `
        <article class="mobile-record-card">
            <div class="mobile-record-head">
                <strong>Presupuesto N&ordm; ${escaparTrimestre(f.codigo_factura)}</strong>
                ${badgeEstadoTrimestre(f.estado)}
            </div>
            <div class="mobile-record-data">
                <div><span>Cliente</span>${escaparTrimestre(f.cliente_nombre)}</div>
                <div><span>Fecha</span>${escaparTrimestre(f.fecha_emision)}</div>
                <div><span>Base imponible</span>${formatoEurosTrimestre(f.subtotal)}</div>
                <div><span>IVA</span>${formatoEurosTrimestre(f.iva_total)}</div>
                <div><span>Total</span><strong>${formatoEurosTrimestre(f.total)}</strong></div>
            </div>
            <div class="mobile-primary-actions">
                <a class="btn btn-sm btn-outline-primary" href="factura-ver.php?id=${f.id_factura}"><i class="bi bi-eye"></i> Ver</a>
                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="descargarPdfFactura(${f.id_factura})"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button class="btn btn-sm btn-outline-info" type="button" onclick="abrirModalEnvioFactura(${f.id_factura}, '${escaparAtributoTrimestre(f.cliente_email || '')}', '${escaparAtributoTrimestre(f.codigo_factura)}')"><i class="bi bi-envelope"></i> Enviar</button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">Mas</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" type="button" onclick="prepararWhatsappFactura(${f.id_factura})"><i class="bi bi-whatsapp me-2"></i>WhatsApp</button></li>
                        <li><button class="dropdown-item" type="button" onclick="cambiarEstadoFactura(${f.id_factura}, 'cobrada')"><i class="bi bi-check2-circle me-2"></i>Marcar cobrado</button></li>
                        <li><button class="dropdown-item" type="button" onclick="cambiarEstadoFactura(${f.id_factura}, 'rechazada')"><i class="bi bi-x-circle me-2"></i>Marcar rechazado</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item text-danger" type="button" onclick="eliminarFactura(${f.id_factura})"><i class="bi bi-trash me-2"></i>Eliminar</button></li>
                    </ul>
                </div>
            </div>
        </article>`;
}

function dibujarGraficaTrimestre(estados) {
    const canvas = document.getElementById('graficaTrimestreEstados');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }
    const orden = ['borrador', 'emitida', 'enviada', 'cobrada', 'rechazada', 'vencida'];
    const valores = orden.map(function (estado) { return estados[estado] || 0; });
    const colores = ['#6B7280', '#2563EB', '#38BDF8', '#198754', '#DC3545', '#FD7E14'];

    if (graficaTrimestre) {
        graficaTrimestre.destroy();
    }
    graficaTrimestre = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: orden.map(function (estado) { return estado.charAt(0).toUpperCase() + estado.slice(1); }),
            datasets: [{ data: valores, backgroundColor: colores }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function formatoEurosTrimestre(valor) {
    return (parseFloat(valor) || 0).toLocaleString('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' EUR';
}

function badgeEstadoTrimestre(estado) {
    const clases = { borrador: 'secondary', emitida: 'primary', enviada: 'info', cobrada: 'success', rechazada: 'danger', vencida: 'warning' };
    const etiqueta = String(estado || '');
    return `<span class="badge text-bg-${clases[etiqueta] || 'secondary'}">${escaparTrimestre(etiqueta.charAt(0).toUpperCase() + etiqueta.slice(1))}</span>`;
}

function escaparTrimestre(texto) {
    return $('<div>').text(texto === null ? '' : texto).html();
}

function escaparAtributoTrimestre(texto) {
    return String(texto || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, ' ');
}
