<?php
session_start();
if (isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
require __DIR__ . '/conexion.php';

$correo = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';

    // 1 · Buscar al usuario por su correo
    $stmt = $mysqli->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ?');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();   // null si no existe
    $stmt->close();

    // 2 · ¿Existe y la contraseña coincide con su hash?
    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);                  // id de sesión nuevo al entrar
        $_SESSION['uid']    = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        header('Location: index.php');
        exit;
    }

    // 3 · Mismo mensaje en los dos casos: no revelamos si el correo existe
    $error = 'Correo o contraseña incorrectos.';
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
    <title>AgendaWeb · Iniciar sesión</title>

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
            <h2>Iniciar sesión</h2>

            <?php if (isset($_GET['registrado'])): ?>
                <div class="alert alert--ok">Cuenta creada. Ya puedes iniciar sesión.</div>
            <?php endif; ?>
            <?php if (isset($_GET['salio'])): ?>
                <div class="alert alert--ok">Cerraste sesión. ¡Hasta pronto!</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert--error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" class="formulario-evento">

                <div class="grupo-campo campo-completo">
                    <label for="correo">Correo</label>
                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        value="<?= e($correo) ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="grupo-campo campo-completo">
                    <label for="clave">Contraseña</label>
                    <input
                        type="password"
                        id="clave"
                        name="clave"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="campo-completo acciones-form">
                    <button type="submit" class="btn-primary">Entrar</button>
                    <a href="registro.php" class="btn-secondary">Crear cuenta</a>
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
