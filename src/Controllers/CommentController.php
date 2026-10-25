<?php
declare(strict_types=1);

namespace src\Controllers;

use src\Core\Database;
use src\Core\Session;
use src\Models\Comment;
use src\Models\Post;

class CommentController extends BaseController
{
    private Comment $comments;
    private Post $posts;

    public function __construct()
    {
        $db             = new Database();   // una sola conexión compartida por los dos modelos
        $this->comments = new Comment($db);
        $this->posts    = new Post($db);
    }

    /** Guarda un comentario nuevo. El formulario llega a ?controller=Comment&action=store&post_id=N */
    public function store(int $postId): void
    {
        $this->requirePost();
        $this->requireLogin('Debes iniciar sesión para comentar.');

        // Comprobamos que el anuncio existe antes de insertar: si no, la clave foránea
        // haría fallar el INSERT con una excepción en lugar de un error amable.
        if ($this->posts->getById($postId) === null) {
            $this->abort(404, 'El anuncio no existe.');
        }

        $back = url('Post', 'show', ['id' => $postId]) . '#comentarios';
        $body = trim(post('body'));

        if ($body === '' || mb_strlen($body) > 1000) {
            Session::flash('error', 'El comentario no puede estar vacío ni superar los 1000 caracteres.');
            redirect($back);
        }

        // SEGURIDAD: post_id viene de la URL (y hemos comprobado que existe), pero el autor
        // (user_id) sale SIEMPRE de la sesión, nunca de un campo del formulario.
        $this->comments->insert([
            'post_id' => $postId,
            'user_id' => Session::userId(),
            'body'    => $body,
        ]);

        Session::flash('success', 'Comentario publicado.');
        redirect($back);
    }

    /** Opcional: el autor de un comentario puede borrarlo (mismas reglas de propiedad que en Post). */
    public function destroy(int $id): void
    {
        $this->requirePost();
        $this->requireLogin();

        $comment = $this->comments->getById($id);
        if ($comment === null) {
            $this->abort(404, 'El comentario no existe.');
        }
        if ((int) $comment['user_id'] !== Session::userId()) {
            $this->abort(403, 'No tienes permiso para hacer esto.');
        }

        $this->comments->delete((int) $comment['id']);

        Session::flash('success', 'Comentario eliminado.');
        redirect(url('Post', 'show', ['id' => $comment['post_id']]) . '#comentarios');
    }
}