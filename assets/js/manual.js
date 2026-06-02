$(function () {
    const modal = document.getElementById('modalManual');
    const textoNodo = document.getElementById('manualTexto');
    const salida = document.getElementById('manualTypewriter');
    const botonTodo = document.getElementById('btnManualMostrarTodo');
    if (!modal || !textoNodo || !salida || !botonTodo) return;

    const texto = textoNodo.textContent.trim();
    let indice = 0;
    let temporizador = null;

    function parar() {
        if (temporizador) {
            clearTimeout(temporizador);
            temporizador = null;
        }
    }

    function mostrarTodo() {
        parar();
        salida.textContent = texto;
        indice = texto.length;
    }

    function escribir() {
        if (indice >= texto.length) {
            parar();
            return;
        }
        indice += texto.charAt(indice) === '\n' ? 2 : 3;
        salida.textContent = texto.slice(0, indice);
        salida.scrollTop = salida.scrollHeight;
        temporizador = setTimeout(escribir, 8);
    }

    $(modal).on('shown.bs.modal', function () {
        parar();
        indice = 0;
        salida.textContent = '';
        escribir();
    });

    $(modal).on('hidden.bs.modal', parar);
    $(botonTodo).on('click', mostrarTodo);
});
