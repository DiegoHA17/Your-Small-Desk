$(function () {
    cargarClientes();

    $('#buscarCliente').on('input', function () {
        cargarClientes();
    });

    $('#btnNuevoCliente').on('click', function () {
        $('#formCliente')[0].reset();
        $('#id_cliente').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCliente')).show();
    });

    $('#formCliente').on('submit', function (e) {
        e.preventDefault();
        $.post('acciones/clientes-acciones.php', $(this).serialize() + '&accion=guardar', function (res) {
            mostrarToast(res.mensaje, res.ok);
            if (res.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalCliente')).hide();
                cargarClientes();
            }
        }, 'json');
    });
});

function cargarClientes() {
    $.get('acciones/clientes-acciones.php', {
        accion: 'listar',
        busqueda: $('#buscarCliente').val() || ''
    }, function (res) {
        if (!res.ok) {
            mostrarToast(res.mensaje, false);
            return;
        }

        const clientes = res.datos.clientes || [];
        if (!clientes.length) {
            $('#tablaClientes').html('<tr><td colspan="6" class="text-center text-muted py-4">No hay clientes.</td></tr>');
            $('#listaClientesMovil').html('<div class="card card-soft p-4 text-center text-muted">No hay clientes.</div>');
            return;
        }

        const filas = clientes.map(function (cliente) {
            return `
                <tr>
                    <td>${escapeHtml(cliente.nombre)}</td>
                    <td>${escapeHtml(cliente.empresa || '')}</td>
                    <td>${escapeHtml(cliente.email || '')}</td>
                    <td>${escapeHtml(cliente.telefono || '')}</td>
                    <td>${escapeHtml(cliente.nif_cif || '')}</td>
                    <td class="text-end table-actions">
                        <button class="btn btn-sm btn-outline-primary" type="button" onclick="editarCliente(${cliente.id_cliente})" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="eliminarCliente(${cliente.id_cliente})" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
        }).join('');

        $('#tablaClientes').html(filas);
        $('#listaClientesMovil').html(clientes.map(function (cliente) {
            return `
                <article class="mobile-record-card">
                    <div class="mobile-record-head">
                        <strong>${escapeHtml(cliente.nombre)}</strong>
                    </div>
                    <div class="mobile-record-data">
                        ${cliente.empresa ? `<div><span>Empresa</span>${escapeHtml(cliente.empresa)}</div>` : ''}
                        ${cliente.email ? `<div><span>Email</span>${escapeHtml(cliente.email)}</div>` : ''}
                        ${cliente.telefono ? `<div><span>Telefono</span>${escapeHtml(cliente.telefono)}</div>` : ''}
                    </div>
                    <div class="mobile-primary-actions">
                        <button class="btn btn-sm btn-outline-primary" type="button" onclick="editarCliente(${cliente.id_cliente})"><i class="bi bi-pencil"></i> Editar</button>
                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="eliminarCliente(${cliente.id_cliente})"><i class="bi bi-trash"></i> Eliminar</button>
                    </div>
                </article>`;
        }).join(''));
    }, 'json');
}

function editarCliente(idCliente) {
    $.get('acciones/clientes-acciones.php', { accion: 'obtener', id_cliente: idCliente }, function (res) {
        if (!res.ok) {
            mostrarToast(res.mensaje, false);
            return;
        }

        const c = res.datos.cliente;
        $('#id_cliente').val(c.id_cliente);
        $('#cliente_nombre').val(c.nombre);
        $('#cliente_empresa').val(c.empresa);
        $('#cliente_email').val(c.email);
        $('#cliente_telefono').val(c.telefono);
        $('#cliente_nif_cif').val(c.nif_cif);
        $('#cliente_direccion').val(c.direccion);
        $('#cliente_ciudad').val(c.ciudad);
        $('#cliente_provincia').val(c.provincia);
        $('#cliente_codigo_postal').val(c.codigo_postal);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCliente')).show();
    }, 'json');
}

function eliminarCliente(idCliente) {
    if (!confirm('Eliminar este cliente?')) {
        return;
    }

    $.post('acciones/clientes-acciones.php', { accion: 'eliminar', id_cliente: idCliente }, function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (res.ok) {
            cargarClientes();
        }
    }, 'json');
}

function escapeHtml(texto) {
    return $('<div>').text(texto === null ? '' : texto).html();
}
