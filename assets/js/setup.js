$(function () {
    $('#formSetup').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $('#btnSetup');
        const $error = $('#setupError');
        const $ok = $('#setupOk');
        const data = new FormData(this);

        $error.addClass('d-none').text('');
        $ok.addClass('d-none').text('');
        $btn.prop('disabled', true).data('texto-original', $btn.html()).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando');

        $.ajax({
            url: 'acciones/setup-acciones.php',
            method: 'POST',
            data,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (res) {
            if (!res.ok) {
                $error.removeClass('d-none').text(res.mensaje || 'No se pudo completar la configuracion.');
                return;
            }

            $ok.removeClass('d-none').text(res.mensaje || 'Configuracion completada.');
            window.setTimeout(function () {
                window.location.href = (res.datos && res.datos.redirect) ? res.datos.redirect : 'login.php';
            }, 700);
        }).fail(function () {
            $error.removeClass('d-none').text('No se pudo completar la configuracion inicial.');
        }).always(function () {
            $btn.prop('disabled', false).html($btn.data('texto-original'));
        });
    });
});
