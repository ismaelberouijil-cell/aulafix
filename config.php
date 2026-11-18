<?php
declare(strict_types=1);
/**
 * Configuración única de AulaFix: datos de conexión a MySQL y valores permitidos.
 * Si tu MySQL tiene otra contraseña o usuario, cámbialo solo aquí.
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'puerto'  => '3306',
        'nombre'  => 'aulafix',
        'usuario' => 'root',
        'clave'   => '',
    ],
    // Listas blancas: el servidor solo acepta estos valores en los formularios.
    'categorias'  => ['hardware', 'software', 'red', 'otro'],
    'prioridades' => ['baja', 'media', 'alta'],
    'estados'     => ['abierta', 'en_curso', 'resuelta'],
];
