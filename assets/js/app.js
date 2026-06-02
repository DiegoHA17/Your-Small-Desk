$(function () {
    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            if ((settings.type || 'GET').toUpperCase() !== 'GET') {
                xhr.setRequestHeader('X-CSRF-Token', window.JJH_CSRF || '');
            }
        }
    });

    $('#sidebarMovil .sidebar-link').on('click', function () {
        const sidebarMovil = document.getElementById('sidebarMovil');
        const offcanvas = bootstrap.Offcanvas.getInstance(sidebarMovil);
        if (offcanvas) {
            offcanvas.hide();
        }
    });

    $('#formConfiguracion').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $boton = $form.find('button[type="submit"]');
        const passwordSmtpInformada = ($form.find('[name="mailrelay_smtp_password"]').val() || '').trim() !== '';
        $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        $.ajax({
            url: 'configuracion.php',
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (res) {
            mostrarToast(res.mensaje, res.ok);
            if (res.ok) {
                $form.find('[name="mailrelay_api_key"]').val('').attr('placeholder', 'Clave configurada');
                if (passwordSmtpInformada) {
                    $form.find('[name="mailrelay_smtp_password"]').val('').attr('placeholder', 'Contrasena configurada');
                }
                if (res.datos && res.datos.logo_documento_url) {
                    $('#logoDocumentoActual').attr('src', res.datos.logo_documento_url).removeClass('d-none');
                    $('#logoDocumentoVacio').addClass('d-none');
                    $('[name="eliminar_logo_documento"]').prop('checked', false);
                    $('[name="logo_documento"]').val('');
                }
            }
        }).fail(function () {
            mostrarToast('No se pudo guardar la configuracion.', false);
        }).always(function () {
            $boton.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar configuracion');
        });
    });

    $('#configTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        window.sessionStorage.setItem('jjh_config_tab', e.target.id);
    });
    const configTabGuardada = window.sessionStorage.getItem('jjh_config_tab');
    if (configTabGuardada && document.getElementById(configTabGuardada)) {
        bootstrap.Tab.getOrCreateInstance(document.getElementById(configTabGuardada)).show();
    }

    function actualizarAvisoBccMetodo() {
        const bccConApi = $('[name="mailrelay_metodo_envio_facturas"]').val() === 'api'
            && $('#mailrelayBccActivo').is(':checked');
        $('#alertaBccMetodoApi').toggleClass('d-none', !bccConApi);
    }

    $('[name="mailrelay_metodo_envio_facturas"], #mailrelayBccActivo').on('change', actualizarAvisoBccMetodo);
    actualizarAvisoBccMetodo();

    $('#btnProbarMailrelay').on('click', function () {
        const destinatario = ($('#mailrelayEmailPrueba').val() || '').trim();
        if (!destinatario) {
            mostrarToast('Indica un email de prueba.', false);
            return;
        }
        if (!confirm('Se enviara un correo de prueba real a ' + destinatario + '. Continuar?')) {
            return;
        }

        const $boton = $(this);
        const $diagnostico = $('#mailrelayDiagnostico').addClass('d-none').text('');
        $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Probando...');

        $.post('acciones/envios-acciones.php', {
            accion: 'probar_mailrelay_simple',
            csrf_token: window.JJH_CSRF || '',
            destinatario: destinatario,
            asunto: 'Prueba Mailrelay - Your Small Desk',
            mensaje: 'Mensaje de prueba enviado desde Your Small Desk para validar la configuracion de Mailrelay.'
        }, function (res) {
            mostrarToast(res.mensaje, res.ok);
            if (res.datos && Object.keys(res.datos).length) {
                $diagnostico.removeClass('d-none').text(JSON.stringify(res.datos, null, 2));
            }
        }, 'json').fail(function () {
            mostrarToast('No se pudo ejecutar la prueba de Mailrelay.', false);
        }).always(function () {
            $boton.prop('disabled', false).html('<i class="bi bi-envelope-check"></i> Probar envio Mailrelay');
        });
    });

    $('#btnProbarMailrelayTxt').on('click', function () {
        const destinatario = ($('#mailrelayEmailPrueba').val() || '').trim();
        const variante = ($('#mailrelayVarianteAdjunto').val() || 'a').toLowerCase();
        if (!destinatario) {
            mostrarToast('Indica un email de prueba.', false);
            return;
        }
        if (!confirm('Se enviara un correo real con el archivo prueba.txt (' + variante.toUpperCase() + ') a ' + destinatario + '. Continuar?')) {
            return;
        }

        const $boton = $(this);
        const $diagnostico = $('#mailrelayDiagnostico').addClass('d-none').text('');
        $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Probando...');

        $.post('acciones/envios-acciones.php', {
            accion: 'probar_mailrelay_adjunto_txt',
            csrf_token: window.JJH_CSRF || '',
            destinatario: destinatario,
            variante_adjunto: variante,
            asunto: 'Prueba de adjunto TXT - Your Small Desk',
            mensaje: 'Prueba de adjunto desde Your Small Desk.'
        }, function (res) {
            mostrarToast(res.mensaje, res.ok);
            if (res.datos && Object.keys(res.datos).length) {
                $diagnostico.removeClass('d-none').text(JSON.stringify(res.datos, null, 2));
            }
        }, 'json').fail(function () {
            mostrarToast('No se pudo ejecutar la prueba de adjunto.', false);
        }).always(function () {
            $boton.prop('disabled', false).html('<i class="bi bi-paperclip"></i> Probar adjunto TXT');
        });
    });

    $('#btnProbarMailrelaySmtp').on('click', function () {
        const destinatario = ($('#mailrelayEmailPruebaSmtp').val() || '').trim();
        if (!destinatario) {
            mostrarToast('Indica un email de prueba SMTP.', false);
            return;
        }
        if (!confirm('Se enviara un correo SMTP real a ' + destinatario + '. Continuar?')) {
            return;
        }

        const $boton = $(this);
        const $diagnostico = $('#mailrelayDiagnosticoSmtp').addClass('d-none').text('');
        $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Probando...');

        $.post('acciones/envios-acciones.php', {
            accion: 'probar_mailrelay_smtp_simple',
            csrf_token: window.JJH_CSRF || '',
            destinatario: destinatario,
            asunto: 'Prueba SMTP Your Small Desk',
            mensaje: 'Prueba de envio SMTP desde Your Small Desk.'
        }, function (res) {
            mostrarToast(res.mensaje, res.ok);
            if (res.datos && Object.keys(res.datos).length) {
                $diagnostico.removeClass('d-none').text(JSON.stringify(res.datos, null, 2));
            }
        }, 'json').fail(function () {
            mostrarToast('No se pudo ejecutar la prueba SMTP.', false);
        }).always(function () {
            $boton.prop('disabled', false).html('<i class="bi bi-envelope-check"></i> Probar envio SMTP');
        });
    });
});

function mostrarToast(mensaje, ok) {
    const tipo = ok ? 'success' : 'danger';
    const id = 'toast-' + Date.now();
    const $toast = $('<div>', {
        id: id,
        class: 'toast align-items-center text-bg-' + tipo + ' border-0',
        role: 'alert',
        'aria-live': 'assertive',
        'aria-atomic': 'true'
    });
    const $contenido = $('<div>', { class: 'd-flex' });
    $contenido.append($('<div>', { class: 'toast-body' }).text(String(mensaje || '')));
    $contenido.append($('<button>', {
        type: 'button',
        class: 'btn-close btn-close-white me-2 m-auto',
        'data-bs-dismiss': 'toast',
        'aria-label': 'Cerrar'
    }));
    $toast.append($contenido);
    $('#toastContainer').append($toast);
    const toast = new bootstrap.Toast(document.getElementById(id), { delay: 5600 });
    toast.show();
}

function abrirModalEnvioFactura(idFactura, destinatario, codigoFactura) {
    $.post('acciones/facturas-acciones.php', {
        accion: 'descargar_pdf',
        id_factura: idFactura
    }, function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (!res.ok) return;

        $('#envio_id_factura').val(idFactura);
        $('#envio_destinatario').val(destinatario || '');
        const empresa = window.JJH_EMPRESA_NOMBRE || 'Your Small Desk';
        $('#envio_asunto').val('Presupuesto N\u00ba ' + codigoFactura + ' - ' + empresa);
        $('#envio_mensaje').val('Hola,\n\nTe adjunto el presupuesto solicitado.\n\nQuedo pendiente de cualquier duda.\n\nUn saludo,\n' + empresa);
        $('#diagnosticoEnvioMailrelay').remove();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEnvio')).show();
    }, 'json').fail(function () {
        mostrarToast('No se pudo preparar el envio.', false);
    });
}

$(document).on('submit', '#formEnvioFactura', function (e) {
    e.preventDefault();
    const $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Enviando...');

    const datosEnvio = $(this).serialize() + '&accion=enviar_correo&csrf_token=' + encodeURIComponent(window.JJH_CSRF || '');
    $.post('acciones/envios-acciones.php', datosEnvio, function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (res.ok) {
            bootstrap.Modal.getInstance(document.getElementById('modalEnvio')).hide();
            setTimeout(function () {
                if (typeof window.recargarVistaFacturas === 'function') {
                    window.recargarVistaFacturas();
                } else {
                    window.location.reload();
                }
            }, 700);
        } else {
            mostrarDiagnosticoEnvioMailrelay(res.datos || {});
        }
    }, 'json').fail(function () {
        mostrarToast('No se pudo enviar la solicitud.', false);
    }).always(function () {
        $btn.prop('disabled', false).html('<i class="bi bi-send"></i> Enviar correo');
    });
});

function mostrarDiagnosticoEnvioMailrelay(datos) {
    $('#diagnosticoEnvioMailrelay').remove();
    if (!Object.prototype.hasOwnProperty.call(datos, 'respuesta_raw')
        && !Object.prototype.hasOwnProperty.call(datos, 'curl_error')
        && !Object.prototype.hasOwnProperty.call(datos, 'error_phpmailer')
        && !Object.prototype.hasOwnProperty.call(datos, 'fallback_desde_api')) {
        return;
    }

    const $bloque = $('<pre>', {
        id: 'diagnosticoEnvioMailrelay',
        class: 'bg-light border rounded p-3 mt-3 small text-break'
    }).text(JSON.stringify(datos, null, 2));

    $('#formEnvioFactura .modal-body').append($bloque);
}

function cambiarEstadoFactura(idFactura, estado) {
    if (!confirm('Confirmar cambio de estado a ' + estado + '?')) {
        return;
    }

    $.post('acciones/facturas-acciones.php', {
        accion: 'cambiar_estado',
        id_factura: idFactura,
        estado: estado
    }, function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (res.ok) {
            setTimeout(function () {
                if (typeof window.recargarVistaFacturas === 'function') {
                    window.recargarVistaFacturas();
                } else if ($('#tablaFacturas').length && typeof cargarFacturas === 'function') {
                    cargarFacturas();
                } else {
                    window.location.reload();
                }
            }, 600);
        }
    }, 'json').fail(function () {
        mostrarToast('No se pudo actualizar el estado.', false);
    });
}

function eliminarFactura(idFactura) {
    if (!confirm('Eliminar este presupuesto y sus envios registrados?')) {
        return;
    }

    $.post('acciones/facturas-acciones.php', {
        accion: 'eliminar_factura',
        id_factura: idFactura
    }, function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (res.ok) {
            if (typeof window.recargarVistaFacturas === 'function') {
                window.recargarVistaFacturas();
            } else if ($('#tablaFacturas').length) {
                cargarFacturas();
            } else {
                window.location.href = 'facturas.php';
            }
        }
    }, 'json').fail(function () {
        mostrarToast('No se pudo eliminar el presupuesto.', false);
    });
}

function descargarPdfFactura(idFactura) {
    $.post('acciones/facturas-acciones.php', {
        accion: 'descargar_pdf',
        id_factura: idFactura
    }, function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (res.ok && res.datos.url_descarga_pdf) {
            window.location.href = res.datos.url_descarga_pdf;
        }
    }, 'json').fail(function () {
        mostrarToast('No se pudo preparar la descarga del PDF.', false);
    });
}

function prepararWhatsappFactura(idFactura) {
    $.post('acciones/facturas-acciones.php', {
        accion: 'preparar_whatsapp',
        id_factura: idFactura
    }, function (res) {
        mostrarToast(res.mensaje, res.ok);
        if (res.ok) {
            compartirFacturaWhatsapp(res.datos);
        }
    }, 'json').fail(function () {
        mostrarToast('No se pudo preparar WhatsApp.', false);
    });
}

async function compartirFacturaWhatsapp(datos) {
    try {
        const respuesta = await fetch(datos.url_descarga_pdf, { credentials: 'same-origin' });
        if (!respuesta.ok) {
            throw new Error('PDF no disponible');
        }
        const blob = await respuesta.blob();
        const archivo = new File([blob], datos.nombre_pdf, { type: 'application/pdf' });

        if (navigator.canShare && navigator.share && navigator.canShare({ files: [archivo] })) {
            await navigator.share({
                files: [archivo],
                title: 'Presupuesto N\u00ba ' + (datos.codigo_factura || datos.id_factura),
                text: datos.mensaje_whatsapp
            });
            return;
        }
    } catch (e) {
        if (e && e.name === 'AbortError') {
            return;
        }
    }

    window.location.href = datos.url_descarga_pdf;
    window.open(datos.url_whatsapp, '_blank');
    mostrarToast('Se ha generado y descargado el PDF. WhatsApp se abrira con el mensaje preparado. Adjunta el PDF manualmente si no se adjunta automaticamente.', true);
}
