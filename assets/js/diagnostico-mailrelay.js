$(function () {
    let resultadoApi = null;
    let resultadoSmtpPuerto = null;

    function valorEstado(valor) {
        return valor ? '<span class="badge text-bg-success">Configurado</span>' : '<span class="badge text-bg-danger">Falta</span>';
    }

    function pintarEstado(datos) {
        const api = datos.api || {};
        const smtp = datos.smtp || {};
        $('#diagnosticoEstadoApi').html(
            '<div class="mb-2">URL: ' + valorEstado(api.url_configurada && api.endpoint_valido) + '</div>'
            + '<div class="mb-2">API key: ' + valorEstado(api.api_key_configurada) + '</div>'
            + '<div>Remitente: ' + valorEstado(api.from_email_configurado && api.from_name_configurado) + '</div>'
        );
        $('#diagnosticoEstadoSmtp').html(
            '<div class="mb-2">Host/puerto: ' + valorEstado(smtp.host_configurado && smtp.puerto_configurado) + '</div>'
            + '<div class="mb-2">Usuario: ' + valorEstado(smtp.usuario_configurado) + '</div>'
            + '<div>Password: ' + valorEstado(smtp.smtp_password_configurada) + '</div>'
        );
        const metodo = datos.metodo_presupuestos === 'api' ? 'API Mailrelay' : 'SMTP Mailrelay';
        $('#diagnosticoMetodo').text(metodo + (datos.debug_detallado ? ' (debug local/dev activo)' : ''));
    }

    function escribirResultado(titulo, respuesta) {
        $('#diagnosticoResultado').text(titulo + '\n' + JSON.stringify(respuesta, null, 2));
    }

    function actualizarRecomendacion() {
        let texto = 'Ejecuta la prueba API y la comprobacion SMTP para obtener una recomendacion.';
        let clase = 'alert-secondary';

        if (resultadoApi && resultadoApi.datos && resultadoApi.datos.cuenta_en_revision) {
            texto = 'Contacta con Mailrelay para que apruebe la cuenta. La API HTTPS conecta, pero Mailrelay todavia no permite enviar.';
            clase = 'alert-warning';
        } else if (resultadoApi && resultadoApi.ok && resultadoSmtpPuerto && !resultadoSmtpPuerto.ok) {
            texto = 'Usa Mailrelay API. SMTP esta bloqueado o no disponible desde este servicio.';
            clase = 'alert-success';
        } else if (resultadoApi && resultadoApi.ok && resultadoSmtpPuerto && resultadoSmtpPuerto.ok) {
            texto = 'La API funciona y SMTP es accesible. Usa API si quieres evitar bloqueos SMTP; usa SMTP solo si necesitas sus capacidades confirmadas.';
            clase = 'alert-success';
        } else if (resultadoApi && !resultadoApi.ok && resultadoSmtpPuerto && !resultadoSmtpPuerto.ok) {
            texto = 'Revisa la configuracion o respuesta de Mailrelay API. SMTP no es una alternativa disponible desde este servicio.';
            clase = 'alert-danger';
        } else if (resultadoApi && !resultadoApi.ok) {
            texto = 'Revisa MAILRELAY API: URL, clave guardada, remitente y respuesta devuelta por Mailrelay.';
            clase = 'alert-warning';
        } else if (resultadoSmtpPuerto && !resultadoSmtpPuerto.ok) {
            texto = 'El servidor probablemente bloquea SMTP. Usa Mailrelay API por HTTPS o un entorno con SMTP habilitado.';
            clase = 'alert-warning';
        }

        $('#diagnosticoRecomendacion')
            .removeClass('alert-secondary alert-success alert-warning alert-danger')
            .addClass(clase)
            .text(texto);
    }

    function emailPrueba() {
        return ($('#diagnosticoEmailPrueba').val() || '').trim();
    }

    $.post('acciones/envios-acciones.php', {
        accion: 'estado_diagnostico_mailrelay',
        csrf_token: window.JJH_CSRF || ''
    }, function (res) {
        if (res.ok) {
            pintarEstado(res.datos || {});
        } else {
            escribirResultado('No se pudo cargar el estado.', res);
        }
    }, 'json').fail(function () {
        escribirResultado('No se pudo cargar el estado.', { ok: false, mensaje: 'Error de comunicacion.' });
    });

    $('#btnDiagnosticoApi').on('click', function () {
        const destinatario = emailPrueba();
        if (!destinatario) {
            mostrarToast('Indica un email de prueba para enviar por API.', false);
            return;
        }
        if (!confirm('Se enviara un correo simple real por Mailrelay API a ' + destinatario + '. Continuar?')) {
            return;
        }
        const $boton = $(this).prop('disabled', true);
        $.post('acciones/envios-acciones.php', {
            accion: 'probar_mailrelay_simple',
            csrf_token: window.JJH_CSRF || '',
            destinatario: destinatario,
            asunto: 'Prueba Mailrelay API - Your Small Desk',
            mensaje: 'Prueba de envio por API desde Your Small Desk.'
        }, function (res) {
            resultadoApi = res;
            mostrarToast(res.mensaje, res.ok);
            escribirResultado('Prueba API HTTPS', res);
            actualizarRecomendacion();
        }, 'json').fail(function () {
            mostrarToast('No se pudo ejecutar la prueba API.', false);
        }).always(function () {
            $boton.prop('disabled', false);
        });
    });

    $('#btnDiagnosticoSmtpPuerto').on('click', function () {
        const $boton = $(this).prop('disabled', true);
        $.post('acciones/envios-acciones.php', {
            accion: 'probar_conectividad_smtp',
            csrf_token: window.JJH_CSRF || ''
        }, function (res) {
            resultadoSmtpPuerto = res;
            mostrarToast(res.mensaje, res.ok);
            escribirResultado('Conectividad SMTP', res);
            actualizarRecomendacion();
        }, 'json').fail(function () {
            mostrarToast('No se pudo ejecutar la comprobacion SMTP.', false);
        }).always(function () {
            $boton.prop('disabled', false);
        });
    });

    $('#btnDiagnosticoSmtpEnvio').on('click', function () {
        const destinatario = emailPrueba();
        if (!destinatario) {
            mostrarToast('Indica un email de prueba para enviar por SMTP.', false);
            return;
        }
        if (!resultadoSmtpPuerto || !resultadoSmtpPuerto.ok) {
            mostrarToast('Comprueba primero que el puerto SMTP sea accesible.', false);
            return;
        }
        if (!confirm('Se enviara un correo simple real por SMTP a ' + destinatario + '. Continuar?')) {
            return;
        }
        const $boton = $(this).prop('disabled', true);
        $.post('acciones/envios-acciones.php', {
            accion: 'probar_mailrelay_smtp_simple',
            csrf_token: window.JJH_CSRF || '',
            destinatario: destinatario,
            asunto: 'Prueba SMTP Your Small Desk',
            mensaje: 'Prueba de envio SMTP desde Your Small Desk.'
        }, function (res) {
            mostrarToast(res.mensaje, res.ok);
            escribirResultado('Prueba de envio SMTP', res);
        }, 'json').fail(function () {
            mostrarToast('No se pudo ejecutar la prueba SMTP.', false);
        }).always(function () {
            $boton.prop('disabled', false);
        });
    });
});
