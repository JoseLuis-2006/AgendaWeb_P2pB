<?php
// Plantilla de conexion.php (el archivo real NO se sube a GitHub: está en .gitignore).
// Cópiala como conexion.php y pon tus datos de MySQL.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli("localhost", "TU_USUARIO", "TU_CONTRASEÑA", "agenda");
    $mysqli->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("No se pudo conectar a la base de datos.");
}
