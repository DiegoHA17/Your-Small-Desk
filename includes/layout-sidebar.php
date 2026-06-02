<?php
$paginaActual = basename($_SERVER['PHP_SELF']);
$nombreEmpresaSidebar = 'Your Small Desk';
if (function_exists('obtenerConfiguracion')) {
    $configSidebar = obtenerConfiguracion();
    $nombreEmpresaSidebar = trim((string) ($configSidebar['nombre_comercial'] ?: ($configSidebar['nombre_empresa'] ?? ''))) ?: 'Your Small Desk';
}

function navActivo(string $archivo, string $paginaActual): string
{
    return $archivo === $paginaActual ? 'active' : '';
}

function renderizarEnlacesSidebar(string $paginaActual): void
{
    ?>
    <nav class="sidebar-nav" aria-label="Menu principal">
        <a class="sidebar-link <?php echo navActivo('inicio.php', $paginaActual); ?>" href="inicio.php"><i class="bi bi-speedometer2"></i><span>Inicio</span></a>
        <a class="sidebar-link <?php echo navActivo('clientes.php', $paginaActual); ?>" href="clientes.php"><i class="bi bi-people"></i><span>Clientes</span></a>
        <a class="sidebar-link <?php echo navActivo('facturas.php', $paginaActual); ?>" href="facturas.php"><i class="bi bi-receipt"></i><span>Presupuestos</span></a>
        <a class="sidebar-link <?php echo navActivo('factura-nueva.php', $paginaActual); ?>" href="factura-nueva.php"><i class="bi bi-plus-circle"></i><span>Nuevo presupuesto</span></a>
        <a class="sidebar-link sidebar-link-external menu-factura-externa" href="https://app.b2brouter.net/login?back_url=https%3A%2F%2Fapp.b2brouter.net%2Fdashboard" target="_blank" rel="noopener noreferrer" title="Abrir B2Brouter en otra pestana">
            <i class="bi bi-receipt"></i>
            <span>Crear factura</span>
            <i class="bi bi-box-arrow-up-right ms-auto icono-externo"></i>
        </a>
        <a class="sidebar-link <?php echo navActivo('trimestres.php', $paginaActual); ?>" href="trimestres.php"><i class="bi bi-calendar3"></i><span>Trimestres</span></a>
        <a class="sidebar-link <?php echo navActivo('configuracion.php', $paginaActual); ?>" href="configuracion.php"><i class="bi bi-gear"></i><span>Configuracion</span></a>
        <a class="sidebar-link sidebar-link-logout" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Cerrar sesion</span></a>
    </nav>
    <?php
}
?>
<aside class="sidebar-desktop d-none d-lg-flex" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-title"><?php echo htmlspecialchars($nombreEmpresaSidebar, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="sidebar-brand-subtitle">Your Small Desk</div>
    </div>
    <?php renderizarEnlacesSidebar($paginaActual); ?>
</aside>

<aside class="offcanvas offcanvas-start sidebar-mobile d-lg-none" tabindex="-1" id="sidebarMovil" aria-labelledby="sidebarMovilTitulo">
    <div class="offcanvas-header sidebar-mobile-header">
        <div class="d-flex align-items-center gap-2">
            <img src="assets/img/logo-jjh.png" alt="" class="brand-logo">
            <div>
                <div class="sidebar-brand-title" id="sidebarMovilTitulo">Your Small Desk</div>
                <div class="sidebar-brand-subtitle"><?php echo htmlspecialchars($nombreEmpresaSidebar, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMovil" aria-label="Cerrar menu"></button>
    </div>
    <div class="offcanvas-body sidebar-mobile-body">
        <?php renderizarEnlacesSidebar($paginaActual); ?>
    </div>
</aside>
