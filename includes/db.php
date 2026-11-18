<?php
declare(strict_types=1);
/**
 * Lectura de config.php y conexión PDO única a MySQL.
 */
date_default_timezone_set('Europe/Madrid');

/**
 * Devuelve la configuración (config.php se lee una sola vez).
 *
 * @return array Contenido de config.php.
 */
function config(): array
{
    static $config = null;
    return $config ??= require __DIR__ . '/../config.php';
}

/**
 * Devuelve la conexión PDO, creándola la primera vez que se pide.
 * utf8mb4 evita problemas con tildes y emojis. EMULATE_PREPARES=false hace que
 * las sentencias preparadas sean reales: MySQL recibe la consulta y los datos
 * por separado, y por eso un dato nunca puede convertirse en SQL (inyección).
 *
 * @return PDO Conexión reutilizable.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config()['db'];
        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['puerto']};dbname={$c['nombre']};charset=utf8mb4",
            $c['usuario'],
            $c['clave'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
