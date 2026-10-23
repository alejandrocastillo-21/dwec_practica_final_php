<?php
declare(strict_types=1);

namespace src\Controllers;

use src\Core\Database;
use src\Core\Session;
use src\Core\View;
use src\Models\User;
use PDOException;

class AuthController extends BaseController
{
    private User $users;

    public function __construct()
    {
        $this->users = new User(new Database());
    }

    public function showRegisterForm(): void
    {
        View::render('auth/register', ['errors' => [], 'old' => []]);
    }

    public function register(): void
    {
        $this->requirePost();

        $username = trim(post('username'));
        $email    = strtolower(trim(post('email')));   // en minúsculas: así "A@x.com" y "a@x.com" son el mismo
        $password = post('password');                  // la contraseña no se recorta: los espacios cuentan
        $confirm  = post('confirm');

        // Validación en el servidor (la de HTML se salta con un simple clic en "inspeccionar")
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            $errors[] = 'El usuario debe tener entre 3 y 50 caracteres (letras, números o _).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
            $errors[] = 'El email no es válido.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        if ($errors === []) {
            try {
                $this->users->insert([
                    'username' => $username,
                    'email'    => $email,
                    // Se guarda solo el hash. PASSWORD_DEFAULT elige el mejor algoritmo disponible
                    // (bcrypt) y genera la sal automáticamente: queda incluida dentro del propio hash.
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                Session::flash('success', 'Cuenta creada. Ya puedes iniciar sesión.');
                redirect(url('Auth', 'showLoginForm'));
            } catch (PDOException $ex) {
                // 23000 = violación de restricción de integridad: aquí, el UNIQUE de email/usuario.
                // Dejamos que lo detecte la BD en vez de hacer un SELECT previo: dos registros
                // simultáneos podrían pasar ambos el SELECT (condición de carrera) y la BD no.
                if ($ex->getCode() !== '23000') {
                    throw $ex;   // cualquier otro error es inesperado: no lo escondemos
                }
                $errors[] = 'Ese email o nombre de usuario ya está registrado.';
            }
        }

        // Hubo errores: volvemos a pintar el formulario conservando lo escrito (salvo contraseñas)
        View::render('auth/register', [
            'errors' => $errors,
            'old'    => ['username' => $username, 'email' => $email],
        ]);
    }

    public function showLoginForm(): void
    {
        if (Session::isLoggedIn()) {
            redirect(url());
        }
        View::render('auth/login', ['error' => null, 'old' => []]);
    }

    public function login(): void
    {
        $this->requirePost();

        $email    = strtolower(trim(post('email')));
        $password = post('password');

        $id = $this->users->findByEmail($email, $password);

        if ($id === null) {
            View::render('auth/login', [
                'error' => 'Email o contraseña incorrectos.',
                'old'   => ['email' => $email],
            ]);
            return;
        }

        Session::login($id, $email);   // guarda id y email en $_SESSION
        Session::flash('success', 'Has iniciado sesión.');
        redirect(url());
    }

    public function logout(): void
    {
        $this->requirePost();   // se cierra sesión con un formulario POST, no con un enlace
        Session::logout();
        redirect(url());
    }
}