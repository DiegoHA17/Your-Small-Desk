Coloca aqui una distribucion portable de PHP para Windows antes de compilar el instalador.

Descarga PHP para Windows en ZIP desde:

https://windows.php.net/download/

Usa una version Thread Safe o Non Thread Safe de PHP 8.2 o superior para x64.

Extrae todo el contenido del ZIP directamente dentro de:

desktop/php/

La estructura debe quedar asi:

desktop/php/php.exe
desktop/php/php.ini
desktop/php/ext/
desktop/php/*.dll

No basta con copiar solo php.exe. PHP necesita sus DLLs, extensiones y configuracion.

Si no existe php.ini, copia:

php.ini-development

y renombralo como:

php.ini

Activa estas extensiones en php.ini:

extension=pdo_sqlite
extension=sqlite3
extension=mbstring
extension=curl
extension=fileinfo
extension=gd
extension=zip

Extensiones criticas que Your Small Desk comprueba al arrancar:

- pdo_sqlite
- sqlite3
- fileinfo
- mbstring

Orden de busqueda usado por Your Small Desk Desktop:

1. desktop/php/php.exe en desarrollo.
2. resources/php/php.exe en la app empaquetada.
3. variable de entorno PHP_PATH.
4. php disponible en el PATH del sistema.

El instalador solo incluira PHP portable si esta carpeta contiene php.exe, php.ini, ext/ y las DLLs antes de ejecutar npm run dist.
