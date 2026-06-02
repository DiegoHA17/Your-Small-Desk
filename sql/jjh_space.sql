SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP TABLE IF EXISTS envios;
DROP TABLE IF EXISTS factura_lineas;
DROP TABLE IF EXISTS facturas;
DROP TABLE IF EXISTS intentos_login;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS configuracion;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_login DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
  id_cliente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(160) NOT NULL,
  empresa VARCHAR(160) DEFAULT NULL,
  email VARCHAR(160) DEFAULT NULL,
  telefono VARCHAR(50) DEFAULT NULL,
  nif_cif VARCHAR(50) DEFAULT NULL,
  direccion VARCHAR(255) DEFAULT NULL,
  ciudad VARCHAR(120) DEFAULT NULL,
  provincia VARCHAR(120) DEFAULT NULL,
  codigo_postal VARCHAR(20) DEFAULT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_clientes_busqueda (nombre, empresa, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracion (
  id_configuracion INT UNSIGNED PRIMARY KEY,
  nombre_empresa VARCHAR(160) NOT NULL DEFAULT 'Your Small Desk',
  email_empresa VARCHAR(160) DEFAULT NULL,
  telefono_empresa VARCHAR(50) DEFAULT NULL,
  direccion_empresa VARCHAR(255) DEFAULT NULL,
  nif_cif_empresa VARCHAR(50) DEFAULT NULL,
  ciudad_empresa VARCHAR(120) DEFAULT NULL,
  provincia_empresa VARCHAR(120) DEFAULT NULL,
  codigo_postal_empresa VARCHAR(20) DEFAULT NULL,
  setup_completado TINYINT(1) NOT NULL DEFAULT 0,
  nombre_comercial VARCHAR(160) DEFAULT NULL,
  nombre_fiscal VARCHAR(180) DEFAULT NULL,
  pais_empresa VARCHAR(120) DEFAULT NULL,
  web_empresa VARCHAR(180) DEFAULT NULL,
  logo_documento VARCHAR(255) DEFAULT NULL,
  mailrelay_api_url VARCHAR(255) DEFAULT NULL,
  mailrelay_api_key VARCHAR(255) DEFAULT NULL,
  mailrelay_from_email VARCHAR(150) DEFAULT NULL,
  mailrelay_from_name VARCHAR(150) DEFAULT NULL,
  mailrelay_bcc_email VARCHAR(150) DEFAULT NULL,
  mailrelay_bcc_activo TINYINT(1) NOT NULL DEFAULT 0,
  mailrelay_metodo_envio_facturas VARCHAR(10) NOT NULL DEFAULT 'smtp',
  mailrelay_smtp_fallback_activo TINYINT(1) NOT NULL DEFAULT 1,
  mailrelay_smtp_host VARCHAR(255) DEFAULT NULL,
  mailrelay_smtp_port INT DEFAULT NULL,
  mailrelay_smtp_usuario VARCHAR(255) DEFAULT NULL,
  mailrelay_smtp_password VARCHAR(255) DEFAULT NULL,
  mailrelay_smtp_seguridad VARCHAR(20) DEFAULT 'tls',
  smtp_host VARCHAR(255) DEFAULT NULL,
  smtp_port INT DEFAULT NULL,
  smtp_usuario VARCHAR(255) DEFAULT NULL,
  smtp_password VARCHAR(255) DEFAULT NULL,
  smtp_from_email VARCHAR(150) DEFAULT NULL,
  smtp_from_name VARCHAR(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE facturas (
  id_factura INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_factura INT UNSIGNED NOT NULL UNIQUE,
  id_cliente INT UNSIGNED NOT NULL,
  fecha_emision DATE NOT NULL,
  estado ENUM('borrador','emitida','enviada','cobrada','rechazada','vencida') NOT NULL DEFAULT 'borrador',
  concepto_general VARCHAR(255) DEFAULT NULL,
  observaciones TEXT DEFAULT NULL,
  aplica_iva TINYINT(1) NOT NULL DEFAULT 1,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  iva_porcentaje DECIMAL(5,2) NOT NULL DEFAULT 21.00,
  iva_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  ruta_pdf VARCHAR(255) DEFAULT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_facturas_clientes FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente),
  INDEX idx_facturas_estado (estado),
  INDEX idx_facturas_cliente (id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE factura_lineas (
  id_linea INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_factura INT UNSIGNED DEFAULT NULL,
  descripcion VARCHAR(1000) NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL DEFAULT 1.00,
  precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_linea DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_lineas_facturas FOREIGN KEY (id_factura) REFERENCES facturas(id_factura) ON DELETE CASCADE,
  INDEX idx_lineas_factura (id_factura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE envios (
  id_envio INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_factura INT UNSIGNED DEFAULT NULL,
  destinatario VARCHAR(160) NOT NULL,
  bcc_email VARCHAR(150) DEFAULT NULL,
  asunto VARCHAR(255) NOT NULL,
  mensaje TEXT NOT NULL,
  estado ENUM('enviado','error') NOT NULL,
  metodo VARCHAR(20) DEFAULT NULL,
  respuesta_error TEXT DEFAULT NULL,
  respuesta_api MEDIUMTEXT DEFAULT NULL,
  fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_envios_facturas FOREIGN KEY (id_factura) REFERENCES facturas(id_factura) ON DELETE CASCADE,
  INDEX idx_envios_factura (id_factura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE intentos_login (
  id_intento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(160) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  correcto TINYINT(1) NOT NULL DEFAULT 0,
  fecha_intento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_intentos_login (email, ip, fecha_intento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (nombre, email, password_hash)
VALUES ('Administrador', 'admin@jjhspace.local', '$2y$10$yttYyZ7FF7KIXHTXq0exJOWYeBez.VvN0Md3JH07k4gVkU1yow7Ra');

INSERT INTO configuracion (
  id_configuracion, nombre_empresa, mailrelay_api_url, mailrelay_api_key,
  mailrelay_from_email, mailrelay_from_name, mailrelay_metodo_envio_facturas,
  mailrelay_smtp_fallback_activo, mailrelay_smtp_port, mailrelay_smtp_seguridad
) VALUES (
  1, 'Your Small Desk', 'https://TU_CUENTA.ipzmarketing.com/api/v1/send_emails',
  'TU_API_KEY_MAILRELAY', 'facturas@tudominio.com', 'Your Small Desk',
  'smtp', 1, 587, 'tls'
);
