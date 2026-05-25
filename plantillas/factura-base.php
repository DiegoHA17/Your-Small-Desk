<?php
$logoPath = realpath(__DIR__ . '/../assets/img/logo-presupuesto-jjh.png');
$logoSrc = ($logoPath && is_file($logoPath)) ? $logoPath : '';
$empresaEmail = trim((string) ($configuracionPdf['email_empresa'] ?: ($configuracionPdf['mailrelay_from_email'] ?? '')));
$empresaTelefono = trim((string) ($configuracionPdf['telefono_empresa'] ?? ''));
$empresaContacto = implode(' · ', array_filter([$empresaEmail, $empresaTelefono], static fn ($dato) => $dato !== ''));
$empresaDireccion = trim(($configuracionPdf['direccion_empresa'] ?? '') . ' ' . ($configuracionPdf['codigo_postal_empresa'] ?? '') . ' ' . ($configuracionPdf['ciudad_empresa'] ?? '') . ' ' . ($configuracionPdf['provincia_empresa'] ?? ''));
$ivaPorcentajePdf = max(0, (float) ($facturaPdf['iva_porcentaje'] ?? 0));
$aplicaIva = $ivaPorcentajePdf > 0;
$fechaEmision = !empty($facturaPdf['fecha_emision']) ? date('d/m/Y', strtotime($facturaPdf['fecha_emision'])) : '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #1F2933; font-size: 10.5px; background: #ffffff; }
        .page { padding: 10mm; background: #ffffff; }
        .paper { background: #ffffff; padding: 5mm; }
        .header { width: 100%; border-bottom: 1.5px solid #174D2A; padding-bottom: 7mm; }
        .company-cell { width: 108mm; vertical-align: top; padding-right: 8mm; }
        .logo { width: 57mm; height: auto; display: block; }
        .company-data { color: #5f6a64; line-height: 1.5; word-wrap: break-word; overflow-wrap: break-word; margin-top: 4mm; }
        .company-data div { margin-bottom: .8mm; }
        .company-data-label { color: #174D2A; font-weight: bold; }
        .invoice-box { text-align: right; vertical-align: top; padding-top: 2mm; width: 62mm; }
        .invoice-title { font-size: 17px; font-weight: bold; color: #174D2A; text-transform: uppercase; word-wrap: break-word; }
        .invoice-date { color: #5f6a64; margin-top: 2mm; }
        .section { margin-top: 7mm; }
        .two-cols { width: 100%; border-collapse: collapse; }
        .two-cols td { vertical-align: top; width: 50%; }
        .box { border: 1px solid #DDE7D8; padding: 4.5mm; background: #ffffff; line-height: 1.45; word-wrap: break-word; overflow-wrap: break-word; }
        .box-title { color: #174D2A; font-weight: bold; font-size: 11px; margin-bottom: 2.5mm; text-transform: uppercase; }
        .lines { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 8mm; }
        .lines .col-cantidad { width: 12%; }
        .lines .col-concepto { width: 44%; }
        .lines .col-precio { width: 22%; }
        .lines .col-total { width: 22%; }
        .lines th { background: #EEF6EA; color: #174D2A; padding: 3mm; border-bottom: 1px solid #174D2A; text-align: left; text-transform: uppercase; font-size: 9.5px; }
        .lines td { padding: 3mm; border-bottom: 1px solid #DDE7D8; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        .lines tr { page-break-inside: avoid; }
        .descripcion { white-space: normal; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.35; }
        .texto-concepto-presupuesto { white-space: normal; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.35; }
        .right { text-align: right; }
        .amount { white-space: normal; word-wrap: break-word; overflow-wrap: break-word; text-align: right; line-height: 1.35; }
        .center { text-align: center; }
        .totals-wrap { width: 100%; border-collapse: collapse; margin-top: 6mm; }
        .totals { width: 76mm; border-collapse: collapse; table-layout: fixed; }
        .totals td { width: 50%; padding: 3mm; border-bottom: 1px solid #DDE7D8; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        .totals .final td { border-top: 2px solid #174D2A; background: #EEF6EA; color: #174D2A; font-size: 13px; font-weight: bold; border-bottom: none; }
        .notes { margin-top: 8mm; border: 1px solid #DDE7D8; padding: 4.5mm; background: #ffffff; min-height: 15mm; word-wrap: break-word; overflow-wrap: break-word; }
        .observaciones-texto { white-space: normal; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.4; }
        .signature { margin-top: 14mm; color: #6B7280; }
        .signature-line { width: 70mm; border-top: 1px solid #6B7280; margin-bottom: 3mm; }
    </style>
</head>
<body>
<div class="page">
    <div class="paper">
        <table class="header">
            <tr>
                <td class="company-cell">
                    <?php if ($logoSrc): ?><img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" class="logo" alt="Podas y Talas JJH"><?php endif; ?>
                    <div class="company-data">
                        <?php if ($empresaContacto !== ''): ?><div><?php echo htmlspecialchars($empresaContacto, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        <?php if (!empty($configuracionPdf['nif_cif_empresa'])): ?><div><span class="company-data-label">NIF/CIF:</span> <?php echo htmlspecialchars($configuracionPdf['nif_cif_empresa'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        <?php if ($empresaDireccion !== ''): ?><div><?php echo htmlspecialchars($empresaDireccion, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                </td>
                <td class="invoice-box">
                    <div class="invoice-title">PRESUPUESTO N&ordm; <?php echo (int) $facturaPdf['codigo_factura']; ?></div>
                    <div class="invoice-date">Fecha de emision: <?php echo htmlspecialchars($fechaEmision); ?></div>
                </td>
            </tr>
        </table>

        <table class="two-cols section">
            <tr>
                <td style="padding-right: 4mm;">
                    <div class="box">
                        <div class="box-title">Datos del cliente</div>
                        <strong><?php echo htmlspecialchars($facturaPdf['cliente_nombre'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <?php if (!empty($facturaPdf['cliente_empresa'])): ?><?php echo htmlspecialchars($facturaPdf['cliente_empresa'], ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                        <?php if (!empty($facturaPdf['cliente_nif_cif'])): ?>NIF/CIF: <?php echo htmlspecialchars($facturaPdf['cliente_nif_cif'], ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                        <?php if (!empty($facturaPdf['cliente_direccion'])): ?><?php echo htmlspecialchars($facturaPdf['cliente_direccion'], ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                        <?php echo htmlspecialchars(trim(($facturaPdf['cliente_codigo_postal'] ?? '') . ' ' . ($facturaPdf['cliente_ciudad'] ?? '') . ' ' . ($facturaPdf['cliente_provincia'] ?? '')), ENT_QUOTES, 'UTF-8'); ?><br>
                        <?php echo htmlspecialchars($facturaPdf['cliente_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </td>
                <td style="padding-left: 4mm;">
                    <div class="box">
                        <div class="box-title">Datos del presupuesto</div>
                        <div class="texto-concepto-presupuesto"><strong>Concepto:</strong> <?php echo htmlspecialchars($facturaPdf['concepto_general'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div><strong>Categoria:</strong> <?php echo $aplicaIva ? 'Con IVA' : 'Sin IVA'; ?></div>
                        <?php if ($aplicaIva): ?><div><strong>IVA:</strong> <?php echo number_format($ivaPorcentajePdf, 2, ',', '.'); ?>%</div><?php endif; ?>
                        <div><strong>Estado:</strong> <?php echo htmlspecialchars($facturaPdf['estado'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="lines">
            <thead>
                <tr>
                    <th class="center col-cantidad">Cant.</th>
                    <th class="col-concepto">Concepto</th>
                    <th class="right col-precio">Precio unitario</th>
                    <th class="right col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lineasPdf as $linea): ?>
                <tr>
                    <td class="center"><?php echo number_format((float) $linea['cantidad'], 2, ',', '.'); ?></td>
                    <td class="descripcion"><?php echo htmlspecialchars($linea['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="amount"><?php echo formatoEuros((float) $linea['precio_unitario']); ?></td>
                    <td class="amount"><?php echo formatoEuros((float) $linea['total_linea']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="totals-wrap">
            <tr>
                <td></td>
                <td style="width: 72mm;">
                    <table class="totals">
                        <tr><td>Subtotal</td><td class="amount"><?php echo formatoEuros((float) $facturaPdf['subtotal']); ?></td></tr>
                        <?php if ($aplicaIva): ?>
                        <tr><td>IVA <?php echo number_format($ivaPorcentajePdf, 2, ',', '.'); ?>%</td><td class="amount"><?php echo formatoEuros((float) $facturaPdf['iva_total']); ?></td></tr>
                        <?php endif; ?>
                        <tr class="final"><td>TOTAL</td><td class="amount"><?php echo formatoEuros((float) $facturaPdf['total']); ?></td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="notes">
            <div class="box-title">Observaciones</div>
            <div class="observaciones-texto"><?php echo nl2br(htmlspecialchars($facturaPdf['observaciones'] ?: '', ENT_QUOTES, 'UTF-8')); ?></div>
        </div>

        <div class="signature">
            <div class="signature-line"></div>
            Firma / conformidad del cliente
        </div>
    </div>
</div>
</body>
</html>
