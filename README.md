# Your Small Desk

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4)
![SQLite](https://img.shields.io/badge/SQLite-local-003B57)
![Electron](https://img.shields.io/badge/Electron-desktop-47848F)
![License](https://img.shields.io/badge/license-community--limited-174D2A)

<p align="center">
  <a href="https://github.com/TU_USUARIO/your-small-desk/releases/latest">
    <img src="https://img.shields.io/badge/Descargar-ultima%20version-174D2A?style=for-the-badge&logo=windows&logoColor=white" alt="Descargar Your Small Desk">
  </a>
</p>

Your Small Desk es una aplicacion local de escritorio para crear presupuestos, gestionar clientes, generar PDFs y controlar estados de cobro, pensada para autonomos, pequenos negocios y microempresas.

La version de escritorio usa PHP, SQLite y Electron. No necesita servidor externo para trabajar en local.

Your Small Desk · Desarrollado por Diego Herrera Ayuso

## Que es Your Small Desk

Una herramienta sencilla para trabajar desde un ordenador Windows con presupuestos, clientes, PDFs, estados y resumenes trimestrales sin depender de una plataforma externa.

## Descargar

Cuando haya una version empaquetada, estara disponible en la seccion Releases de GitHub:

[Descargar ultima version](https://github.com/TU_USUARIO/your-small-desk/releases/latest)

## Caracteristicas

- Gestion de clientes.
- Creacion de presupuestos.
- Exportacion a PDF.
- Estados: borrador, emitido, enviado, cobrado, rechazado y vencido.
- Resumen por trimestres.
- Configuracion de datos de empresa.
- Logo personalizable para documentos.
- Funcionamiento local con SQLite.
- Version de escritorio con Electron.
- Sin necesidad de servidor externo.
- Copias de seguridad sencillas copiando la base SQLite y la carpeta `storage/`.
- Mailrelay opcional por API o SMTP.

## Capturas

Pendiente anadir capturas.

- `docs/screenshots/login.png`
- `docs/screenshots/dashboard.png`
- `docs/screenshots/presupuesto.png`
- `docs/screenshots/configuracion.png`

## Instalacion para usuario normal

1. Descarga la ultima version desde Releases.
2. Instala Your Small Desk.
3. Abre la aplicacion.
4. Completa la configuracion inicial.
5. Entra con el usuario creado.

## Primer uso

La primera vez que abras la app aparecera un asistente para configurar:

- datos de empresa;
- logo del documento;
- usuario administrador;
- correo de acceso;
- contrasena.

Despues de completar el asistente, entra desde el login con el usuario creado.

## Configuracion inicial

Los datos de empresa y el logo se usan en presupuestos, PDFs y documentos generados.

Puedes cambiarlos despues desde:

```text
Configuracion -> Empresa
```

El logo del documento no reemplaza obligatoriamente el favicon ni el branding interno de Your Small Desk.

## Crear presupuestos

1. Crea o selecciona un cliente.
2. Abre Nuevo presupuesto.
3. Anade conceptos, cantidad y precio.
4. Elige Con IVA o Sin IVA.
5. Guarda el presupuesto.
6. Descarga el PDF o usa las acciones de correo/WhatsApp si estan configuradas.

## Datos de empresa y logo

La app permite configurar nombre comercial, datos fiscales, direccion, contacto y logo del documento. El logo subido se usa en presupuestos y PDFs.

Solo se aceptan logos `png`, `jpg`, `jpeg` o `webp` de hasta 2 MB. No subas logos reales al repositorio.

## Correos / Mailrelay

Mailrelay es opcional. Puedes configurarlo desde la app o mediante variables si preparas un entorno propio.

API:

- usa HTTPS;
- suele funcionar aunque el servidor bloquee SMTP;
- recomendada si el proveedor no permite salida SMTP;
- depende de que la cuenta Mailrelay este aprobada.

SMTP:

- usa puertos de correo como 465 o 587;
- puede estar bloqueado en algunos hostings o servidores;
- es util para copia oculta real BCC si el servidor lo permite.

Si tu servidor bloquea SMTP, usa API. Si necesitas copia oculta real y el servidor permite SMTP, usa SMTP.

No confundas SMTP con SSH: SSH es acceso remoto, SMTP es envio de correo.

## Copias de seguridad

Para hacer copia de seguridad, copia:

```text
data/jjh_space.sqlite
storage/
```

El nombre `jjh_space.sqlite` es tecnico heredado y puede mantenerse aunque el nombre visible de la app sea Your Small Desk.

Ahi estan:

- base de datos;
- PDFs generados;
- logos;
- archivos de trabajo.

Guarda una copia periodica en un disco externo o nube privada.

## Desarrollo

Instala dependencias PHP:

```powershell
composer install
```

Ejecuta la version Electron:

```powershell
cd desktop
npm install
npm start
```

La app creara la base indicada por `SQLITE_PATH`. Si no defines esa variable, el fallback tecnico heredado sigue siendo `data/jjh_space.sqlite`.

## Compilar instalador

```powershell
cd desktop
npm install
npm run dist
```

Los instaladores se generan en:

```text
desktop/dist/
```

Los `.exe` generados deben publicarse en GitHub Releases, no dentro del repositorio.

## Seguridad

- Contrasenas guardadas con `password_hash`.
- Acciones sensibles protegidas con CSRF.
- Base SQLite local en `data/`.
- PDFs y logos en `storage/`.
- Subida de logos limitada a setup inicial y configuracion con sesion.
- No subas `data/*.sqlite`, PDFs generados, logos reales, logs ni claves privadas.
- Revisa `.gitignore` antes de publicar.

## Manual rapido

Consulta:

[docs/manual.md](docs/manual.md)

Tambien puedes abrir el manual desde:

```text
Configuracion -> Manual y licencia -> Ver manual
```

## Licencia

Your Small Desk se distribuye bajo una licencia comunitaria de uso gratuito limitado.

Uso gratuito permitido para autonomos, pequenos negocios, microempresas, uso personal y educativo.

Empresas medianas o grandes, facturacion anual superior a 100.000 EUR, reventa, redistribucion como producto propio, SaaS o integraciones comerciales de terceros requieren autorizacion escrita del autor.

Consulta el archivo [LICENSE](LICENSE).

Esta licencia no sustituye asesoramiento legal profesional. Si necesitas un uso empresarial ampliado o redistribucion, contacta con el autor.

## Licencias de terceros

Consulta [docs/third-party-licenses.md](docs/third-party-licenses.md).

## Autor

Desarrollado por Diego Herrera Ayuso.

No elimines ni ocultes el aviso de autoria:

```text
Your Small Desk · Desarrollado por Diego Herrera Ayuso
```
