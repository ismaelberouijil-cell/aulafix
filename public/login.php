<?php
declare(strict_types=1);
/**
 * Inicio de sesión: password_verify, nuevo identificador de sesión al entrar y
 * casilla "Recordar mi correo" (cookie de 30 días que guarda solo el correo).
 */
require __DIR__ . '/../includes/funciones.php';

if (usuario_actual() !== null) {
    redirigir('incidencias.php');
}

$error = '';
// Si hay cookie de "recordar", se rellena el correo y se deja marcada la casilla.
$email = is_string($_COOKIE['recordar_email'] ?? null) ? $_COOKIE['recordar_email'] : '';
$recordar = $email !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $email = mb_strtolower(leer_texto('email'));
    $clave = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';
    $recordar = isset($_POST['recordar']);

    $st = db()->prepare('SELECT id, nombre, clave_hash, rol FROM usuarios WHERE email = ?');
    $st->execute([$email]);
    $fila = $st->fetch();

    if ($fila !== false && password_verify($clave, $fila['clave_hash'])) {
        // Id de sesión nuevo al entrar: si alguien conocía el anterior (fijación de
        // sesión), ya no le sirve.
        session_regenerate_id(true);
        $_SESSION['usuario'] = ['id' => (int) $fila['id'], 'nombre' => $fila['nombre'], 'rol' => $fila['rol']];

        // Cookie solo con el correo (nunca la contraseña). HttpOnly: JavaScript no la lee.
        // SameSite=Lax: no se envía desde otras webs.
        $opciones = ['path' => '/', 'httponly' => true, 'samesite' => 'Lax'];
        if ($recordar) {
            setcookie('recordar_email', $email, $opciones + ['expires' => time() + 30 * 86400]);
        } else {
            setcookie('recordar_email', '', $opciones + ['expires' => time() - 3600]); // la borra
        }
        redirigir('incidencias.php');
    }
    // Mismo mensaje si falla el correo o la contraseña: así no se puede averiguar
    // qué correos están registrados.
    $error = 'Correo o contraseña incorrectos.';
}

$titulo = 'Iniciar sesión';
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Iniciar sesión</h1>
<?php if ($error !== ''): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post">
    <?= csrf_campo() ?>
    <label for="email">Correo</label>
    <input id="email" name="email" type="email" value="<?= e($email) ?>" required>

    <label for="clave">Contraseña</label>
    <input id="clave" name="clave" type="password" autocomplete="current-password" required>

    <label class="check"><input type="checkbox" name="recordar" <?= $recordar ? 'checked' : '' ?>> Recordar mi correo</label>
    <button type="submit">Entrar</button>
</form>
<?php include __DIR__ . '/../includes/pie.php'; ?>
