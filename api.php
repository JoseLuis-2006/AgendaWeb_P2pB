<?php
/* api.php · P10 · mis eventos en JSON
 * GET api.php                  → todos mis eventos
 * GET api.php?q=examen         → busca en título o descripción
 * GET api.php?categoria=3      → solo una categoría
 * Sin sesión → 401 · Otro método → 405 · Falla la BD → 500
 */

session_start();

// 1 · Lo PRIMERO que sale: la etiqueta "esto es JSON"
header('Content-Type: application/json; charset=utf-8');

// Una sola salida para todas las respuestas: código + JSON + fin
function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2 · Sin sesión no hay datos: 401 = "no sé quién eres"
//     (no usamos partes/sesion.php: ese guardia redirige a login.php con HTML)
if (!isset($_SESSION['uid'])) {
    responder(401, ['error' => 'Inicia sesión para consultar tus eventos.']);
}
$usuarioId = (int) $_SESSION['uid'];

// Esta API solo CONSULTA: cualquier otro método se rechaza
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responder(405, ['error' => 'Método no permitido. Usa GET.']);
}

require __DIR__ . '/conexion.php';

// 3 · Filtros opcionales: lo mismo que el buscador de P9
//     is_string evita un error fatal si alguien manda ?q[]=algo
$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 60) : '';
$categoriaId = (int) ($_GET['categoria'] ?? 0);

$condiciones = ['e.usuario_id = ?'];          // SIEMPRE: solo mis eventos
$tipos       = 'i';
$valores     = [$usuarioId];

if ($q !== '') {
    // % y _ son comodines de LIKE: se escapan para buscarlos como texto normal
    $patron = '%' . addcslashes($q, '%_\\') . '%';
    $condiciones[] = '(e.titulo LIKE ? OR e.descripcion LIKE ?)';
    $tipos      .= 'ss';
    $valores[]   = $patron;
    $valores[]   = $patron;
}
if ($categoriaId > 0) {
    $condiciones[] = 'e.categoria_id = ?';
    $tipos      .= 'i';
    $valores[]   = $categoriaId;
}

// 4 · Nunca SELECT *: se entregan solo las columnas que sirven a quien consume
$sql = "SELECT e.id, e.titulo, e.fecha,
               TIME_FORMAT(e.hora, '%H:%i') AS hora,
               c.nombre AS categoria, e.descripcion
          FROM eventos e
          JOIN categorias c ON c.id = e.categoria_id
         WHERE " . implode(' AND ', $condiciones) . "
         ORDER BY e.fecha, e.hora";

try {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $mysqli->close();
} catch (mysqli_sql_exception $e) {
    // Sin detalles técnicos hacia afuera: tu P11 mostrará "no se pudieron cargar"
    responder(500, ['error' => 'No se pudieron cargar los eventos.']);
}

// 5 · Respuesta: datos de contexto + los filtros aplicados + el arreglo de eventos
responder(200, [
    'usuario' => $_SESSION['nombre'] ?? '',
    'total'   => count($eventos),
    'filtros' => ['q' => $q, 'categoria' => $categoriaId],
    'eventos' => $eventos,
]);

// Sin etiqueta de cierre de PHP, a propósito: ningún espacio sobrante rompe el JSON.