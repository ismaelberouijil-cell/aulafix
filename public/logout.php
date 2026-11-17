<?php
declare(strict_types=1);
/** Cierra la sesión por completo. Solo acepta POST con token CSRF. */
require __DIR__ . '/../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('incidencias.php');
}
csrf_verificar();

// Cierre completo: vaciar los datos, borrar la cookie de sesión del navegador y
// destruir la sesión en el servidor. Con solo una de las tres quedaría algo abierto.
$_SESSION = [];
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();

redirigir('login.php');
