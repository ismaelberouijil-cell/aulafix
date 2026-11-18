<?php
declare(strict_types=1);
/**
 * Funciones comunes de AulaFix: escape de salida, sesión, CSRF, permisos,
 * validación de formularios y consultas de incidencias.
 */
require_once __DIR__ . '/db.php';

// Los errores van al log y no a la pantalla: podrían enseñar rutas o consultas SQL.
ini_set('display_errors', '0');

// Sesión de PHP: guarda en el servidor quién ha iniciado sesión.
session_start();

/**
 * Escapa un texto antes de escribirlo en el HTML (evita XSS).
 * Se escapa al SALIR y no al guardar: en la base de datos queda el texto original
 * y cada destino (HTML, JSON...) aplica el escape que necesita.
 *
 * @param mixed $valor Texto que se va a mostrar.
 * @return string Texto seguro para HTML.
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige y termina el script (patrón Post-Redirect-Get: así F5 no reenvía el formulario).
 *
 * @param string $url Destino.
 */
function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** @return string Token CSRF de la sesión (se crea la primera vez). */
function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

/** @return string Campo oculto con el token CSRF, listo para meter en un formulario. */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Comprueba el token CSRF del POST. Si no coincide responde 403: así otra web
 * no puede enviar formularios en nombre del usuario.
 */
function csrf_verificar(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        error_pagina(403, 'El formulario ha caducado o no es válido.');
    }
}

/**
 * Muestra una página de error (403 o 404) y termina.
 * Los permisos se comprueban siempre en el servidor: ocultar un botón no protege
 * nada si alguien envía la petición a mano.
 *
 * @param int $codigo Código HTTP (403 o 404).
 * @param string $mensaje Explicación para el usuario.
 */
function error_pagina(int $codigo, string $mensaje): never
{
    http_response_code($codigo);
    $titulo = 'Error ' . $codigo;
    include __DIR__ . '/cabecera.php';
    echo '<h1>Error ' . $codigo . '</h1><p>' . e($mensaje) . '</p>';
    include __DIR__ . '/pie.php';
    exit;
}

/** @return array|null Usuario en sesión (id, nombre, rol) o null si no ha entrado. */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/**
 * Exige sesión iniciada: si no la hay, manda al login.
 *
 * @return array Usuario en sesión.
 */
function exigir_login(): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        redirigir('login.php');
    }
    return $usuario;
}

/** Guarda un mensaje que se mostrará una sola vez tras la redirección. */
function flash(string $mensaje): void
{
    $_SESSION['flash'] = $mensaje;
}

/** @return string|null Mensaje flash pendiente (se borra al leerlo). */
function tomar_flash(): ?string
{
    $mensaje = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_string($mensaje) ? $mensaje : null;
}

/**
 * Lee un campo de texto del POST sin espacios sobrantes.
 *
 * @param string $campo Nombre del campo.
 * @return string Texto ('' si falta o no es texto, por ejemplo campo[] manipulado a mano).
 */
function leer_texto(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/**
 * Comprueba que la longitud (en caracteres, no en bytes) está entre dos límites.
 *
 * @param string $texto Texto a validar.
 * @param int $min Mínimo.
 * @param int $max Máximo.
 * @return bool True si cumple.
 */
function validar_longitud(string $texto, int $min, int $max): bool
{
    $n = mb_strlen($texto);
    return $n >= $min && $n <= $max;
}

/**
 * Pinta el error de un campo (si lo hay) justo debajo de él.
 *
 * @param array $errores Errores indexados por nombre de campo.
 * @param string $campo Campo cuyo error se quiere mostrar.
 * @return string HTML del error o cadena vacía.
 */
function error_campo(array $errores, string $campo): string
{
    return isset($errores[$campo])
        ? '<span class="error" role="alert">' . e($errores[$campo]) . '</span>'
        : '';
}

// Consulta base de incidencias con el código del equipo y el nombre del autor.
const SQL_INCIDENCIAS = 'SELECT i.id, i.usuario_id, e.codigo AS equipo, i.categoria, i.prioridad,
                                i.descripcion, i.estado, u.nombre AS autor, i.creada_en
                         FROM incidencias i
                         JOIN equipos e  ON e.id = i.equipo_id
                         JOIN usuarios u ON u.id = i.usuario_id';

/**
 * Lista todas las incidencias, de la más nueva a la más antigua.
 *
 * @return array[] Filas de la base de datos.
 */
function obtener_incidencias(): array
{
    return db()->query(SQL_INCIDENCIAS . ' ORDER BY i.creada_en DESC, i.id DESC')->fetchAll();
}

/**
 * Busca una incidencia por id. Consulta preparada: el id va aparte del SQL.
 *
 * @param int $id Identificador.
 * @return array|null Fila o null si no existe.
 */
function obtener_incidencia(int $id): ?array
{
    $st = db()->prepare(SQL_INCIDENCIAS . ' WHERE i.id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/**
 * Convierte las incidencias al JSON del "contrato" con el cliente (apartado 3.4).
 * Las opciones JSON_HEX_* escapan < > & ' ", así un texto como "</script>" no
 * puede cerrar la etiqueta <script> donde se incrusta el JSON.
 *
 * @param array[] $filas Resultado de obtener_incidencias().
 * @return string JSON seguro para incrustar en HTML.
 */
function incidencias_a_json(array $filas): string
{
    $datos = [];
    foreach ($filas as $f) {
        $datos[] = [
            'id'          => (int) $f['id'],
            'equipo'      => $f['equipo'],
            'categoria'   => $f['categoria'],
            'prioridad'   => $f['prioridad'],
            'descripcion' => $f['descripcion'],
            'estado'      => $f['estado'],
            'autor'       => $f['autor'],
            'creada_en'   => (new DateTimeImmutable($f['creada_en']))->format('c'),
        ];
    }
    return json_encode(
        $datos,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
}

/**
 * Un profesor borra cualquier incidencia; un alumno solo las suyas y si siguen abiertas.
 *
 * @param array $usuario Usuario en sesión.
 * @param array $incidencia Fila de la incidencia.
 * @return bool True si puede borrarla.
 */
function puede_borrar(array $usuario, array $incidencia): bool
{
    return $usuario['rol'] === 'profesor'
        || ((int) $incidencia['usuario_id'] === (int) $usuario['id'] && $incidencia['estado'] === 'abierta');
}
