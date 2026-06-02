# Licencias de terceros

Este inventario resume dependencias principales detectadas antes de publicar Your Small Desk.

## PHP / Composer

| Dependencia | Version detectada | Licencia declarada | Fuente |
| --- | ---: | --- | --- |
| PHP portable para Windows | 8.x | PHP License 3.01 | `desktop/php/license.txt` en build local |
| `mpdf/mpdf` | v8.3.1 | GPL-2.0-only | `composer.lock` |
| `phpmailer/phpmailer` | v6.12.0 | LGPL-2.1-only | `composer.lock` |

El instalador de Windows puede incluir PHP portable para que la app funcione con doble clic sin instalar PHP manualmente. La redistribucion de PHP exige conservar el aviso de licencia y la atribucion del PHP Group. El build conserva `license.txt` dentro de `resources/php/`.

## Node / Electron

| Dependencia | Version detectada | Licencia declarada | Fuente |
| --- | ---: | --- | --- |
| `electron` | 30.5.1 | MIT | `desktop/package-lock.json` |
| `electron-builder` | 24.13.3 | MIT | `desktop/package-lock.json` |

## Frontend por CDN

| Recurso | Version usada | Licencia conocida | Uso |
| --- | ---: | --- | --- |
| Bootstrap | 5.3.3 | MIT | CSS/JS de interfaz |
| Bootstrap Icons | 1.11.3 | MIT | Iconos de interfaz |
| jQuery | 3.7.1 | MIT | Interacciones AJAX/UI |
| Chart.js | 4.4.1 | MIT | Graficas del dashboard/trimestres |

## Recursos graficos

Los iconos y logos incluidos en `assets/img/` y `desktop/resources/` deben considerarse recursos propios del proyecto o aportados por el autor antes de publicar.

El logo de documento de cada empresa se sube desde el setup/configuracion y no debe incluir datos reales en el repositorio.

## Riesgos y pendientes

- PHP se redistribuye bajo PHP License 3.01. No usar el nombre PHP para promocionar Your Small Desk como si fuese un producto oficial del PHP Group.
- `mpdf/mpdf` declara GPL-2.0-only. Antes de distribuir publicamente un instalador con una licencia propia restrictiva, conviene revisar compatibilidad legal de distribuir mPDF junto con Your Small Desk.
- `phpmailer/phpmailer` declara LGPL-2.1-only. Revisar obligaciones de redistribucion si se empaqueta en instalador.
- Las dependencias transitivas de Node y Composer figuran en `desktop/package-lock.json` y `composer.lock`; no se ha detectado una licencia principal claramente incompatible salvo el punto GPL de mPDF, que requiere revision.
- Esta revision no sustituye asesoramiento legal profesional.
