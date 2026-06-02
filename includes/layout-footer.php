        </section>
        <?php
        $versionApp = is_file(__DIR__ . '/../VERSION') ? trim((string) file_get_contents(__DIR__ . '/../VERSION')) : '0.1.0';
        ?>
        <footer class="app-copyright">Your Small Desk &middot; Desarrollado por Diego Herrera Ayuso &middot; Version <?php echo htmlspecialchars($versionApp, ENT_QUOTES, 'UTF-8'); ?></footer>
    </main>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.JJH_CSRF = <?php echo json_encode(obtenerCsrfToken(), JSON_UNESCAPED_UNICODE); ?>;</script>
<script>window.JJH_EMPRESA_NOMBRE = <?php echo json_encode($nombreEmpresaLayout ?? 'Your Small Desk', JSON_UNESCAPED_UNICODE); ?>;</script>
<script src="assets/js/app.js"></script>
<?php if (!empty($cargarChart)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<?php if (!empty($jsPagina)): ?>
<script src="assets/js/<?php echo htmlspecialchars($jsPagina, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
<?php foreach (($jsExtra ?? []) as $archivoJsExtra): ?>
<script src="assets/js/<?php echo htmlspecialchars($archivoJsExtra, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>
</body>
</html>
