<?php
declare(strict_types=1);

namespace src\Core;

class Csrf
{
    private const KEY = 'csrf_token';

    /** Devuelve el token de la sesión; si todavía no hay ninguno, lo crea. */
    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            // random_bytes: aleatoriedad criptográficamente segura (no usar rand() ni uniqid(),
            // son predecibles). 32 bytes = 256 bits. bin2hex los pasa a texto (64 caracteres).
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    /** El <input> oculto que se pega dentro de cada formulario POST. */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::KEY . '" value="' . self::token() . '">';
    }

    /** ¿El token recibido en el POST coincide con el de la sesión? */
    public static function isValid(): bool
    {
        $sent     = $_POST[self::KEY] ?? '';
        $expected = $_SESSION[self::KEY] ?? '';

        if (!is_string($sent) || $sent === '' || $expected === '') {
            return false;   // falta el token (o la sesión no tiene uno): rechazamos
        }

        // hash_equals compara en "tiempo constante". Con === o strcmp, la comparación se
        // detiene en el primer carácter distinto, y midiendo cuánto tarda en responder un
        // atacante podría ir deduciendo el token carácter ación ("ataque de temporización").
        // hash_equals tarda lo mismo acierte o falle, así que no filtra esa información.
        return hash_equals($expected, $sent);
    }
}