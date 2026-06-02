# Your Small Desk Desktop

Launcher Electron para abrir Your Small Desk como aplicacion de escritorio en Windows sin reescribir la app PHP.

## Que hace

1. Busca PHP.
2. Arranca `php -S 127.0.0.1:PUERTO -t RUTA_APP`.
3. Espera a que el servidor local responda.
4. Abre Your Small Desk en una ventana Electron.
5. Cierra el proceso PHP al cerrar la app.

No cambia la aplicacion web existente y no migra PHP, jQuery ni Bootstrap.

## Requisitos de desarrollo

- Node.js.
- PHP 8.1 o superior con `pdo_sqlite` activo.
- Dependencias PHP instaladas con Composer en la raiz de Your Small Desk.

## Ejecutar en desarrollo

Desde la carpeta `desktop`:

```powershell
npm install
npm start
```

Electron buscara PHP en este orden:

1. `desktop/php/php.exe`
2. Variable de entorno `PHP_PATH`
3. `php` disponible en el PATH del sistema

## PHP portable

Para que el usuario final no dependa del PATH, coloca una distribucion portable de PHP en:

```text
desktop/php/php.exe
```

La carpeta `desktop/php/` debe contener la distribucion completa, no solo `php.exe`:

```text
desktop/php/php.exe
desktop/php/php.ini
desktop/php/ext/
desktop/php/*.dll
```

Activa en `php.ini`:

```text
extension=pdo_sqlite
extension=sqlite3
extension=mbstring
extension=curl
extension=fileinfo
extension=gd
extension=zip
```

El archivo `desktop/php/README-PHP-PORTABLE.txt` documenta la preparacion completa.

## Base de datos SQLite

La version de escritorio usa SQLite. No hace falta MySQL, MariaDB ni phpMyAdmin para usar la app local.

La base de datos queda en:

```text
data/jjh_space.sqlite
```

En una app empaquetada, Electron guarda el SQLite en la carpeta de datos del usuario de Windows, normalmente:

```text
%APPDATA%\Your Small Desk\data\jjh_space.sqlite
```

Si el archivo no existe, Your Small Desk lo crea automaticamente al arrancar y ejecuta:

```text
sql/jjh_space_sqlite.sql
```

En el primer arranque abre `setup.php`. Desde ese asistente se configuran la empresa, el logo del documento y el usuario administrador inicial. Hasta completar el setup no se muestra el login normal.

Variables que Electron pasa al proceso PHP:

```dotenv
APP_ENV=desktop
DEBUG=false
APP_URL=http://127.0.0.1:PUERTO
DB_DRIVER=sqlite
SQLITE_PATH=data/your_small_desk.sqlite
DB_CHARSET=utf8mb4
SESSION_SECURE=false
```

Si necesitas otra ubicacion para la base, define la variable antes de ejecutar Electron:

```powershell
$env:SQLITE_PATH = "data/your_small_desk.sqlite"
npm start
```

La configuracion de Mailrelay/API/SMTP se guarda en la tabla `configuracion` dentro del SQLite, igual que en la version web.

## Migrar datos desde MySQL local

Si ya tienes clientes, presupuestos y configuracion Mailrelay en una instalacion MySQL local anterior, puedes volcarlos al SQLite:

```powershell
cd ..
$env:MYSQL_MIGRATE_HOST = "localhost"
$env:MYSQL_MIGRATE_PORT = "3306"
$env:MYSQL_MIGRATE_DB = "jjh_space"
$env:MYSQL_MIGRATE_USER = "root"
$env:MYSQL_MIGRATE_PASS = ""
$env:SQLITE_PATH = "data/your_small_desk.sqlite"
php scripts/migrar-mysql-a-sqlite.php --fresh
```

`--fresh` vacia primero las tablas SQLite para evitar mezclar datos de prueba con los reales. El script mantiene los IDs originales cuando es posible y copia la tabla `configuracion`, incluida la configuracion de Mailrelay guardada.

## Copias de seguridad

Para hacer una copia de seguridad conserva:

- `data/jjh_space.sqlite`
- la carpeta `storage/`

Tambien puedes ejecutar desde la raiz del proyecto:

```powershell
$env:DB_DRIVER = "sqlite"
php scripts/backup-sqlite.php
```

## Empaquetar para Windows

Desde `desktop`:

```powershell
npm run dist
```

La salida se genera en:

```text
desktop/dist/
```

La configuracion deja preparado:

- Instalador NSIS.
- Version portable.

El build copia `desktop/php/` dentro de `resources/php/`. Si compilas sin PHP portable, el `.exe` arrancara pero mostrara que no encontro PHP.

## Icono

Si existe `desktop/resources/icon.ico`, Electron lo usara como icono de ventana e instalador.

Si necesitas generarlo, convierte:

```text
assets/img/logo-jjh.png
```

a:

```text
desktop/resources/icon.ico
```

## Enlaces externos

La ventana Electron solo permite navegar dentro de `http://127.0.0.1:PUERTO`.

Enlaces externos como WhatsApp, B2Brouter o Mailrelay se abren en el navegador predeterminado del sistema.

## Limitaciones actuales

- Necesita PHP portable incluido o PHP disponible en el PATH.
- Necesita las extensiones `pdo_sqlite`, `sqlite3`, `fileinfo` y `mbstring`.
- En desarrollo los PDFs siguen en `storage/`; al empaquetar conviene revisar una ruta de storage escribible si se instala en una carpeta protegida.
- No sincroniza datos entre ordenadores.
- No sustituye despliegues web avanzados si decides mantenerlos.

## Futuro recomendado

- Copias de seguridad automaticas.
- Automatizar la descarga/verificacion de PHP portable antes del build.
