# Notas antiguas de despliegue en servidor externo

Este documento conserva notas historicas de despliegue web para instalaciones avanzadas. No forma parte del uso principal de Your Small Desk como aplicacion local de escritorio.

La version recomendada para usuarios finales es:

- app de escritorio con Electron;
- PHP local arrancado por Electron;
- SQLite en `data/jjh_space.sqlite`;
- backups copiando `data/` y `storage/`.

Si se despliega Your Small Desk en un hosting externo, revisa:

- variables de entorno de base de datos;
- permisos de `storage/`;
- salida SMTP del proveedor;
- configuracion de Mailrelay por API o SMTP;
- HTTPS y cookies seguras.

Algunos proveedores bloquean SMTP saliente. En ese caso usa Mailrelay API por HTTPS.
