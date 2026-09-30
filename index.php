<?php
require 'conexion.php';

// Consultar los eventos ordenados por fecha y hora
$sql = "SELECT id, titulo, fecha, hora, categoria, descripcion FROM eventos ORDER BY fecha ASC, hora ASC";
$resultado = $mysqli->query($sql);

// Guardar los registros en un arreglo asociativo
$eventos = $resultado->fetch_all(MYSQLI_ASSOC);

$resultado->free();
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
        <?php if (isset($_GET['ok']) && $_GET['ok'] === '1'): ?>
            <div class="alert alert--ok" role="status">&#9989; Evento guardado correctamente.</div>
        <?php endif; ?>

        <div class="page__header">
            <div>
                <h1 class="page__title">Mis eventos</h1>
                <p class="page__subtitle">Lista de compromisos y actividades registradas</p>
            </div>
            <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
        </div>

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
                        <span class="card__badge <?= in_array($evento['categoria'], ['trabajo', 'estudio']) ? 'badge-teal' : 'badge-azul' ?>">
                            <?= htmlspecialchars(ucfirst($evento['categoria']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        
                        <!-- Sanitización con htmlspecialchars() para prevenir XSS -->
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