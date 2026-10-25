<?php
declare(strict_types=1);

// Funciones auxiliares globales (se cargan con require desde index.php).

/** Escapa texto para HTML (anti-XSS). Úsalo en TODO lo que se vuelque a una vista. */
function e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/** Construye la URL de una acción: url('Post', 'show', ['id' => 3]) -> /posts/3 */
function url(?string $controller = null, ?string $action = null, array $params = []): string
{
    return \src\Core\Router::url($controller, $action, $params);
}

/** Redirige y corta la ejecución (sin exit, el script seguiría corriendo tras el header). */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Lee un campo de $_POST como string. Si falta o llega como array (?campo[]=x) devuelve ''. */
function post(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

/** Lee un parámetro de la URL ($_GET) como string. Si falta o es un array, devuelve ''. */
function get(string $key): string
{
    $value = $_GET[$key] ?? '';
    return is_string($value) ? $value : '';
}

/** Convierte una fecha de MySQL ("2026-10-20 18:30:00") a "20/10/2026 18:30". */
function format_date(string $mysqlDate): string
{
    return date('d/m/Y H:i', strtotime($mysqlDate));
}

/** Campo oculto con el token CSRF. Va dentro de CADA <form method="post">. */
function csrf_field(): string
{
    return \src\Core\Csrf::field();   // ya es HTML seguro (solo hex), no se escapa
}