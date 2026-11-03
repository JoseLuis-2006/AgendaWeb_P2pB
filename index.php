<?php
require __DIR__ . '/partes/sesion.php';
require 'conexion.php';

// Escapa texto antes de ponerlo en el HTML
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Avisos que llegan por la URL después de una redirección (patrón PRG)
$avisos = [
    'ok'        => 'Evento guardado correctamente.',
    'editado'   => 'Evento actualizado correctamente.',
    'eliminado' => 'Evento eliminado.',
    'noexiste'  => 'Ese evento no existe.',
];
$aviso = null;
$claseAviso = 'alert--ok';
foreach ($avisos as $clave => $mensaje) {
    if (($_GET[$clave] ?? null) === '1') {
        $aviso = $mensaje;
        if ($clave === 'noexiste') {
            $claseAviso = 'alert--error';
        }
        break;
    }
}

// ───────── P9 · Paso 1: lee lo que se busca ─────────
$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 60) : '';   // texto, máx. 60
$categoriaId = (int) ($_GET['categoria'] ?? 0);                                  // 0 = todas

// Las categorías del filtro (igual que el <select> de registrar.php)
$categorias = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
$idsValidos = array_map('intval', array_column($categorias, 'id'));
if (!in_array($categoriaId, $idsValidos, true)) {
    $categoriaId = 0;                                   // una categoría inventada = todas
}
$buscando = ($q !== '' || $categoriaId !== 0);

// ───────── P9 · Paso 2: arma la consulta por piezas ─────────
$condiciones = ['e.usuario_id = ?'];                    // SIEMPRE: solo mis eventos (P8)
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
if ($categoriaId !== 0) {
    $condiciones[] = 'e.categoria_id = ?';
    $tipos      .= 'i';
    $valores[]   = $categoriaId;
}

// implode une NUESTRAS piezas fijas; los datos del usuario solo viajan por los ?
$sql = 'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion,
               c.id AS categoria_id, c.nombre AS categoria
          FROM eventos e
          JOIN categorias c ON c.id = e.categoria_id
         WHERE ' . implode(' AND ', $condiciones) . '
         ORDER BY e.fecha ASC, e.hora ASC';
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($tipos, ...$valores);                 // ... reparte el arreglo en argumentos
$stmt->execute();
$eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Resumen por categoría (sin cambios). El filtro va en el ON para que
// las categorías sin eventos tuyos sigan apareciendo con 0.
$stmt = $mysqli->prepare(
    'SELECT c.nombre, COUNT(e.id) AS total
       FROM categorias c
       LEFT JOIN eventos e ON e.categoria_id = c.id AND e.usuario_id = ?
      GROUP BY c.id, c.nombre
      ORDER BY c.nombre'
);
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb · Mis Eventos</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="index.php" class="logo">Agenda<span>Web</span></a>
            <nav class="nav">
                <a href="index.php" class="nav__link is-active">Mis eventos</a>
                <a href="registrar.php" class="nav__link">Nuevo evento</a>
                <span class="nav__link">Hola, <?= e($usuarioNombre) ?></span>
                <a href="logout.php" class="nav__link">Salir</a>
            </nav>
        </div>
    </header>

    <main class="contenedor">

        <!-- AVISO DINÁMICO (Patrón PRG) -->
        <?php if ($aviso !== null): ?>
            <div class="alert <?= $claseAviso ?>" role="status"><?= $claseAviso === 'alert--ok' ? '&#9989;' : '&#9888;&#65039;' ?> <?= e($aviso) ?></div>
        <?php endif; ?>

        <div class="page__header">
            <div>
                <h1 class="page__title">Mis eventos</h1>
                <!-- P9 · Paso 5: el subtítulo cambia si estás buscando -->
                <p class="page__subtitle">
                    <?php if ($buscando): ?>
                        <?= count($eventos) ?> resultado<?= count($eventos) === 1 ? '' : 's' ?><?= $q !== '' ? ' para «' . e($q) . '»' : '' ?>
                    <?php else: ?>
                        <?= count($eventos) ?> eventos registrados
                    <?php endif; ?>
                </p>
            </div>
            <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
        </div>

        <!-- RESUMEN POR CATEGORÍA (GROUP BY) -->
        <p class="resumen">
            <?php foreach ($resumen as $r): ?>
                <span class="card__badge badge-azul">
                    <?= e($r['nombre']) ?> · <?= (int) $r['total'] ?>
                </span>
            <?php endforeach; ?>
        </p>

        <!-- P9 · Paso 4: FORMULARIO DE BÚSQUEDA (GET) -->
        <form class="buscador" method="get" action="index.php" role="search">
            <input class="form-input" type="search" name="q" value="<?= e($q) ?>" maxlength="60"
                   placeholder="Buscar en título o descripción…" aria-label="Buscar eventos">
            <select class="form-select" name="categoria" aria-label="Filtrar por categoría">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Buscar</button>
            <?php if ($buscando): ?>
                <a href="index.php" class="btn-secondary">Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- LISTA DE EVENTOS / SIN RESULTADOS / ESTADO VACÍO -->
        <?php if (empty($eventos) && $buscando): ?>
            <div class="empty-state">
                <p>Sin resultados para tu búsqueda.</p>
                <a href="index.php" class="btn-secondary">Ver todos mis eventos</a>
            </div>
        <?php elseif (empty($eventos)): ?>
            <div class="empty-state">
                <p>No tienes eventos registrados aún.</p>
                <a href="registrar.php" class="btn-primary">+ Crear el primero</a>
            </div>
        <?php else: ?>
            <section class="card-list">
                <?php foreach ($eventos as $evento): ?>
                    <article class="card">
                        <!-- Teal para Trabajo (1) y Estudio (3); el resto, azul -->
                        <span class="card__badge <?= in_array((int) $evento['categoria_id'], [1, 3], true) ? 'badge-teal' : 'badge-azul' ?>">
                            <?= e($evento['categoria']) ?>
                        </span>

                        <h3 class="card__title"><?= e($evento['titulo']) ?></h3>

                        <div class="card__meta">
                            📅 <?= e($evento['fecha']) ?>
                            <?php if (!empty($evento['hora'])): ?>
                                · ⏰ <?= e(date('H:i', strtotime($evento['hora']))) ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($evento['descripcion'])): ?>
                            <p class="card__text"><?= nl2br(e($evento['descripcion'])) ?></p>
                        <?php endif; ?>

                        <div class="card__actions">
                            <a href="editar.php?id=<?= (int) $evento['id'] ?>" class="btn-secondary btn-sm">Editar</a>

                            <!-- Eliminar usa POST (nunca un enlace) y pide confirmación -->
                            <form action="borrar.php" method="post" class="form-inline"
                                  onsubmit="return confirm('¿Eliminar este evento? Esta acción no se puede deshacer.');">
                                <input type="hidden" name="id" value="<?= (int) $evento['id'] ?>">
                                <button type="submit" class="btn-danger btn-sm">Eliminar</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · 2026
        </div>
    </footer>

</body>
</html>