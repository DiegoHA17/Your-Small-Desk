# JJH Space

Aplicacion privada uniusuario para gestionar presupuestos de **Podas y Talas JJH**: clientes, presupuestos, PDF, envio por correo mediante Mailrelay API, WhatsApp asistido y estados de cobro.

## Stack

- PHP procedural 8.1 o superior
- MySQL / MariaDB
- jQuery, Bootstrap 5 y Bootstrap Icons
- mPDF para PDFs
- cURL de PHP para Mailrelay API
- PHPMailer para el envio SMTP de presupuestos con adjuntos
- Chart.js para el dashboard

No usa frameworks PHP, frontend SPA, multiusuario, pagos, IA ni integraciones fiscales.

## Instalacion en XAMPP

1. Copia `jjh-space` en `C:\xampp\htdocs`.
2. Activa Apache y MySQL desde XAMPP.
3. Activa las extensiones PHP necesarias en `C:\xampp\php\php.ini`:

```ini
extension=curl
extension=gd
extension=mbstring
```

4. Instala dependencias:

```bash
cd C:\xampp\htdocs\jjh-space
composer install
```

5. Crea una base `jjh_space`, seleccionala e importa `sql/jjh_space.sql` en phpMyAdmin para una instalacion nueva. El archivo crea las tablas en la base seleccionada y no fuerza el nombre de base, para que pueda importarse tambien en Railway.

Para una instalacion existente que ya tenga presupuestos guardados internamente como facturas, ejecuta una sola vez `sql/actualizacion_mailrelay_api.sql`.

## Conexion de base de datos

La conexion se configura mediante variables de entorno. En XAMPP, si no se definen, se mantienen valores locales compatibles con `localhost`, puerto `3306`, usuario `root` y base `jjh_space`.

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_NAME=jjh_space
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

En produccion define estos valores en Railway; no guardes credenciales en archivos PHP ni en Git.

## Codificacion UTF-8 y tildes

JJH Space utiliza `utf8mb4` en la conexion MySQL, HTML, JSON, PDF y correo. En Railway define obligatoriamente:

```dotenv
DB_CHARSET=utf8mb4
```

No uses `latin1` ni `utf8`. La aplicacion rechazara una configuracion `DB_CHARSET` distinta de `utf8mb4` para evitar guardar nuevos textos corruptos.

Para una base ya creada, selecciona la base correspondiente y ejecuta `sql/actualizacion_utf8mb4.sql`. Este script convierte la base y sus tablas a `utf8mb4_unicode_ci`; no puede recuperar automaticamente textos que ya esten guardados con caracteres rotos como `JosÃ©`.

Para exportar desde MySQL/MariaDB local conservando tildes:

```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" --default-character-set=utf8mb4 --host=localhost --port=3306 --user=root jjh_space > "C:\Users\DIEGO\Downloads\jjh_space_utf8mb4.sql"
```

Para importar en Railway usa un cliente MySQL 8 compatible con `caching_sha2_password`, no el cliente MariaDB incluido en XAMPP:

```powershell
mysqlsh --sql --host=HOST_PUBLICO --port=PUERTO_PUBLICO --user=root --password --database=railway --file="C:\Users\DIEGO\Downloads\jjh_space_utf8mb4.sql"
mysqlsh --sql --host=HOST_PUBLICO --port=PUERTO_PUBLICO --user=root --password --database=railway --file="C:\Users\DIEGO\Desktop\JJH\jjh-space\sql\actualizacion_utf8mb4.sql"
```

No uses `utf8_encode()` ni `utf8_decode()` como parche: la solucion es mantener UTF-8 de extremo a extremo.

## Usuario inicial

- Email: `admin@jjhspace.local`
- Password: `1234`

La contraseña se almacena como hash de PHP. Este usuario solo permite completar la instalacion: cambia correo y contraseña desde `Configuracion > Cuenta` antes de exponer la aplicacion a Internet.

## Configuracion de Mailrelay API

1. Entra en tu cuenta de Mailrelay.
2. Obtiene una API key.
3. Copia la URL API de tu cuenta, con formato similar a `https://TU_CUENTA.ipzmarketing.com/api/v1/send_emails`.
4. Entra en `JJH Space > Configuracion`.
5. Introduce URL API, API key, email remitente y nombre remitente.
6. Usa `Probar envio Mailrelay` con un email de prueba para validar el payload basico sin adjunto.
7. Si la prueba simple funciona, usa `Probar adjunto TXT` con las variantes A, B y C para aislar el rechazo de adjuntos.

El envio por correo usa el **PDF adjunto real**. No se envia un enlace al PDF.

La API key guardada no se vuelve a mostrar en la interfaz. Mailrelay se configura desde el panel de JJH Space y sus valores se almacenan en la tabla `configuracion`; nunca se envian al navegador.

El payload de adjuntos queda centralizado en `includes/mailrelay-api.php`, funcion `construirPayloadMailrelay()`. El formato principal utiliza `content`, `file_name`, `content_type` y `content_id`, conforme al ejemplo oficial facilitado.

El remitente configurado debe pertenecer al dominio autenticado `@podasytalasjjh.es`.

La opcion **Copia oculta de respaldo** usa `addBCC()` en el envio SMTP del presupuesto: es una copia oculta del mismo correo y del mismo PDF adjunto. No se realiza un segundo envio y el cliente no ve esa direccion. Si se activa BCC con metodo API, JJH Space bloquea ese envio y solicita seleccionar SMTP, ya que BCC real no esta confirmado para ese flujo API.

Para ver la respuesta real de Mailrelay durante el diagnostico local, configura la variable de entorno `APP_ENV=local` o `APP_ENV=dev` antes de iniciar Apache/PHP. En produccion no se devuelve el cuerpo bruto de la respuesta:

```powershell
$env:APP_ENV = 'local'
```

Los botones de prueba piden confirmacion antes de enviar el correo real y nunca exponen la API key. La prueba TXT envia un archivo pequeno con variantes controladas: A incluye `content_id`, B lo omite y C omite `text_part_auto`. En local/dev la respuesta real y un payload depurado permiten confirmar el motivo del HTTP 422.

El adjunto se construye en `includes/mailrelay-api.php` con el formato oficial de Mailrelay: `content`, `file_name`, `content_type` y `content_id`.

## Mailrelay SMTP para presupuestos adjuntos

En Railway, usa preferentemente **Mailrelay API por HTTPS**. Segun la documentacion oficial de Railway, SMTP saliente solo esta disponible en el plan Pro o superior; Free, Trial y Hobby deben utilizar servicios de correo mediante API HTTPS. Si usas Pro y necesitas SMTP, prueba la conectividad desde el diagnostico antes de seleccionarlo para los presupuestos.

1. Introduce host, puerto, seguridad, usuario y password SMTP indicados en el panel de Mailrelay. Para la cuenta indicada como referencia: host `smtp1.s.ipzmarketing.com`, puerto `587` y seguridad `TLS`.
2. Mantiene como remitente una cuenta del dominio autenticado, por ejemplo `info@podasytalasjjh.es` o `facturas@podasytalasjjh.es`.
3. No dependas del fallback SMTP en Railway Free, Trial o Hobby: el puerto estara bloqueado aunque la API funcione.
4. Activa la copia oculta de respaldo si necesitas recibir cada presupuesto enviado; el cliente no ve ese destinatario.

La password SMTP no se devuelve al navegador una vez guardada y se conserva al editar si el campo se deja vacio. Se almacena en la tabla `configuracion` para mantener operativo el panel actual.

Secuencia de comprobacion recomendada:

1. Probar envio simple por API y revisar el detalle devuelto si Mailrelay lo rechaza.
2. Abrir `diagnostico-mailrelay.php` y comprobar conectividad SMTP sin enviar correo.
3. Probar envio simple por SMTP solo si el puerto conecta y el plan Railway lo permite.
4. Solo si la API simple responde correctamente, probar TXT por API con variantes A, B y C.
5. Enviar un presupuesto PDF por el metodo que haya respondido correctamente.
6. Si usas SMTP Pro, repetir el envio con copia oculta activada.

## Comprobar Mailrelay en Railway

La pagina privada `diagnostico-mailrelay.php` esta disponible solo tras iniciar sesion. Lee la configuracion Mailrelay guardada en la tabla `configuracion`; no envia la API key ni la password SMTP al navegador.

1. Guarda la URL API, API key, remitente y, si procede, datos SMTP en `Configuracion`.
2. Abre `Diagnostico Mailrelay` desde el enlace de la pestana **API y pruebas**.
3. Introduce un email propio de prueba y pulsa **Probar API HTTPS**. Esta accion envia un correo simple real mediante Mailrelay API.
4. Pulsa **Comprobar puerto SMTP**. Esta prueba solo intenta abrir el host y puerto configurados, sin enviar correo ni usar la password.
5. Si SMTP resulta accesible, puedes ejecutar **Probar envio SMTP** para confirmar autenticacion.

Interpretacion:

- API correcta y SMTP inaccesible: usa Mailrelay API. Es el resultado esperado en Railway Free, Trial o Hobby.
- API responde que la cuenta esta en revision: Railway no esta bloqueando HTTPS; Mailrelay debe aprobar la cuenta.
- API correcta y SMTP accesible: puedes utilizar ambos metodos; SMTP requiere Railway Pro o superior.
- API rechazada por credenciales/configuracion: revisa la URL API, la clave guardada y el remitente autenticado.
- SMTP con timeout o conexion rechazada: el plan Railway probablemente bloquea SMTP o Mailrelay no acepta la conexion desde ese servicio.

Si el correo simple por API funciona pero Mailrelay rechaza el PDF adjunto, el diagnostico confirma un problema del payload/capacidad de adjuntos de Mailrelay, no de la red HTTPS de Railway. En Railway Free, Trial o Hobby no se puede sustituirlo por SMTP porque esa salida esta bloqueada; hay que resolver el adjunto API o desplegar en un plan con SMTP disponible.

La respuesta cruda de Mailrelay se muestra solo en entorno `local` o `dev` con `DEBUG=true`. En produccion se conserva la respuesta JSON no sensible y el codigo HTTP, sin revelar secretos. Referencia oficial: [Railway Outbound Networking - Email delivery](https://docs.railway.com/networking/outbound-networking).

## PDF y WhatsApp

- El PDF se genera con mPDF desde `plantillas/factura-base.php`.
- Al descargar, enviar correo o preparar WhatsApp, el PDF se regenera y sobrescribe la version anterior.
- El nombre de archivo es `factura_1.pdf`, `factura_2.pdf`, etc.
- WhatsApp intenta compartir el archivo con Web Share API; si el navegador no permite adjuntar archivos, descarga el PDF y abre WhatsApp con el mensaje preparado.

## Estructura

- `inicio.php`: dashboard.
- `clientes.php`: gestion de clientes.
- `facturas.php`, `factura-nueva.php`, `factura-ver.php`: flujo visible de presupuestos (nombres internos conservados por compatibilidad).
- `acciones/`: endpoints AJAX con respuestas JSON.
- `includes/mailrelay-api.php`: envio y diagnostico de Mailrelay API mediante cURL.
- `includes/mailrelay-smtp.php`: envio SMTP de presupuestos adjuntos mediante PHPMailer.
- `includes/pdf.php`: generacion de PDF.
- `plantillas/factura-base.php`: plantilla editable A4.
- `storage/facturas/`: PDFs protegidos del acceso directo.
- `sql/`: instalacion nueva y migracion Mailrelay API.

La tabla `envios` registra el canal utilizado (`api` o `smtp`) y permite entradas de pruebas sin presupuesto asociado. La base de datos conserva los nombres internos `facturas` e `id_factura` para no romper la aplicacion existente.

JJH Space incluye un acceso externo a B2Brouter para crear facturas reales. Este enlace abre B2Brouter en una nueva pestana y no implica integracion interna ni uso de API.

## Cuenta y borrados protegidos

En `JJH Space > Configuracion` hay una seccion **Cuenta** para actualizar el correo de acceso o la contrasena. Ambas operaciones requieren la contrasena actual; la nueva contrasena se guarda mediante `password_hash()` y debe tener al menos 8 caracteres.

La seccion **Zona peligrosa** permite vaciar presupuestos o clientes de forma controlada:

1. Pulsa `Generar token de seguridad`.
2. Copia el token temporal mostrado; caduca en 5 minutos.
3. Abre la confirmacion correspondiente e introduce exactamente ese token.
4. Para eliminar clientes no puede existir ningun presupuesto. Primero deben eliminarse los presupuestos.

El borrado de presupuestos elimina sus lineas, envios asociados y PDF generados en `storage/facturas/` o `storage/presupuestos/`. Estas acciones solo se aceptan por POST, requieren sesion y CSRF valido, y se ejecutan mediante transacciones de base de datos.

## Identidad visual del navegador

El logo oficial se usa en login, sidebar y plantilla PDF mediante `assets/img/logo-jjh.png`. La aplicacion incluye favicons, `apple-touch-icon`, iconos instalables y `manifest.json` para accesos directos.

Los metadatos Open Graph y Twitter utilizan `assets/img/og-image.png`. En el servidor publico se recomienda definir la variable de entorno `APP_URL` con la URL absoluta de JJH Space, por ejemplo `https://tu-dominio.example/jjh-space`, para que los servicios externos puedan resolver correctamente la imagen compartida.

## Flujo basico

1. Crear cliente.
2. Crear presupuesto, con IVA o sin IVA.
3. Guardar borrador o emitir.
4. Descargar PDF.
5. Enviar el PDF adjunto por correo.
6. Compartir por WhatsApp.
7. Cambiar el estado.

## Seguridad basica

- Sesion privada y regeneracion del identificador tras login.
- CSRF en operaciones POST.
- Consultas MySQLi preparadas.
- Validacion de ids, estados, email, asunto y mensaje.
- API key usada exclusivamente en PHP servidor.
- PDFs servidos bajo sesion; `storage` no se publica directamente.
- Diagnostico detallado de correo solo con `APP_ENV=local|dev` y `DEBUG=true`.
- Sesiones con cookie `HttpOnly`, `SameSite=Lax`, expiracion por inactividad y cookie segura bajo HTTPS.
- Directorios internos bloqueados por Apache en el contenedor de produccion.

## Variables de entorno

Usa `.env.example` como referencia y no subas un archivo `.env` real:

```dotenv
APP_ENV=production
APP_URL=https://TU-DOMINIO.up.railway.app
DEBUG=false
SESSION_SECURE=true
SESSION_IDLE_TIMEOUT=1800
DB_HOST=
DB_PORT=3306
DB_NAME=
DB_USER=
DB_PASS=
DB_CHARSET=utf8mb4
```

Las variables necesarias para el runtime son `APP_*`, `DEBUG`, `SESSION_*` y `DB_*`. Los datos de Mailrelay se rellenan en `Configuracion`; por compatibilidad con la aplicacion actual, se usan los valores persistidos en la base de datos y no variables `MAILRELAY_*`.

Riesgo conocido: la API key y la password SMTP quedan protegidas por el control de acceso a la base de datos, pero no cifradas a nivel de columna. Cifrarlas requeriria gestionar una clave maestra externa y migrar los valores existentes; no se ha introducido ese cambio para no romper la configuracion ya guardada.

## Despliegue en Railway

El proyecto incluye `Dockerfile`; Railway utiliza un `Dockerfile` situado en la raiz del servicio cuando esta presente.

1. Sube `jjh-space` a un repositorio privado y crea un servicio Railway desde el repositorio.
2. Si es un subdirectorio del repositorio, configura el directorio raiz del servicio como `jjh-space`.
3. Añade un servicio MySQL en Railway.
4. En el servicio web, crea referencias a las variables del servicio MySQL; no copies la URL publica como conexion de la app:

```dotenv
APP_ENV=production
APP_URL=https://TU-APP.up.railway.app
DEBUG=false
SESSION_SECURE=true
SESSION_IDLE_TIMEOUT=1800
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
DB_CHARSET=utf8mb4
```

Si el servicio de base de datos no se llama `MySQL`, usa `Add Reference` en Railway para generar las referencias con el nombre real.

5. Para una instalacion nueva, importa `sql/jjh_space.sql` en la base `railway`. Para subir tus datos locales existentes, exporta e importa un volcado como se explica debajo.
6. Expone el puerto `80` del contenedor Apache.
7. Adjunta un volumen al servicio web montado en `/var/www/html/storage` para conservar PDFs entre despliegues.
8. Tras desplegar, cambia inmediatamente el correo y la contraseña iniciales.
9. Entra en `Configuracion` y guarda la API/SMTP de Mailrelay y el BCC si procede.

El contenedor desactiva `mpm_event` y `mpm_worker`, activa exclusivamente `mpm_prefork` para PHP Apache, instala `mysqli`, `curl`, `gd`, `mbstring`, `exif` y `zip`, ejecuta Composer sin dependencias de desarrollo y crea `storage` con permisos de escritura incluso cuando el volumen ya esta montado.

### Importar SQL o datos locales en Railway desde PowerShell

`MYSQL_PUBLIC_URL` se usa solo desde tu equipo para importar. La aplicacion desplegada debe conectarse mediante las variables internas `DB_*` anteriores.

Para instalar tablas vacias, copia el host y puerto publicos del servicio MySQL con la traduccion automatica del navegador desactivada y usa MySQL Shell:

```powershell
mysqlsh --sql --host=HOST_PUBLICO --port=PUERTO_PUBLICO --user=root --password --database=railway --file="C:\Users\DIEGO\Desktop\JJH\jjh-space\sql\jjh_space.sql"
```

Para trasladar los clientes, presupuestos y configuracion Mailrelay que ya tengas en XAMPP:

```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" --host=localhost --port=3306 --user=root --default-character-set=utf8mb4 jjh_space > "C:\Users\DIEGO\Desktop\JJH\jjh_space_backup.sql"
mysqlsh --sql --host=HOST_PUBLICO --port=PUERTO_PUBLICO --user=root --password --database=railway --file="C:\Users\DIEGO\Desktop\JJH\jjh_space_backup.sql"
mysqlsh --sql --host=HOST_PUBLICO --port=PUERTO_PUBLICO --user=root --password --database=railway --file="C:\Users\DIEGO\Desktop\JJH\jjh-space\sql\actualizacion_utf8mb4.sql"
```

El cliente pide la contraseña en el prompt: no la escribas en el comando ni la subas a Git. El volcado contiene configuracion sensible de Mailrelay si ya la guardaste en la app; mantenlo fuera del repositorio y eliminalo cuando no lo necesites.

Documentacion oficial:

- [Dockerfiles](https://docs.railway.com/deploy/dockerfiles)
- [MySQL](https://docs.railway.com/databases/mysql)
- [Variables](https://docs.railway.com/reference/variables)
- [Volumes](https://docs.railway.com/volumes)

## Checklist post-deploy

1. Confirmar `APP_ENV=production`, `DEBUG=false` y `SESSION_SECURE=true`.
2. Cambiar las credenciales iniciales.
3. Verificar que `/storage/`, `/sql/`, `/includes/` y `/vendor/` devuelven acceso denegado.
4. Crear un cliente y presupuesto de prueba y descargar su PDF.
5. Probar Mailrelay API desde `diagnostico-mailrelay.php`; probar SMTP con PDF y BCC real solo si el servicio Railway esta en Pro o la conectividad SMTP se confirma.
6. Confirmar que el volumen conserva PDFs tras un redespliegue.
7. Revisar logs sin activar datos de diagnostico en las respuestas web.
8. Confirmar que los logs no muestran `AH00534: More than one MPM loaded`.
