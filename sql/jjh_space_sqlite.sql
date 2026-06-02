PRAGMA foreign_keys = ON;
PRAGMA encoding = "UTF-8";

CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_login TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS clientes (
  id_cliente INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  empresa TEXT DEFAULT NULL,
  email TEXT DEFAULT NULL,
  telefono TEXT DEFAULT NULL,
  nif_cif TEXT DEFAULT NULL,
  direccion TEXT DEFAULT NULL,
  ciudad TEXT DEFAULT NULL,
  provincia TEXT DEFAULT NULL,
  codigo_postal TEXT DEFAULT NULL,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_clientes_nombre ON clientes(nombre);
CREATE INDEX IF NOT EXISTS idx_clientes_email ON clientes(email);

CREATE TABLE IF NOT EXISTS configuracion (
  id_configuracion INTEGER PRIMARY KEY,
  nombre_empresa TEXT NOT NULL DEFAULT 'Your Small Desk',
  email_empresa TEXT DEFAULT NULL,
  telefono_empresa TEXT DEFAULT NULL,
  direccion_empresa TEXT DEFAULT NULL,
  nif_cif_empresa TEXT DEFAULT NULL,
  ciudad_empresa TEXT DEFAULT NULL,
  provincia_empresa TEXT DEFAULT NULL,
  codigo_postal_empresa TEXT DEFAULT NULL,
  setup_completado INTEGER NOT NULL DEFAULT 0,
  nombre_comercial TEXT DEFAULT NULL,
  nombre_fiscal TEXT DEFAULT NULL,
  pais_empresa TEXT DEFAULT NULL,
  web_empresa TEXT DEFAULT NULL,
  logo_documento TEXT DEFAULT NULL,
  mailrelay_api_url TEXT DEFAULT NULL,
  mailrelay_api_key TEXT DEFAULT NULL,
  mailrelay_from_email TEXT DEFAULT NULL,
  mailrelay_from_name TEXT DEFAULT NULL,
  mailrelay_bcc_email TEXT DEFAULT NULL,
  mailrelay_bcc_activo INTEGER NOT NULL DEFAULT 0,
  mailrelay_metodo_envio TEXT NOT NULL DEFAULT 'api',
  mailrelay_metodo_envio_facturas TEXT NOT NULL DEFAULT 'api',
  mailrelay_smtp_fallback_activo INTEGER NOT NULL DEFAULT 0,
  mailrelay_smtp_host TEXT DEFAULT NULL,
  mailrelay_smtp_port INTEGER DEFAULT NULL,
  mailrelay_smtp_usuario TEXT DEFAULT NULL,
  mailrelay_smtp_password TEXT DEFAULT NULL,
  mailrelay_smtp_seguridad TEXT DEFAULT 'tls',
  smtp_host TEXT DEFAULT NULL,
  smtp_port INTEGER DEFAULT NULL,
  smtp_usuario TEXT DEFAULT NULL,
  smtp_password TEXT DEFAULT NULL,
  smtp_from_email TEXT DEFAULT NULL,
  smtp_from_name TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS facturas (
  id_factura INTEGER PRIMARY KEY AUTOINCREMENT,
  codigo_factura INTEGER NOT NULL UNIQUE,
  id_cliente INTEGER NOT NULL,
  fecha_emision TEXT NOT NULL,
  estado TEXT NOT NULL DEFAULT 'borrador',
  concepto_general TEXT DEFAULT NULL,
  observaciones TEXT DEFAULT NULL,
  aplica_iva INTEGER NOT NULL DEFAULT 1,
  subtotal REAL NOT NULL DEFAULT 0,
  iva_porcentaje REAL NOT NULL DEFAULT 21,
  iva_total REAL NOT NULL DEFAULT 0,
  total REAL NOT NULL DEFAULT 0,
  ruta_pdf TEXT DEFAULT NULL,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
);

CREATE INDEX IF NOT EXISTS idx_facturas_estado ON facturas(estado);
CREATE INDEX IF NOT EXISTS idx_facturas_cliente ON facturas(id_cliente);

CREATE TABLE IF NOT EXISTS factura_lineas (
  id_linea INTEGER PRIMARY KEY AUTOINCREMENT,
  id_factura INTEGER DEFAULT NULL,
  descripcion TEXT NOT NULL,
  cantidad REAL NOT NULL DEFAULT 1,
  precio_unitario REAL NOT NULL DEFAULT 0,
  total_linea REAL NOT NULL DEFAULT 0,
  FOREIGN KEY (id_factura) REFERENCES facturas(id_factura) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_lineas_factura ON factura_lineas(id_factura);

CREATE TABLE IF NOT EXISTS envios (
  id_envio INTEGER PRIMARY KEY AUTOINCREMENT,
  id_factura INTEGER DEFAULT NULL,
  destinatario TEXT NOT NULL,
  bcc_email TEXT DEFAULT NULL,
  asunto TEXT NOT NULL,
  mensaje TEXT NOT NULL,
  estado TEXT NOT NULL,
  metodo TEXT DEFAULT NULL,
  respuesta_error TEXT DEFAULT NULL,
  respuesta_api TEXT DEFAULT NULL,
  fecha_envio TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_factura) REFERENCES facturas(id_factura) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_envios_factura ON envios(id_factura);

CREATE TABLE IF NOT EXISTS intentos_login (
  id_intento INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL,
  ip TEXT NOT NULL,
  correcto INTEGER NOT NULL DEFAULT 0,
  fecha_intento TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_intentos_login ON intentos_login(email, ip, fecha_intento);
