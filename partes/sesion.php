<?php
// EL GUARDIA: se incluye al inicio de index.php, registrar.php, editar.php y borrar.php.
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// Quién está usando la app (lo usan las consultas con WHERE usuario_id = ?)
$usuarioId     = (int) $_SESSION['uid'];
$usuarioNombre = $_SESSION['nombre'] ?? '';
