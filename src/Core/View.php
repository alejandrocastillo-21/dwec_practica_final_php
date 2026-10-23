<?php
declare(strict_types=1);

namespace src\Core;

class View
{
    /**
     * Renderiza src/Views/$view.php dentro de la plantilla común (layout.php).
     * $data son las variables que verá la vista: ['error' => 'x'] -> $error dentro del archivo.
     */
    public static function render(string $view, array $data = []): void
    {
        // extract() convierte cada clave del array en una variable local.
        // EXTR_SKIP evita que una clave pise variables ya existentes.
        extract($data, EXTR_SKIP);

        // Output buffering: en vez de enviar el HTML al navegador, lo capturamos en una
        // variable ($content). Después el layout lo coloca en su sitio, de modo que la
        // cabecera y el pie se escriben una sola vez para todas las vistas.
        ob_start();
        require __DIR__ . '/../Views/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout.php';
    }
}