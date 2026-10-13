<?php
require 'conexion.php';

// Avisos que llegan por la URL después de una redirección (patrón PRG)
$avisos = [
    'ok'        => 'Evento guardado correctamente.',
    'editado'   => 'Evento actualizado correctamente.',
    'eliminado' => 'Evento eliminado.',
];
$aviso = null;
foreach ($avisos as $clave => $mensaje) {
    if (($_GET[$clave] ?? null) === '1') {
        $aviso = $mensaje;
        break;
    }
}

// Paso 5: JOIN para traer el NOMBRE de la categoría (en la tabla solo hay un número)
$resultado = $mysqli->query(
    'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion,
            c.id AS categoria_id, c.nombre AS categoria
       FROM eventos e
       JOIN categorias c ON c.id = e.categoria_id
      ORDER BY e.fecha ASC, e.hora ASC'
);
$eventos = $resultado->fetch_all(MYSQLI_ASSOC);
$resultado->free();

// Paso 6: resumen por categoría. LEFT JOIN para que también salgan las que tienen 0 eventos
$resumen = $mysqli->query(
    'SELECT c.nombre, COUNT(e.id) AS total
       FROM categorias c
       LEFT JOIN eventos e ON e.categoria_id = c.id
      GROUP BY c.id, c.nombre
      ORDER BY c.nombre'
)->fetch_all(MYSQLI_ASSOC);

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
            </nav>
        </div>
    </header>

    <main class="contenedor">

        <!-- AVISO DINÁMICO (Patrón PRG) -->
        <?php if ($aviso !== null): ?>
            <div class="alert alert--ok" role="status">&#9989; <?= htmlspecialchars($aviso, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="page__header">
            <div>
                <h1 class="page__title">Mis eventos</h1>
                <p class="page__subtitle">Lista de compromisos y actividades registradas</p>
            </div>
            <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
        </div>

        <!-- RESUMEN POR CATEGORÍA (GROUP BY) -->
        <p class="resumen">
            <?php foreach ($resumen as $r): ?>
                <span class="card__badge badge-azul">
                    <?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?> · <?= (int) $r['total'] ?>
                </span>
            <?php endforeach; ?>
        </p>

        <!-- LISTA DE EVENTOS O ESTADO VACÍO -->
        <?php if (empty($eventos)): ?>
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
                            <?= htmlspecialchars($evento['categoria'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <h3 class="card__title"><?= htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>

                        <div class="card__meta">
                            📅 <?= htmlspecialchars($evento['fecha'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($evento['hora'])): ?>
                                · ⏰ <?= htmlspecialchars(date('H:i', strtotime($evento['hora'])), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($evento['descripcion'])): ?>
                            <p class="card__text"><?= nl2br(htmlspecialchars($evento['descripcion'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php endif; ?>

                        <div class="card__actions">
                            <a href="editar.php?id=<?= (int) $evento['id'] ?>" class="btn-secondary btn-sm">Editar</a>

                            <!-- Eliminar usa POST (nunca un enlace) y pide confirmación -->
                            <form action="eliminar.php" method="post" class="form-inline"
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