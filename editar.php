<?php
require 'conexion.php';

// 1. El id viene en la URL (editar.php?id=5). Se valida que sea un entero positivo.
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: index.php');
    exit;
}

// 2. Cargar el evento actual (consulta preparada)
$stmt = $mysqli->prepare(
    'SELECT titulo, fecha, hora, categoria_id, descripcion FROM eventos WHERE id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$evento = $stmt->get_result()->fetch_assoc();   // null si no existe
$stmt->close();

if ($evento === null) {          // id que no existe: de vuelta a la lista
    header('Location: index.php');
    exit;
}

// 3. Categorías desde la BD (igual que en registrar.php)
$resultado  = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre');
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);
$resultado->free();
$idsValidos = array_map('intval', array_column($categorias, 'id'));

// 4. El formulario arranca con los datos guardados.
//    La hora llega como "14:30:00"; el campo <input type="time"> necesita "14:30".
$errores     = [];
$titulo      = $evento['titulo'];
$fecha       = $evento['fecha'];
$hora        = $evento['hora'] !== null ? substr($evento['hora'], 0, 5) : '';
$categoriaId = (int) $evento['categoria_id'];
$descripcion = $evento['descripcion'] ?? '';

// 5. Si se envió el formulario, los valores enviados reemplazan a los guardados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    $titulo      = $leer('titulo');
    $fecha       = $leer('fecha');
    $hora        = $leer('hora');
    $categoriaId = (int) $leer('categoria_id');
    $descripcion = $leer('descripcion');

    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'El título no puede exceder los 120 caracteres.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } else {
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!($fechaObj && $fechaObj->format('Y-m-d') === $fecha)) {
            $errores['fecha'] = 'Proporciona una fecha válida (AAAA-MM-DD).';
        }
    }

    if ($hora !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
        $errores['hora'] = 'Proporciona una hora válida (HH:MM).';
    }

    if (!in_array($categoriaId, $idsValidos, true)) {
        $errores['categoria_id'] = 'Elige una categoría válida.';
    }

    if (mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede superar los 500 caracteres.';
    }

    if (empty($errores)) {
        $hora_db        = ($hora !== '') ? $hora : null;
        $descripcion_db = ($descripcion !== '') ? $descripcion : null;

        // UPDATE con WHERE id = ?  (sin WHERE se cambiarían TODOS los eventos)
        $stmt = $mysqli->prepare(
            'UPDATE eventos
                SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ?
              WHERE id = ?'
        );
        // s s s i s i  → el último (i) es el id del WHERE
        $stmt->bind_param('sssisi', $titulo, $fecha, $hora_db, $categoriaId, $descripcion_db, $id);
        $stmt->execute();
        $stmt->close();
        $mysqli->close();

        header('Location: index.php?editado=1', true, 303);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb · Editar evento</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="layout">

    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="index.php" class="logo">Agenda<span>Web</span></a>
            <nav class="nav">
                <a href="index.php" class="nav__link">Mis eventos</a>
                <a href="registrar.php" class="nav__link">Nuevo evento</a>
            </nav>
        </div>
    </header>

    <main class="contenedor">
        <section class="tarjeta">
            <h2>Editar evento</h2>

            <!-- El id viaja en la URL del action -->
            <form method="post" action="editar.php?id=<?= $id ?>" class="formulario-evento" novalidate>

                <div class="grupo-campo">
                    <label for="titulo">Título *</label>
                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= isset($errores['titulo']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['titulo'])): ?>
                        <span class="error-texto"><?= $errores['titulo'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="fecha">Fecha *</label>
                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        value="<?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= isset($errores['fecha']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['fecha'])): ?>
                        <span class="error-texto"><?= $errores['fecha'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="hora">Hora</label>
                    <input
                        type="time"
                        id="hora"
                        name="hora"
                        value="<?= htmlspecialchars($hora, ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= isset($errores['hora']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['hora'])): ?>
                        <span class="error-texto"><?= $errores['hora'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="categoria_id">Categoría *</label>
                    <select
                        id="categoria_id"
                        name="categoria_id"
                        class="<?= isset($errores['categoria_id']) ? 'input-error' : '' ?>"
                    >
                        <option value="">— Elige una —</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"
                                <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['categoria_id'])): ?>
                        <span class="error-texto"><?= $errores['categoria_id'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo campo-completo">
                    <label for="descripcion">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="3"
                        placeholder="Notas o detalles adicionales..."
                        class="<?= isset($errores['descripcion']) ? 'input-error' : '' ?>"
                    ><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php if (isset($errores['descripcion'])): ?>
                        <span class="error-texto"><?= $errores['descripcion'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="campo-completo acciones-form">
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                    <a href="index.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </section>
    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · Jose Luis · 2026
        </div>
    </footer>

</body>
</html>