<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/helpers.php';

use src\Core\Router;
use src\Core\Session;

Session::start();

// parse_url(..., PHP_URL_PATH) se queda solo con la ruta, sin el "?q=..." de la query string
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

(new Router())->dispatch($_SERVER['REQUEST_METHOD'], $path);