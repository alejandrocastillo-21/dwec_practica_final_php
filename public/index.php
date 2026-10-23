<?php
declare(strict_types=1);

// Controlador frontal: TODAS las peticiones entran por aquí.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/helpers.php';

use src\Core\Session;

Session::start();

// Si la URL no indica controlador, la portada es el listado de anuncios
$controller = $_GET['controller'] ?? 'Post';
$action     = $_GET['action'] ?? 'index';

// SEGURIDAD: controller y action vienen de la URL, es decir, del usuario.
// Solo aceptamos letras (impide "../", "__construct", etc.) y comprobamos que
// la clase y el método existen y son públicos antes de ejecutar nada.
$class = 'src\\Controllers\\' . ucfirst((string) $controller) . 'Controller';

if (
    !is_string($controller) || !is_string($action)
    || !preg_match('/^[A-Za-z]+$/', $controller)
    || !preg_match('/^[a-z][A-Za-z]*$/', $action)
    || !class_exists($class)
    || !method_exists($class, $action)
    || !(new ReflectionMethod($class, $action))->isPublic()   // los métodos private/protected no se pueden invocar desde la URL
) {
    http_response_code(404);
    exit('Página no encontrada.');
}

(new $class())->$action();