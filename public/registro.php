<?php
declare(strict_types=1);
/**
 * Registro de usuarios nuevos (siempre con rol alumno). Valida en el servidor,
 * guarda la contraseña con Argon2id y redirige al login (PRG).
 */
require __DIR__ . '/../includes/funciones.php';

if (usuario_actual() !== null) {
    redirigir('incidencias.php');
}

$errores = [];
$nombre = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $nombre = leer_texto('nombre');
    $email = mb_strtolower(leer_texto('email'));
    // Las contraseñas no se recortan con trim(): los espacios pueden formar parte de ellas.
    $clave = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';
    $repite = is_string($_POST['repite'] ?? null) ? $_POST['repite'] : '';

    if (!validar_longitud($nombre, 2, 80)) {
        $errores['nombre'] = 'El nombre debe tener entre 2 y 80 caracteres.';
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errores['email'] = 'Escribe un correo válido.';
    } else {
        // Consulta preparada: el correo va aparte del SQL, no se puede inyectar nada.
        $st = db()->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetchColumn() !== false) {
            $errores['email'] = 'Ese correo ya está registrado.';
        }
    }

    // Contraseña: al menos 10 caracteres, con alguna letra y algún número.
    if (mb_strlen($clave) < 10 || preg_match('/[A-Za-z]/', $clave) !== 1 || preg_match('/\d/', $clave) !== 1) {
        $errores['clave'] = 'Mínimo 10 caracteres, con letras y números.';
    }
    if ($clave !== $repite) {
        $errores['repite'] = 'Las contraseñas no coinciden.';
    }

    if ($errores === []) {
        $st = db()->prepare('INSERT INTO usuarios (nombre, email, clave_hash, rol) VALUES (?, ?, ?, ?)');
        $st->execute([$nombre, $email, password_hash($clave, PASSWORD_ARGON2ID), 'alumno']);
        flash('Cuenta creada. Ya puedes iniciar sesión.');
        redirigir('login.php');
    }
}

$titulo = 'Registro';
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Crear cuenta</h1>
<form method="post" novalidate>
    <?= csrf_campo() ?>
    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" value="<?= e($nombre) ?>" required>
    <?= error_campo($errores, 'nombre') ?>

    <label for="email">Correo</label>
    <input id="email" name="email" type="email" value="<?= e($email) ?>" required>
    <?= error_campo($errores, 'email') ?>

    <label for="clave">Contraseña</label>
    <input id="clave" name="clave" type="password" autocomplete="new-password" required>
    <?= error_campo($errores, 'clave') ?>

    <label for="repite">Repite la contraseña</label>
    <input id="repite" name="repite" type="password" autocomplete="new-password" required>
    <?= error_campo($errores, 'repite') ?>

    <button type="submit">Registrarme</button>
</form>
<?php include __DIR__ . '/../includes/pie.php'; ?>
