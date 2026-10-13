<?php
// Solo se acepta POST: un enlace GET podría borrar datos al ser visitado
// por un rastreador, un prefetch del navegador o un enlace malicioso.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: index.php');
    exit;
}

require 'conexion.php';

$stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();
$mysqli->close();

// PRG: el 303 obliga al navegador a pedir index.php con GET
header('Location: index.php?eliminado=1', true, 303);
exit;