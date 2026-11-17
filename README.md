# AulaFix — tablón de incidencias del aula

Proyecto intermodular 0612 (cliente) + 0613 (servidor), hecho de forma individual. PHP 8.5, PDO, MySQL 8 y JavaScript en módulos, sin librerías ni frameworks.

## Cómo arrancarlo

Necesitas PHP 8.5 (con la extensión `pdo_mysql`) y MySQL 8 en marcha.

1. Si tu MySQL no usa usuario `root` sin contraseña, cambia los datos de `config.php` (es el único sitio donde están).
2. Desde la carpeta del proyecto, crea la base de datos con datos de ejemplo:
   `php database/instalar.php`
3. Arranca el servidor de PHP, que solo publica la carpeta `public/`:
   `php -S localhost:3306 -t public`
4. Abre http://localhost:3306

## Usuarios de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Profesor | profesor@aulafix.test | Profesor2026 |
| Alumna | lucia@aulafix.test | Alumna2026x |
| Alumno | marcos@aulafix.test | Alumno2026x |

También se puede crear una cuenta nueva en «Registrarse» (siempre con rol alumno).

## Estructura

```
config.php        conexión a MySQL y valores permitidos (único fichero de configuración)
database/         esquema.sql (tablas) e instalar.php (datos de ejemplo)
includes/         db.php, funciones.php, cabecera.php, pie.php
public/           lo único accesible desde el navegador
  *.php           páginas
  css/estilos.css
  js/             main.js, incidencia.js, vistas.js, fechas.js, preferencias.js, nueva.js
```

## Qué hace cada parte

- **Servidor (PHP):** recibe la petición, comprueba la sesión y los permisos, habla con MySQL y devuelve HTML. Solo `public/` se puede pedir desde el navegador; la configuración y las consultas quedan fuera.
- **Navegador (JavaScript):** recibe esa página y, si JavaScript funciona, sustituye la tabla por el panel (resumen, pestañas, filtros y tabla ordenable). Todo esto ocurre en el navegador, sin volver a pedir nada al servidor.
- **Recorrido de una petición:** el navegador pide `incidencias.php` → PHP comprueba la sesión → consulta las incidencias con PDO → genera la tabla HTML y el JSON → el navegador pinta la página → `main.js` lee el JSON y monta el panel.

## Decisiones técnicas

- **El panel sale de una plantilla** (`<template>` en `incidencias.php`): la estructura está en HTML y JavaScript solo la copia y rellena los datos con el DOM.
- **Los datos viajan en JSON dentro de la página** (`<script type="application/json">`), porque en estas unidades no se usa `fetch`. El JSON se genera con `json_encode` y las opciones `JSON_HEX_*`, para que un texto con `</script>` no pueda romper la página.
- **JavaScript en módulos** (`type="module"`): cada fichero tiene una responsabilidad y no hay variables globales.
- **Sin librerías:** el objetivo es practicar el DOM y los objetos predefinidos del lenguaje.
- **Si no hay JavaScript** se sigue viendo la tabla generada por PHP.
- **Seguridad:** consultas preparadas con PDO, `e()` en toda la salida de PHP, `textContent` en JavaScript, token CSRF en todos los formularios POST, patrón Post-Redirect-Get, contraseñas con Argon2id, id de sesión nuevo al entrar y permisos comprobados en el servidor (403).
- **Transacción** al cambiar el estado: la actualización y la línea de historial se guardan juntas o no se guarda ninguna.

## Uso de herramientas de IA

Utilizacion de la IA para acelerar el proyecto y explicacion de codigo para poder facilitar y entender progama

## Etiquetas Git

`hito-1` (UT1-UT3) y `entrega-final` (UT4-UT5).
