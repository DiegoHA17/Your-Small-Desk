<?php
require_once __DIR__ . '/includes/seguridad.php';
exigirSesion();

$tituloPagina = 'Diagnostico Mailrelay';
$jsPagina = 'diagnostico-mailrelay.js';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="d-flex flex-column gap-4">
    <div>
        <h2 class="h4 mb-1">Diagnostico Mailrelay en Railway</h2>
        <p class="text-muted mb-0">Comprueba la API HTTPS y la conectividad SMTP sin mostrar credenciales.</p>
    </div>

    <div class="alert alert-info mb-0" role="alert">
        En Railway Free, Trial y Hobby, el SMTP saliente esta bloqueado. La API Mailrelay por HTTPS es la opcion preferente. En Pro, comprueba SMTP antes de usarlo.
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><i class="bi bi-cloud-check me-2"></i>Configuracion API</h3>
                    <div id="diagnosticoEstadoApi" class="text-muted small">Cargando...</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><i class="bi bi-hdd-network me-2"></i>Configuracion SMTP</h3>
                    <div id="diagnosticoEstadoSmtp" class="text-muted small">Cargando...</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><i class="bi bi-signpost-split me-2"></i>Metodo actual</h3>
                    <div id="diagnosticoMetodo" class="text-muted small">Cargando...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-body p-4">
            <h3 class="h5 mb-3">Pruebas controladas</h3>
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label" for="diagnosticoEmailPrueba">Email destinatario para pruebas de envio</label>
                    <input class="form-control" type="email" id="diagnosticoEmailPrueba" placeholder="destinatario@ejemplo.com">
                    <div class="form-text">La prueba API y la prueba SMTP real envian un correo simple a esta direccion.</div>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="button" id="btnDiagnosticoApi">
                            <i class="bi bi-send-check"></i> Probar API HTTPS
                        </button>
                        <button class="btn btn-outline-primary" type="button" id="btnDiagnosticoSmtpPuerto">
                            <i class="bi bi-ethernet"></i> Comprobar puerto SMTP
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="btnDiagnosticoSmtpEnvio">
                            <i class="bi bi-envelope-check"></i> Probar envio SMTP
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-text mt-3">
                La prueba de puerto SMTP solo abre una conexion TCP; no envia correo. La prueba SMTP real debe utilizarse solo si el puerto es accesible.
            </div>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-body p-4">
            <h3 class="h5 mb-3">Recomendacion</h3>
            <div class="alert alert-secondary mb-0" id="diagnosticoRecomendacion" role="alert">
                Ejecuta la prueba API y la comprobacion SMTP para obtener una recomendacion.
            </div>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-body p-4">
            <h3 class="h5 mb-2">Resultado tecnico</h3>
            <p class="small text-muted">Nunca se muestran API keys ni passwords. La respuesta cruda solo se incluye si el servidor esta en modo local/dev con DEBUG activo.</p>
            <pre class="bg-light border rounded p-3 small text-break mb-0" id="diagnosticoResultado">Sin pruebas ejecutadas.</pre>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/layout-footer.php'; ?>
