<?php
declare(strict_types=1);

namespace src\Controllers;

use src\Core\Session;
use src\Core\View;
use src\Core\Csrf;

/**
 * Comprobaciones comunes a todos los controladores.
 * Sus métodos son "protected": index.php solo ejecuta métodos públicos, así que
 * nadie puede invocarlos escribiendo una URL.
 */
abstract class BaseController
{
    /** Las acciones que modifican datos solo aceptan POST y exigen un token CSRF válido. */
    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Método no permitido.');
        }

        // Es lo PRIMERO que se hace tras comprobar el método: si el token no vale,
        // no se ejecuta nada más de la acción (ni mirar la sesión, ni la BD).
        if (!Csrf::isValid()) {
            $this->abort(403, 'Token CSRF inválido o caducado. Recarga la página e inténtalo de nuevo.');
        }
    }
    /** Si no hay sesión, redirige al login con un mensaje explicativo (y corta la ejecución). */
    protected function requireLogin(string $message = 'Debes iniciar sesión para hacer eso.'): void
    {
        if (!Session::isLoggedIn()) {
            Session::flash('error', $message);
            redirect(url('Auth', 'showLoginForm'));
        }
    }

    /** Muestra una página de error con su código HTTP (403, 404...) y termina. */
    protected function abort(int $code, string $message): void
    {
        http_response_code($code);
        View::render('error', ['code' => $code, 'message' => $message]);
        exit;
    }

        /** Lee un id entero positivo de la URL (por defecto ?id=; también sirve ?post_id=). Si no es válido, 404. */
    protected function idFromRequest(string $key = 'id'): int
    {
        // filter_var devuelve false si no es un entero válido (también si el parámetro no existe)
        $id = filter_var($_GET[$key] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            $this->abort(404, 'Recurso no encontrado.');
        }
        return $id;
    }

    
}