<?php
require_once __DIR__ . '/includes/seguridad.php';
exigirSesion();

$tituloPagina = 'Clientes';
$jsPagina = 'clientes.js';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between mb-3">
    <div class="input-group" style="max-width: 420px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input class="form-control" type="search" id="buscarCliente" placeholder="Buscar por nombre, empresa o email">
    </div>
    <button class="btn btn-primary" type="button" id="btnNuevoCliente">
        <i class="bi bi-plus-circle"></i> Nuevo cliente
    </button>
</div>

<div class="card card-soft desktop-data-table">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Empresa</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>NIF/CIF</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaClientes">
                <tr><td colspan="6" class="text-center text-muted py-4">Cargando clientes...</td></tr>
            </tbody>
        </table>
    </div>
</div>
<div class="mobile-record-list d-md-none" id="listaClientesMovil">
    <div class="card card-soft p-4 text-center text-muted">Cargando clientes...</div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-lg">
        <form class="modal-content" id="formCliente">
            <div class="modal-header">
                <h5 class="modal-title">Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_cliente" id="id_cliente">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input class="form-control" name="nombre" id="cliente_nombre" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Empresa</label>
                        <input class="form-control" name="empresa" id="cliente_empresa">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" id="cliente_email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefono</label>
                        <input class="form-control" name="telefono" id="cliente_telefono">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NIF/CIF</label>
                        <input class="form-control" name="nif_cif" id="cliente_nif_cif">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Direccion</label>
                        <input class="form-control" name="direccion" id="cliente_direccion">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ciudad</label>
                        <input class="form-control" name="ciudad" id="cliente_ciudad">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Provincia</label>
                        <input class="form-control" name="provincia" id="cliente_provincia">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Codigo postal</label>
                        <input class="form-control" name="codigo_postal" id="cliente_codigo_postal">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
