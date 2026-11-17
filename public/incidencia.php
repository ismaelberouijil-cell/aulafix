<?php
declare(strict_types=1);
/**
 * Detalle de una incidencia con su historial. El profesorado cambia el estado
 * (actualización + línea de historial en una transacción) y se puede borrar
 * según los permisos.
 */
require __DIR__ . '/../includes/funciones.php';
$usuario = exigir_login();

// (int) convierte a 0 cualquier valor raro de la URL, y 0 se trata como "no existe".
$id = (int) ($_GET['id'] ?? 0);
$incidencia = $id > 0 ? obtener_incidencia($id) : null;
if ($incidencia === null) {
    error_pagina(404, 'La incidencia no existe.');
}

$errores = [];
$nuevoEstado = $incidencia['estado'];
$comentario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $accion = leer_texto('accion');

    if ($accion === 'cambiar_estado') {
        // Permiso comprobado en el servidor: un alumno que envíe este POST a mano recibe 403.
        if ($usuario['rol'] !== 'profesor') {
            error_pagina(403, 'Solo el profesorado puede cambiar el estado.');
        }
        $nuevoEstado = leer_texto('estado');
        $comentario = leer_texto('comentario');

        if (!in_array($nuevoEstado, config()['estados'], true)) {
            $errores['estado'] = 'Estado no válido.';
        } elseif ($nuevoEstado === $incidencia['estado']) {
            $errores['estado'] = 'Elige un estado distinto del actual.';
        }
        if (!validar_longitud($comentario, 1, 300)) {
            $errores['comentario'] = 'El comentario es obligatorio (máximo 300 caracteres).';
        }

        if ($errores === []) {
            $pdo = db();
            $ahora = date('Y-m-d H:i:s');
            try {
                // Transacción: se guardan el cambio de estado Y su línea de historial, o
                // ninguno de los dos. Sin ella, un fallo entre las dos consultas dejaría la
                // incidencia cambiada sin rastro de quién lo hizo ni por qué.
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE incidencias SET estado = ?, actualizada_en = ? WHERE id = ?')
                    ->execute([$nuevoEstado, $ahora, $id]);
                $pdo->prepare(
                    'INSERT INTO historial (incidencia_id, usuario_id, estado_anterior, estado_nuevo, comentario, creado_en)
                     VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([$id, $usuario['id'], $incidencia['estado'], $nuevoEstado, $comentario, $ahora]);
                $pdo->commit();
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack(); // deshace lo que se hubiera guardado a medias
                }
                throw $ex;
            }
            flash('Estado actualizado.');
            redirigir('incidencia.php?id=' . $id);
        }
    } elseif ($accion === 'borrar') {
        if (!puede_borrar($usuario, $incidencia)) {
            error_pagina(403, 'No puedes borrar esta incidencia.');
        }
        // El historial se borra solo gracias a ON DELETE CASCADE de la tabla.
        db()->prepare('DELETE FROM incidencias WHERE id = ?')->execute([$id]);
        flash('Incidencia borrada.');
        redirigir('incidencias.php');
    }
}

// Historial de la incidencia, del cambio más reciente al más antiguo.
$st = db()->prepare(
    'SELECT h.estado_anterior, h.estado_nuevo, h.comentario, h.creado_en, u.nombre
     FROM historial h JOIN usuarios u ON u.id = h.usuario_id
     WHERE h.incidencia_id = ? ORDER BY h.creado_en DESC, h.id DESC'
);
$st->execute([$id]);
$historial = $st->fetchAll();

$titulo = 'Incidencia #' . $id;
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Incidencia #<?= (int) $incidencia['id'] ?> · <?= e($incidencia['equipo']) ?></h1>
<dl class="detalle">
    <dt>Estado</dt><dd><?= e($incidencia['estado']) ?></dd>
    <dt>Categoría</dt><dd><?= e($incidencia['categoria']) ?></dd>
    <dt>Prioridad</dt><dd><?= e($incidencia['prioridad']) ?></dd>
    <dt>Autor</dt><dd><?= e($incidencia['autor']) ?></dd>
    <dt>Creada</dt><dd><?= e((new DateTimeImmutable($incidencia['creada_en']))->format('d/m/Y H:i')) ?></dd>
    <dt>Descripción</dt><dd><?= e($incidencia['descripcion']) ?></dd>
</dl>

<h2>Historial</h2>
<?php if ($historial === []): ?>
    <p>Sin cambios de estado todavía.</p>
<?php else: ?>
    <ul>
    <?php foreach ($historial as $h): ?>
        <li><?= e((new DateTimeImmutable($h['creado_en']))->format('d/m/Y H:i')) ?> ·
            <?= e($h['nombre']) ?>: <?= e($h['estado_anterior']) ?> → <?= e($h['estado_nuevo']) ?> — <?= e($h['comentario']) ?></li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($usuario['rol'] === 'profesor'): ?>
<h2>Cambiar estado</h2>
<form method="post" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="cambiar_estado">
    <label for="estado">Nuevo estado</label>
    <select id="estado" name="estado">
        <?php foreach (config()['estados'] as $es): ?>
            <option value="<?= e($es) ?>" <?= $es === $nuevoEstado ? 'selected' : '' ?>><?= e($es) ?></option>
        <?php endforeach; ?>
    </select>
    <?= error_campo($errores, 'estado') ?>
    <label for="comentario">Comentario</label>
    <textarea id="comentario" name="comentario" rows="3" maxlength="300"><?= e($comentario) ?></textarea>
    <?= error_campo($errores, 'comentario') ?>
    <button type="submit">Guardar cambio</button>
</form>
<?php endif; ?>

<?php if (puede_borrar($usuario, $incidencia)): ?>
<form method="post" class="borrar">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="borrar">
    <button type="submit" class="peligro">Borrar incidencia</button>
</form>
<?php endif; ?>
<p><a href="incidencias.php">← Volver al listado</a></p>
<?php include __DIR__ . '/../includes/pie.php'; ?>
