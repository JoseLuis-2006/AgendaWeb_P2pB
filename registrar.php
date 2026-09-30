<?php
// Variables iniciales para el formulario pegajoso
$errores     = [];
$titulo      = '';
$fecha       = '';
$hora        = '';
$categoria   = '';
$descripcion = '';

// Fase 3: Solo procesar si el método de envío es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitización inicial de entradas (si alguien manda algo que no es texto, se toma como vacío)
    $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    $titulo      = $leer('titulo');
    $fecha       = $leer('fecha');
    $hora        = $leer('hora');
    $categoria   = $leer('categoria');
    $descripcion = $leer('descripcion');

    // Fase 4: Lista blanca de categorías
    $categoriasOK = ['trabajo', 'personal', 'estudio', 'ocio'];

    // Validar Título
    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'El título no puede exceder los 120 caracteres.';
    }

    // Validar Fecha
    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } else {
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!($fechaObj && $fechaObj->format('Y-m-d') === $fecha)) {
            $errores['fecha'] = 'Proporciona una fecha válida (AAAA-MM-DD).';
        }
    }

    // Validar Hora (opcional, pero si viene debe ser HH:MM)
    if ($hora !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
        $errores['hora'] = 'Proporciona una hora válida (HH:MM).';
    }

    // Validar Categoría
    if (!in_array($categoria, $categoriasOK, true)) {
        $errores['categoria'] = 'Selecciona una categoría válida.';
    }

    // Validar Descripción (Opcional, pero con límite)
    if (mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede superar los 500 caracteres.';
    }

    // Si no hay errores de validación, procedemos a guardar
    if (empty($errores)) {
        require 'conexion.php';

        // Manejo de valores opcionales para almacenar NULL en lugar de cadenas vacías
        $hora_db        = ($hora !== '') ? $hora : null;
        $descripcion_db = ($descripcion !== '') ? $descripcion : null;

        // Consulta preparada con comodines (?)
        $sql = "INSERT INTO eventos (titulo, fecha, hora, categoria, descripcion) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $mysqli->prepare($sql);

        // "sssss" indica que se pasan 5 parámetros de tipo string
        $stmt->bind_param("sssss", $titulo, $fecha, $hora_db, $categoria, $descripcion_db);

        // Ejecución y liberación de recursos
        $stmt->execute();
        $nuevoId = $stmt->insert_id;
        $stmt->close();
        $mysqli->close();

        // Redirección del Patrón PRG (Fase 7). El 303 le dice al navegador que la siguiente
        // petición sea GET, así recargar index.php no vuelve a enviar el formulario.
        header('Location: index.php?ok=1', true, 303);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">

    <!-- Asegúrate de incluir la carpeta css/ en la ruta -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="layout">

    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="index.php" class="logo">Agenda<span>Web</span></a>
            <nav class="nav">
                <a href="index.php" class="nav__link">Mis eventos</a>
                <a href="registrar.php" class="nav__link is-active">Nuevo evento</a>
            </nav>
        </div>
    </header>

    <main class="contenedor">
        <section class="tarjeta">
            <h2>Nuevo Evento</h2>

            <!-- Formulario autoprocesado -->
            <form method="post" action="" class="formulario-evento" novalidate>
                
                <!-- Campo: Título -->
                <div class="grupo-campo">
                    <label for="titulo">Título *</label>
                    <input 
                        type="text" 
                        id="titulo" 
                        name="titulo" 
                        placeholder="Ej. Reunión de proyecto" 
                        value="<?= htmlspecialchars($titulo) ?>"
                        class="<?= isset($errores['titulo']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['titulo'])): ?>
                        <span class="error-texto"><?= $errores['titulo'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- Campo: Fecha -->
                <div class="grupo-campo">
                    <label for="fecha">Fecha *</label>
                    <input 
                        type="date" 
                        id="fecha" 
                        name="fecha" 
                        value="<?= htmlspecialchars($fecha) ?>"
                        class="<?= isset($errores['fecha']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['fecha'])): ?>
                        <span class="error-texto"><?= $errores['fecha'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- Campo: Hora (Opcional) -->
                <div class="grupo-campo">
                    <label for="hora">Hora</label>
                    <input 
                        type="time" 
                        id="hora" 
                        name="hora"
                        value="<?= htmlspecialchars($hora) ?>"
                        class="<?= isset($errores['hora']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['hora'])): ?>
                        <span class="error-texto"><?= $errores['hora'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- Campo: Categoría -->
                <div class="grupo-campo">
                    <label for="categoria">Categoría *</label>
                    <select 
                        id="categoria" 
                        name="categoria"
                        class="<?= isset($errores['categoria']) ? 'input-error' : '' ?>"
                    >
                        <option value="">-- Selecciona --</option>
                        <option value="trabajo" <?= $categoria === 'trabajo' ? 'selected' : '' ?>>Trabajo</option>
                        <option value="personal" <?= $categoria === 'personal' ? 'selected' : '' ?>>Personal</option>
                        <option value="estudio" <?= $categoria === 'estudio' ? 'selected' : '' ?>>Estudio</option>
                        <option value="ocio" <?= $categoria === 'ocio' ? 'selected' : '' ?>>Ocio</option>
                    </select>
                    <?php if (isset($errores['categoria'])): ?>
                        <span class="error-texto"><?= $errores['categoria'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- Campo: Descripción -->
                <div class="grupo-campo campo-completo">
                    <label for="descripcion">Descripción</label>
                    <textarea 
                        id="descripcion" 
                        name="descripcion" 
                        rows="3"
                        placeholder="Notas o detalles adicionales..."
                        class="<?= isset($errores['descripcion']) ? 'input-error' : '' ?>"
                    ><?= htmlspecialchars($descripcion) ?></textarea>
                    <?php if (isset($errores['descripcion'])): ?>
                        <span class="error-texto"><?= $errores['descripcion'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary">Guardar Evento</button>
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