<?php
require_once __DIR__ . '/includes/seguridad.php';

if (!setupCompletado()) {
    header('Location: setup.php');
    exit;
}

if (empty($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

header('Location: inicio.php');
exit;
