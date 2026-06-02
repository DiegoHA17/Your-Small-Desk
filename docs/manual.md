# Manual rapido de Your Small Desk

Your Small Desk es una app local para crear presupuestos, clientes y PDFs. Esta pensada para autonomos y pequenos negocios que necesitan una herramienta sencilla, instalable y sin servidor externo obligatorio.

## 1. Que es Your Small Desk

- App local para crear presupuestos, clientes y PDFs.
- Pensada para autonomos y pequenos negocios.
- En modo escritorio usa SQLite y no necesita servidor externo.
- Permite trabajar con estados, trimestres, logo de documento y datos de empresa personalizados.

## 2. Primer uso

Al abrir la app por primera vez aparece la configuracion inicial.

Debes indicar:

- datos de empresa;
- logo del documento;
- usuario administrador;
- correo de acceso;
- contrasena inicial.

Despues de completar el asistente, entra con el usuario creado y cambia la contrasena si usaste una provisional.

## 3. Clientes

Desde Clientes puedes:

- crear cliente;
- editar cliente;
- eliminar cliente;
- seleccionar cliente al crear un presupuesto.

El email, telefono, empresa y direccion se reutilizan en presupuestos y comunicaciones.

## 4. Presupuestos

Desde Nuevo presupuesto puedes:

- seleccionar cliente;
- anadir conceptos;
- usar Con IVA o Sin IVA;
- guardar;
- descargar PDF;
- enviar por correo si Mailrelay esta configurado;
- abrir WhatsApp con un mensaje preparado.

Los datos de empresa y el logo del documento salen de la configuracion.

## 5. Estados

Estados disponibles:

- borrador;
- emitido;
- enviado;
- cobrado;
- rechazado;
- vencido.

Los resumenes se calculan desde el estado actual de cada presupuesto.

## 6. Trimestres

La vista de trimestres sirve para revisar importes y presupuestos por periodo.

Ayuda a controlar:

- cobrados;
- pendientes;
- rechazados;
- totales por trimestre.

Es una vista de apoyo para control interno y para preparar informacion para la gestoria.

## 7. Copias de seguridad

Para hacer copia de seguridad, guarda:

- `data/jjh_space.sqlite`
- `storage/`

Ahi estan:

- base de datos;
- PDFs generados;
- logos;
- archivos de trabajo.

Recomendacion: copia esos elementos periodicamente en un disco externo o nube privada.

## 8. Mailrelay y correos

Mailrelay es opcional. La app puede funcionar sin correo si solo descargas PDFs o usas WhatsApp.

API:

- usa HTTPS;
- suele funcionar incluso en servidores que bloquean SMTP;
- recomendada si el hosting no permite salida SMTP;
- depende de que la cuenta Mailrelay este aprobada.

SMTP:

- usa puertos de correo como 465 o 587;
- puede estar bloqueado en algunos hostings o servidores;
- recomendado para copia oculta real BCC si el servidor lo permite;
- puede fallar si el proveedor bloquea salida SMTP.

Si tu servidor bloquea SMTP, usa API. Si necesitas copia oculta real y el servidor permite SMTP, usa SMTP.

Si Mailrelay indica que la cuenta esta en revision, hay que esperar la aprobacion o contactar con soporte de Mailrelay. La app no puede desbloquear una cuenta pendiente de revision.

## 9. Servidores sin salida SMTP

Algunos proveedores bloquean los puertos SMTP salientes.

En ese caso:

- prueba primero API;
- no confundas SMTP con SSH;
- SSH sirve para acceso remoto;
- SMTP sirve para enviar correo.

## 10. Licencia

Your Small Desk tiene uso gratuito limitado para autonomos, pequenos negocios, uso personal y uso educativo.

Grandes empresas, facturacion anual superior a 100.000 EUR, reventa, redistribucion como producto propio, SaaS o integraciones comerciales de terceros requieren autorizacion escrita del autor.

No elimines la autoria:

Your Small Desk · Desarrollado por Diego Herrera Ayuso
