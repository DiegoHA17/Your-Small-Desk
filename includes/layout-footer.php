        </section>
    </main>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.JJH_CSRF = <?php echo json_encode(obtenerCsrfToken()); ?>;</script>
<script src="assets/js/app.js"></script>
<?php if (!empty($cargarChart)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<?php if (!empty($jsPagina)): ?>
<script src="assets/js/<?php echo htmlspecialchars($jsPagina, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
</body>
</html>
