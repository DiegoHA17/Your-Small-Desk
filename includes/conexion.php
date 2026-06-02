<?php

function valorEntorno(string $nombre, string $porDefecto = ''): string
{
    $valor = getenv($nombre);
    return $valor === false || $valor === '' ? $porDefecto : (string) $valor;
}

function valorBooleanoEntorno(string $nombre, bool $porDefecto): bool
{
    $valor = getenv($nombre);
    if ($valor === false || trim((string) $valor) === '') {
        return $porDefecto;
    }

    $normalizado = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $normalizado ?? $porDefecto;
}

define('APP_ENV', strtolower(valorEntorno('APP_ENV', 'local')));
define('APP_URL', rtrim(valorEntorno('APP_URL', ''), '/'));
define('DEBUG_APP', valorBooleanoEntorno('DEBUG', APP_ENV !== 'production'));

function esProduccion(): bool
{
    return APP_ENV === 'production';
}

function esDebug(): bool
{
    return DEBUG_APP && !esProduccion();
}

function registrarError(Throwable|string $error): void
{
    $mensaje = $error instanceof Throwable ? $error->getMessage() : $error;
    error_log('[Your Small Desk] ' . $mensaje);
}

ini_set('display_errors', esDebug() ? '1' : '0');
ini_set('log_errors', '1');

define('DB_HOST', valorEntorno('DB_HOST', 'localhost'));
define('DB_PORT', (int) valorEntorno('DB_PORT', '3306'));
define('DB_USER', valorEntorno('DB_USER', 'root'));
define('DB_PASS', valorEntorno('DB_PASS', ''));
define('DB_NAME', valorEntorno('DB_NAME', 'jjh_space'));
define('DB_CHARSET', strtolower(trim(valorEntorno('DB_CHARSET', 'utf8mb4'))));
define('DB_DRIVER', strtolower(trim(valorEntorno('DB_DRIVER', 'mysql'))));
define('SQLITE_PATH', valorEntorno('SQLITE_PATH', 'data/jjh_space.sqlite'));

define('SMTP_HOST_DEFAULT', 'smtp.mailrelay.com');
define('SMTP_PORT_DEFAULT', 587);
define('SMTP_USER_DEFAULT', 'TU_USUARIO_MAILRELAY');
define('SMTP_PASS_DEFAULT', 'TU_PASSWORD_MAILRELAY');
define('SMTP_FROM_DEFAULT', 'facturas@tudominio.com');
define('SMTP_FROM_NAME_DEFAULT', 'Your Small Desk');

define('MAILRELAY_API_URL_DEFAULT', 'https://TU_CUENTA.ipzmarketing.com/api/v1/send_emails');
define('MAILRELAY_API_KEY_DEFAULT', 'TU_API_KEY_MAILRELAY');
define('MAILRELAY_FROM_EMAIL_DEFAULT', 'facturas@tudominio.com');
define('MAILRELAY_FROM_NAME_DEFAULT', 'Your Small Desk');

if (!defined('MYSQLI_ASSOC')) {
    define('MYSQLI_ASSOC', 1);
}

function esSqlite(): bool
{
    return DB_DRIVER === 'sqlite';
}

function rutaProyecto(): string
{
    return dirname(__DIR__);
}

function rutaSqlite(): string
{
    $ruta = SQLITE_PATH;
    $esAbsoluta = str_starts_with($ruta, DIRECTORY_SEPARATOR)
        || preg_match('/^[A-Za-z]:[\\\\\/]/', $ruta) === 1;

    return $esAbsoluta ? $ruta : rutaProyecto() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta);
}

function obtenerConexion()
{
    static $conexion = null;

    if ($conexion !== null) {
        return $conexion;
    }

    if (esSqlite()) {
        $conexion = crearConexionSqlite();
        return $conexion;
    }

    if (DB_CHARSET !== 'utf8mb4') {
        throw new RuntimeException('DB_CHARSET debe configurarse como utf8mb4.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conexion->set_charset(DB_CHARSET);
    $conexion->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    asegurarMigracionesBasicas($conexion);

    return $conexion;
}

function crearConexionSqlite(): ConexionSqliteCompat
{
    if (!class_exists(PDO::class) || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('La extension PDO SQLite de PHP no esta activa.');
    }

    $ruta = rutaSqlite();
    $directorio = dirname($ruta);
    if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo crear la carpeta de datos SQLite.');
    }

    $esNueva = !is_file($ruta) || filesize($ruta) === 0;
    $pdo = new PDO('sqlite:' . $ruta);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA encoding = "UTF-8"');

    if ($esNueva || !sqliteTieneTablas($pdo)) {
        inicializarSqlite($pdo);
    }

    $conexion = new ConexionSqliteCompat($pdo);
    asegurarMigracionesBasicas($conexion);
    asegurarDatosInicialesSqlite($pdo);

    return $conexion;
}

function sqliteTieneTablas(PDO $pdo): bool
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
    return (int) $stmt->fetchColumn() > 0;
}

function inicializarSqlite(PDO $pdo): void
{
    $schema = rutaProyecto() . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'jjh_space_sqlite.sql';
    if (!is_file($schema)) {
        throw new RuntimeException('No se encontro el esquema SQLite sql/jjh_space_sqlite.sql.');
    }

    $sql = file_get_contents($schema);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer el esquema SQLite.');
    }

    $pdo->exec($sql);
}

function asegurarDatosInicialesSqlite(PDO $pdo): void
{
    $totalConfiguracion = (int) $pdo->query('SELECT COUNT(*) FROM configuracion WHERE id_configuracion = 1')->fetchColumn();
    if ($totalConfiguracion === 0) {
        $pdo->exec(
            "INSERT INTO configuracion (
                id_configuracion, nombre_empresa, mailrelay_api_url, mailrelay_api_key,
                mailrelay_from_email, mailrelay_from_name, mailrelay_metodo_envio_facturas,
                mailrelay_smtp_fallback_activo, mailrelay_smtp_port, mailrelay_smtp_seguridad, setup_completado
            ) VALUES (
                1, 'Your Small Desk', '" . MAILRELAY_API_URL_DEFAULT . "', '" . MAILRELAY_API_KEY_DEFAULT . "',
                '" . MAILRELAY_FROM_EMAIL_DEFAULT . "', '" . MAILRELAY_FROM_NAME_DEFAULT . "', 'api',
                0, 587, 'tls', 0
            )"
        );
    }
}

function asegurarMigracionesBasicas($conexion): void
{
    $columnas = [
        'setup_completado' => esSqlite() ? 'INTEGER NOT NULL DEFAULT 0' : 'TINYINT(1) NOT NULL DEFAULT 0',
        'nombre_comercial' => esSqlite() ? 'TEXT DEFAULT NULL' : 'VARCHAR(160) DEFAULT NULL',
        'nombre_fiscal' => esSqlite() ? 'TEXT DEFAULT NULL' : 'VARCHAR(180) DEFAULT NULL',
        'pais_empresa' => esSqlite() ? 'TEXT DEFAULT NULL' : 'VARCHAR(120) DEFAULT NULL',
        'web_empresa' => esSqlite() ? 'TEXT DEFAULT NULL' : 'VARCHAR(180) DEFAULT NULL',
        'logo_documento' => esSqlite() ? 'TEXT DEFAULT NULL' : 'VARCHAR(255) DEFAULT NULL',
    ];

    foreach ($columnas as $columna => $definicion) {
        if (!columnaExisteConfiguracion($conexion, $columna)) {
            $conexion->query('ALTER TABLE configuracion ADD COLUMN ' . $columna . ' ' . $definicion);
        }
    }
}

function columnaExisteConfiguracion($conexion, string $columna): bool
{
    if (esSqlite()) {
        $resultado = $conexion->query('PRAGMA table_info(configuracion)');
        foreach ($resultado->fetch_all(MYSQLI_ASSOC) as $fila) {
            if (($fila['name'] ?? '') === $columna) {
                return true;
            }
        }
        return false;
    }

    $stmt = $conexion->prepare('SHOW COLUMNS FROM configuracion LIKE ?');
    $stmt->bind_param('s', $columna);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function setupCompletado(): bool
{
    try {
        $conexion = obtenerConexion();
        $stmtConfig = $conexion->prepare('SELECT setup_completado FROM configuracion WHERE id_configuracion = 1 LIMIT 1');
        $stmtConfig->execute();
        $config = $stmtConfig->get_result()->fetch_assoc();

        $stmtUsuarios = $conexion->prepare('SELECT COUNT(*) AS total FROM usuarios');
        $stmtUsuarios->execute();
        $usuarios = $stmtUsuarios->get_result()->fetch_assoc();

        return !empty($config['setup_completado']) && (int) ($usuarios['total'] ?? 0) > 0;
    } catch (Throwable $e) {
        registrarError($e);
        return false;
    }
}

class ConexionSqliteCompat
{
    public int|string $insert_id = 0;
    public int $affected_rows = 0;

    public function __construct(private PDO $pdo)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function prepare(string $sql): SentenciaSqliteCompat
    {
        return new SentenciaSqliteCompat($this, $this->pdo->prepare($this->normalizarSql($sql)));
    }

    public function query(string $sql): ResultadoSqliteCompat
    {
        $stmt = $this->pdo->query($this->normalizarSql($sql));
        return new ResultadoSqliteCompat($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function begin_transaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function beginTransaction(): bool
    {
        return $this->begin_transaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    private function normalizarSql(string $sql): string
    {
        $sql = preg_replace('/NOW\(\)\s*-\s*INTERVAL\s+15\s+MINUTE/i', "datetime('now', '-15 minutes')", $sql);
        $sql = preg_replace('/NOW\(\)/i', "datetime('now')", $sql);
        $sql = preg_replace('/CURDATE\(\)/i', "date('now')", $sql);
        $sql = preg_replace('/YEAR\(([^)]+)\)/i', "CAST(strftime('%Y', $1) AS INTEGER)", $sql);
        $sql = preg_replace('/MONTH\(([^)]+)\)/i', "CAST(strftime('%m', $1) AS INTEGER)", $sql);
        $sql = preg_replace('/CAST\(([^)]+)\s+AS\s+CHAR\)/i', 'CAST($1 AS TEXT)', $sql);

        return $sql;
    }
}

class SentenciaSqliteCompat
{
    public int $affected_rows = 0;
    private array $parametros = [];

    public function __construct(private ConexionSqliteCompat $conexion, private PDOStatement $stmt)
    {
    }

    public function bind_param(string $tipos, &...$parametros): bool
    {
        $this->parametros = $parametros;
        return true;
    }

    public function execute(?array $parametros = null): bool
    {
        $valores = $parametros ?? array_values($this->parametros);
        $ok = $this->stmt->execute($valores);
        $this->affected_rows = $this->stmt->rowCount();
        $this->conexion->affected_rows = $this->affected_rows;
        $this->conexion->insert_id = $this->conexion->pdo()->lastInsertId();

        return $ok;
    }

    public function get_result(): ResultadoSqliteCompat
    {
        return new ResultadoSqliteCompat($this->stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

class ResultadoSqliteCompat
{
    private int $posicion = 0;

    public function __construct(private array $filas)
    {
    }

    public function fetch_assoc(): ?array
    {
        return $this->filas[$this->posicion++] ?? null;
    }

    public function fetch_all(int $modo = MYSQLI_ASSOC): array
    {
        return $this->filas;
    }

    public function fetch_row(): ?array
    {
        $fila = $this->fetch_assoc();
        return $fila === null ? null : array_values($fila);
    }
}
