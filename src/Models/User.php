<?php
declare(strict_types=1);

namespace src\Models;

use src\Core\Database;

class User extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'users');   // el nombre de la tabla ya no hay que pasarlo desde fuera
    }

    /**
     * Comprueba las credenciales. Devuelve el id del usuario si son correctas, o null si no.
     *
     * OJO: aunque el enunciado habla de buscar por email y contraseña "encriptada", NO se puede
     * hacer "WHERE email = ? AND password = ?". password_hash() añade una sal aleatoria, así que
     * la misma contraseña genera un hash distinto cada vez y nunca coincidiría en el SQL.
     * Lo correcto es: 1) buscar por email, 2) comparar con password_verify() en PHP.
     */
    public function findByEmail(string $email, string $password): ?int
    {
        $user = $this->db->fetchOne(
            'SELECT id, password FROM users WHERE email = :email',
            ['email' => $email]
        );

        if ($user === null || !password_verify($password, $user['password'])) {
            return null;   // mismo resultado si falla el email o la contraseña (no revelamos cuál)
        }

        return (int) $user['id'];
    }
}