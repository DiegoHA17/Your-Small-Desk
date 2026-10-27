# Your Small Desk

Gestiona clientes, crea presupuestos y genera PDFs desde una aplicación de escritorio con almacenamiento local SQLite, pensada para autónomos y pequeños negocios.

La aplicación combina **PHP y Electron** y no necesita un servidor externo para trabajar en modo escritorio. El envío de correo mediante Mailrelay es opcional.

Your Small Desk · Desarrollado por Diego Herrera Ayuso

## Qué puedes hacer

- **Gestionar clientes:** crear, editar y eliminar sus fichas, y reutilizar sus datos en presupuestos.
- **Preparar presupuestos:** añadir conceptos, cantidades y precios, con IVA o sin IVA.
- **Generar documentos PDF** con los datos de tu empresa y su logo.
- **Controlar estados:** borrador, emitido, enviado, cobrado, rechazado y vencido.
- **Consultar resúmenes trimestrales** de importes y presupuestos.
- **Compartir presupuestos:** enviarlos por correo con Mailrelay configurado o abrir WhatsApp con un mensaje preparado.

Los resúmenes trimestrales sirven como apoyo para el control interno y para preparar información destinada a la gestoría.

## Distribución e instalación

La configuración de empaquetado incluye dos formatos para **Windows**: instalador NSIS y aplicación portable. La versión declarada en [desktop/package.json](desktop/package.json) es **0.1.0**.

Las [notas de la versión 0.1.0](docs/release-notes-v0.1.0.md) describen la primera distribución instalable. La disponibilidad actual de descargas publicadas está pendiente de comprobar.

Si dispones del instalador:

1. Instala Your Small Desk.
2. Abre la aplicación.
3. Completa el asistente inicial.
4. Accede con el usuario que acabas de crear.

El paquete puede incluir PHP portable para evitar su instalación manual. Al compilar desde el código, debes incorporarlo siguiendo las instrucciones de desarrollo.

## Primer presupuesto

En el primer inicio, el asistente permite configurar los datos de empresa, el logo del documento y el usuario administrador con su correo y contraseña.

Después:

1. Crea un cliente o selecciona uno existente.
2. Abre **Nuevo presupuesto**.
3. Añade los conceptos, cantidades y precios.
4. Elige **Con IVA** o **Sin IVA**.
5. Guarda el presupuesto.
6. Descarga el PDF o utiliza las opciones de comunicación disponibles.

Puedes modificar los datos de empresa y el logo desde **Configuración → Empresa**. Estos datos se utilizan en los presupuestos y documentos generados.

Los formatos de logo documentados son PNG, JPG/JPEG y WEBP, con un tamaño máximo de 2 MB. El logo del documento es independiente del icono y de la identidad visual de la aplicación.

Para ampliar estas instrucciones, consulta el [manual rápido](docs/manual.md), también accesible desde **Configuración → Manual y licencia → Ver manual**.

## Correo opcional con Mailrelay

Puedes usar la aplicación sin configurar correo para crear presupuestos y descargar PDFs. Mailrelay ofrece dos métodos de envío cuya configuración se guarda por separado:

| Método | Cuándo utilizarlo |
| --- | --- |
| API mediante HTTPS | Si el proveedor bloquea las conexiones SMTP. Requiere una cuenta Mailrelay habilitada para enviar. |
| SMTP | Si la red permite conexiones de correo saliente; puede ser útil cuando necesitas copia oculta BCC. |

El método activo determina cómo se envían los presupuestos. Guardar la configuración API no elimina la configuración SMTP, ni a la inversa.

Según el comportamiento documentado, dejar vacía una API key o contraseña SMTP ya configurada conserva el valor anterior. Una cuenta Mailrelay pendiente de aprobación debe resolverse con el proveedor.

## Datos y copias de seguridad

La documentación utiliza esta ubicación predeterminada para la base SQLite:

```text
data/jjh_space.sqlite
```

El nombre `jjh_space.sqlite` es un identificador técnico heredado. También se conservan nombres internos como `jjh-space` y archivos relacionados con `facturas`.

Para las copias de seguridad, guarda:

- La base SQLite que esté utilizando tu instalación.
- La carpeta `storage/`, donde se almacenan PDFs, logos y archivos de trabajo.

Comprueba la ubicación efectiva de los datos en tu instalación antes de hacer la copia, especialmente si has personalizado `SQLITE_PATH`. Guarda copias periódicas en un disco externo o en una nube privada.

No publiques bases de datos, documentos de clientes, logos privados, logs, claves ni archivos `.env`.

## Desarrollo

### Tecnologías y requisitos

- PHP **8.1 o superior**, según [composer.json](composer.json).
- Composer para instalar mPDF y PHPMailer.
- Node.js y npm para ejecutar Electron y el empaquetador.
- PHP portable para preparar la distribución Windows.

El manifiesto de escritorio declara Electron `^30.5.1` y electron-builder `^24.13.3`. No se especifica en las evidencias una versión mínima de Node.js.

### Preparar PHP portable

Coloca la distribución completa de PHP para Windows dentro de `desktop/php/`, incluyendo:

```text
desktop/php/php.exe
desktop/php/php.ini
desktop/php/ext/
desktop/php/*.dll
desktop/php/extras/ssl/cacert.pem
desktop/php/license.txt
```

La lista de preparación de releases requiere estas extensiones:

```text
pdo_sqlite
sqlite3
curl
openssl
mbstring
fileinfo
gd
zip
```

Configura `curl.cainfo` y `openssl.cafile` para utilizar el certificado `cacert.pem`. Conserva también el archivo de licencia de PHP.

Consulta la [lista de preparación de releases](docs/github-release-checklist.md) para revisar los requisitos de empaquetado.

### Instalar dependencias y ejecutar

Desde la raíz del proyecto, en PowerShell:

```powershell
composer install --no-dev --optimize-autoloader
cd desktop
npm install
npm start
```

`npm start` ejecuta `electron .`, según el manifiesto de escritorio.

La variable `SQLITE_PATH` permite indicar la ubicación de la base de datos según la documentación existente. El archivo [.env.example](.env.example) enumera las variables previstas para la aplicación, sesiones, SQLite y Mailrelay; no publiques valores privados.

### Generar los paquetes Windows

Una vez preparado PHP portable y las dependencias:

```powershell
cd desktop
npm run dist
```

Ejecuta este comando desde la raíz; si ya estás en `desktop/`, ejecuta únicamente `npm run dist`.

La salida configurada es `desktop/dist/`, con estos nombres de artefacto:

```text
Your-Small-Desk-Setup-0.1.0.exe
Your-Small-Desk-Portable-0.1.0.exe
```

Comprueba que el paquete incluye PHP, sus extensiones, el certificado, su licencia y las dependencias de Composer. Si aparece «No se encontró PHP», revisa `desktop/php/` y vuelve a compilar.

Los instaladores y directorios generados deben mantenerse fuera del código versionado.

### Estructura principal

| Ruta | Contenido |
| --- | --- |
| `desktop/` | Entrada Electron y configuración de empaquetado. |
| `acciones/` | Acciones PHP de clientes, presupuestos, configuración y acceso. |
| `includes/` | Conexión, seguridad, PDF e integraciones de correo. |
| `plantillas/` | Plantilla de documentos. |
| `assets/` | Estilos, JavaScript e imágenes. |
| `sql/` | Esquemas y actualizaciones de base de datos. |
| `docs/` | Manual, seguridad y documentación de distribución. |

El repositorio también incluye un [Dockerfile](Dockerfile) basado en PHP 8.2 y Apache. La ruta de uso principal documentada aquí es la aplicación de escritorio.

## Seguridad y comprobaciones

La [lista de seguridad](docs/security-checklist.md) documenta controles de acceso por sesión, contraseñas con hash, protección CSRF, consultas preparadas y validación de imágenes.

Antes de distribuir una compilación, revisa esa lista y la [auditoría de prepublicación](docs/pre-publication-audit.md). Esta última registra verificaciones anteriores; no sustituye la comprobación de la compilación que vayas a entregar.

## Licencia y autoría

Your Small Desk se distribuye bajo **Your Small Desk Community License**, una licencia *source-available* de uso gratuito limitado.

Permite determinados usos para autónomos, pequeños negocios, microempresas, uso personal, educativo e interno no masivo. Requieren autorización escrita, entre otros:

- El uso en empresas medianas o grandes, o con facturación anual superior a **100.000 EUR**.
- La reventa o redistribución como producto propio.
- La oferta como SaaS o servicio alojado a terceros.
- La integración en productos comerciales de terceros.

Consulta las condiciones completas en [LICENSE](LICENSE). El acceso al código no implica permiso de uso o redistribución sin restricciones.

Las dependencias tienen sus propias condiciones: consulta [Licencias de terceros](docs/third-party-licenses.md). Si el paquete incluye PHP portable, debe conservar su licencia y atribución, normalmente en `resources/php/license.txt`.

La auditoría del proyecto señala una revisión pendiente de compatibilidad entre la licencia propia y la distribución de mPDF bajo GPL-2.0-only. Debe resolverse antes de distribuir instaladores públicos.

Desarrollado por **Diego Herrera Ayuso**. Conserva el aviso de autoría:

```text
Your Small Desk · Desarrollado por Diego Herrera Ayuso
```
