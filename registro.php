<?php
session_start();
if (isset($_SESSION['uid'])) {          // ya tiene sesión: no necesita registrarse
    header('Location: index.php');
    exit;
}
require __DIR__ . '/conexion.php';

$nombre = $correo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));   // Ana@x.com = ana@x.com
    $clave  = $_POST['clave']  ?? '';                          // la contraseña NO se recorta
    $clave2 = $_POST['clave2'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);   // se guarda el HASH, nunca la clave
        try {
            $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();
            header('Location: login.php?registrado=1');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {               // 1062 = valor repetido en un UNIQUE
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
            }
        }
    }
}

// Escapa texto antes de ponerlo en el HTML
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb · Crear cuenta</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="layout">

    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="login.php" class="logo">Agenda<span>Web</span></a>
        </div>
    </header>

    <main class="contenedor">
        <section class="tarjeta tarjeta--auth">
            <h2>Crear cuenta</h2>

            <?php if (isset($errores['general'])): ?>
                <div class="alert alert--error" role="alert"><?= e($errores['general']) ?></div>
            <?php endif; ?>

            <form method="post" action="registro.php" class="formulario-evento">

                <!-- Nombre -->
                <div class="grupo-campo campo-completo">
                    <label for="nombre">Nombre</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="80"
                        value="<?= e($nombre) ?>"
                        autocomplete="name"
                        class="<?= isset($errores['nombre']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['nombre'])): ?>
                        <span class="error-texto"><?= e($errores['nombre']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Correo -->
                <div class="grupo-campo campo-completo">
                    <label for="correo">Correo</label>
                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        value="<?= e($correo) ?>"
                        autocomplete="email"
                        class="<?= isset($errores['correo']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['correo'])): ?>
                        <span class="error-texto"><?= e($errores['correo']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Contraseña (nunca se escribe de vuelta en el HTML) -->
                <div class="grupo-campo campo-completo">
                    <label for="clave">Contraseña</label>
                    <input
                        type="password"
                        id="clave"
                        name="clave"
                        minlength="8"
                        autocomplete="new-password"
                        class="<?= isset($errores['clave']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['clave'])): ?>
                        <span class="error-texto"><?= e($errores['clave']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo campo-completo">
                    <label for="clave2">Repítela</label>
                    <input
                        type="password"
                        id="clave2"
                        name="clave2"
                        minlength="8"
                        autocomplete="new-password"
                        class="<?= isset($errores['clave2']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['clave2'])): ?>
                        <span class="error-texto"><?= e($errores['clave2']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="campo-completo acciones-form">
                    <button type="submit" class="btn-primary">Crear cuenta</button>
                    <a href="login.php" class="btn-secondary">Ya tengo cuenta</a>
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