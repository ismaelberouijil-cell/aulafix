<?php
declare(strict_types=1);
/**
 * Pie común: cierra la página y carga como módulos los JS que la página
 * haya indicado en $scripts (nombres de fichero dentro de public/js/).
 */
?>
</main>
<?php foreach (($scripts ?? []) as $js): ?>
<script type="module" src="js/<?= e($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
