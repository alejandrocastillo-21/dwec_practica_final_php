<?php
declare(strict_types=1);

namespace src\Core;

/** Todo lo relacionado con $_SESSION en un solo sitio. */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,    // la cookie de sesión no es legible desde JavaScript
                'cookie_samesite' => 'Lax',   // no se envía en peticiones POST desde otros sitios
            ]);
        }
    }

    public static function login(int $id, string $email): void
    {
        //Genera un ID de sesión nuevo al autenticarse: evita la "fijación de sesión"
        // (un atacante que conociera el ID previo al login no podría usarlo después).
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);   // token nuevo tras autenticarse
        $_SESSION['user_id']    = $id;
        $_SESSION['user_email'] = $email;
    }

    public static function logout(): void
    {
        $_SESSION = [];                                   // 1) vacía los datos en memoria
        if (ini_get('session.use_cookies')) {             // 2) caduca la cookie en el navegador
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?: 'Lax',
            ]);
        }
        session_destroy();                                // 3) destruye la sesión en el servidor
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function userEmail(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }

    /** Guarda un mensaje para mostrarlo en la PRÓXIMA página (tras una redirección). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /** Devuelve los mensajes pendientes y los borra, para que se vean una sola vez. */
    public static function pullFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}