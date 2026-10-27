<?php
require __DIR__ . '/partes/sesion.php';   // el guardia: sin sesión no se borra nada

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

// AND usuario_id = ?  → solo puedes borrar TUS eventos
$stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $id, $usuarioId);
$stmt->execute();
$borrados = $stmt->affected_rows;     // 0 si el id no existe o es de otra persona
$stmt->close();
$mysqli->close();

// PRG: el 303 obliga al navegador a pedir index.php con GET
$destino = $borrados > 0 ? 'index.php?eliminado=1' : 'index.php?noexiste=1';
header('Location: ' . $destino, true, 303);
exit;