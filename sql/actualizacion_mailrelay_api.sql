SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE configuracion
  ADD COLUMN IF NOT EXISTS mailrelay_api_url VARCHAR(255) NULL AFTER nif_cif_empresa,
  ADD COLUMN IF NOT EXISTS mailrelay_api_key VARCHAR(255) NULL AFTER mailrelay_api_url,
  ADD COLUMN IF NOT EXISTS mailrelay_from_email VARCHAR(150) NULL AFTER mailrelay_api_key,
  ADD COLUMN IF NOT EXISTS mailrelay_from_name VARCHAR(150) NULL AFTER mailrelay_from_email,
  ADD COLUMN IF NOT EXISTS mailrelay_bcc_email VARCHAR(150) NULL AFTER mailrelay_from_name,
  ADD COLUMN IF NOT EXISTS mailrelay_bcc_activo TINYINT(1) NOT NULL DEFAULT 0 AFTER mailrelay_bcc_email,
  ADD COLUMN IF NOT EXISTS mailrelay_metodo_envio_facturas VARCHAR(10) NOT NULL DEFAULT 'smtp' AFTER mailrelay_bcc_activo,
  ADD COLUMN IF NOT EXISTS mailrelay_smtp_fallback_activo TINYINT(1) NOT NULL DEFAULT 1 AFTER mailrelay_metodo_envio_facturas,
  ADD COLUMN IF NOT EXISTS mailrelay_smtp_host VARCHAR(255) NULL AFTER mailrelay_smtp_fallback_activo,
  ADD COLUMN IF NOT EXISTS mailrelay_smtp_port INT NULL AFTER mailrelay_smtp_host,
  ADD COLUMN IF NOT EXISTS mailrelay_smtp_usuario VARCHAR(255) NULL AFTER mailrelay_smtp_port,
  ADD COLUMN IF NOT EXISTS mailrelay_smtp_password VARCHAR(255) NULL AFTER mailrelay_smtp_usuario,
  ADD COLUMN IF NOT EXISTS mailrelay_smtp_seguridad VARCHAR(20) NULL DEFAULT 'tls' AFTER mailrelay_smtp_password;

ALTER TABLE envios
  ADD COLUMN IF NOT EXISTS bcc_email VARCHAR(150) NULL AFTER destinatario,
  ADD COLUMN IF NOT EXISTS metodo VARCHAR(20) NULL AFTER estado,
  ADD COLUMN IF NOT EXISTS respuesta_api MEDIUMTEXT NULL AFTER respuesta_error;

ALTER TABLE envios
  MODIFY COLUMN id_factura INT UNSIGNED NULL;

UPDATE configuracion
SET mailrelay_api_url = COALESCE(mailrelay_api_url, 'https://TU_CUENTA.ipzmarketing.com/api/v1/send_emails'),
    mailrelay_api_key = COALESCE(mailrelay_api_key, 'TU_API_KEY_MAILRELAY'),
    mailrelay_from_email = COALESCE(mailrelay_from_email, 'facturas@tudominio.com'),
    mailrelay_from_name = COALESCE(mailrelay_from_name, 'Your Small Desk')
WHERE id_configuracion = 1;
