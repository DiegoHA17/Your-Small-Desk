$(function () {
    function enviarFormularioCuenta($form, textoProcesando, textoOriginal, alCompletar) {
        const $boton = $form.find('button[type="submit"]');
        $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> ' + textoProcesando);
        $.post('acciones/configuracion-acciones.php', $form.serialize(), function (res) {
            mostrarToast(res.mensaje, res.ok);
            if (res.ok && typeof alCompletar === 'function') {
                alCompletar(res);
            }
        }, 'json').fail(function () {
            mostrarToast('No se pudo completar la operacion.', false);
        }).always(function () {
            $boton.prop('disabled', false).html(textoOriginal);
        });
    }

    $('#formCambiarCorreo').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const correo = $form.find('[name="nuevo_email"]').val();
        enviarFormularioCuenta(
            $form,
            'Guardando...',
            '<i class="bi bi-envelope-check"></i> Guardar nuevo correo',
            function () {
                $('#correoCuentaActual').text(correo);
                $form[0].reset();
            }
        );
    });

    $('#formCambiarPassword').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        enviarFormularioCuenta(
            $form,
            'Actualizando...',
            '<i class="bi bi-key"></i> Cambiar contrasena',
            function () {
                $form[0].reset();
            }
        );
    });

    $('#btnGenerarTokenSeguridad').on('click', function () {
        const $boton = $(this);
        $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Generando...');
        $.post('acciones/configuracion-acciones.php', {
            accion: 'generar_token_seguridad',
            csrf_token: window.JJH_CSRF || ''
        }, function (res) {
            mostrarToast(res.mensaje, res.ok);
            if (res.ok) {
                $('#tokenSeguridadTexto').text(res.datos.token);
                $('#avisoTokenSeguridad').removeClass('d-none');
            }
        }, 'json').fail(function () {
            mostrarToast('No se pudo generar el token de seguridad.', false);
        }).always(function () {
            $boton.prop('disabled', false).html('<i class="bi bi-shield-lock"></i> Generar token de seguridad');
        });
    });

    function procesarBorrado($form, modalId, textoProcesando, textoOriginal) {
        const $boton = $form.find('button[type="submit"]');
        $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> ' + textoProcesando);
        $.post('acciones/configuracion-acciones.php', $form.serialize(), function (res) {
            mostrarToast(res.mensaje, res.ok);
            if (res.ok) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).hide();
                $('#avisoTokenSeguridad').addClass('d-none');
                $('#tokenSeguridadTexto').text('');
                setTimeout(function () {
                    window.location.reload();
                }, 900);
            }
        }, 'json').fail(function () {
            mostrarToast('No se pudo completar el borrado.', false);
        }).always(function () {
            $boton.prop('disabled', false).text(textoOriginal);
        });
    }

    $('#formEliminarPresupuestos').on('submit', function (e) {
        e.preventDefault();
        procesarBorrado($(this), 'modalEliminarPresupuestos', 'Eliminando...', 'Eliminar presupuestos definitivamente');
    });

    $('#formEliminarClientes').on('submit', function (e) {
        e.preventDefault();
        procesarBorrado($(this), 'modalEliminarClientes', 'Eliminando...', 'Eliminar clientes definitivamente');
    });
});
