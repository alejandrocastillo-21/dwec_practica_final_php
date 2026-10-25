<?php
declare(strict_types=1);

namespace src\Controllers;

use src\Core\Database;
use src\Core\Session;
use src\Core\View;
use src\Models\Post;
use src\Models\Comment;


class PostController extends BaseController
{
        private Post $posts;
        private Comment $comments;

    public function __construct()
    {
        $db             = new Database();
        $this->posts    = new Post($db);
        $this->comments = new Comment($db);
    }

    /** Listado de anuncios; si llega ?q=... filtra por título o categoría. */
    public function index(): void
    {
        $q     = trim(get('q'));
        $posts = $this->posts->search($q);

        // search() devuelve null si no hay resultados: la vista siempre recibe un array
        View::render('posts/index', ['posts' => $posts ?? [], 'q' => $q]);
    }

    /** Detalle de un anuncio (el id viene en la URL: ?id=3). */
        public function show(int $id): void
    {
        $post = $this->posts->findWithAuthor($id);
        if ($post === null) {
            $this->abort(404, 'El anuncio no existe.');
        }

        $currentUserId = Session::userId();   // null si no hay sesión

        View::render('posts/show', [
            'post'          => $post,
            'comments'      => $this->comments->findByPost((int) $post['id']) ?? [],
            'currentUserId' => $currentUserId,
            // Solo sirve para mostrar u ocultar botones. La seguridad real está
            // en findOwnedPost() (anuncios) y en CommentController::destroy() (comentarios).
            'isOwner'       => $currentUserId !== null && (int) $post['user_id'] === $currentUserId,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin('Debes iniciar sesión para publicar un anuncio.');
        $this->renderForm('Nuevo anuncio', url('Post', 'store'), ['title' => '', 'category' => '', 'body' => '']);
    }

    public function store(): void
    {
        $this->requirePost();
        $this->requireLogin('Debes iniciar sesión para publicar un anuncio.');

        [$data, $errors] = $this->validated();
        if ($errors !== []) {
            $this->renderForm('Nuevo anuncio', url('Post', 'store'), $data, $errors);
            return;
        }

        // SEGURIDAD: el autor sale de la SESIÓN, nunca de un campo del formulario
        // (un campo oculto "user_id" podría ser manipulado por el usuario).
        $id = $this->posts->insert($data + ['user_id' => Session::userId()]);

        Session::flash('success', 'Anuncio publicado.');
        redirect(url('Post', 'show', ['id' => $id]));
    }

    public function edit(int $id): void
    {
        $post = $this->findOwnedPost($id);
        $this->renderForm('Editar anuncio', url('Post', 'update', ['id' => $post['id']]), $post);
    }

    public function update(int $id): void
    {
        $this->requirePost();
        $post = $this->findOwnedPost($id);  // comprueba sesión + existencia + propiedad

        [$data, $errors] = $this->validated();
        if ($errors !== []) {
            $this->renderForm('Editar anuncio', url('Post', 'update', ['id' => $post['id']]), $data, $errors);
            return;
        }

        // $data solo tiene title, category y body: user_id no se toca, así que
        // nadie puede "regalar" ni robar un anuncio cambiando su autor.
        $this->posts->update((int) $post['id'], $data);

        Session::flash('success', 'Anuncio actualizado.');
        redirect(url('Post', 'show', ['id' => $post['id']]));
    }

    public function destroy(int $id): void
    {
        $this->requirePost();
        $post = $this->findOwnedPost($id);

        $this->posts->delete((int) $post['id']);

        Session::flash('success', 'Anuncio eliminado.');
        redirect(url('Post', 'index'));
    }

    /**
     * AUTORIZACIÓN: carga el anuncio del id de la URL y comprueba que el usuario
     * logueado es su autor. Si algo falla, corta con un error; si no, devuelve el anuncio.
     * (Autenticación = saber quién eres; autorización = saber si puedes hacer esto.)
     */
    private function findOwnedPost(int $id): array
    {
        $this->requireLogin();

        $post = $this->posts->getById($id);
        if ($post === null) {
            $this->abort(404, 'El anuncio no existe.');
        }
        if ((int) $post['user_id'] !== Session::userId()) {
            $this->abort(403, 'No tienes permiso para hacer esto.');
        }
        return $post;
    }

    /** Valida el formulario. Devuelve [datos limpios, lista de errores]. */
    private function validated(): array
    {
        $data = [
            'title'    => trim(post('title')),
            'category' => post('category'),
            'body'     => trim(post('body')),
        ];

        $errors = [];
        $titleLength = mb_strlen($data['title']);   // mb_: cuenta caracteres, no bytes (acentos, ñ...)
        if ($titleLength < 3 || $titleLength > 150) {
            $errors[] = 'El título debe tener entre 3 y 150 caracteres.';
        }
        if (!in_array($data['category'], Post::CATEGORIES, true)) {
            $errors[] = 'Elige una categoría válida.';
        }
        if ($data['body'] === '' || mb_strlen($data['body']) > 5000) {
            $errors[] = 'El texto del anuncio es obligatorio (máximo 5000 caracteres).';
        }

        return [$data, $errors];
    }

    /** Muestra el formulario compartido por "crear" y "editar". */
    private function renderForm(string $heading, string $formAction, array $post, array $errors = []): void
    {
        View::render('posts/form', [
            'heading'    => $heading,
            'formAction' => $formAction,
            'post'       => $post,
            'errors'     => $errors,
        ]);
    }
}