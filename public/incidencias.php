<?php
declare(strict_types=1);
/**
 * Listado de incidencias: tabla HTML generada en PHP (se ve aunque no haya
 * JavaScript), datos en JSON y una plantilla con la estructura del panel.
 * Si JavaScript funciona, main.js cambia la tabla de PHP por el panel.
 */
require __DIR__ . '/../includes/funciones.php';
exigir_login();

$filas = obtener_incidencias();
$json = incidencias_a_json($filas);

$titulo = 'Incidencias';
$scripts = ['main.js'];
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Incidencias del aula</h1>
<p><a class="boton" href="nueva.php">Avisar de una incidencia</a></p>

<div id="panel-contenedor">
    <div class="tabla-scroll">
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Equipo</th><th>Categoría</th><th>Prioridad</th><th>Estado</th><th>Descripción</th><th>Autor</th></tr>
        </thead>
        <tbody>
        <?php foreach ($filas as $f): ?>
            <tr>
                <td><?= e((new DateTimeImmutable($f['creada_en']))->format('d/m/Y H:i')) ?></td>
                <td><a href="incidencia.php?id=<?= (int) $f['id'] ?>"><?= e($f['equipo']) ?></a></td>
                <td><?= e($f['categoria']) ?></td>
                <td><?= e($f['prioridad']) ?></td>
                <td><?= e($f['estado']) ?></td>
                <td><?= e($f['descripcion']) ?></td>
                <td><?= e($f['autor']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Un <template> no se muestra: es solo el molde del panel. main.js lo copia a la página
     y rellena los datos con el DOM. Aquí no hay datos de usuarios, solo estructura. -->
<template id="plantilla-panel">
    <section aria-label="Panel de incidencias">
        <div class="resumen">
            <div class="tarjeta"><strong id="n-abierta" class="tarjeta-num">0</strong> Abiertas</div>
            <div class="tarjeta"><strong id="n-en_curso" class="tarjeta-num">0</strong> En curso</div>
            <div class="tarjeta"><strong id="n-resuelta" class="tarjeta-num">0</strong> Resueltas</div>
            <div class="tarjeta tarjeta-urgente"><strong id="n-urgentes" class="tarjeta-num">0</strong> Urgentes sin resolver</div>
        </div>

        <div class="pestanas" role="tablist">
            <button type="button" role="tab" class="pestana" data-estado="todas">Todas</button>
            <button type="button" role="tab" class="pestana" data-estado="abierta">Abiertas</button>
            <button type="button" role="tab" class="pestana" data-estado="en_curso">En curso</button>
            <button type="button" role="tab" class="pestana" data-estado="resuelta">Resueltas</button>
        </div>

        <div class="filtros">
            <input id="buscador" type="search" placeholder="Buscar por equipo, descripción o autor" aria-label="Buscar incidencias">
            <select id="prioridad" aria-label="Filtrar por prioridad">
                <option value="todas">Todas las prioridades</option>
                <option value="alta">Alta</option>
                <option value="media">Media</option>
                <option value="baja">Baja</option>
            </select>
        </div>

        <!-- aria-live: los lectores de pantalla anuncian el contador cuando cambia. -->
        <p id="contador" class="contador" aria-live="polite"></p>

        <div class="tabla-scroll">
        <table class="tabla">
            <thead>
                <tr>
                    <th><button type="button" class="orden" data-campo="fecha">Fecha</button></th>
                    <th><button type="button" class="orden" data-campo="equipo">Equipo</button></th>
                    <th>Categoría</th>
                    <th><button type="button" class="orden" data-campo="prioridad">Prioridad</button></th>
                    <th>Estado</th>
                    <th>Descripción</th>
                    <th>Autor</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla"></tbody>
        </table>
        </div>
    </section>
</template>

<!-- type="application/json": el navegador no lo ejecuta, solo lo guarda como texto para main.js. -->
<script id="datos-incidencias" type="application/json"><?= $json ?></script>
<?php include __DIR__ . '/../includes/pie.php'; ?>
