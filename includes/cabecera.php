<?php
declare(strict_types=1);
/**
 * Cabecera común: <head>, menú de navegación y mensaje flash. Usa $titulo si existe.
 */
require_once __DIR__ . '/funciones.php';

$u = usuario_actual();
$flash = tomar_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'AulaFix') ?> · AulaFix</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="barra">
    <a class="marca" href="incidencias.php">AulaFix</a>
    <nav>
        <?php if ($u !== null): ?>
            <a href="incidencias.php">Incidencias</a>
            <a href="nueva.php">Nueva</a>
            <span class="usuario"><?= e($u['nombre']) ?> (<?= e($u['rol']) ?>)</span>
            <form method="post" action="logout.php" class="en-linea">
                <?= csrf_campo() ?>
                <button type="submit">Cerrar sesión</button>
            </form>
        <?php else: ?>
            <a href="login.php">Entrar</a>
            <a href="registro.php">Registrarse</a>
        <?php endif; ?>
    </nav>
</header>
<main>
<?php if ($flash !== null): ?>
    <p class="flash" role="status"><?= e($flash) ?></p>
<?php endif; ?>
