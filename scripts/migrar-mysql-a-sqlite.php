<?php

require_once __DIR__ . '/../includes/conexion.php';

$fresh = in_array('--fresh', $argv, true);

$mysqlHost = valorEntorno('MYSQL_MIGRATE_HOST', valorEntorno('DB_HOST', 'localhost'));
$mysqlPort = (int) valorEntorno('MYSQL_MIGRATE_PORT', valorEntorno('DB_PORT', '3306'));
$mysqlDb = valorEntorno('MYSQL_MIGRATE_DB', valorEntorno('DB_NAME', 'jjh_space'));
$mysqlUser = valorEntorno('MYSQL_MIGRATE_USER', valorEntorno('DB_USER', 'root'));
$mysqlPass = valorEntorno('MYSQL_MIGRATE_PASS', valorEntorno('DB_PASS', ''));

$tablas = [
    'usuarios',
    'clientes',
    'configuracion',
    'facturas',
    'factura_lineas',
    'envios',
    'intentos_login',
];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql = new mysqli($mysqlHost, $mysqlUser, $mysqlPass, $mysqlDb, $mysqlPort);
$mysql->set_charset('utf8mb4');

$sqlite = crearConexionSqlite()->pdo();
$sqlite->beginTransaction();

try {
    $sqlite->exec('PRAGMA foreign_keys = OFF');

    if ($fresh) {
        foreach (array_reverse($tablas) as $tabla) {
            $sqlite->exec('DELETE FROM ' . $tabla);
        }
    }

    foreach ($tablas as $tabla) {
        $resultado = $mysql->query('SELECT * FROM ' . $tabla);
        $migradas = 0;

        while ($fila = $resultado->fetch_assoc()) {
            $columnas = array_keys($fila);
            $columnasSql = implode(', ', array_map(fn ($columna) => '"' . str_replace('"', '""', $columna) . '"', $columnas));
            $placeholders = implode(', ', array_fill(0, count($columnas), '?'));
            $sql = 'INSERT OR REPLACE INTO ' . $tabla . ' (' . $columnasSql . ') VALUES (' . $placeholders . ')';
            $stmt = $sqlite->prepare($sql);
            $stmt->execute(array_values($fila));
            $migradas++;
        }

        echo $tabla . ': ' . $migradas . ' filas migradas' . PHP_EOL;
    }

    $sqlite->exec('PRAGMA foreign_keys = ON');
    $sqlite->commit();
    echo 'Migracion completada en: ' . rutaSqlite() . PHP_EOL;
} catch (Throwable $e) {
    $sqlite->rollBack();
    fwrite(STDERR, 'Error migrando datos: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
