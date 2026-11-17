<?php
declare(strict_types=1);
/**
 * Script de instalación (php database/instalar.php): crea las tablas a partir de
 * esquema.sql y carga usuarios, equipos e incidencias de ejemplo.
 */
require __DIR__ . '/../includes/db.php';

$cfg = config()['db'];

// Primero una conexión sin base de datos elegida, porque esquema.sql hace el CREATE DATABASE.
$admin = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['puerto']};charset=utf8mb4",
    $cfg['usuario'],
    $cfg['clave'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$admin->exec((string) file_get_contents(__DIR__ . '/esquema.sql'));

$pdo = db();

// Usuarios de prueba. Las contraseñas se guardan con Argon2id, nunca en claro.
$insU = $pdo->prepare('INSERT INTO usuarios (nombre, email, clave_hash, rol) VALUES (?, ?, ?, ?)');
$usuarios = [
    ['Paula Profesora', 'profesor@aulafix.test', 'Profesor2026', 'profesor'],
    ['Lucía Alumna',    'lucia@aulafix.test',    'Alumna2026x',  'alumno'],
    ['Marcos Alumno',   'marcos@aulafix.test',   'Alumno2026x',  'alumno'],
];
foreach ($usuarios as [$nombre, $email, $clave, $rol]) {
    $insU->execute([$nombre, $email, password_hash($clave, PASSWORD_ARGON2ID), $rol]);
}

$insE = $pdo->prepare('INSERT INTO equipos (codigo, descripcion) VALUES (?, ?)');
$equipos = [
    ['PC-01', 'Ordenador del puesto 1'],
    ['PC-02', 'Ordenador del puesto 2'],
    ['PROY-01', 'Proyector del aula'],
    ['IMP-01', 'Impresora del pasillo'],
];
foreach ($equipos as $equipo) {
    $insE->execute($equipo);
}

// Cada ejemplo: equipo_id, usuario_id, categoría, prioridad, descripción, estado y cuándo se creó.
$insI = $pdo->prepare(
    'INSERT INTO incidencias (equipo_id, usuario_id, categoria, prioridad, descripcion, estado, creada_en, actualizada_en)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$ahora = new DateTimeImmutable();
$ejemplos = [
    [3, 2, 'hardware', 'alta',  'El proyector se apaga solo a los diez minutos.', 'abierta',  '-1 day'],
    [1, 2, 'software', 'media', 'El ordenador no arranca el entorno de desarrollo.', 'en_curso', '-3 days'],
    [4, 3, 'hardware', 'baja',  'La impresora se ha quedado sin tóner.', 'resuelta', '-8 days'],
    [2, 3, 'red',      'alta',  'No hay conexión a internet en el puesto 2.', 'abierta',  '-2 hours'],
    [1, 3, 'otro',     'baja',  'Falta el ratón en el puesto 1.', 'abierta',  '-5 days'],
    [2, 2, 'software', 'media', 'El navegador va muy lento desde ayer.', 'en_curso', '-4 days'],
];
foreach ($ejemplos as [$equipoId, $usuarioId, $categoria, $prioridad, $descripcion, $estado, $desde]) {
    $fecha = $ahora->modify($desde)->format('Y-m-d H:i:s');
    $insI->execute([$equipoId, $usuarioId, $categoria, $prioridad, $descripcion, $estado, $fecha, $fecha]);
}

echo "Base de datos instalada.\n";
echo "Profesor: profesor@aulafix.test / Profesor2026\n";
echo "Alumna:   lucia@aulafix.test / Alumna2026x\n";
