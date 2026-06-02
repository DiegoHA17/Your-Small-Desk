<?php

require_once __DIR__ . '/../includes/conexion.php';

$origen = rutaSqlite();
if (!is_file($origen)) {
    fwrite(STDERR, "No existe la base SQLite: {$origen}" . PHP_EOL);
    exit(1);
}

$directorioBackups = __DIR__ . '/../backups';
if (!is_dir($directorioBackups) && !mkdir($directorioBackups, 0775, true) && !is_dir($directorioBackups)) {
    fwrite(STDERR, 'No se pudo crear la carpeta backups.' . PHP_EOL);
    exit(1);
}

$destino = $directorioBackups . '/jjh_space_' . date('Y-m-d_H-i-s') . '.sqlite';
if (!copy($origen, $destino)) {
    fwrite(STDERR, 'No se pudo crear la copia de seguridad.' . PHP_EOL);
    exit(1);
}

echo 'Backup creado: ' . realpath($destino) . PHP_EOL;
