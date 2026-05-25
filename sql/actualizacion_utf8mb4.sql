-- Ejecutar sobre la base seleccionada.
-- Local: selecciona jjh_space. Railway: selecciona railway.
-- Esta migracion asegura el juego de caracteres; no corrige textos que ya se hayan guardado corruptos.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE clientes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE configuracion CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE facturas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE factura_lineas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE envios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE intentos_login CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
