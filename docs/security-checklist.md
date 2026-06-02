# Checklist de seguridad

## Sesiones y login

- Login con `password_hash` y `password_verify`.
- Regeneracion de sesion tras login correcto.
- Acceso privado protegido por sesion.
- Logout destruye sesion.

## CSRF y acciones

- Acciones POST sensibles con CSRF.
- No usar GET para borrados o cambios de datos.
- Respuestas JSON uniformes sin trazas tecnicas.

## Base de datos

- SQLite local en modo escritorio.
- Consultas preparadas.
- Setup inicial crea configuracion y usuario.
- No subir `data/*.sqlite` al repositorio.

## Subida de imagenes

Zonas permitidas:

- setup inicial, solo si la configuracion no esta completada;
- ajustes/configuracion, solo con sesion iniciada.

Formatos permitidos:

- PNG;
- JPG/JPEG;
- WEBP.

Validaciones requeridas:

- metodo POST;
- CSRF;
- tamano maximo 2 MB;
- MIME real con `finfo`;
- validacion de imagen real con `getimagesize`;
- nombre generado por servidor;
- ruta fija dentro de `storage/logos/`;
- no aceptar SVG, PHP, JS, HTML, PDF, ZIP ni ejecutables;
- no aceptar rutas externas ni nombres originales;
- no borrar logos fuera de `storage/logos/`.

Publicacion:

- no subir logos reales al repositorio;
- mantener `storage/logos/.gitkeep`;
- mantener `storage/logos/.htaccess`;
- publicar solo recursos graficos propios o con permiso.

## Archivos y PDFs

- PDFs generados en `storage/`.
- No subir PDFs reales.
- No exponer rutas internas.

## Mailrelay

- No publicar API keys ni passwords SMTP.
- No mostrar claves completas en interfaz ni JSON.
- API por HTTPS.
- SMTP solo si el servidor permite salida SMTP.
- No confundir SMTP con SSH.

## Electron

- `nodeIntegration: false`.
- `contextIsolation: true`.
- `enableRemoteModule: false`.
- Preload minimo.
- Navegacion limitada al servidor local.
- Enlaces externos abiertos fuera de Electron.
- No abrir DevTools por defecto.

## Release

- No commitear `desktop/dist/`.
- No commitear `.exe`.
- Subir instaladores solo a GitHub Releases.
