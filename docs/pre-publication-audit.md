# Auditoria prepublicacion

Fecha: 2026-06-02

## Cierre final de release

Resultado general: Your Small Desk queda preparado para publicar como app local de escritorio. Se corrigieron problemas de PHP portable, SSL/cacert, Composer/vendor, separacion API/SMTP y build Electron.

Comandos ejecutados:

- `desktop/php/php.exe -v`
- `desktop/php/php.exe -m`
- `desktop/php/php.exe -r "echo ini_get('curl.cainfo')..."`
- `composer validate --no-check-publish`
- `composer install --no-dev --optimize-autoloader`
- `php -l` en PHP principales modificados
- `node --check assets/js/app.js`
- `node --check desktop/main.js`
- `npm install`
- `npm run dist`

Resultados:

- PHP portable arranca correctamente.
- Extensiones verificadas: `pdo_sqlite`, `sqlite3`, `curl`, `openssl`, `mbstring`, `fileinfo`, `gd`, `zip`.
- `desktop/php/extras/ssl/cacert.pem` existe.
- `curl.cainfo` y `openssl.cafile` apuntan a `cacert.pem` en desarrollo.
- Electron reconfigura `php.ini` al arrancar para apuntar a la ruta real de `cacert.pem` en la app instalada.
- Composer valida correctamente.
- `vendor/autoload.php`, `vendor/phpmailer/phpmailer` y `vendor/mpdf/mpdf` existen tras Composer.
- `npm install` termina sin vulnerabilidades reportadas.
- `npm run dist` termina correctamente.

Instaladores generados:

- `desktop/dist/Your-Small-Desk-Setup-0.1.0.exe`
- `desktop/dist/Your-Small-Desk-Portable-0.1.0.exe`

El paquete `win-unpacked` incluye:

- `resources/php/php.exe`
- `resources/php/extras/ssl/cacert.pem`
- `resources/php/license.txt`
- `resources/jjh-space/vendor/autoload.php`
- `resources/jjh-space/vendor/phpmailer/phpmailer`
- `resources/jjh-space/vendor/mpdf/mpdf`

Prueba local HTTP con SQLite temporal:

- PHP embebido arranco con `DB_DRIVER=sqlite`.
- La app creo una base SQLite temporal.
- La primera carga devolvio `302` a `setup.php`, comportamiento esperado en instalacion limpia.

## Cambio de nombre

Resultado: el nombre visible de la app se ha cambiado a Your Small Desk.

Se mantienen nombres tecnicos heredados cuando cambiarlos podria romper rutas, recursos empaquetados o compatibilidad:

- carpeta del repositorio;
- recurso interno `jjh-space` dentro del paquete Electron;
- archivo tecnico `data/jjh_space.sqlite`;
- nombres internos relacionados con `facturas`.

## Autoria

Autor correcto:

- Diego Herrera Ayuso

Copyright visible requerido:

- `Your Small Desk Â· Desarrollado por Diego Herrera Ayuso`

Resultado: no se han detectado referencias al nombre de autor incorrecto en archivos publicables.

## Datos privados

Resultado: no se han detectado claves reales, tokens privados, correos personales, contrasenas reales ni URLs privadas en los archivos preparados para publicacion.

Se revisaron patrones relacionados con:

- passwords y secrets;
- tokens y API keys;
- Mailrelay y SMTP;
- credenciales de base de datos;
- correos personales;
- rutas locales;
- referencias antiguas a servidor externo;
- datos de empresas concretas.

Los resultados relevantes son configuracion legitima, campos de formulario, placeholders o ejemplos sin secreto.

Limpieza realizada:

- eliminadas bases SQLite locales de `data/`;
- eliminados PDFs generados de `storage/facturas/` y `storage/presupuestos/`;
- eliminado logo subido local de `storage/logos/`;
- eliminadas sesiones y temporales de `storage/tmp/` y `storage/facturas/tmp/`;
- eliminada carpeta generada `desktop/dist/` despues de verificar el build.

Nota SQL:

- `sql/jjh_space.sql` incluye un usuario inicial de ejemplo `admin@jjhspace.local` y placeholders `TU_CUENTA`, `TU_API_KEY_MAILRELAY` y `facturas@tudominio.com`.
- No son datos reales, pero si se publica una instalacion enfocada solo a escritorio/SQLite, el flujo recomendado es el setup inicial y `sql/jjh_space_sqlite.sql`.

## Referencias antiguas

Resultado: README principal, manual principal, configuracion e interfaz no presentan despliegues externos como requisito.

Las notas antiguas de despliegue en servidor externo se han separado en `docs/legacy/`.

Resultado SMTP: la documentacion principal usa SMTP para correo saliente y no lo mezcla con otros protocolos.

## Licencia

Tipo actual:

- `Your Small Desk Community License`;
- licencia source-available;
- uso gratuito limitado;
- no MIT;
- no GPL propia;
- no uso ilimitado.

Coherencia revisada:

- README no presenta Your Small Desk como MIT.
- README no indica uso sin restricciones.
- LICENSE indica limites de uso y necesidad de licencia comercial para usos ampliados.

Pendiente legal:

- Esta licencia no sustituye asesoramiento legal profesional.
- Si se va a comercializar, revender o redistribuir de forma amplia, conviene revisar la licencia con un profesional.

## Dependencias

Resumen:

- `mpdf/mpdf` v8.3.1 declara `GPL-2.0-only`.
- `phpmailer/phpmailer` v6.12.0 declara `LGPL-2.1-only`.
- PHP portable para Windows declara `PHP License 3.01`.
- `electron` 30.5.1 declara `MIT`.
- `electron-builder` 24.13.3 declara `MIT`.
- Bootstrap 5.3.3, Bootstrap Icons 1.11.3, jQuery 3.7.1 y Chart.js 4.4.1 se usan por CDN y declaran licencias permisivas conocidas.

Detalle:

- `docs/third-party-licenses.md`

Riesgo principal:

- El instalador puede incluir PHP portable para Windows. Su licencia exige conservar el aviso de copyright, condiciones y atribucion del PHP Group. El build conserva `resources/php/license.txt`.
- No usar el nombre PHP para promocionar Your Small Desk como producto oficial o derivado autorizado del PHP Group.
- mPDF declara GPL-2.0-only. Antes de distribuir instaladores publicos con una licencia propia restrictiva, conviene revisar compatibilidad legal de distribuir mPDF junto con Your Small Desk.

## Recursos graficos

Recursos detectados:

- `assets/img/logo-jjh.png`
- favicons e iconos derivados;
- `assets/img/og-image.png`;
- `desktop/resources/icon.ico`.

Estado:

- No se han detectado imagenes de clientes reales en carpetas preparadas para Git.
- El logo de documento de cada empresa se carga desde setup/configuracion y los logos subidos en `storage/logos/` estan ignorados.

Pendiente:

- Confirmar que los logos incluidos en `assets/img/` y `desktop/resources/` son recursos propios o tienen permiso de uso antes de publicar.

## Subida de imagenes

Zonas permitidas:

- setup inicial, solo si la configuracion no esta completada;
- ajustes/configuracion, solo con sesion iniciada.

Validaciones aplicadas:

- metodo POST;
- CSRF;
- bloqueo de setup si ya esta completado;
- MIME real con `finfo`;
- `getimagesize()` para confirmar imagen real;
- formatos permitidos `png`, `jpg`, `jpeg`, `webp`;
- tamano maximo 2 MB;
- nombre seguro generado por servidor;
- destino fijo `storage/logos/`;
- borrado del logo anterior solo si pertenece a `storage/logos/`;
- proteccion `storage/logos/.htaccess`;
- logos subidos ignorados por Git.

No hay endpoints adicionales de subida de imagenes detectados.

## Seguridad

Revision general:

- login con hash de contrasena;
- CSRF en acciones sensibles;
- SQLite/PDO en modo escritorio;
- subida de logo validada;
- zona peligrosa con token;
- respuestas JSON sin claves completas;
- Electron con `nodeIntegration: false`, `contextIsolation: true`, `enableRemoteModule: false`;
- enlaces externos abiertos fuera de Electron con protocolo controlado.

No se han cambiado flujos de presupuestos, clientes, PDF, trimestres ni Mailrelay salvo textos/documentacion.

## GitHub

`.gitignore` cubre:

- `/vendor/`
- `/node_modules/`
- `/desktop/node_modules/`
- `/desktop/dist/`
- `/data/*.sqlite`
- `/data/*.sqlite-journal`
- `/storage/tmp/*`
- `/storage/logs/*`
- `/storage/facturas/*.pdf`
- `/storage/presupuestos/*.pdf`
- `/storage/logos/*`
- `.env`
- `.env.local`
- `.env.desktop`
- `*.zip`
- `*.exe`
- `.DS_Store`
- `Thumbs.db`

Carpetas con marcador:

- `data/.gitkeep`
- `storage/tmp/.gitkeep`
- `storage/logs/.gitkeep`
- `storage/facturas/.gitkeep`
- `storage/presupuestos/.gitkeep`
- `storage/logos/.gitkeep`
- `docs/screenshots/.gitkeep`

Los `.exe` generados quedan en `desktop/dist/` y deben subirse a GitHub Releases, no al repositorio.

Nombres normalizados:

- `docs/manual.md`
- `docs/release-notes-v0.1.0.md`
- `docs/github-release-checklist.md`
- `docs/security-checklist.md`
- `docs/pre-publication-audit.md`
- `docs/third-party-licenses.md`
- `docs/legacy/`
- `scripts/backup-sqlite.php`
- `scripts/migrar-mysql-a-sqlite.php`

## Build exe

Electron queda configurado con:

- `productName`: Your Small Desk
- `appId`: `es.yoursmalldesk.desktop`
- NSIS: `Your-Small-Desk-Setup-${version}.${ext}`
- Portable: `Your-Small-Desk-Portable-${version}.${ext}`

Build verificado correctamente antes de limpiar generados:

- `desktop/dist/Your-Small-Desk-Setup-0.1.0.exe`
- `desktop/dist/Your-Small-Desk-Portable-0.1.0.exe`
- `desktop/dist/win-unpacked/resources/php/php.exe`
- `desktop/dist/win-unpacked/resources/php/license.txt`

El build actual incluye PHP portable y sus extensiones criticas. Los instaladores siguen fuera del repositorio y deben subirse solo a GitHub Releases.

## Pendientes antes de publicar

- Ejecutar validaciones finales despues de cada cambio.
- Subir a GitHub Releases solo los `.exe` de version `0.1.0`.
- No subir `desktop/dist/` al repo.
- Revisar legalmente compatibilidad de mPDF GPL-2.0-only con la licencia propia si se va a distribuir publicamente.
- Confirmar permiso/autoria de los recursos graficos incluidos.
- Enlace de descarga del README configurado hacia `DiegoHA17/Your-Small-Desk`.
- Anadir capturas reales sin datos privados en `docs/screenshots/`.
- Revisar el contenido final de la Release antes de publicarla.

