<?php
declare(strict_types=1);
/**
 * Formulario para avisar de una incidencia. Valida con listas blancas, muestra
 * los errores junto a cada campo, conserva lo escrito, guarda con PDO y redirige (PRG).
 */
require __DIR__ . '/../includes/funciones.php';
$usuario = exigir_login();

// Equipos desde la base de datos para el desplegable.
$equipos = db()->query('SELECT id, codigo, descripcion FROM equipos ORDER BY codigo')->fetchAll();
$idsEquipo = array_map(static fn(array $e): int => (int) $e['id'], $equipos);

$errores = [];
$v = ['equipo_id' => '', 'categoria' => '', 'prioridad' => '', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    foreach ($v as $campo => $_) {
        $v[$campo] = leer_texto($campo);
    }

    // Listas blancas: aunque envíen un valor inventado a mano, solo pasan los permitidos.
    if (!in_array((int) $v['equipo_id'], $idsEquipo, true)) {
        $errores['equipo_id'] = 'Elige un equipo de la lista.';
    }
    if (!in_array($v['categoria'], config()['categorias'], true)) {
        $errores['categoria'] = 'Elige una categoría válida.';
    }
    if (!in_array($v['prioridad'], config()['prioridades'], true)) {
        $errores['prioridad'] = 'Elige una prioridad.';
    }
    if (!validar_longitud($v['descripcion'], 10, 500)) {
        $errores['descripcion'] = 'La descripción debe tener entre 10 y 500 caracteres.';
    }

    if ($errores === []) {
        $ahora = date('Y-m-d H:i:s');
        $st = db()->prepare(
            'INSERT INTO incidencias (equipo_id, usuario_id, categoria, prioridad, descripcion, estado, creada_en, actualizada_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([(int) $v['equipo_id'], $usuario['id'], $v['categoria'], $v['prioridad'], $v['descripcion'], 'abierta', $ahora, $ahora]);
        flash('Incidencia registrada correctamente.');
        redirigir('incidencias.php');
    }
}

$titulo = 'Nueva incidencia';
$scripts = ['nueva.js'];
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Nueva incidencia</h1>
<form method="post" novalidate>
    <?= csrf_campo() ?>

    <label for="equipo_id">Equipo</label>
    <select id="equipo_id" name="equipo_id" required>
        <option value="">— Elige —</option>
        <?php foreach ($equipos as $eq): ?>
            <option value="<?= (int) $eq['id'] ?>" <?= (string) $eq['id'] === $v['equipo_id'] ? 'selected' : '' ?>>
                <?= e($eq['codigo']) ?> · <?= e($eq['descripcion']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?= error_campo($errores, 'equipo_id') ?>

    <label for="categoria">Categoría</label>
    <select id="categoria" name="categoria" required>
        <option value="">— Elige —</option>
        <?php foreach (config()['categorias'] as $c): ?>
            <option value="<?= e($c) ?>" <?= $c === $v['categoria'] ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
    </select>
    <?= error_campo($errores, 'categoria') ?>

    <fieldset>
        <legend>Prioridad</legend>
        <?php foreach (config()['prioridades'] as $p): ?>
            <label class="check"><input type="radio" name="prioridad" value="<?= e($p) ?>" <?= $p === $v['prioridad'] ? 'checked' : '' ?>> <?= e($p) ?></label>
        <?php endforeach; ?>
        <?= error_campo($errores, 'prioridad') ?>
    </fieldset>

    <label for="descripcion">Descripción</label>
    <textarea id="descripcion" name="descripcion" rows="5" maxlength="600" required><?= e($v['descripcion']) ?></textarea>
    <span id="contador-desc" class="ayuda">Entre 10 y 500 caracteres</span>
    <?= error_campo($errores, 'descripcion') ?>

    <button type="submit">Enviar</button>
</form>
<?php include __DIR__ . '/../includes/pie.php'; ?>
