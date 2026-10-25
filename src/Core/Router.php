<?php
declare(strict_types=1);

namespace src\Core;

use ReflectionMethod;
use src\Controllers\BaseController;

class Router
{
    // Segmento de la URL -> nombre del controlador. Si no está aquí, se usa ucfirst("segmento").
    private const CONTROLLERS = ['posts' => 'Post', 'comments' => 'Comment', 'auth' => 'Auth'];

    // Nombres amigables de la URL -> nombre real del método
    private const ACTIONS = ['delete' => 'destroy', 'new' => 'create'];

    // Rutas explícitas, que se comprueban ANTES que la regla genérica:
    // [método HTTP, regex de la ruta, controlador, acción, ruta para url() o null]
    private const ROUTES = [
        ['GET',  '#^/$#',         'Post', 'index',            null],
        ['GET',  '#^/login$#',    'Auth', 'showLoginForm',    '/login'],
        ['POST', '#^/login$#',    'Auth', 'login',            '/login'],
        ['GET',  '#^/register$#', 'Auth', 'showRegisterForm', '/register'],
        ['POST', '#^/register$#', 'Auth', 'register',         '/register'],
        ['POST', '#^/logout$#',   'Auth', 'logout',           '/logout'],
    ];

    /** Interpreta la petición y ejecuta el método del controlador que corresponda. */
    public function dispatch(string $httpMethod, string $path): void
    {
        $route = $this->resolve($httpMethod, $path);
        if ($route === null) {
            $this->notFound();
        }
        [$controller, $action, $args] = $route;

        $class = 'src\\Controllers\\' . $controller . 'Controller';

        // SEGURIDAD: la URL la controla el usuario. Solo ejecutamos clases que sean
        // controladores de verdad (hijos de BaseController) y métodos PÚBLICOS.
        if (!class_exists($class) || !is_subclass_of($class, BaseController::class) || !method_exists($class, $action)) {
            $this->notFound();
        }
        $method = new ReflectionMethod($class, $action);
        if (!$method->isPublic()) {
            $this->notFound();
        }

        // El número de ids de la URL debe coincidir con los parámetros del método:
        // /posts/delete (sin id) o /posts/create/5 (id de más) -> 404, no un error de PHP.
        if (count($args) !== $method->getNumberOfParameters()) {
            $this->notFound();
        }

        (new $class())->$action(...$args);   // "..." reparte el array como argumentos: destroy(3)
    }

    /** Devuelve [controlador, acción, [argumentos]] o null si la ruta no existe. */
    private function resolve(string $httpMethod, string $path): ?array
    {
        if ($path !== '/') {
            $path = rtrim($path, '/');   // /posts/ y /posts son lo mismo
        }

        foreach (self::ROUTES as [$method, $pattern, $controller, $action]) {
            if ($method === $httpMethod && preg_match($pattern, $path)) {
                return [$controller, $action, []];
            }
        }

        // Regla genérica: /controlador[/accion][/id], para CUALQUIER controlador.
        //   ^/([a-z]+)                       1: controlador (letras minúsculas)
        //   (?:/([a-z]+(?:-[a-z]+)*))?       2: acción opcional (admite guiones: "show-form")
        //   (?:/([1-9][0-9]*))?$             3: id opcional (entero positivo, sin ceros a la izquierda)
        // "(?:...)" agrupa sin capturar. Una acción solo puede tener letras y un id solo
        // dígitos, así que en /posts/3 el "3" cae directamente en el grupo 3.
        $pattern = '#^/([a-z]+)(?:/([a-z]+(?:-[a-z]+)*))?(?:/([1-9][0-9]*))?$#';
        if (!preg_match($pattern, $path, $m)) {
            return null;
        }

        // Los grupos opcionales que no encajan pueden faltar en $m: usamos ?? ''
        $segment = $m[1];
        $action  = $m[2] ?? '';
        $id      = $m[3] ?? '';

        if ($action === '') {
            $action = $id === '' ? 'index' : 'show';   // /posts -> index ; /posts/3 -> show(3)
        }

        $controller = self::CONTROLLERS[$segment] ?? ucfirst($segment);
        $action     = self::ACTIONS[$action] ?? $this->kebabToCamel($action);

        return [$controller, $action, $id === '' ? [] : [(int) $id]];
    }

    /** Hace lo contrario que dispatch: url('Post','edit',['id'=>3]) -> "/posts/edit/3". */
    public static function url(?string $controller = null, ?string $action = null, array $params = []): string
    {
        if ($controller === null) {
            return '/';
        }

        // Primero, las rutas explícitas (login, register, logout)
        foreach (self::ROUTES as [, , $routeController, $routeAction, $path]) {
            if ($path !== null && $routeController === $controller && $routeAction === $action) {
                return $path . self::query($params);
            }
        }

        // Id para la ruta: si hay "id" o "post_id" va como segmento de la URL
        $id = null;
        foreach (['id', 'post_id'] as $key) {
            if (isset($params[$key])) {
                $id = (int) $params[$key];
                unset($params[$key]);   // lo que quede en $params irá como ?clave=valor
                break;
            }
        }

        $segment = array_search($controller, self::CONTROLLERS, true) ?: strtolower($controller);
        $path    = '/' . $segment;

        // index y show se expresan sin nombre de acción: /posts y /posts/3
        if ($action !== null && $action !== 'index' && $action !== 'show') {
            $path .= '/' . (array_search($action, self::ACTIONS, true) ?: self::camelToKebab($action));
        }
        if ($id !== null) {
            $path .= '/' . $id;
        }

        return $path . self::query($params);
    }

    private static function query(array $params): string
    {
        return $params === [] ? '' : '?' . http_build_query($params);
    }

    /** "show-form" -> "showForm" */
    private function kebabToCamel(string $text): string
    {
        return lcfirst(str_replace('-', '', ucwords($text, '-')));
    }

    /** "showForm" -> "show-form" */
    private static function camelToKebab(string $text): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $text));
    }

    private function notFound(): never
    {
        http_response_code(404);
        exit('Página no encontrada.');
    }
}