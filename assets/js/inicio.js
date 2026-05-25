$(function () {
    const datos = window.JJH_ESTADOS || {};
    const orden = ['borrador', 'emitida', 'enviada', 'cobrada', 'rechazada', 'vencida'];
    const colores = ['#6c757d', '#0d6efd', '#0dcaf0', '#198754', '#dc3545', '#fd7e14'];
    const valores = orden.map(function (estado) { return datos[estado] || 0; });

    const canvas = document.getElementById('graficaEstados');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: orden.map(function (estado) { return estado.charAt(0).toUpperCase() + estado.slice(1); }),
            datasets: [{
                data: valores,
                backgroundColor: colores
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
